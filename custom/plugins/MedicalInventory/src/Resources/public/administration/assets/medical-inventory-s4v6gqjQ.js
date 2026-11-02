const{Component:i,Module:n}=Shopware;i.register("medical-inventory-dashboard",{inject:["loginService"],template:`
        <sw-page class="medical-inventory-dashboard">
            <template #smart-bar-header><h2>Medical Inventory</h2></template>
            <template #content>
                <div class="sw-card-view" style="padding:24px">
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <h3>Inventory overview</h3>
                        <sw-button variant="primary" :is-loading="isLoading" @click="loadDashboard">Refresh</sw-button>
                    </div>
                    <sw-alert v-if="error" variant="error" appearance="default" :show-icon="true" style="margin:16px 0">{{ error }}</sw-alert>
                    <sw-alert v-if="success" variant="success" appearance="default" :show-icon="true" style="margin:16px 0">{{ success }}</sw-alert>

                    <div v-if="dashboard" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin:20px 0">
                        <sw-card v-for="(value,label) in dashboard.summary" :key="label" :title="formatLabel(label)"><h2>{{ value }}</h2></sw-card>
                    </div>

                    <sw-card title="Create warehouse" style="margin:16px 0">
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px">
                            <sw-text-field v-model:value="newWarehouse.code" label="Warehouse code"/>
                            <sw-text-field v-model:value="newWarehouse.name" label="Name"/>
                            <sw-text-field v-model:value="newWarehouse.countryCode" label="Country code"/>
                            <sw-number-field v-model:value="newWarehouse.priority" label="Allocation priority" :min="0" :max="65535"/>
                        </div>
                        <sw-button variant="primary" :disabled="!canCreateWarehouse" @click="createWarehouse">Create warehouse</sw-button>
                    </sw-card>

                    <sw-card v-if="!selectedWarehouse" title="Warehouses" style="margin:16px 0">
                        <sw-text-field v-model:value="warehouseSearch" label="Search by code, name or country"/>
                        <div v-if="warehousePageItems.length===0" style="padding:24px;text-align:center">No warehouses match your search.</div>
                        <div v-for="warehouse in warehousePageItems" :key="warehouse.id"
                             style="display:flex;align-items:center;gap:12px;padding:12px;border-bottom:1px solid #e0e0e0;flex-wrap:wrap">
                            <div style="flex:1;min-width:220px">
                                <strong>{{ warehouse.code }}</strong> — {{ warehouse.name }}
                                <div><small>{{ warehouse.country_code }} · {{ warehouse.available_units }} available · {{ warehouse.stock_rows }} stock rows</small></div>
                            </div>
                            <sw-number-field v-model:value="warehouse.priority" label="Priority" :min="0" :max="65535" size="small"/>
                            <sw-button size="small" @click="saveWarehouse(warehouse)">Save priority</sw-button>
                            <sw-button size="small" :variant="warehouse.active?'danger':'success'" @click="toggleWarehouse(warehouse)">{{ warehouse.active?'Deactivate':'Activate' }}</sw-button>
                            <sw-button size="small" variant="primary" @click="openWarehouse(warehouse)">View details</sw-button>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;padding-top:16px">
                            <span>Page {{ warehousePage }} / {{ warehousePageCount }} · {{ filteredWarehouses.length }} warehouses</span>
                            <div style="display:flex;gap:8px">
                                <sw-button size="small" :disabled="warehousePage<=1" @click="warehousePage--">Previous</sw-button>
                                <sw-button size="small" :disabled="warehousePage>=warehousePageCount" @click="warehousePage++">Next</sw-button>
                            </div>
                        </div>
                    </sw-card>

                    <template v-else>
                        <sw-card :title="'Warehouse: '+selectedWarehouse.code" style="margin:16px 0">
                            <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
                                <div><h3>{{ selectedWarehouse.name }}</h3><p>{{ selectedWarehouse.country_code }} · Priority {{ selectedWarehouse.priority }}</p><p>{{ selectedWarehouse.available_units }} available units</p></div>
                                <sw-button @click="closeWarehouse">Back to warehouses</sw-button>
                            </div>
                        </sw-card>

                        <sw-card title="Add or adjust product stock" style="margin:16px 0">
                            <sw-text-field v-model:value="productSearch" label="Search product by number or name" @update:value="searchProducts"/>
                            <div v-if="productResults.length" style="border:1px solid #d1d5db;border-radius:4px;margin-bottom:12px">
                                <button v-for="product in productResults" :key="product.id" type="button" @click="selectProduct(product)"
                                        style="display:block;width:100%;text-align:left;padding:10px;border:0;border-bottom:1px solid #e5e7eb;background:white;cursor:pointer">
                                    <strong>{{ product.productNumber }}</strong> — {{ product.name }}
                                </button>
                            </div>
                            <p v-if="selectedProduct">Selected: <strong>{{ selectedProduct.productNumber }}</strong> — {{ selectedProduct.name }}</p>
                            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px">
                                <select v-model="stockOperation" class="sw-field__input" aria-label="Stock operation">
                                    <option value="set">Set physical quantity</option><option value="add">Add quantity</option><option value="remove">Remove quantity</option>
                                </select>
                                <sw-number-field v-model:value="stockQuantity" label="Quantity" :min="0" :max="4294967295"/>
                            </div>
                            <sw-button variant="primary" :disabled="!selectedProduct||!validStockQuantity" @click="adjustStock">Apply stock change</sw-button>
                        </sw-card>

                        <sw-card title="Products and stock levels" style="margin:16px 0">
                            <sw-text-field v-model:value="stockSearch" label="Filter products in this warehouse"/>
                            <div v-if="stockPageItems.length===0" style="padding:24px;text-align:center">No stock rows found for this warehouse.</div>
                            <div v-for="stock in stockPageItems" :key="stock.product_id"
                                 style="display:flex;align-items:center;gap:12px;padding:12px;border-bottom:1px solid #e0e0e0;flex-wrap:wrap">
                                <div style="flex:1;min-width:220px">
                                    <strong>{{ stock.product_number }}</strong>
                                    <div><small>Physical {{ stock.quantity }} · Reserved {{ stock.reserved_quantity }} · Available {{ stock.available_quantity }}</small></div>
                                </div>
                                <sw-number-field v-model:value="stock.minimum_quantity" label="Minimum stock" :min="0" :max="4294967295" size="small"/>
                                <sw-button size="small" @click="saveMinimum(stock)">Save threshold</sw-button>
                            </div>
                            <div style="display:flex;justify-content:space-between;align-items:center;padding-top:16px">
                                <span>Page {{ stockPage }} / {{ stockPageCount }} · {{ filteredStocks.length }} products</span>
                                <div style="display:flex;gap:8px">
                                    <sw-button size="small" :disabled="stockPage<=1" @click="stockPage--">Previous</sw-button>
                                    <sw-button size="small" :disabled="stockPage>=stockPageCount" @click="stockPage++">Next</sw-button>
                                </div>
                            </div>
                        </sw-card>
                    </template>

                    <sw-card title="Open low-stock alerts" style="margin:16px 0">
                        <sw-button size="small" :is-loading="isScanning" @click="scanAlerts">Scan now</sw-button>
                        <sw-data-grid v-if="dashboard" :data-source="dashboard.alerts" :columns="alertColumns"
                                      :show-selection="false" :show-actions="false" :is-loading="isLoading"/>
                        <sw-empty-state v-if="dashboard&&dashboard.alerts.length===0" title="No open alerts"/>
                    </sw-card>
                </div>
            </template>
        </sw-page>
    `,data(){return{isLoading:!1,isScanning:!1,error:null,success:null,dashboard:null,selectedWarehouseCode:null,warehouseSearch:"",warehousePage:1,stockSearch:"",stockPage:1,productSearch:"",productResults:[],selectedProduct:null,stockOperation:"add",stockQuantity:1,newWarehouse:{code:"",name:"",countryCode:"DE",priority:100},pageSize:10,alertColumns:[{property:"warehouse_code",label:"Warehouse"},{property:"product_id",label:"Product ID"},{property:"threshold_quantity",label:"Threshold"},{property:"available_quantity",label:"Available"},{property:"opened_at",label:"Opened at"}]}},created(){this.loadDashboard()},computed:{canCreateWarehouse(){return this.newWarehouse.code.trim()!==""&&this.newWarehouse.name.trim()!==""&&/^[A-Za-z]{2}$/.test(this.newWarehouse.countryCode)},validStockQuantity(){return Number.isInteger(Number(this.stockQuantity))&&Number(this.stockQuantity)>=0&&Number(this.stockQuantity)<=4294967295&&(this.stockOperation==="set"||Number(this.stockQuantity)>0)},selectedWarehouse(){return this.dashboard&&this.selectedWarehouseCode&&this.dashboard.warehouses.find(e=>e.code===this.selectedWarehouseCode)||null},filteredWarehouses(){if(!this.dashboard)return[];const e=this.warehouseSearch.trim().toLowerCase();return e?this.dashboard.warehouses.filter(t=>[t.code,t.name,t.country_code].some(s=>String(s||"").toLowerCase().includes(e))):this.dashboard.warehouses},warehousePageCount(){return Math.max(1,Math.ceil(this.filteredWarehouses.length/this.pageSize))},warehousePageItems(){const e=(this.warehousePage-1)*this.pageSize;return this.filteredWarehouses.slice(e,e+this.pageSize)},filteredStocks(){if(!this.dashboard||!this.selectedWarehouseCode)return[];const e=this.stockSearch.trim().toLowerCase();return this.dashboard.stock.filter(t=>t.warehouse_code===this.selectedWarehouseCode&&(!e||String(t.product_number||"").toLowerCase().includes(e)))},stockPageCount(){return Math.max(1,Math.ceil(this.filteredStocks.length/this.pageSize))},stockPageItems(){const e=(this.stockPage-1)*this.pageSize;return this.filteredStocks.slice(e,e+this.pageSize)}},watch:{warehouseSearch(){this.warehousePage=1},stockSearch(){this.stockPage=1}},methods:{getHttpClient(){return Shopware.Application.getContainer("init").httpClient},getRequestConfig(){return{headers:{Authorization:`Bearer ${this.loginService.getToken()}`}}},setError(e,t){var s,a,o,r;this.error=((r=(o=(a=(s=e==null?void 0:e.response)==null?void 0:s.data)==null?void 0:a.errors)==null?void 0:o[0])==null?void 0:r.detail)||e.message||t,this.success=null},notifySuccess(e){this.success=e,this.error=null},async createWarehouse(){try{await this.getHttpClient().post("_action/medical-inventory/warehouses",this.newWarehouse,this.getRequestConfig()),this.newWarehouse={code:"",name:"",countryCode:"DE",priority:100},await this.loadDashboard(),this.notifySuccess("Warehouse created successfully.")}catch(e){this.setError(e,"Unable to create warehouse.")}},async saveWarehouse(e){try{await this.getHttpClient().patch("_action/medical-inventory/warehouses/"+encodeURIComponent(e.code),{priority:Number(e.priority)},this.getRequestConfig()),await this.loadDashboard(),this.notifySuccess("Warehouse priority updated.")}catch(t){this.setError(t,"Unable to update warehouse.")}},async toggleWarehouse(e){try{await this.getHttpClient().patch("_action/medical-inventory/warehouses/"+encodeURIComponent(e.code),{active:!e.active},this.getRequestConfig()),await this.loadDashboard(),this.notifySuccess("Warehouse status updated.")}catch(t){this.setError(t,"Unable to change warehouse status.")}},openWarehouse(e){this.selectedWarehouseCode=e.code,this.stockSearch="",this.stockPage=1,this.selectedProduct=null,this.productSearch="",this.productResults=[]},closeWarehouse(){this.selectedWarehouseCode=null},async searchProducts(){const e=this.productSearch.trim();if(e.length<2){this.productResults=[];return}try{const t=await this.getHttpClient().get("_action/medical-inventory/products?term="+encodeURIComponent(e),this.getRequestConfig());this.productResults=t.data.data||[]}catch(t){this.setError(t,"Unable to search products.")}},selectProduct(e){this.selectedProduct=e,this.productSearch=e.productNumber+" — "+e.name,this.productResults=[]},async adjustStock(){if(!(!this.selectedWarehouse||!this.selectedProduct||!this.validStockQuantity))try{await this.getHttpClient().post("_action/medical-inventory/warehouses/"+encodeURIComponent(this.selectedWarehouse.code)+"/stock",{productId:this.selectedProduct.id,operation:this.stockOperation,quantity:Number(this.stockQuantity)},this.getRequestConfig()),await this.loadDashboard(),this.notifySuccess("Product stock updated successfully.")}catch(e){this.setError(e,"Unable to update product stock.")}},async saveMinimum(e){try{await this.getHttpClient().patch("_action/medical-inventory/warehouses/"+encodeURIComponent(e.warehouse_code)+"/products/"+e.product_id+"/minimum",{minimumQuantity:Number(e.minimum_quantity)},this.getRequestConfig()),await this.loadDashboard(),this.notifySuccess("Minimum stock threshold updated.")}catch(t){this.setError(t,"Unable to update minimum stock.")}},async scanAlerts(){this.isScanning=!0;try{const e=await this.getHttpClient().post("_action/medical-inventory/alerts/scan",{},this.getRequestConfig());await this.loadDashboard(),this.notifySuccess((e.data.openOrUpdated||0)+" low-stock alerts opened or updated.")}catch(e){this.setError(e,"Unable to scan stock alerts.")}finally{this.isScanning=!1}},async loadDashboard(){this.isLoading=!0,this.error=null;try{const e=await this.getHttpClient().get("_action/medical-inventory/dashboard",this.getRequestConfig());this.dashboard=e.data}catch(e){this.setError(e,"Unable to load inventory data.")}finally{this.isLoading=!1}},formatLabel(e){return e.replace(/([A-Z])/g," $1").replace(/^./,t=>t.toUpperCase())}}});n.register("medical-inventory",{type:"plugin",name:"MedicalInventory",title:"Medical Inventory",description:"Multi-warehouse stock, reservations and shipments",routePrefix:"medical.inventory",color:"#3b82f6",icon:"regular-box",routes:{index:{component:"medical-inventory-dashboard",path:"index"}},navigation:[{id:"medical-inventory-overview",label:"Overview",color:"#3b82f6",path:"medical.inventory.index",icon:"regular-chart-bar",parent:"sw-catalogue",position:100}]});
//# sourceMappingURL=medical-inventory-s4v6gqjQ.js.map
