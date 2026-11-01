const { Component, Module } = Shopware;

Component.register('medical-inventory-dashboard', {
    inject: ['loginService'],
    template: `
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
                    <sw-card title="Warehouses">
                        <sw-data-grid
                            v-if="dashboard"
                            :data-source="dashboard.warehouses"
                            :columns="warehouseColumns"
                            :show-selection="false"
                            :show-actions="false"
                            :is-loading="isLoading"
                        />
                    </sw-card>
                    <sw-card title="Open low-stock alerts" style="margin-top:16px">
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
    `,
    data() {
        return {
            isLoading: false,
            error: null,
            dashboard: null,
            warehouseColumns: [
                { property: 'code', label: 'Code' },
                { property: 'name', label: 'Name' },
                { property: 'country_code', label: 'Country' },
                { property: 'priority', label: 'Priority' },
                { property: 'active', label: 'Active' },
                { property: 'stock_rows', label: 'Stock rows' },
                { property: 'available_units', label: 'Available units' },
            ],
            alertColumns: [
                { property: 'warehouse_code', label: 'Warehouse' },
                { property: 'product_id', label: 'Product ID' },
                { property: 'threshold_quantity', label: 'Threshold' },
                { property: 'available_quantity', label: 'Available' },
                { property: 'opened_at', label: 'Opened at' },
            ],
        };
    },
    created() {
        this.loadDashboard();
    },
    methods: {
        async loadDashboard() {
            this.isLoading = true;
            this.error = null;

            try {
                const httpClient = Shopware.Application.getContainer('init').httpClient;
                const response = await httpClient.get('_action/medical-inventory/dashboard', {
                    headers: this.loginService.getBearerAuthentication(),
                });
                this.dashboard = response.data;
            } catch (error) {
                this.error = error?.response?.data?.errors?.[0]?.detail || error.message || 'Unable to load inventory data.';
            } finally {
                this.isLoading = false;
            }
        },
        formatLabel(value) {
            return value.replace(/([A-Z])/g, ' $1').replace(/^./, (letter) => letter.toUpperCase());
        },
    },
});

Module.register('medical-inventory', {
    type: 'plugin',
    name: 'MedicalInventory',
    title: 'Medical Inventory',
    description: 'Multi-warehouse stock, reservations and shipments',
    color: '#3b82f6',
    icon: 'regular-box',
    routes: {
        index: {
            component: 'medical-inventory-dashboard',
            path: 'index',
        },
    },
    navigation: [{
        id: 'medical-inventory-overview',
        label: 'Overview',
        color: '#3b82f6',
        path: 'medical.inventory.index',
        icon: 'regular-chart-bar',
        position: 100,
    }],
});
