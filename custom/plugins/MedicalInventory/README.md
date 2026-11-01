# MedicalInventory demo data

The demo command creates a small, repeatable medical-supplies catalogue and stock
for the two sample warehouses. It is safe to run more than once:

- Existing warehouses are reused and never renamed or overwritten.
- Existing products are reused by product number and never overwritten.
- Existing warehouse/product stock rows are preserved.
- Only missing demo products and missing stock rows are created.

## Seed the demo catalogue

From the project root:

```bash
docker compose exec web bin/console medical:inventory:demo-data
```

The command requires an EUR currency and at least one tax rule. It prefers the
19% tax rule and falls back to the first configured tax rule.

## Inspect the result

```bash
docker compose exec web bin/console medical:inventory:warehouse list
docker compose exec web bin/console medical:inventory:stock list --warehouse=DE-KOELN
docker compose exec web bin/console medical:inventory:stock list --warehouse=FR-PARIS_01
```

The plugin stock is tracked per warehouse. Shopware's native product stock is
initialized to the sum of the demo warehouse quantities only when a demo
product is first created; existing products are not changed.
