# Skill Exchange Platform

A responsive PHP + MySQL skill-sharing platform for college projects.

## Features

- User registration and login with secure password hashing
- Skill listings and profile pages
- Search and filtering by skill category and proficiency
- Exchange requests with statuses
- Messaging system for accepted exchanges
- Session scheduling and review system
- Admin dashboard for managing users and categories

## Project structure

- index.php
- register.php
- login.php
- dashboard.php
- explore.php
- add_skill.php
- edit_skill.php
- requests.php
- profile.php
- edit_profile.php
- messages.php
- sessions.php
- reviews.php
- about.php
- contact.php
- database.sql
- config/database.php
- includes/*
- assets/*
- admin/*

## Setup

1. Start Apache and MySQL.
2. Create a database named `skill_exchange` and import `database.sql`.
3. Update database credentials in `config/database.php` if needed.
4. Place the project in your Apache web root, such as `htdocs` or `www`.
5. Open the site in the browser.

## Admin account

A default admin account is included in the database seed:

- Email: admin@skillx.com
- Password: admin123

For production, create a stronger admin password after setup.

## Troubleshooting

- Database connection errors: verify MySQL is running and credentials are correct.
- Missing PHP extensions: ensure `pdo_mysql` is enabled.
- Session problems: confirm cookies are enabled in the browser and `session_start()` is present.
- Blank pages: check Apache error logs and PHP display_errors settings.
