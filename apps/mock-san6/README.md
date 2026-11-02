# Mock SAN6 ERP

An independent, SAN6-inspired ERP simulator used for local development and automated tests of the Shopware `MedicalERPConnector` plugin. This is a fictional development system; it does not reproduce proprietary SAN6 internals or use production data.

## Start

From the repository root:

```bash
docker compose -f compose.yaml -f compose.override.yaml -f compose.san6.yaml up -d --build san6-database san6-api
```

Check the API:

```bash
curl http://localhost:8081/api/health
```

The API is available to Shopware containers at `http://san6-api:8081`. The independent MariaDB is exposed on host port `3307` and is available inside the Compose network as `san6-database:3306`.

## Stop

```bash
docker compose -f compose.yaml -f compose.override.yaml -f compose.san6.yaml stop san6-api san6-database
```

The SAN6 database uses its own named volume and is separate from the Shopware database.

## Planned API surface

- Products and product master data
- Stock availability
- Customers (only the fields required by integration tests)
- Order submission and confirmation
- CSV import/export for bulk data
- XML document exchange

The first implementation intentionally starts with the health endpoint. Business endpoints will be added alongside the corresponding plugin integration tests.
