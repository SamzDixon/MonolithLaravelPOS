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
