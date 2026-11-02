import './acl';
const { Component, Module } = Shopware;
Component.register('medical-product-documents', {
    inject: ['loginService', 'acl'],
    template: `
        <sw-page class="medical-product-documents">
            <template #smart-bar-header><h2>Medical product documents</h2></template>
            <template #content>
                <div class="sw-card-view" style="padding:24px">
                    <sw-alert v-if="error" variant="error" appearance="default" :show-icon="true">{{ error }}</sw-alert>
                    <sw-alert v-if="success" variant="success" appearance="default" :show-icon="true">{{ success }}</sw-alert>
                    <sw-card title="Select product" style="margin:16px 0">
                        <sw-text-field v-model:value="productId" label="Product UUID" placeholder="Paste Shopware product UUID"/>
                        <sw-button variant="primary" :disabled="!isValidProductId" :is-loading="loading" @click="loadDocuments">Load documents</sw-button>
                    </sw-card>
                    <sw-card v-if="isValidProductId && acl.can('product:update')" title="Add a document" style="margin:16px 0">
                        <sw-text-field v-model:value="form.title" label="Document title"/>
                        <sw-select-field v-model:value="form.documentType" label="Document type">
                            <option value="CE_CERTIFICATE">CE certificate</option><option value="DECLARATION_OF_CONFORMITY">Declaration of conformity</option>
                            <option value="TECHNICAL_DATASHEET">Technical data sheet</option><option value="USER_MANUAL">User manual</option>
                            <option value="SAFETY_DATA_SHEET">Safety data sheet</option><option value="OTHER">Other</option>
                        </sw-select-field>
                        <sw-entity-single-select entity="media" v-model:value="form.mediaId" label="Media file"/>
                        <sw-switch-field v-model:value="form.isPrivate" label="Private (media must already be private)"/>
                        <sw-button variant="primary" :disabled="!canCreate" :is-loading="saving" @click="saveDocument">{{ editingId ? 'Save changes' : 'Add document' }}</sw-button>
                        <sw-button v-if="editingId" @click="cancelEdit">Cancel</sw-button>
                    </sw-card>
                    <sw-card v-if="isValidProductId" title="Existing documents" style="margin:16px 0">
                        <div v-if="documents.length===0 && !loading" style="padding:24px;text-align:center">No documents registered for this product.</div>
                        <div v-for="doc in documents" :key="doc.id" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;padding:12px 0;border-bottom:1px solid #ddd">
                            <div style="flex:1;min-width:220px"><strong>{{ doc.title }}</strong><div><small>{{ doc.documentType }} · {{ doc.fileName || doc.mediaId }}</small></div>
                                <span v-if="doc.isPrivate">Private document</span><span v-else>Public document</span>
                            </div>
                            <sw-button v-if="doc.url && !doc.isPrivate" size="small" @click="openDocument(doc.url)">Open file</sw-button>
                            <sw-button v-if="acl.can('product:update')" size="small" @click="startEdit(doc)">Edit</sw-button>
                            <sw-button v-if="acl.can('product:delete')" size="small" variant="danger" @click="deleteDocument(doc)">Delete</sw-button>
                        </div>
                    </sw-card>
                </div>
            </template>
        </sw-page>`,
    data() { return { productId:'', documents:[], loading:false, saving:false, error:null, success:null,
        form:{title:'',documentType:'TECHNICAL_DATASHEET',mediaId:null,isPrivate:false}, editingId:null }; },
    computed: {
        isValidProductId() { return /^[0-9a-f]{32}$/i.test(this.productId.trim()); },
        canCreate() { return this.isValidProductId && this.form.title.trim()!=='' && !!this.form.mediaId && !!this.form.documentType; },
    },
    methods: {
        http() { return Shopware.Application.getContainer('init').httpClient; },
        config() { return {headers:{Authorization:`Bearer ${this.loginService.getToken()}`}}; },
        showError(error,fallback) { this.error=error?.response?.data?.errors?.[0]?.detail || error.message || fallback; this.success=null; },
        async loadDocuments() {
            if (!this.isValidProductId) return; this.loading=true; this.error=null;
            try { const r=await this.http().get(`_action/medical-product-management/products/${this.productId.trim()}/documents`,this.config()); this.documents=r.data.data||[]; }
            catch(e) { this.showError(e,'Unable to load product documents.'); } finally { this.loading=false; }
        },
        async saveDocument() {
            if (!this.canCreate || !this.acl.can('product:update')) return; this.saving=true; this.error=null;
            try {
                if (this.editingId) {
                    await this.http().patch(`_action/medical-product-management/documents/${this.editingId}`,this.form,this.config());
                } else {
                    await this.http().post(`_action/medical-product-management/products/${this.productId.trim()}/documents`,this.form,this.config());
                }
                this.cancelEdit(); await this.loadDocuments(); this.success='Document saved successfully.';
            } catch(e) { this.showError(e,'Unable to save document.'); } finally { this.saving=false; }
        },
        startEdit(doc) {
            this.editingId=doc.id;
            this.form={title:doc.title,documentType:doc.documentType,mediaId:doc.mediaId,isPrivate:doc.isPrivate};
        },
        cancelEdit() {
            this.editingId=null;
            this.form={title:'',documentType:'TECHNICAL_DATASHEET',mediaId:null,isPrivate:false};
        },
        async deleteDocument(doc) {
            if (!this.acl.can('product:delete')) return;
            try { await this.http().delete(`_action/medical-product-management/documents/${doc.id}`,this.config()); await this.loadDocuments(); this.success='Document deleted successfully.'; }
            catch(e) { this.showError(e,'Unable to delete document.'); }
        },
        openDocument(url) { if (typeof url==='string' && url.startsWith('http')) window.open(url,'_blank','noopener'); },
    },
});
Module.register('medical-product-management', {
    type:'plugin',name:'MedicalProductManagement',title:'Medical Product Management',
    description:'Medical product attributes and technical documents',routePrefix:'medical.product.management',
    color:'#0f766e',icon:'regular-file-text',
    routes:{index:{component:'medical-product-documents',path:'index',meta:{privilege:'medical_product_management.viewer'}}},
    navigation:[{id:'medical-product-management-documents',label:'Product documents',color:'#0f766e',
        path:'medical.product.management.index',icon:'regular-file-text',parent:'sw-catalogue',position:110,privilege:'medical_product_management.viewer'}],
});