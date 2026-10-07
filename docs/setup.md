# Project Setup

This guide explains how to set up Aviate with Isaac for local development.

## Requirements

Make sure you have:

- Git
- PHP
- Composer
- A code editor such as VS Code
- Access to the GitHub repository
- Access to the Supabase project

## 1. Clone the Repository

```bash
git clone https://github.com/marthijensen/aviate-with-isaac.git
cd aviate-with-isaac
```

## 2. Configure Environment Variables

Copy `.env.example` to `.env` and fill in the Supabase values from the Supabase dashboard (**Project Settings > API Keys**).

```env
SUPABASE_URL=https://your-project.supabase.co
SUPABASE_PUBLISHABLE_KEY=
SUPABASE_SECRET_KEY=
APP_TIMEZONE=America/Denver
```

`APP_TIMEZONE` controls how lesson dates and times are displayed. Use a [PHP time zone name](https://www.php.net/manual/en/timezones.php).

The secret key is only used by PHP on the server. Never commit `.env`.

## 3. Create the Database Tables

Run the SQL files in `supabase/migrations/` in order, using the Supabase **SQL Editor**.

To add sample students and lessons, first make sure there is at least one user in **Authentication > Users**, then run `supabase/seed.sql` in the SQL Editor.

## 4. Run the App

PHP 8.1 or newer with the `curl` extension is required.

```bash
php -S localhost:8000 -t public public/index.php
```

Then open http://localhost:8000. It redirects to the dashboard.

## 5. Routing

Every request goes through `public/index.php`, the front controller. Routes are defined in `src/routes.php`:

| Method | Path | Page |
| --- | --- | --- |
| GET | `/` | Redirects to `/dashboard` |
| GET | `/dashboard` | Dashboard with upcoming lessons |

Unknown paths show a 404 page. Pages are views in `views/`, rendered inside `views/layout.php`.

On Apache, `public/.htaccess` sends requests to the front controller. Point the web server's document root at `public/`.

## 6. Run Tests

```bash
php tests/UpcomingLessonsTest.php
```

```bash
php tests/RouterTest.php
```
