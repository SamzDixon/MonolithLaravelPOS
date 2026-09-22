# RetailPay

A monolithic inventory and sales management system for KK Wholesalers, built with Laravel 11, Blade, and MySQL. Tracks stock across branches and stores, records sales, and moves stock between locations with a full audit trail.

---

## What It Does

The system supports the KK Wholesalers scenario from the brief: two branches, one with a single store and the other with two stores, selling wholesale products and moving stock between locations as needed.

**Implemented:**

- Authentication with three roles: `admin`, `branch_manager`, `store_manager`. Public registration is disabled — users are created by administrators.
- Branch management: create, edit, deactivate, soft-delete.
- Store management scoped to branches.
- Product catalogue with SKU, cost price, selling price, reorder level, and margin display.
- User management with role-conditional forms (branch and store fields appear only when relevant).
- Stock levels per store, backed by an immutable movement ledger.
- Sales recording with atomic stock decrement, line-item form, live stock check, and per-item pricing snapshots.
- Void sales: reverses stock and records compensating ledger entries; only admins and branch managers may void.
- Stock transfers with a three-state lifecycle (`pending → dispatched → received`) and a `cancelled` terminal state.
- Receive form with per-line quantity confirmation — corrections for damaged or short deliveries are recorded against the ledger.
- Dashboard with stock value, sales today, low-stock alerts, and recent movements.
- Live filtering on stock levels, stock movements, and sales.
- Role-based authorization enforced via policies.

**Deferred (see Limitations):**

- Reporting screens (sales by period, top-selling products, stock turnover).
- CSV or PDF export.
- Nightly reconciliation of `stock_levels` from the ledger.

---

## Technology Stack

- **Laravel 11** — application framework
- **PHP 8.2+**
- **MySQL / MariaDB** — primary database (InnoDB for transactions)
- **Blade** — server-rendered views
- **Tailwind CSS** — styling (via Laravel Breeze)
- **Alpine.js** — small interactive components (toasts, modals, sidebar, live filters)
- **jQuery** — sale and transfer line-item manipulation
- **Vite** — asset bundling

**Why Blade and not a Vue SPA:** the brief permits either. A POS terminal in a Kenyan wholesale business runs on low-end hardware with unreliable connectivity. Server-rendered Blade pages work without JavaScript, degrade gracefully, and require no separate build pipeline for the reviewer to reproduce. The interactive pieces (modals, toasts, dropdowns, sidebar) are small enough that Alpine and jQuery handle them without adding a framework runtime.

---

## Setup

### Requirements

- PHP 8.2 or later with `pdo_mysql`, `mbstring`, `xml`, `ctype`, `json`, `bcmath`
- Composer 2.x
- Node.js 18+ and npm
- MySQL 8.0+ or MariaDB 10.4+

### Installation

