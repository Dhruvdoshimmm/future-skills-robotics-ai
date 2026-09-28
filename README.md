# Future Skills – Robotics & AI

Production-oriented split-stack implementation for the Future Skills – Robotics & AI school program.

## Stack

- Frontend: HTML5, CSS3, JavaScript, AngularJS 1.8.3, Bootstrap 5
- Frontend hosting: Vercel/static hosting
- Backend: PHP 8.1+ with PDO
- Database: MySQL 8 / MariaDB compatible schema
- SPA routing: AngularJS hash routing (`#!/...`) so the frontend needs no server-side rewrites

The initial content is seeded from the supplied Future Skills – Robotics & AI brochure, including the 10-month / 8-session structure, Grades 1–12 progression, zero-investment model, ₹200 student/month model, project examples, and Future Ready Skills messaging.

## Project structure

```text
future-skills-robotics-ai/
├── index.html
├── api.php
├── database.sql
├── config.example.env
├── vercel.json
├── README.md
├── assets/
│   ├── app.js
│   └── styles.css
└── views/
    ├── home.html
    ├── scope.html
    ├── skills.html
    ├── faq.html
    ├── contact.html
    └── admin.html
```

## 1. Create the MySQL database

Import `database.sql` into your MySQL/MariaDB server.

Example:

```bash
mysql -u root -p < database.sql
```

Create a dedicated database user with only the privileges required by this application. Do not use the MySQL root account from PHP.

## 2. Configure the PHP backend

The backend reads secrets from environment variables.

Required:

- `DB_HOST`
- `DB_NAME`
- `DB_USER`
- `DB_PASS`
- `ADMIN_EMAIL`
- `ADMIN_PASSWORD_HASH`
- `JWT_SECRET`
- `ALLOWED_ORIGINS`

Generate a password hash:

```bash
php -r "echo password_hash('YOUR_STRONG_ADMIN_PASSWORD', PASSWORD_DEFAULT), PHP_EOL;"
```

Generate a strong JWT secret, for example:

```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Set `ALLOWED_ORIGINS` to the exact Vercel origin, e.g.:

```text
https://your-project.vercel.app
```

If you later attach a custom domain, add that origin as well, separated by commas.

### Important

Do not put database credentials, `ADMIN_PASSWORD_HASH`, or `JWT_SECRET` in the frontend or commit them to Git.

## 3. Deploy the PHP API

Upload `api.php` to a PHP 8.1+ HTTPS host.

The public endpoint will look like:

```text
https://api.example.com/api.php
```

Confirm the server supports:

- PHP 8.1+
- PDO + `pdo_mysql`
- environment variables
- HTTPS
- `Authorization` headers
- CORS response headers

If your provider does not expose environment variables directly, use a server-side configuration mechanism supported by that provider. Avoid placing secrets in a publicly accessible `.env` file.

## 4. Point the frontend to the API

Open:

```text
assets/app.js
```

Change:

```javascript
app.constant("API_BASE", "https://YOUR-PHP-HOST.example.com/api.php");
```

to your actual API endpoint.

No database credentials or admin secrets belong in this file.

## 5. Deploy the frontend to Vercel

From the project directory:

```bash
npm install -g vercel
vercel
```

Or import the Git repository into Vercel.

This project is static, so there is no Node backend and no build step required.

The `vercel.json` file adds basic browser security headers.

## 6. Test the public flow

Test:

1. Home (`#!/`)
2. Scope (`#!/scope`)
3. Future Ready Skills (`#!/skills`)
4. FAQ (`#!/faq`)
5. Contact (`#!/contact`)

Submit a contact request and verify it appears in MySQL.

## 7. Test the admin flow

Open:

```text
https://YOUR-VERCEL-DOMAIN/#/admin
```

Sign in with the configured `ADMIN_EMAIL` and password.

Verify:

- Home, Scope and Skills content loads
- edited fields can be saved
- incoming inquiries appear
- Pending status is shown
- inline replies change status to Replied
- replies are stored in MySQL

The frontend stores only the short-lived bearer token in `sessionStorage`. The database password and password hash never reach the browser.

## API actions

The controller uses:

```text
GET  api.php?action=content
PUT  api.php?action=content
POST api.php?action=admin_login
POST api.php?action=contact
GET  api.php?action=messages
POST api.php?action=reply
```

The public content and contact endpoints do not require an admin token. Content updates, message retrieval and replies require the bearer token.

## Production hardening checklist

Before going live:

- Use HTTPS for both Vercel and the API host.
- Replace all placeholder environment variables.
- Use a unique strong admin password.
- Use a randomly generated JWT secret of at least 32 bytes.
- Restrict `ALLOWED_ORIGINS` to your actual frontend origin(s).
- Use a dedicated MySQL user.
- Enable automated database backups.
- Keep PHP, MySQL/MariaDB and the hosting OS patched.
- Put the API behind your host's HTTPS/WAF/rate-limiting controls if available.
- Consider adding login rate limiting at the web-server/WAF layer.
- Monitor PHP error logs; never display raw database exceptions to visitors.
- Consider a transactional email provider if replies should be emailed automatically. The current requirement is to save replies to the database; it does not claim email delivery.
- Review and replace CDN dependencies with pinned/self-hosted assets if your organization's security policy requires it.

## Source basis

Initial program content follows the supplied brochure's wording and structure. In particular, the brochure states:

- Future Skills – Robotics & AI and the “Empowering Young Minds for Tomorrow's World” positioning.
- Grades 1–12.
- Cognitive foundations, creativity/innovation, problem-solving through design, and the transition from technology users to creators.
- Zero investment / ₹0 setup cost and no hardware purchase.
- 10-month program, 8 sessions/month, 40+ projects.
- Arduino Nano & Uno, robotics/electronics, sensors/automation, IoT & AI.
- Four learning levels for Grades 1–3, 4–6, 7–9 and 10–12.
- The eight-session methodology.
- Foundation, intermediate, advanced IoT/automation and AI/capstone projects.
- ₹200 per student/month.
- Contact details shown in the brochure.

The application deliberately does not invent claims beyond those materials; operational/security behavior is implementation design.
