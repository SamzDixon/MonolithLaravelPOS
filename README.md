# KK Wholesalers RetailPay Simple ERP

A monolithic inventory and sales management system for KK Wholesalers, built with Laravel 11, Blade, and MySQL. Tracks stock across branches and stores, records sales, receives stock from suppliers, and moves stock between locations with a full audit trail.

---

## What it does

The system covers the KK Wholesalers scenario: two branches, one with a single store and the other with two stores. Stock is tracked per store, sales are recorded at the point of sale, and stock can be moved between stores when one location needs what another has spare.

**Implemented:**

- **Authentication with three roles** — `admin`, `branch_manager`, `store_manager`. Public registration is disabled; users are created by administrators.
- **Organisation setup** — branches, stores, products, and users. Each can be created, edited, deactivated, and soft-deleted.
- **Supplier management** — suppliers are business-wide master data. Every operational role can add and edit them; only admins can delete.
- **Stock tracking** — current levels per store, backed by an immutable movement ledger. Every change to stock writes a `stock_movements` row with a signed delta and a reference back to whatever caused it (sale, receipt, transfer, adjustment).
- **Goods receipts** — stock from a named supplier arrives against a `GRN-YYYYMMDD-NNNN` reference. Each receipt records the supplier, the receiving store, per-line unit cost at time of delivery, and who signed for it.
- **POS sales** — line-item sale form with live stock check, per-item pricing snapshots, and atomic decrement inside a row-locked transaction.
- **Sale voiding** — reverses stock and writes compensating ledger entries. Admins and branch managers only.
- **Stock transfers** — three-state lifecycle: `pending → dispatched → received`, or `cancelled`. Each transition records who performed it and when.
- **Per-line receive confirmation** — the receiving store can correct the arrived quantity if a delivery is short or damaged. The discrepancy is preserved in the ledger.
- **Role-scoped dashboards** — the dashboard shows different content for admin, branch manager, and store manager.
- **Live filtering** on stock levels, stock movements, and sales.

**Deliberately left out — see "Scope decisions" below:**

- Reporting screens (sales by period, top-selling products, stock turnover).
- CSV or PDF export.
- Nightly reconciliation of the stock-level cache from the ledger.
- A request → approval workflow for transfers.

---

## Tech stack

- **Laravel 11** — application framework
- **PHP 8.2+**
- **MySQL 8 / MariaDB 10.4+** — InnoDB for transactions
- **Blade** — server-rendered views
- **Tailwind CSS** — styling, via Laravel Breeze
- **Alpine.js** — toasts, modals, sidebar, live filters
- **jQuery** — line-item manipulation on the sale and receipt forms
- **Vite** — asset bundling

**Why Blade and not a Vue SPA.** The brief permits either, and a POS terminal in a Kenyan wholesale business runs on hardware that doesn't enjoy being asked to hydrate a JavaScript app over a dodgy connection. Server-rendered Blade pages work without JavaScript, degrade gracefully, and require no build step for the reviewer to reproduce. The interactive bits are small enough that Alpine and jQuery handle them without adding a runtime.

---

## Setup

### Requirements

- PHP 8.2+ with `pdo_mysql`, `mbstring`, `xml`, `ctype`, `json`, `bcmath`
- Composer 2.x
- Node.js 18+ and npm
- MySQL 8.0+ or MariaDB 10.4+

### Installation

```bash
git clone git@github.com:SamzDixon/MonolithLaravelPOS.git
cd MonolithLaravelPOS

composer install
npm install

cp .env.example .env
php artisan key:generate
```

### Database

```bash
mysql -u root -p -e "CREATE DATABASE retailpay CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Then edit `.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=retailpay
DB_USERNAME=root
DB_PASSWORD=
```

### Migrate and seed

```bash
php artisan migrate:fresh --seed
```

This creates the schema and seeds the KK Wholesalers scenario: two branches, three stores, eight products, five suppliers, six users, and opening stock at every store.

### Build and run

```bash
npm run build
php artisan serve
```

Open `http://127.0.0.1:8000` and log in with one of the credentials below.

For active development, run Vite in watch mode in a second terminal so Tailwind recompiles on Blade changes:

```bash
npm run dev
```

---

## Sample credentials

Every seeded user's password is `password`.

