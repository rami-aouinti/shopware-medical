# MedicalProductManagement

Shopware 6.7 medical product catalogue extensions and technical document management.

## Included
- Medical product custom fields on the native Shopware product entity.
- Server-side validation through Shopware PreWriteValidationEvent for package quantity, package unit and minimum order quantity.
- Product document DAL entity linked to native product and media entities; multiple documents can be linked to one product.
- Administration module under Catalogues > Product documents.
- CRUD action routes protected by native product ACL privileges.
- Public documents return a URL only when both the document and media are public.
- Private documents are streamed through Shopware's core DownloadResponseGenerator, using its private filesystem and configured download strategy. The endpoint requires both `product:read` and `media:read`, and sends no-store security headers.
- Private/public media consistency checks prevent a public document from referencing private media or vice versa.
- Unit tests, DAL registration tests and route/ACL integration tests.

## Business rules
- package_quantity is optional, but when supplied it must be a positive integer.
- package_unit is optional; when supplied it must contain 1–32 non-whitespace characters.
- minimum_order_quantity is optional, but when supplied it must be a finite number greater than zero.
- A product can have multiple documents. Each document is a separate DAL row with its own title, type, media and privacy flag.

## Document privacy
A private document must reference a Shopware media item already created with its native private flag enabled. A public document must reference public media. The plugin rejects mismatches and never returns a public URL for a private document. Private downloads use Shopware's core download response generator instead of constructing a filesystem path or exposing a storage URL.

The custom document entity is not added to Store API fields. Its CRUD endpoints are Administration API action routes with product:read, product:update and product:delete ACL checks. The private download endpoint additionally requires media:read. Do not upload confidential files into public media storage.

## Update
From the Shopware project root:
- bin/console plugin:refresh
- bin/console plugin:update MedicalProductManagement
- bin/console database:migrate --all
- bin/console medical:product:install-fields
- bin/console cache:clear
- bin/console administration:build

## Tests
- Unit tests: `vendor/bin/phpunit --configuration=phpunit.dist.xml --testsuite="MedicalProductManagement Unit"`
- DAL and API integration tests: configure a dedicated disposable database in `MEDICAL_INVENTORY_TEST_DATABASE_URL`, then run `vendor/bin/phpunit --configuration=phpunit.dal.xml.dist --testsuite="MedicalProductManagement DAL Integration"` and `vendor/bin/phpunit --configuration=phpunit.dal.xml.dist --testsuite="MedicalProductManagement API Functional"`
- Complete integration suite: `composer test:integration`

Never point the integration test database at your local development or production Shopware database. The DAL bootstrapper force-installs Shopware in the configured test database. Validate the Administration build and the complete plugin in the target Docker environment before production use.
