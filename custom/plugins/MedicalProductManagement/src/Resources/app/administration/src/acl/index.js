Shopware.Service('privileges').addPrivilegeMappingEntry({
    category: 'permissions',
    parent: 'catalogues',
    key: 'medical_product_management',
    roles: {
        viewer: {
            privileges: ['product:read', 'media:read'],
            dependencies: [],
        },
        editor: {
            privileges: ['product:update'],
            dependencies: ['medical_product_management.viewer'],
        },
        creator: {
            privileges: ['product:create', 'product:update'],
            dependencies: ['medical_product_management.viewer', 'medical_product_management.editor'],
        },
        deleter: {
            privileges: ['product:delete'],
            dependencies: ['medical_product_management.viewer'],
        },
    },
});