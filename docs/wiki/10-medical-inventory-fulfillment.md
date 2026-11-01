# MedicalInventory — Fulfillment lifecycle

## Transaction boundary

The plugin decorates Shopware's `OrderPersisterInterface`. The decorated
persister first writes the Shopware order, then the plugin plans and reserves
every managed product line inside the same Doctrine DBAL transaction. If
allocation, reservation, or audit persistence fails, the transaction rolls
back both the order and the inventory changes.

Products that have no `medical_warehouse_stock` row remain outside this
plugin's inventory scope and continue to use native Shopware stock.

## Shopware native stock integration

Shopware 6.7's `OrderStockSubscriber` changes native product stock when order
line items are created, edited, or cancelled. MedicalInventory decorates
Shopware's `StockStorage` and filters those stock alterations for products
that have a warehouse-stock row. This prevents the same order from reducing
both the warehouse availability and the mirrored `product.stock` value twice.
The native storage still handles products outside MedicalInventory, and its
availability indexing remains delegated to Shopware.

## Reservation states

- `ACTIVE`: quantity is reserved and may still be shipped.
- `CONSUMED`: all reserved quantity has been shipped.
- `RELEASED`: the remaining reservation was released after cancellation.

Each order allocation is unique per order, product, product version and
warehouse. This unique key protects against duplicate allocation writes.

## Stock movement audit

Every physical or reserved quantity change writes an immutable
`medical_stock_movement` row in the same transaction. The two signed deltas
separate physical stock changes from reservation changes. Order and shipment
operations include their reference type and identifier.

## Partial shipments

A shipment belongs to one order and one warehouse. Each shipment item consumes
part of an existing order allocation. The service locks the allocation and
reservation rows, validates the remaining quantity, decreases physical and
reserved stock, updates shipped totals, and appends a tracking event atomically.

## Cancellation

When Shopware transitions an order to the cancelled state, active reservation
remainders are released. Already shipped quantities are not restored. For
native closeout products, the remaining native Shopware stock deduction is
restored as part of the same transaction.

## Low-stock monitoring

A per-warehouse/product threshold is stored on the stock row. The hourly
scheduled task scans active warehouses, opens or refreshes an alert when
available quantity is at or below the threshold, and resolves an open alert
when availability rises above it.

## Administration

The Administration dashboard uses authenticated Admin API actions to list
warehouses and stock, create warehouses, update priority/active status, update
minimum quantities, and trigger a low-stock scan. Shipment creation and
delivery updates are also available through Admin API routes and the console.

## Verification boundary

The MariaDB integration suite executes the plugin migration against a real
MariaDB service and checks idempotence, preservation of existing stock,
transaction rollback for audit records, and database uniqueness for order
allocations. Unit tests separately cover planner and DAL service mapping.
