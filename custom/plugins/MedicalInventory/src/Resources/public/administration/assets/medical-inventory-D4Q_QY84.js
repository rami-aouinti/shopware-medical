const{Component:r,Module:n}=Shopware;r.register("medical-inventory-dashboard",{inject:["loginService"],template:`
        <sw-page class="medical-inventory-dashboard">
            <template #smart-bar-header>
                <h2>Medical Inventory</h2>
            </template>
            <template #content>
                <div class="sw-card-view" style="padding: 24px">
                    <sw-button variant="primary" :is-loading="isLoading" @click="loadDashboard">
                        Refresh
                    </sw-button>
                    <sw-alert v-if="error" variant="error" appearance="default" :show-icon="true">
                        {{ error }}
                    </sw-alert>
                    <div v-if="dashboard" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin:20px 0">
                        <sw-card v-for="(value, label) in dashboard.summary" :key="label" :title="formatLabel(label)">
                            <h2>{{ value }}</h2>
                        </sw-card>
                    </div>
                    <sw-card title="Create warehouse" style="margin-top:16px">
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px">
                            <sw-text-field v-model:value="newWarehouse.code" label="Warehouse code" />
                            <sw-text-field v-model:value="newWarehouse.name" label="Name" />
                            <sw-text-field v-model:value="newWarehouse.countryCode" label="Country code" />
                            <sw-number-field v-model:value="newWarehouse.priority" label="Allocation priority" :min="0" :max="65535" />
                        </div>
                        <sw-button variant="primary" :disabled="!canCreateWarehouse" @click="createWarehouse">Create warehouse</sw-button>
                    </sw-card>
                    <sw-card title="Warehouses">
                        <div v-if="dashboard" v-for="warehouse in dashboard.warehouses" :key="warehouse.id"
                             style="display:flex;align-items:center;gap:12px;padding:12px;border-bottom:1px solid #e0e0e0">
                            <div style="flex:1">
                                <strong>{{ warehouse.code }}</strong> — {{ warehouse.name }}
                                <small>({{ warehouse.country_code }}) · {{ warehouse.available_units }} available</small>
                            </div>
                            <sw-number-field v-model:value="warehouse.priority" label="Priority" :min="0" :max="65535" size="small" />
                            <sw-button size="small" @click="saveWarehouse(warehouse)">Save</sw-button>
                            <sw-button size="small" :variant="warehouse.active ? 'danger' : 'success'" @click="toggleWarehouse(warehouse)">
                                {{ warehouse.active ? 'Deactivate' : 'Activate' }}
                            </sw-button>
                        </div>
                    </sw-card>
                    <sw-card title="Warehouse stock and minimum thresholds" style="margin-top:16px">
                        <div v-if="dashboard" v-for="stock in dashboard.stock" :key="stock.warehouse_code + stock.product_id"
                             style="display:flex;align-items:center;gap:12px;padding:10px;border-bottom:1px solid #e0e0e0">
                            <div style="flex:1">
                                <strong>{{ stock.product_number }}</strong> — {{ stock.warehouse_code }}
                                <small>Physical {{ stock.quantity }} · Reserved {{ stock.reserved_quantity }} · Available {{ stock.available_quantity }}</small>
                            </div>
                            <sw-number-field v-model:value="stock.minimum_quantity" label="Minimum" :min="0" :max="4294967295" size="small" />
                            <sw-button size="small" @click="saveMinimum(stock)">Save</sw-button>
                        </div>
                    </sw-card>
                    <sw-card title="Open low-stock alerts" style="margin-top:16px">
                        <sw-button size="small" @click="scanAlerts">Scan now</sw-button>
                        <sw-data-grid
                            v-if="dashboard"
                            :data-source="dashboard.alerts"
                            :columns="alertColumns"
                            :show-selection="false"
                            :show-actions="false"
                            :is-loading="isLoading"
                        />
                        <sw-empty-state v-if="dashboard && dashboard.alerts.length === 0" title="No open alerts" />
                    </sw-card>
                </div>
            </template>
        </sw-page>
    `,data(){return{isLoading:!1,error:null,dashboard:null,newWarehouse:{code:"",name:"",countryCode:"DE",priority:100},alertColumns:[{property:"warehouse_code",label:"Warehouse"},{property:"product_id",label:"Product ID"},{property:"threshold_quantity",label:"Threshold"},{property:"available_quantity",label:"Available"},{property:"opened_at",label:"Opened at"}]}},created(){this.loadDashboard()},computed:{canCreateWarehouse(){return this.newWarehouse.code.trim()!==""&&this.newWarehouse.name.trim()!==""&&/^[A-Za-z]{2}$/.test(this.newWarehouse.countryCode)}},methods:{getHttpClient(){return Shopware.Application.getContainer("init").httpClient},getRequestConfig(){return{headers:this.loginService.getBearerAuthentication()}},async createWarehouse(){var a,t,s,i;try{await this.getHttpClient().post("_action/medical-inventory/warehouses",this.newWarehouse,this.getRequestConfig()),this.newWarehouse={code:"",name:"",countryCode:"DE",priority:100},await this.loadDashboard()}catch(e){this.error=((i=(s=(t=(a=e==null?void 0:e.response)==null?void 0:a.data)==null?void 0:t.errors)==null?void 0:s[0])==null?void 0:i.detail)||e.message||"Unable to create warehouse."}},async saveWarehouse(a){var t,s,i,e;try{await this.getHttpClient().patch("_action/medical-inventory/warehouses/"+encodeURIComponent(a.code),{priority:Number(a.priority)},this.getRequestConfig()),await this.loadDashboard()}catch(o){this.error=((e=(i=(s=(t=o==null?void 0:o.response)==null?void 0:t.data)==null?void 0:s.errors)==null?void 0:i[0])==null?void 0:e.detail)||o.message||"Unable to update warehouse."}},async toggleWarehouse(a){var t,s,i,e;try{await this.getHttpClient().patch("_action/medical-inventory/warehouses/"+encodeURIComponent(a.code),{active:!a.active},this.getRequestConfig()),await this.loadDashboard()}catch(o){this.error=((e=(i=(s=(t=o==null?void 0:o.response)==null?void 0:t.data)==null?void 0:s.errors)==null?void 0:i[0])==null?void 0:e.detail)||o.message||"Unable to change warehouse status."}},async saveMinimum(a){var t,s,i,e;try{await this.getHttpClient().patch("_action/medical-inventory/warehouses/"+encodeURIComponent(a.warehouse_code)+"/products/"+a.product_id+"/minimum",{minimumQuantity:Number(a.minimum_quantity)},this.getRequestConfig()),await this.loadDashboard()}catch(o){this.error=((e=(i=(s=(t=o==null?void 0:o.response)==null?void 0:t.data)==null?void 0:s.errors)==null?void 0:i[0])==null?void 0:e.detail)||o.message||"Unable to update minimum stock."}},async scanAlerts(){var a,t,s,i;try{await this.getHttpClient().post("_action/medical-inventory/alerts/scan",{},this.getRequestConfig()),await this.loadDashboard()}catch(e){this.error=((i=(s=(t=(a=e==null?void 0:e.response)==null?void 0:a.data)==null?void 0:t.errors)==null?void 0:s[0])==null?void 0:i.detail)||e.message||"Unable to scan stock alerts."}},async loadDashboard(){var a,t,s,i;this.isLoading=!0,this.error=null;try{const o=await Shopware.Application.getContainer("init").httpClient.get("_action/medical-inventory/dashboard",{headers:this.loginService.getBearerAuthentication()});this.dashboard=o.data}catch(e){this.error=((i=(s=(t=(a=e==null?void 0:e.response)==null?void 0:a.data)==null?void 0:t.errors)==null?void 0:s[0])==null?void 0:i.detail)||e.message||"Unable to load inventory data."}finally{this.isLoading=!1}},formatLabel(a){return a.replace(/([A-Z])/g," $1").replace(/^./,t=>t.toUpperCase())}}});n.register("medical-inventory",{type:"plugin",name:"MedicalInventory",title:"Medical Inventory",description:"Multi-warehouse stock, reservations and shipments",routePrefix:"medical.inventory",color:"#3b82f6",icon:"regular-box",routes:{index:{component:"medical-inventory-dashboard",path:"index"}},navigation:[{id:"medical-inventory-overview",label:"Overview",color:"#3b82f6",path:"medical.inventory.index",icon:"regular-chart-bar",position:100}]});
//# sourceMappingURL=medical-inventory-D4Q_QY84.js.map