```bash
# 1. Clone the repository
git clone git@github.com:SamzDixon/MonolithLaravelPOS.git
cd MonolithLaravelPOS

# 2. Install PHP dependencies
composer install

# 3. Install JS dependencies
npm install

# 4. Create the environment file
cp .env.example .env

# 5. Generate the application key
php artisan key:generate

Database
Create the database:

bash
mysql -u root -p -e "CREATE DATABASE retailpay CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
Edit .env:

text
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=retailpay
DB_USERNAME=root
DB_PASSWORD=
Migrate and Seed
bash
php artisan migrate:fresh --seed
This creates the schema and seeds the KK Wholesalers scenario: two branches, three stores, eight products, six users, and opening stock at every store.

Build Assets and Run
bash
npm run build
php artisan serve
Open http://127.0.0.1:8000 and log in with one of the credentials below.

For Active Development
In a separate terminal, run Vite in watch mode so Tailwind recompiles on Blade changes:

bash
npm run dev
Sample Credentials
All seeded users have the password password.

Role	Email	Scope
Administrator	admin@kkwholesalers.co.ke	Full system access
Branch Manager (Nairobi)	bm.nairobi@kkwholesalers.co.ke	All stores in Nairobi branch
Branch Manager (Mombasa)	bm.mombasa@kkwholesalers.co.ke	All stores in Mombasa branch
Store Manager (Nairobi Main)	sm.nairobimain@kkwholesalers.co.ke	Nairobi Main store only
Store Manager (Mombasa Main)	sm.mombasamain@kkwholesalers.co.ke	Mombasa Main store only
Store Manager (Mombasa Warehouse)	sm.mombasawarehouse@kkwholesalers.co.ke	Mombasa Warehouse store only
Testing
bash
php artisan test
31 tests, 72 assertions, all passing. The suite uses SQLite in-memory (Laravel's default for tests) to keep php artisan test fast and portable. The application runs on MySQL as required by the brief.

The suite covers:

Authentication — login, logout, password reset, password update (Breeze defaults).

Authorization — role boundaries on sales and transfers; store managers cannot see or act on other stores' records.

Dashboard — access per role, guest redirect, deactivated user logout.

Sale recording — atomic stock decrement, insufficient-stock rejection, reference format.

Stock transfer lifecycle — full pending → dispatched → received flow with ledger assertions on both ends; dispatch rejects insufficient stock; double-receive is blocked at the policy layer.

If you want to run tests against MySQL instead of SQLite, edit phpunit.xml and set DB_CONNECTION=mysql plus a separate DB_DATABASE.

Assumptions
Where the brief was not explicit, I made the following assumptions. Each is documented so the reviewer can see the reasoning.

Hierarchy. Stock lives at the store level, never at the branch. A branch is an organisational unit containing one or more stores. This matches the brief's description ("Branch 1 has 1 store, Branch 2 has 2 stores").

One SKU per product. The brief mentions "products/SKUs" interchangeably. I modelled one product = one SKU. If the business later sells the same product in multiple sizes or colours, this would need a variant table. Noted as a limitation.

Single currency (KES). A wholesale business in Kenya. Multi-currency was not asked for and would complicate every price column with an exchange rate table. Hardcoded KES in views.

Prices are set per product, not per store. All stores sell the same product at the same price. This keeps v1 sane. A real deployment would likely want per-store price lists.

Stock transfers require two-step confirmation. A transfer is dispatched from the source store (which decrements stock there) and then received at the destination (which increments stock there). See the Notable Design Decisions section for why.

No negative stock is permitted. Every decrement is checked against current quantity, inside a database transaction with a row-level lock. If a cashier tries to sell more than is on the shelf, the entire operation rolls back and an error surfaces.

Sales are cash/credit-agnostic at the stock level. We record that a sale happened and decrement stock. Payment integration (M-Pesa, card, cash drawer reconciliation) is explicitly out of scope.

Soft deletes on master data. Branches, stores, products, users, sales, and transfers are never hard-deleted. Their historical records must remain intact for audit. Unique validation rules filter soft-deleted rows to allow reuse of names and codes.

Users are managed by admins, not self-service. There is no "delete my account" or "edit my profile" flow. POS users are created, assigned a role, and deactivated when they leave. Their historical sales remain attributed to them.

Timezone: Africa/Nairobi. Timestamps stored in UTC, displayed in EAT.

Notable Design Decisions
Beyond the assumptions above, three decisions shaped the codebase.

1. Transfers have a three-state lifecycle, not a single mutation
A transfer is not "move 20 units from A to B." It is:

A branch manager creates a pending transfer listing intended quantities.

The source store manager confirms dispatch — stock leaves A, the ledger records transfer_out, the transfer enters dispatched.

The destination store manager confirms receipt — stock enters B, the ledger records transfer_in, the transfer becomes received.

Every state transition records *_by and *_at. If stock goes missing in transit, the audit trail names both the dispatcher and the receiver. A single "move" mutation would leave no way to represent or investigate that gap.

2. Receive quantities can differ from dispatch quantities
Real stock gets lost, damaged, or miscounted in transit. Rather than force a match, the receive form lets the operator enter the actual arrived quantity per line. The controller persists those values, and StockService::receiveTransfer credits the destination store with what actually arrived. The difference stands in the ledger, in the transfer's history, and in the receive page's Difference column.

3. Every stock mutation goes through StockService with a row lock
Nothing outside StockService writes to stock_levels or stock_movements. Every public method opens a database transaction and issues SELECT ... FOR UPDATE on the affected stock row before reading, checking, and writing. Without this, two concurrent sales against the same product can both read the same quantity and both decrement — leaving stock at a negative value or double-decrementing.

The lockLevel() helper handles the race on the very first insert of a (store, product) pair: two requests both miss, both try to insert, the unique constraint kills one, and the loser re-reads the row with the lock held.

4. Every FormRequest::authorize() was rewritten deliberately
Laravel's make:request scaffolds authorize() with return false; for security. Every request class in this project was rewritten to either check the user's role directly or delegate to a policy via $user->can(...). This is why the DispatchTransferRequest and ReceiveTransferRequest call into StockTransferPolicy — one source of truth for "who can do what to a transfer."

Known Limitations
Ledger reconciliation is manual. stock_levels is written alongside the ledger but is not periodically re-derived from it. A nightly php artisan stock:reconcile command would close this gap. In the meantime, php artisan migrate:fresh --seed rebuilds the demo.

No reporting screens. The dashboard shows top-level KPIs. Sales-by-period, top-selling products, and stock-turnover reports are not built. The underlying queries exist in the controllers.

No CSV or PDF export. Lists are viewable but not exportable.

Live-filter pagination shows page one only. The JSON endpoints return the first 25–30 rows of the filtered set. For KK Wholesalers' current volume this is fine; at scale it would need cursor pagination in the Alpine layer.

No optimistic concurrency on the POS form. If two cashiers submit a sale against the same product at the same moment, the row lock serialises them correctly — one waits for the other. But if a cashier holds the form open for several minutes and stock changes underneath them, the sale is rejected at submit time rather than warning them earlier. A live stock check on focus would improve this.

Transfers in transit have no dedicated store. Once dispatched, stock is deducted from the source and does not appear on any destination until received. The ledger records the deduction and the eventual arrival; a query for "stock currently in transit" is derivable from the ledger but has no UI.

No purchase orders or supplier management. Stock enters the system via seeded opening entries, sales, transfers, and manual adjustments. A real wholesale business receives stock from suppliers against POs. Out of scope for this assessment.

No queue-based work. All operations are synchronous. A queue worker for report generation or batch operations is a Day-2 improvement.

Project Structure
text
app/
├── Http/
│   ├── Controllers/
│   │   ├── BranchController.php
│   │   ├── DashboardController.php
│   │   ├── ProductController.php
│   │   ├── SaleController.php
│   │   ├── StockLevelController.php
│   │   ├── StockMovementController.php
│   │   ├── StockTransferController.php
│   │   ├── StoreController.php
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
│       ├── StoreStoreRequest.php
│       ├── StoreTransferRequest.php
│       ├── StoreUserRequest.php
│       ├── UpdateBranchRequest.php
│       ├── UpdateProductRequest.php
│       ├── UpdateStoreRequest.php
│       └── UpdateUserRequest.php
├── Models/
│   ├── Branch.php
│   ├── Product.php
│   ├── Sale.php
│   ├── SaleItem.php
│   ├── StockLevel.php
│   ├── StockMovement.php
│   ├── StockTransfer.php
│   ├── StockTransferItem.php
│   ├── Store.php
│   └── User.php
├── Policies/
│   ├── BranchPolicy.php
│   ├── ProductPolicy.php
│   ├── SalePolicy.php
│   ├── StockTransferPolicy.php
│   ├── StorePolicy.php
│   └── UserPolicy.php
└── Services/
    └── StockService.php    ← all stock mutations funnel through here

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
    ├── sales/
    ├── stock-levels/
    ├── stock-movements/
    ├── stores/
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
Author
Built as a trial assessment. The brief is deliberately under-specified; the assumptions and decisions above are documented to make the reasoning visible.
