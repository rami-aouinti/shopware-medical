# MedicalInventory demo data

The demo command creates a repeatable medical-supplies catalogue and warehouse
stock for the two sample warehouses. It is safe to run more than once:

- Existing warehouses are reused and never renamed or overwritten.
- Existing products are reused by product number and never overwritten.
- Existing warehouse/product stock rows are preserved.
- Only missing demo products and missing stock rows are created.

## Seed the demo catalogue

From the project root:

~~~bash
docker compose exec web bin/console medical:inventory:demo-data
~~~

The command requires an EUR currency and at least one tax rule. It prefers the
19% tax rule and falls back to the first configured tax rule.

## Inspect the result

~~~bash
docker compose exec web bin/console medical:inventory:warehouse list
docker compose exec web bin/console medical:inventory:stock list --warehouse=DE-KOELN
docker compose exec web bin/console medical:inventory:stock list --warehouse=FR-PARIS_01
~~~

The plugin stock is tracked per warehouse. Shopware's native product stock is
initialized to the sum of the demo warehouse quantities only when a demo
product is first created; existing products are not changed.

## Tests and quality tools

Install the project dependencies, including the new development tools:

~~~bash
docker compose exec web composer install --no-interaction
~~~

Run the plugin unit tests and MariaDB integration tests:

~~~bash
docker compose exec web composer test:plugin
docker compose exec web composer test:integration
~~~

Run every quality gate:

~~~bash
docker compose exec web composer quality
~~~

The available fix commands are composer cs:cbf, composer cs:fix, and
composer rector:fix. The CI pipeline runs checks in dry-run mode; it never
rewrites source files automatically.

## Xdebug

The repository includes an optional Compose override based on the PHP 8.5
Shopware development image. Rebuild the web container to enable Xdebug:

~~~bash
docker compose up -d --build web
docker compose exec web php -v
docker compose exec web php --ri xdebug
~~~

Configure the IDE to listen on port 9003, use the host mapping
/var/www/html to the project root, and start debugging with the
XDEBUG_TRIGGER environment variable or cookie.


## Warehouse priority and allocation planning

Every warehouse has an allocation priority from 0 to 65535. Lower values have
higher priority; newly created warehouses default to 100. Existing warehouses
receive priority 100 through an additive migration, without changing their
stock or other warehouse data.

Set a priority when creating a warehouse:

~~~bash
docker compose exec web bin/console medical:inventory:warehouse create \
  --code=DE-KOELN --name="Köln Central Warehouse" --country=DE --priority=10
~~~

Change an existing priority:

~~~bash
docker compose exec web bin/console medical:inventory:warehouse set-priority \
  --code=DE-KOELN --priority=10
~~~

The allocation planner follows these rules:

1. Ignore inactive warehouses and warehouses with no available stock.
2. Prefer one warehouse that can fulfil the complete requested quantity.
3. If several warehouses can fulfil it, choose the lowest priority value.
4. For equal priorities, choose the warehouse with the greatest available stock.
5. If no single warehouse can fulfil it, split the quantity in priority order,
   using available stock descending as the tie-breaker.
6. Use warehouse code as a deterministic final tie-breaker.
7. If combined stock is insufficient, return an insufficient-stock exception;
   no stock is reserved or changed by the planner.

## Order allocation and reservation

The order persister is decorated so order persistence and warehouse reservation
run inside one DBAL transaction. If any managed product cannot be allocated or
reserved, the order write and all reservations roll back together.

- Product line items with MedicalInventory stock rows are allocated by the priority planner.
- Products without MedicalInventory stock rows remain under native Shopware stock management.
- Each warehouse allocation creates an order allocation and an active stock reservation.
- Reservation and stock movements are written in the same database transaction.
- Cancelling an order releases only the unshipped reservation remainder.
- Shipping consumes reserved and physical stock; partial shipments are supported.
- Stock movements are append-only audit records with signed physical/reserved deltas.

## Shipment commands

Create a shipment for one product from the allocated warehouse:

~~~bash
docker compose exec web bin/console medical:inventory:shipment ship \
  --order=<ORDER_UUID> --warehouse=DE-KOELN \
  --product=<PRODUCT_UUID> --quantity=5 \
  --carrier=DHL --tracking=TRACK123
~~~

List shipments for an order:

~~~bash
docker compose exec web bin/console medical:inventory:shipment list --order=<ORDER_UUID>
~~~

Mark a shipment delivered:

~~~bash
docker compose exec web bin/console medical:inventory:shipment delivered --shipment=<SHIPMENT_UUID>
~~~

## Low-stock alerts

Set a minimum quantity for one warehouse/product pair:

~~~bash
docker compose exec web bin/console medical:inventory:stock minimum \
  --warehouse=DE-KOELN --product=<PRODUCT_UUID> --quantity=10
~~~

Run an alert scan manually:

~~~bash
docker compose exec web bin/console medical:inventory:alerts:scan
~~~

A scheduled task also scans hourly. It opens or updates one active alert when
available stock is at or below the configured threshold and resolves it when
stock rises above the threshold.

## Administration

The Medical Inventory module provides a warehouse dashboard, warehouse
creation, priority and active-status management, stock threshold editing,
low-stock alert visibility, and a manual alert scan. Shipment creation and
delivery updates are also exposed through protected Admin API actions.

## Database integration tests

The CI job starts MariaDB 11.4 and runs the plugin migration integration suite.
It verifies that the new tables are created, the migration is idempotent,
existing physical/reserved quantities are preserved, movement audit writes
participate in transactions, and order allocations have a database-level
idempotency constraint.

The integration suite tests the migration and database constraints directly.
The DAL allocation service is covered separately by the plugin unit tests.
