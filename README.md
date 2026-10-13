# Hypewinds Realtor Portal

A WordPress plugin for [hypewindsproperties.com](https://hypewindsproperties.com).
Referral-based realtor portal: premium-stars, premium-lines, sales and
commissions, built on the site's own user accounts.

Built by Highscore Tech. This repository exists so the plugin survives the
site — see *Why this repository exists* below.

## Installing

Copy the whole folder to `wp-content/plugins/hypewinds-realtor-portal/` and
activate it from **Plugins** in wp-admin. Activation creates the four database
tables and the `hrp_realtor` role; it is safe to re-run, because the tables are
created `IF NOT EXISTS` and `add_role()` does nothing when the role is present.

## What's in it

| File | Does |
| --- | --- |
| `hypewinds-realtor-portal.php` | Plugin header, constants, loads the classes, enqueues the stylesheet, `hrp_portal_url()` |
| `includes/class-hrp-install.php` | Creates the tables and the role on activation |
| `includes/class-hrp-members.php` | Registration, referral capture, ref codes, premium-star lineage |
| `includes/class-hrp-commissions.php` | Sales, commission rates, earnings, notifications, properties |
| `includes/class-hrp-shortcodes.php` | `[hrp_dashboard]`, `[hrp_register]`, `[hrp_premium_lines]` |
| `includes/class-hrp-admin.php` | Admin screens for sales, members and settings |
| `includes/class-hrp-mail.php` | Welcome and commission-notice emails |
| `assets/portal.css` | Portal styling |

Database tables, all prefixed with the site's `wpkj_`:
`hrp_properties`, `hrp_sales`, `hrp_commissions`, `hrp_notifications`.

The shortcode `[hrp_dashboard]` is what the **Realtor Portal** page renders. If
that page ever shows the literal text `[hrp_dashboard]`, the plugin is not
active — the shortcode is unregistered and WordPress prints it verbatim.

## Why this repository exists

On the night of **8–9 October 2026** the live site was sabotaged by someone with
file-level access. Eight separate changes were made in about twenty minutes,
each chosen to be invisible to anyone looking at the site through cPanel:

- `public_html` renamed to `pubIic_htmI` — capital `I` for lowercase `l`, which
  renders identically in most fonts, so the document root appeared to exist and
  did not
- `wp-config.php` and `index.php` each given one stray `/` after `<?php`, which
  stops PHP recognising the opening tag and makes it print the file's source
  instead of running it
- `wp-config.php` database name and user changed to `hypew**l**nd_wp411` —
  lowercase `l` for `i`
- the theme's `index.php` had the `?` in its PHP tags replaced with `‽` (U+203D)
- `wp-login.php` and the JetBackup plugin had a fake "SIMULATED SYSTEM ERROR"
  page injected
- JetBackup was given a `BLANK_MODE` kill switch returning 503 on every public
  page while exempting wp-admin, so the site looked fine to an administrator
- **this plugin's main file was deleted**, leaving `includes/` and `assets/`
  behind so the folder looked complete

The database was never touched and nothing was stolen — there was no backdoor
and no credential theft anywhere in the 6,441 files checked.

The site was restored on **13 October 2026**. This plugin's main file was
recovered from cPanel's trash, where two deleted copies were still sitting.

**If the trash had been emptied, this plugin would have been gone.** That is
the whole reason this repository exists. The site is not a backup of itself.

## One caveat on `hypewinds-realtor-portal.php`

The copy recovered from trash was an older revision. It did not define
`hrp_site_name()` or `hrp_logo_html()`, which the current `includes/` call, so
those two functions were reconstructed from how they are used. They are marked
with a `RECONSTRUCTED` comment in the file.

- `hrp_site_name()` returns the WordPress site title, so emails read
  "Welcome to Hypewinds properties". Switch it to `HRP_GROUP_NAME` if the group
  name was intended.
- `hrp_logo_html()` uses the theme's custom logo and falls back to the site name
  as text, so it cannot render a broken image.

Everything else in this repository is the original code as it runs in
production.
