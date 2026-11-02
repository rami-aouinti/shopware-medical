# MedicalInventory observability

## Structured application logs

MedicalInventory writes structured PSR-3 records through Shopware's \`logger\` service.

Important event names:

- \`medical_inventory.api.request\`: inventory API request started.
- \`medical_inventory.api.response\`: route, HTTP status and duration in milliseconds.
- \`medical_inventory.api.exception\`: inventory API exception class.
- \`medical_inventory.monitoring.collected\` / \`medical_inventory.monitoring.failed\`: monitoring collection result.
- \`medical_inventory.alert_scan.started\` / \`completed\` / \`failed\`: scheduled low-stock scan lifecycle.

The request subscriber deliberately does not log request bodies, authorization headers, customer data or tracking numbers.

## Monitoring API

Authenticated Shopware API endpoint:

\`GET /api/_action/medical-inventory/monitoring\`

The route uses the \`product:read\` ACL and returns:

- total and active warehouses;
- stock row count, physical quantity, reserved quantity and available quantity;
- open low-stock alerts;
- active reservations;
- shipments currently in the \`SHIPPED\` state.

The response is a JSON snapshot with a generation timestamp. It is suitable for an external poller or dashboard. It is not a Prometheus exporter and does not retain historical time series.

## Local verification

Run inside the Shopware container:

\`\`\`bash
bin/console cache:clear
bin/console debug:container MedicalInventory\\\\Monitoring\\\\InventoryMonitoringService
bin/console debug:router | grep medical_inventory
bin/console scheduled-task:register
bin/console messenger:consume scheduled-task -vv
\`\`\`

Call the endpoint with a valid Shopware API bearer token:

\`\`\`bash
curl -H "Authorization: Bearer $SHOPWARE_ACCESS_TOKEN" \\
  http://127.0.0.1:8000/api/_action/medical-inventory/monitoring
\`\`\`

Inspect logs with:

\`\`\`bash
tail -f var/log/dev.log
\`\`\`

## Operational notes

- Configure production log retention and centralized log shipping at the platform level.
- Poll the monitoring endpoint at a modest interval (for example, every 60 seconds).
- Alert externally when the endpoint is unavailable, open alerts exceed an agreed threshold, or the available stock gauge becomes unexpectedly negative.
- The metrics endpoint reports a current snapshot; add a time-series backend such as Prometheus/Grafana only when historical charts and alert rules are required.
