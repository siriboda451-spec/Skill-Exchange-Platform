# Skill Exchange Platform — Render deployment package

This project uses PHP, Apache, and MySQL. The Dockerfile prepares the PHP web runtime for Render.

## Important: the database must be hosted separately

Render's PHP deployment is done through Docker. This app uses MySQL, so you need a MySQL database that allows connections from your web service. A database available only on your own laptop (`localhost`) will not work once deployed.

## Deploy to Render

1. Upload this project to a GitHub repository. Keep `Dockerfile` and `index.php` at the repository root.
2. Create a MySQL database with a provider that permits remote connections, and import `database.sql` into that database. The SQL script creates/selects a database named `skill_exchange`; if your provider has already created a database for you, remove the first two lines (`CREATE DATABASE...` and `USE skill_exchange;`) before importing, or import the table statements into the database selected in phpMyAdmin.
3. In Render, choose **New + → Web Service**, connect the GitHub repository, and select **Docker** as the runtime.
4. Leave Build Command blank (Dockerfile is used). Leave Docker Command blank unless you know you need to override it.
5. In Render → service → **Environment**, add these variables using your database provider's actual values:
   - `DB_HOST` — database hostname (not `localhost`)
   - `DB_PORT` — database port, usually `3306`
   - `DB_NAME` — database name
   - `DB_USER` — database username
   - `DB_PASSWORD` — database password
6. Deploy. Open the Render URL after deployment finishes.

## Security before public use

- Do not commit passwords, `.env` files, or database credentials to GitHub.
- The SQL seed includes a default admin account (`admin@skillx.com` / `admin123`). Change/remove this account and set a strong unique admin password before sharing the site publicly.
- Use a database provider that supports remote connections and configure its access restrictions where available.

## Local development

For XAMPP, the defaults in `config/database.php` are `127.0.0.1`, database `skill_exchange`, user `root`, and an empty password. Import `database.sql` first.
