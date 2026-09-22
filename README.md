# RetailPay

A monolithic inventory and sales management system for KK Wholesalers, built with Laravel 11, Blade, and MySQL. Tracks stock across branches and stores, records sales, and moves stock between locations with a full audit trail.

---

## What It Does

The system supports the KK Wholesalers scenario from the brief: two branches, one with a single store and the other with two stores, selling wholesale products and moving stock between locations as needed.

**Implemented:**
- Authentication with three roles: `admin`, `branch_manager`, `store_manager`
- Branch management (create, edit, deactivate, delete)
- Store management scoped to branches
- Product catalogue with cost price, selling price, and reorder levels
- Stock levels per store, backed by an immutable movement ledger
- Sales recording with atomic stock decrement
- Stock transfers with a dispatch/receive lifecycle
- Dashboard with stock value, sales today, low-stock alerts, recent movements
- Role-based authorization enforced via policies

**Planned (see Limitations):**
- Product, user, stock level, and stock movement Blade views
- Reporting screens (sales by period, stock turnover)
- CSV export

---

## Technology Stack

- **Laravel 11** — application framework
- **PHP 8.2+**
- **MySQL / MariaDB** — primary database (InnoDB for transactions)
- **Blade** — server-rendered views
- **Tailwind CSS** — styling (via Laravel Breeze)
- **Alpine.js** — small interactive components (toasts, modals, sidebar, line-item forms)
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
git clone git@github.com:<your-username>/retailpay.git
cd retailpay

# 2. Install PHP dependencies
composer install

# 3. Install JS dependencies
npm install

# 4. Create the environment file
cp .env.example .env

# 5. Generate the application key
php artisan key:generate
