# MedicalProductManagement

Shopware 6.7 plugin for medical product catalogue information.

## Current scope

The first implementation step registers a dedicated product custom-field set:

- Medical reference
- Manufacturer reference
- Units per package
- Package unit
- Minimum order quantity
- Storage conditions
- Special handling flag

The fields are attached to Shopware's native `product` entity. The plugin does not duplicate product, manufacturer, media, category or stock entities.

## Installation

From the Shopware project root:

```bash
bin/console plugin:refresh
bin/console plugin:install --activate MedicalProductManagement
bin/console medical:product:install-fields
bin/console cache:clear
```

The installation command is idempotent and can be executed again to update the field-set definition.

## Planned next steps

1. Add typed product-document DAL entity linked to the native product and media entities.
2. Add server-side validation for package quantity and minimum order quantity.
3. Add document management in the Shopware Administration.
4. Add controlled Store API exposure and automated tests.
