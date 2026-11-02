# Bro Medical Observability Stack

Independent monitoring services. The Shopware CLI-managed `compose.yaml` remains untouched; start the stack with the additional `compose.monitoring.yaml` overlay.

## Components

| Component | Purpose | Local endpoint |
|---|---|---|
| Grafana | Metrics, logs and traces dashboards | http://localhost:3000 |
| Prometheus | Infrastructure and business metrics | http://localhost:9090 |
| Loki | Central log storage | http://localhost:3100 |
| Grafana Alloy | Docker log collection (Promtail replacement) | http://localhost:12345 |
| OpenTelemetry Collector | OTLP receiver | localhost:4317 / localhost:4318 |
| Tempo | Trace storage | http://localhost:3200 |
| cAdvisor | Container metrics | http://localhost:8081 |
| Node Exporter | Host metrics | http://localhost:9100 |

cAdvisor uses host port 8081 because this repository's Shopware compose already publishes port 8080.

## Configure Shopware credentials

Create a Shopware integration with the `product:read` privilege. Copy its access key and secret to the repository root file `.env.monitoring`.

```bash
cp monitoring/.env.monitoring.example .env.monitoring
nano .env.monitoring
```

Set a long unique Grafana password. The root `.gitignore` excludes this file.

## Start from repository root

```bash
docker compose --env-file .env.monitoring -f compose.yaml -f compose.monitoring.yaml config
docker compose --env-file .env.monitoring -f compose.yaml -f compose.monitoring.yaml pull
docker compose --env-file .env.monitoring -f compose.yaml -f compose.monitoring.yaml up -d
docker compose --env-file .env.monitoring -f compose.yaml -f compose.monitoring.yaml ps
```

Prometheus uses OAuth2 client-credentials against `/api/oauth/token` to scrape the ACL-protected route `/api/_action/medical-inventory/metrics`.

## Grafana

Sign in at http://localhost:3000 with the credentials from `.env.monitoring`. Prometheus, Loki and Tempo datasources and the **Bro Medical – MedicalInventory** dashboard are provisioned automatically.

## Logs

Alloy discovers Docker containers and forwards stdout/stderr to Loki. Symfony logs are written as JSON to stdout by the root Monolog handler.

LogQL examples:

```logql
{job="docker"} |= "medical_inventory"
{job="docker"} | json | event="medical_inventory.api.exception"
```

## Business metrics

The Prometheus endpoint exports warehouse counts, stock rows, physical/reserved/available units, open low-stock alerts, active reservations and shipments in SHIPPED state.

## Tracing status

Tempo and the OpenTelemetry Collector accept OTLP traces and metrics. The Shopware container receives the OTLP endpoint environment variables. PHP spans will appear only after a compatible OpenTelemetry PHP SDK/auto-instrumentation package is installed and configured; the stack does not fabricate application traces.

## Operations

```bash
docker compose --env-file .env.monitoring -f compose.yaml -f compose.monitoring.yaml logs -f grafana prometheus loki alloy otel-collector tempo
# Prometheus targets: http://localhost:9090/targets
docker compose --env-file .env.monitoring -f compose.yaml -f compose.monitoring.yaml down
# Destructive: also deletes stored metrics, logs, traces and Grafana state
docker compose --env-file .env.monitoring -f compose.yaml -f compose.monitoring.yaml down -v
```

This is a local development stack, not a hardened public production deployment. All host-published ports bind to localhost. Alloy needs Docker socket access; protect the host and do not expose its UI publicly. Retention: Loki 7 days, Tempo 48 hours, Prometheus 15 days.
