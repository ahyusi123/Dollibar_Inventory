# Dollibar Inventory Management System

Dollibar is a standalone Inventory application built with Laravel 11, Sanctum and React/Vite. It owns product catalogue, warehouses, suppliers, stock balances, stock movements and purchase orders. It is **not** an HR or Accounting application.

## System boundaries

- **Dollibar Inventory** is the source of truth for SKUs, stock quantities, warehouses, receipts, issues and purchase orders.
- **Dolibarr HR** remains the source of truth for employees. Inventory can accept employee reference updates from HR; it does not manage payroll, attendance or HR roles.
- **Crater Accounting** remains the source of truth for invoices and accounting. Inventory can hand purchase-order data to Crater; it does not post journal entries or replace accounting workflows.

The integrations are API adapters. The HR sync endpoint stores an external employee reference, not a second HR employee master. The Crater exporter is configurable and must be checked against the exact Crater version and its enabled API before production.

## Requirements

- PHP 8.2+
- Composer
- MySQL 8+ (SQLite is also suitable for local development if configured)
- Node.js 18+
- npm

## Install

### Backend
```bash
cd backend
copy .env.example .env       # Windows PowerShell: Copy-Item .env.example .env
composer install
php artisan key:generate
# Configure DB_* in .env
php artisan migrate --seed
php artisan serve
```

The seeded local demo account is `admin@dollibar.test` / `password`. Change it immediately and do not use it in production.

### Frontend
```bash
cd frontend
npm install
npm run dev
```
Vite proxies `/api` to `http://127.0.0.1:8000`. Open `http://localhost:5173`.

For production, build with `npm run build` and serve the generated `frontend/dist` through your web server. Set `APP_DEBUG=false`, use HTTPS, configure CORS and a strong `APP_KEY`, and create non-demo user accounts.

## Roles and access

Supported login roles are `inventory_admin`, `inventory_staff`, and `integration`. HR and Accounting roles are deliberately not created in this application. The `users.role` column defaults to `inventory_admin`; create dedicated integration users/tokens for system-to-system access. Sanctum bearer tokens are used for API authentication.

## API

Base URL: `https://YOUR-INVENTORY-HOST/api/v1`

All endpoints below except `POST /login` and the signed webhook require:
`Authorization: Bearer YOUR_TOKEN`
`Accept: application/json`

| Method | Endpoint | Purpose |
|---|---|---|
| POST | `/login` | Exchange Inventory credentials for a Sanctum token |
| POST | `/logout` | Revoke current token |
| GET | `/me` | Current authenticated user |
| GET | `/dashboard` | Product count, low stock, stock value, recent movements |
| GET/POST | `/products` | List/create products |
| GET/PUT/PATCH/DELETE | `/products/{id}` | Read/update/delete a product |
| GET/POST | `/categories` | List/create categories |
| GET/POST | `/warehouses` | List/create warehouses |
| GET/POST | `/suppliers` | List/create suppliers |
| GET | `/stock-movements` | Read movement audit history |
| POST | `/stock/adjustments` | Receive, issue or adjust stock |
| GET/POST | `/purchase-orders` | List/create purchase orders |\n| POST | `/purchase-orders/{id}/items` | Add a product line to a purchase order; recalculates total |
| POST | `/integrations/tokens` | Create a separate connector token |
| GET | `/integrations` | Integration configuration status |
| PUT | `/integrations/hr/employees/{externalId}` | Receive an employee reference from HR |
| POST | `/integrations/crater/purchase-orders/{id}/export` | Send PO data to Crater |
| POST | `/integrations/webhooks` | Receive authenticated external events |

List endpoints are paginated and accept `?search=...&per_page=50`.

### Stock adjustment request

```http
POST /api/v1/stock/adjustments
Authorization: Bearer YOUR_TOKEN
Content-Type: application/json
```
```json
{
  "product_id": 1,
  "warehouse_id": 1,
  "type": "receipt",
  "quantity": 25,
  "reference": "GRN-2026-0001",
  "notes": "Goods received"
}
```
`type` is `receipt`, `issue`, or `adjustment`. Quantity must be greater than zero. An issue is rejected if the warehouse does not have enough stock. The balance and movement ledger are updated in one database transaction.

## Connect Dolibarr HR

1. Create a dedicated integration token in Inventory → API Integrations. Keep it in the HR server's secret store.
2. In the HR system, configure an outbound webhook/job for employee create/update events.
3. Map the HR employee's immutable ID to `{externalId}` and send the allowed employee fields to:
   `PUT https://YOUR-INVENTORY-HOST/api/v1/integrations/hr/employees/{externalId}`
4. Include `Authorization: Bearer INVENTORY_CONNECTOR_TOKEN` and JSON content type.
5. Inventory records the external ID and a minimal employee reference in `external_references`. HR remains authoritative. Do not send salary, bank, government ID or other unnecessary personal data.

Example:
```bash
curl -X PUT "https://YOUR-INVENTORY-HOST/api/v1/integrations/hr/employees/HR-1001" \
  -H "Authorization: Bearer INVENTORY_CONNECTOR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"employee_no":"HR-1001","name":"Juan Dela Cruz","email":"juan@example.com","department":"IT","status":"Active"}'
```

## Connect Crater Accounting

1. Create a Crater API token/service credential with only the invoice-create permissions needed.
2. Configure `CRATER_URL`, `CRATER_TOKEN`, and `CRATER_INVOICE_ENDPOINT` in Inventory's `.env`; the endpoint defaults to `/api/v1/invoices`.
3. Create suppliers and purchase orders in Inventory, then call:
   `POST /api/v1/integrations/crater/purchase-orders/{id}/export`
4. Inventory sends PO reference, supplier reference, currency, total and line items to `{CRATER_URL}{CRATER_INVOICE_ENDPOINT}`, using bearer authentication.
5. The returned Crater invoice ID is saved to `purchase_orders.crater_invoice_id`.

**Important compatibility step:** Crater API routes, authentication and invoice payload fields can differ by release or deployment. Verify the invoice-create route and required JSON fields in the API documentation for your installed Crater version. If its route/schema differs, adjust only `IntegrationController::craterExport()` to map Inventory's PO payload to that Crater API. Test with a sandbox company first; do not assume a successful HTTP response means accounting approval or payment.

## Webhooks

Set `INTEGRATION_WEBHOOK_SECRET` to a long random secret. External systems may send:
```http
POST /api/v1/integrations/webhooks
X-Integration-Secret: YOUR_SHARED_SECRET
Content-Type: application/json
```
```json
{"event":"invoice.created","source":"crater","external_id":"INV-123","data":{"status":"draft"}}
```
The endpoint validates the secret and stores the event payload in `integration_events`. Add event-specific processing/idempotency before using it to trigger stock changes.

## Operational notes

- Use HTTPS and firewall the API to trusted networks where practical.
- Keep API tokens out of source control and frontend code.
- Issue separate tokens per external system; rotate/revoke them when staff or integrations change.
- Add retries, idempotency keys, delivery logs and reconciliation reports before high-volume production use.
- Back up the database and test migrations in staging.
- Legacy asset-management tables from the original starter project may still exist in the database for backward-compatible migration history, but are no longer exposed in the Inventory UI or v1 API. New functionality is built around inventory products and stock.
