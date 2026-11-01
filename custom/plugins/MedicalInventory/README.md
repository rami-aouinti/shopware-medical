# MedicalInventory demo data

The demo command creates a repeatable medical-supplies catalogue and warehouse
stock for the two sample warehouses. It is safe to run more than once:

- Existing warehouses are reused and never renamed or overwritten.
- Existing products are reused by product number and never overwritten.
- Existing warehouse/product stock rows are preserved.
- Only missing demo products and missing stock rows are created.

## Seed the demo catalogue

From the project root:

\`\`\`bash
docker compose exec web bin/console medical:inventory:demo-data
\`\`\`

The command requires an EUR currency and at least one tax rule. It prefers the
19% tax rule and falls back to the first configured tax rule.

## Inspect the result

\`\`\`bash
docker compose exec web bin/console medical:inventory:warehouse list
docker compose exec web bin/console medical:inventory:stock list --warehouse=DE-KOELN
docker compose exec web bin/console medical:inventory:stock list --warehouse=FR-PARIS_01
\`\`\`

The plugin stock is tracked per warehouse. Shopware's native product stock is
initialized to the sum of the demo warehouse quantities only when a demo
product is first created; existing products are not changed.

## Tests and quality tools

Install the project dependencies, including the new development tools:

\`\`\`bash
docker compose exec web composer update --with-all-dependencies --no-interaction
\`\`\`

Run the plugin unit tests:

\`\`\`bash
docker compose exec web composer test:plugin
\`\`\`

Run every quality gate:

\`\`\`bash
docker compose exec web composer quality
\`\`\`

The available fix commands are \`composer cs:cbf\`, \`composer cs:fix\`, and
\`composer rector:fix\`. The CI pipeline runs checks in dry-run mode; it never
rewrites source files automatically.

## Xdebug

The repository includes an optional Compose override based on the PHP 8.5
Shopware development image. Rebuild the web container to enable Xdebug:

\`\`\`bash
docker compose up -d --build web
docker compose exec web php -v
docker compose exec web php --ri xdebug
\`\`\`

Configure the IDE to listen on port \`9003\`, use the host mapping
\`/var/www/html\` to the project root, and start debugging with the
\`XDEBUG_TRIGGER\` environment variable or cookie.
