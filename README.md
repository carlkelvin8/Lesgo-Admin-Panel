# LesGo Admin Panel

Operations and management dashboard for LesGo Courier Service, built with Laravel 13, Tailwind CSS, and MySQL.

## Features

- Secure administrator login with rate limiting, remember-me, and password recovery
- Dashboard metrics for users, drivers, partners, orders, revenue, and support
- User, driver, partner, service, and partner-menu management
- Order tracking and status management
- Payments, wallets, document verification, and notifications
- Support tickets, FAQ knowledge base, ratings, and review moderation
- Analytics, daily reports, revenue reports, and CSV export
- Security-event monitoring and audit logs
- Role-based access for Super Admin, Operations, Finance, and Support staff
- Administrator profile, password rotation, and active-session management
- Queued in-app, email, Firebase HTTP v1 push, and SMS-webhook delivery tracking
- Wallet adjustments, top-up review, refund records, and payment reconciliation
- Responsive LesGo purple-and-white interface

## Requirements

- PHP 8.3 or newer with `pdo_mysql`
- Composer
- Node.js and npm
- MySQL 8.0 or newer

## Installation

1. Clone the repository and enter the project directory.

   ```bash
   git clone https://github.com/carlkelvin8/Lesgo-Admin-Panel.git
   cd Lesgo-Admin-Panel
   ```

2. Install dependencies and create the environment file.

   ```bash
   composer install
   npm install
   cp .env.example .env
   php artisan key:generate
   ```

3. Create the MySQL database.

   ```sql
   CREATE DATABASE lesgo_admin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

4. Update the credentials in `.env` if your MySQL setup differs from the defaults.

   ```dotenv
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=lesgo_admin
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. Build and initialize the application.

   ```bash
   php artisan migrate --seed
   npm run build
   php artisan serve
   ```

Open `http://127.0.0.1:8000/admin/login`.

## Development Admin Account

- Email: `admin@lesgo.com`
- Password: `password`

Change this password immediately outside local development.

In production, the seeder will only create/update the administrator when
`ADMIN_PASSWORD` is explicitly configured. Existing administrator accounts are
automatically assigned the backward-compatible `super_admin` access level.

## Testing

The automated tests use an isolated SQLite in-memory database; the application itself defaults to MySQL.

```bash
php artisan test
```

## Password Reset Email

The local environment defaults to the `log` mailer. Reset links are written to `storage/logs/laravel.log`. Configure a production mail provider in `.env` before deployment.

## Production Operations

Run migrations during deployment and configure a persistent queue worker:

```bash
php artisan migrate --force
php artisan db:seed --class=SecuritySettingsSeeder --force
php artisan queue:work --queue=notifications,default --tries=3 --timeout=60
```

Email notifications use the configured Laravel mailer. Firebase push uses the
FCM HTTP v1 API and accepts either `FIREBASE_CREDENTIALS` (service-account JSON
file path) or `FIREBASE_CREDENTIALS_BASE64` plus `FIREBASE_PROJECT_ID`. SMS uses
the provider-neutral `SMS_WEBHOOK_URL` and optional `SMS_WEBHOOK_TOKEN`.

The August 19 operations migration is additive: it does not remove or rename
existing mobile columns, and partial refunds remain `paid` in the existing
payment status until the full amount has been refunded.
