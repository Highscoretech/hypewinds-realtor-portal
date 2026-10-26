# Hypewinds Realtor Portal

The realtor portal for hypewindsproperties.com: referral sign-up, premium-stars
and premium-lines (three generations), sales and the commission they pay, and
an admin area. Plain PHP and MySQL, no framework and no WordPress: it runs from
its own folder, `public_html/realtor-portal/`, because the files are there,
not because anything has to switch it on.

It replaces the old `hypewinds-realtor-portal` WordPress plugin. It started
fresh on 26 Oct 2026: accounts from the plugin were not carried over.

Needs PHP 8.1+ with PDO MySQL, and MySQL 5.7+ / MariaDB 10.3+.

## Layout

| Path | What |
| --- | --- |
| `index.php` | Router: every page and form handler |
| `.htaccess` | Sends requests to `index.php`; refuses `src/`, `config.php`, `schema.sql` |
| `config.sample.php` | Copy to `config.php` (never committed) |
| `schema.sql` | Tables, all prefixed `portal_`. Safe to re-run |
| `src/bootstrap.php` | Config, database, session, CSRF, helpers |
| `src/auth.php` | Sign in/out, login throttling, password reset |
| `src/members.php` | Registration, referral codes (CID), generations |
| `src/commissions.php` | Sales, commissions, notifications, properties |
| `src/mail.php` | Welcome, commission and reset emails |
| `src/layout.php`, `src/views/` | Pages |
| `assets/` | Stylesheet, script, logo |

## Running it locally

```sh
cp config.sample.php config.php   # point it at a local MySQL database
php -S localhost:8099 index.php
```

Then open `http://localhost:8099/setup?key=<setup_key>` once to create the
tables and the admin account.

## Deploying to cPanel

1. **Database.** cPanel → MySQL Databases: create a database and a user, and
   give the user ALL PRIVILEGES on it.
2. **Files.** Upload everything except `.git` to
   `public_html/realtor-portal/`. A real folder there takes precedence over
   WordPress's page of the same name, so the URL stays the same.
3. **Config.** Copy `config.sample.php` to `config.php` on the server and fill
   in the database details and a long random `setup_key`.
4. **Setup.** Open `https://hypewindsproperties.com/realtor-portal/setup?key=<setup_key>`
   and create the admin account. Setup closes itself once an admin exists.
5. **Settings.** Sign in as admin, set the commission rate and the WhatsApp
   link, and add the properties realtors should see.
6. **Check mail.** Register a test realtor with a real inbox and confirm the
   welcome email arrives. `mail()` can report success without the mail ever
   being delivered, so only a received email proves it works.

## How it behaves

- **Registration**: the realtor never picks a password. One is generated and
  emailed with their CID and referral link. If the email cannot be sent, the
  details are shown once on screen instead, so nobody is locked out.
- **New premium-line**: when someone registers with a referral link, the
  referrer gets an email (name, email, phone, CID) and a portal notification.
- **Referral link**: `/realtor-portal/register?ref=<CID>`. Whoever registers
  with it becomes that member's premium-line, permanently: nothing in the
  portal can change someone's premium-star after sign-up.
- **Sales**: the admin records a sale against the realtor who made it. The
  realtor's premium-star earns the commission rate in force at that moment,
  and the rate is stored with the commission, so later rate changes never
  rewrite past earnings.
- **Security**: passwords hashed with `password_hash`; CSRF tokens on every
  form; sign-in locked for 15 minutes after 5 failures per email or 20 per IP;
  reset links single-use, valid for an hour, stored only as hashes; email links
  built from `base_url`, never from the request's Host header.
