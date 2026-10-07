# SkillLink: C2C marketplace for local services

SkillLink is a customer-to-customer marketplace where people offer skilled local services (think tutoring, repairs, design) and other users can find and order them. I built it for the Web Development and e-Commerce module at Eduvos (final mark: 89%) and ran it live on free PHP hosting during the assessment.

## What it does

**For users**
- Register and log in (passwords stored with `password_hash`, checked with `password_verify`).
- Create a seller profile with an optional profile photo and billing details.
- Post service listings with an image. Uploads are checked, then centre-cropped to 800×600 so every card on the market looks the same.
- Browse the market, search by listing or seller name, and sort results.
- Edit your own listings (ownership is checked on the server, not just hidden in the UI).
- View your orders on a status timeline and cancel them.
- Send a message through the contact page.

**For admins**
- A dashboard with live stats pulled from the database: users, active listings, disputes and payouts.
- A listing approval queue. New listings start as `pending_review` and only appear on the market once an admin approves them. Rejected listings are archived.
- The same navigation as users, with an admin badge, so admins can check any page without being redirected away.

## How it's built

- **Backend:** plain PHP with PDO. Every query that takes user input uses prepared statements.
- **Database:** MySQL, 14 tables: users, profiles, billing info, categories, listings, orders, reviews, contact messages, moderation flags, disputes, verifications, account actions, payout batches and payouts.
- **Frontend:** hand-written HTML and CSS, one stylesheet per page area, with a shared hamburger nav. JavaScript is only used for small touches.
- **Security basics:** hashed passwords, session-based login with role checks (`requireLogin`, `requireAdminLogin`), output escaped with `htmlspecialchars`, and upload type and size checks.

The folder has two sets of pages:

| Files | Purpose |
|---|---|
| `*.php` | The real app, connected to MySQL |
| `*.html` | Static design mock-ups of the same pages, used to design the UI before wiring up PHP (open with VS Code Live Server) |

## Design diagrams

Part of the assignment was modelling the system before building it. The images are high resolution, so open them to zoom in.

| Diagram | |
|---|---|
| Context diagram | ![Context diagram](docs/diagrams/Context%20Diagram.png) |
| Use case diagram | ![Use case diagram](docs/diagrams/ITECA%20Assignment%20Use%20Case.png) |
| Data flow diagrams | ![Data flow diagrams](docs/diagrams/DFD%20Diagrams.png) |
| CRC cards | ![CRC diagram](docs/diagrams/CRC%20Diagram.png) |
| Enhanced ERD | ![EERD](docs/diagrams/EERD%20Diagram.png) |
| SQL database schema | ![SQL schema](docs/diagrams/SQL%20Database%20Schema.jpg) |

## Run it locally (XAMPP)

1. Install [XAMPP](https://www.apachefriends.org/) and start **Apache** and **MySQL**.
2. Copy this folder into `C:\xampp\htdocs\skilllink`.
3. In phpMyAdmin, create a database called `skilllink`, then import `database.sql`.
4. Optional: run `seed_demo_data.sql` for 5 demo users with listings and orders. Demo accounts use the password `password`.
5. Open `http://localhost/skilllink/index.php`.

On localhost the app connects as `root` with no password. For a live server, replace the `PUT_YOUR_..._HERE` placeholders in `db.php` with your database details, or set `DB_HOST`, `DB_NAME`, `DB_USER` and `DB_PASS` as environment variables.

To make yourself an admin, register normally and then run:

```sql
UPDATE users SET role = 'admin' WHERE email = 'you@example.com';
```

## What I'd do next

- Add a "place order" button on market listings (orders currently come from seed data).
- Let admins resolve disputes and approve verifications from the dashboard, not just view them.
- Add CSRF tokens to forms and regenerate the session ID on login.
- Build a reviews UI on top of the existing `reviews` table.

## More of my work

- [RandAhead](https://github.com/codePexx-ZA/randahead): budget forecasting web app for South African SMEs (C#, Python, PostgreSQL, HTML/CSS/JS)
- [Starting-Projects](https://github.com/codePexx-ZA/Starting-Projects): smaller C# practice projects

**Liam Dalgleish** · Software engineering student, Eduvos Pretoria · [LinkedIn](https://www.linkedin.com/in/liam-dalgleish-53964626a/)