| Role | Email | Scope |
|---|---|---|
| Administrator | `admin@kkwholesalers.co.ke` | Full system access |
| Branch Manager (Nairobi) | `bm.nairobi@kkwholesalers.co.ke` | All stores in Nairobi branch |
| Branch Manager (Mombasa) | `bm.mombasa@kkwholesalers.co.ke` | All stores in Mombasa branch |
| Store Manager (Nairobi Main) | `sm.nairobimain@kkwholesalers.co.ke` | Nairobi Main store only |
| Store Manager (Mombasa Main) | `sm.mombasamain@kkwholesalers.co.ke` | Mombasa Main store only |
| Store Manager (Mombasa Warehouse) | `sm.mombasawarehouse@kkwholesalers.co.ke` | Mombasa Warehouse store only |

---

## Testing

```bash
php artisan test
```

31 tests, 72 assertions, all passing. The suite runs on SQLite in-memory (Laravel's default for tests) so `php artisan test` is fast and doesn't need a MySQL daemon. The application itself runs on MySQL as the brief requires.

The suite covers:

- **Authentication** — login, logout, password reset, password update (Breeze defaults).
- **Authorization** — role boundaries on sales and transfers; a store manager cannot see or act on another store's records.
- **Dashboard** — access per role, guest redirect, deactivated user logout.
- **Sale recording** — atomic stock decrement, insufficient-stock rejection, reference format.
- **Stock transfer lifecycle** — full `pending → dispatched → received` flow with ledger assertions on both ends; dispatch rejects insufficient stock; double-receive is blocked at the policy layer.

To run tests against MySQL instead of SQLite, edit `phpunit.xml` and set `DB_CONNECTION=mysql` plus a separate `DB_DATABASE`.

---

## Assumptions

Where the brief wasn't explicit, I made these calls. They're listed so the reasoning is visible.

1. **Hierarchy.** Stock lives at the *store*, never the branch. A branch is an organisational unit containing one or more stores. Matches the brief's "Branch 1 has 1 store, Branch 2 has 2 stores".

2. **One SKU per product.** The brief uses "products/SKUs" interchangeably. One product = one SKU. If the business ever sells the same product in multiple sizes or colours, that needs a variant table. Noted in limitations.

3. **Single currency (KES).** Multi-currency wasn't asked for and would complicate every price column with an exchange rate table. Hardcoded KES in the views.

4. **Prices are per product, not per store.** All stores sell at the same price. Keeps v1 sane. A real deployment would likely want per-store price lists.

5. **Stock transfers require two-step confirmation.** A transfer is dispatched from the source (decrementing stock there) and then received at the destination (incrementing stock there). See design decisions below for why.

6. **No negative stock.** Every decrement is checked against current quantity inside a transaction with a row-level lock. If a cashier tries to sell more than is on the shelf, the whole operation rolls back with an error.

7. **Sales are cash/credit-agnostic at the stock level.** We record that a sale happened and decrement stock. Payment integration — M-Pesa, card, cash drawer reconciliation — is out of scope.

8. **Soft deletes on master data.** Branches, stores, products, users, sales, and transfers are never hard-deleted. Their historical records stay intact. Unique validation rules filter soft-deleted rows so names and codes can be reused.

9. **Products are a business-wide catalogue; suppliers are operational contacts.** Product master data — SKU, cost, selling price, reorder level — affects every sale and every report, so admins have full management access (branch managers can view and edit; store managers see it only through the POS form). Suppliers are external entities that store managers meet day-to-day, so all operational roles can add and edit them. Only admins can delete a supplier, because that's a cross-branch decision.

10. **Cross-branch transfers are admin-only.** A branch manager's authority ends at their branch boundary. Allowing one branch manager to pull stock from another branch's store would affect a store whose manager had no say in the decision. Cross-branch reallocation is a business-wide balancing decision — that's what the admin role exists for. Branch managers move stock between stores in their own branch; when they need stock from another branch, they coordinate with the admin.

11. **Users are managed by admins, not self-service.** No "delete my account" or "edit my profile" flow. POS users are created, assigned a role, and deactivated when they leave. Their historical sales stay attributed to them.

12. **Timezone: Africa/Nairobi.** Kenya is a fixed +03:00 with no daylight saving. `APP_TIMEZONE=Africa/Nairobi` is set in `.env.example` so a fresh clone gets the right time without manual config.

---

## Notable design decisions

A few choices that shaped the codebase. Not the only ones, but the ones worth explaining.

### 1. Transfers have a three-state lifecycle, not a single "move" action

A transfer isn't "shift 20 units from A to B" as one atomic step. It's:

1. Someone creates a `pending` transfer listing the intended quantities.
2. The source store manager confirms **dispatch** — stock leaves A, the ledger records `transfer_out`, the transfer enters `dispatched`.
3. The destination store manager confirms **receipt** — stock enters B, the ledger records `transfer_in`, the transfer becomes `received`.

Every state transition records `*_by` and `*_at`. If stock goes missing in transit, the audit trail names both the dispatcher and the receiver. A single "move" mutation can't represent or investigate that gap. Real stock gets lost, damaged, or miscounted in transit; the model has to accommodate it.

### 2. Receive quantities can differ from dispatch quantities

Continuing from above — the receive form lets the operator enter the actual arrived quantity per line. The controller persists those values, and `StockService::receiveTransfer` credits the destination store with what actually arrived. The difference stands in the ledger, in the transfer's history, and in the receive page's Difference column. If a delivery is short by three bags, that's recorded as a fact, not silently rounded up.

### 3. Every stock mutation goes through StockService with a row lock

Nothing outside `StockService` writes to `stock_levels` or `stock_movements`. Every public method opens a database transaction and issues `SELECT ... FOR UPDATE` on the affected stock row before reading, checking, and writing. Without this, two concurrent sales against the same product can both read the same quantity and both decrement — you end up oversold, or the stock goes negative.

The `lockLevel()` helper handles the race on the very first insert of a `(store, product)` pair. Two requests both miss, both try to insert, the unique constraint kills one, and the loser re-reads the row with the lock held.

### 4. Every FormRequest::authorize() was rewritten deliberately

`php artisan make:request` scaffolds `authorize()` with `return false;` — secure by default. Every request class in this project was rewritten to check the user's role or delegate to a policy via `$user->can(...)`. This means dispatch and receive share one source of truth with their policies.

### 5. Eager-load column restrictions — a lesson learned the hard way

Laravel lets you write `->with('relation:id,name')` to load only certain columns. It looks like a performance win, and it is — until some other code reaches for a column you didn't select. Eloquent returns `null` silently, and policies that compare that `null` to a real value fail with no error.

Three separate bugs in this project traced back to this. `StockTransfer::fromStore:id,name` broke the dispatch button, `toStore:id,name` broke receive, and `StockReceipt::store:id,name` broke receipt reversal — each because the policy tried to read `branch_id` from a relation that hadn't loaded it.

The fix — which is also a good general rule — is to include any column a policy or view touches in the eager-load list. Two of the affected controllers now load the full store relation without column restriction, because the performance difference at KK's scale is negligible and the correctness difference is absolute.

---

## Scope decisions

The brief asked for a system to manage sales and stock, move stock between stores, and understand the history of stock movements. That's what's here.

**Three things were added beyond the strict scope:**

- **Suppliers and goods receipts.** The brief mentions a store "receives stock"; a real system has to know where it came from. The receipt module is a natural extension of that requirement, not a separate feature.
- **Role-scoped dashboards.** The brief names three roles and asks for "basic information that would help a manager understand the current state". The dashboard is tailored per role because a store manager has no business seeing another branch's numbers.
- **Live filtering.** A UI convenience, not a business requirement — but it materially improves day-to-day usability of the lists a manager works in.

**What was deliberately *not* built:**

- **Approval workflows for transfers.** A `request → approval → escalation` flow would be closer to a real ERP, but the brief explicitly says the objective is *not* to build a production-ready ERP. Transfers in this delivery are initiated by branch managers or admins within their scope, dispatched by the source side, and received by the destination side. That covers the stated requirement of moving stock between stores with full attribution, without layering on a workflow engine that wasn't asked for.
- **Reporting and exports.** The dashboard surfaces the KPIs a manager needs at a glance. Deeper reporting is deferred — see limitations.
- **Reconciliation jobs.** The ledger and the stock-level cache are written together in the same transaction. A nightly reconciliation command would close the loop for production; it isn't needed for the demo.

---

## Known limitations

Honest list of what's not there.

1. **No ledger reconciliation.** `stock_levels` is written alongside the ledger but is not periodically re-derived from it. A nightly `php artisan stock:reconcile` command would close this gap. Right now, `php artisan migrate:fresh --seed` rebuilds the demo from scratch.

2. **No reporting screens.** The dashboard shows top-level KPIs. Sales-by-period, top-selling products, and stock-turnover reports aren't built. The underlying queries exist inside the controllers; the UI doesn't.

3. **No CSV or PDF export.** Lists are viewable but not exportable.

4. **Live-filter pagination shows page one only.** The JSON endpoints return the first 25–30 rows of the filtered set. For KK's current volume this is fine; at scale it would need cursor pagination in the Alpine layer.

5. **No optimistic concurrency on the POS form.** If two cashiers submit a sale against the same product simultaneously, the row lock serialises them — one waits for the other. But if a cashier holds the form open for several minutes and stock changes underneath them, the sale is rejected at submit time rather than warning earlier. A live stock check on field focus would improve this.

6. **Stock "in transit" has no dedicated store.** Once a transfer is dispatched, stock is deducted from the source and doesn't appear on any destination until received. The ledger records the deduction and the eventual arrival; a query for "stock currently in transit" is derivable from the ledger but has no UI.

7. **No purchase orders.** Stock arrives via goods receipts, not against formal POs. A real wholesale operation would likely want POs with partial fulfilment and backorder handling; the receipt module is intentionally single-step.

8. **No queue-based work.** All operations are synchronous. A queue worker for report generation or batch operations is a day-two improvement.

9. **No cashier role.** The brief names three roles. A production deployment would add a cashier with narrower scope — sales recording and viewing only, with voids routed through manager approval.

---

## Project structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── BranchController.php
│   │   ├── DashboardController.php
│   │   ├── ProductController.php
│   │   ├── SaleController.php
│   │   ├── StockLevelController.php
│   │   ├── StockMovementController.php
│   │   ├── StockReceiptController.php
│   │   ├── StockTransferController.php
│   │   ├── StoreController.php
│   │   ├── SupplierController.php
│   │   └── UserController.php
│   ├── Middleware/
│   │   ├── EnsureUserHasRole.php
│   │   └── EnsureUserIsActive.php
│   └── Requests/
│       ├── DispatchTransferRequest.php
│       ├── ReceiveTransferRequest.php
│       ├── StoreBranchRequest.php
│       ├── StoreProductRequest.php
│       ├── StoreSaleRequest.php
│       ├── StoreStockReceiptRequest.php
│       ├── StoreStoreRequest.php
│       ├── StoreSupplierRequest.php
│       ├── StoreTransferRequest.php
│       ├── StoreUserRequest.php
│       ├── UpdateBranchRequest.php
│       ├── UpdateProductRequest.php
│       ├── UpdateStoreRequest.php
│       ├── UpdateSupplierRequest.php
│       └── UpdateUserRequest.php
├── Models/
│   ├── Branch.php
│   ├── Product.php
│   ├── Sale.php
│   ├── SaleItem.php
│   ├── StockLevel.php
│   ├── StockMovement.php
│   ├── StockReceipt.php
│   ├── StockReceiptItem.php
│   ├── StockTransfer.php
│   ├── StockTransferItem.php
│   ├── Store.php
│   ├── Supplier.php
│   └── User.php
├── Policies/
│   ├── BranchPolicy.php
│   ├── ProductPolicy.php
│   ├── SalePolicy.php
│   ├── StockReceiptPolicy.php
│   ├── StockTransferPolicy.php
│   ├── StorePolicy.php
│   ├── SupplierPolicy.php
│   └── UserPolicy.php
└── Services/
    └── StockService.php    ← the only place that mutates stock

database/
├── factories/
├── migrations/
└── seeders/                ← KK Wholesalers scenario

resources/
└── views/
    ├── branches/
    ├── components/
    │   ├── confirm-delete.blade.php
    │   ├── live-filter.blade.php
    │   └── toast-stack.blade.php
    ├── layouts/
    │   ├── app.blade.php
    │   └── navigation.blade.php    ← collapsible sidebar
    ├── products/
    ├── receipts/
    ├── sales/
    ├── stock-levels/
    ├── stock-movements/
    ├── stores/
    ├── suppliers/
    ├── transfers/
    └── users/

tests/
├── Feature/
│   ├── Auth/
│   ├── AuthorizationTest.php
│   ├── DashboardTest.php
│   ├── SaleRecordingTest.php
│   └── StockTransferLifecycleTest.php
└── Unit/
    └── StockServiceTest.php
```

---

## Where stock comes from

Every unit that moves through the system traces back to exactly one of these:

- `opening` — seeded demo data.
- `receipt` — arrived from a named supplier against a GRN reference on a specific date.
- `sale` — sold at a specific store against a SALE reference.
- `transfer_in` / `transfer_out` — moved between stores with both ends attributed to a user.
- `adjustment` — a documented correction with a reason (including sale voids and receipt reversals).

That's the point of the ledger. Nothing appears from nowhere.

---

## Author

Built as a trial assessment. The brief is deliberately under-specified; the reasoning is documented here so a reviewer can see how the decisions were made, not just what was decided.