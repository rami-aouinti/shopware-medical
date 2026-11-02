# MedicalProductManagement

Shopware 6.7 medical product catalogue extensions and technical document management.

## Included
- Medical product custom fields on the native Shopware product entity.
- Server-side validation through Shopware PreWriteValidationEvent for package quantity, package unit and minimum order quantity.
- Product document DAL entity linked to native product and media entities.
- Administration module under Catalogues > Product documents.
- CRUD action routes protected by native product ACL privileges.
- Private/public media consistency checks. Private document responses never include a public file URL.
- Unit tests, DAL registration tests and route/ACL integration tests.

## Business rules
- package_quantity is optional, but when supplied it must be a positive integer.
- package_unit must contain 1–32 non-whitespace characters and is required when package_quantity is configured.
- minimum_order_quantity is optional, but when supplied it must be a finite number greater than zero.

## Document privacy
A private document must reference a Shopware media item already created with its native private flag enabled. A public document must reference public media. The plugin rejects mismatches and never returns a public URL for a private document. It deliberately does not toggle the privacy flag of an existing media item because that item may be shared by other entities.

The custom document entity is not added to Store API fields. Its CRUD endpoints are Administration API action routes with product:read, product:update and product:delete ACL checks. Do not upload confidential files into public media storage.

## Update
From the Shopware project root:
- bin/console plugin:refresh
- bin/console plugin:update MedicalProductManagement
- bin/console database:migrate --all
- bin/console medical:product:install-fields
- bin/console cache:clear
- bin/console administration:build

## Tests
- vendor/bin/phpunit --configuration=phpunit.dist.xml --testsuite="MedicalProductManagement Unit"
- vendor/bin/phpunit --configuration=phpunit.dist.xml --testsuite="MedicalProductManagement DAL Integration"
- vendor/bin/phpunit --configuration=phpunit.dist.xml --testsuite="MedicalProductManagement API Functional"

Run integration suites only after the Shopware test database is configured. Validate the complete plugin in the target Docker environment before production use.