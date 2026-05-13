# Security Policy

## Reporting a vulnerability

Please report suspected vulnerabilities privately through GitHub Security Advisories at https://github.com/anderix/campfire/security/advisories/new. If you would rather not use GitHub, email david.anderson@excelano.com instead. I aim to respond within seven days.

Please do not open public issues for security problems.

## Supported versions

Campfire is built from source and self-hosted by each operator. Security fixes ship through `main`; pull and redeploy to apply them. There are no maintained release branches.

## Hosting model

There is no central Campfire instance. Each troop or operator clones this repository and runs it on their own web server, typically a shared hosting account. Anderix and Excelano do not see your data, your Brevo key, your calendar feed, or your families. Anything you do with Campfire stays inside your hosting account and your own outbound API connections.

## What Campfire can access

Campfire makes two outbound connections:

- **Scoutbook Plus iCal feed** at `api.scouting.org`. Campfire fetches the calendar URL you configure in Settings and reads upcoming events. The feed is read-only and unauthenticated; the URL itself is the access token, so treat it as a secret in the same way you would a calendar share link.
- **Brevo API** at `api.brevo.com`. Campfire uses the API key you paste into Settings to send the newsletter. The key is used only for sending email and only with the "From" address you have verified in Brevo.

Campfire does not call any other network endpoint. There is no telemetry, no analytics, no phone-home, and no remote logging.

## What Campfire stores

Campfire keeps everything in a single SQLite database at `db/campfire.db`, which is placed below the web root and protected by a deny-all `.htaccess` so it cannot be downloaded over HTTP.

The database holds:

- **Admin accounts** — email, display name, and a bcrypt password hash from PHP's `password_hash()`. No plaintext passwords are stored.
- **Families and members** — family names, recipient email addresses, recipient display names, and a per-member unsubscribe token. No last names, no dates of birth, and no BSA member IDs are stored.
- **Scout accounts** — labels (typically a first name) and a numeric balance. No financial account numbers.
- **Settings** — including your Brevo API key, your Scoutbook Plus calendar URL, and your "From" address. These are credentials; treat the database file accordingly when copying or backing up.
- **Email log** — a record of when sends ran, how many recipients each send reached, and any error details from Brevo. No message bodies are stored.

Campfire stores no session tokens beyond the standard PHP session cookie, no remote refresh tokens, and no Scoutbook credentials (the iCal feed does not use authentication).

## What Campfire serves

The web-accessible surface is intentionally small: `index.php`, `events.php`, and the assets under `public/`. The `src/`, `templates/`, `db/`, and `cron.php` paths are blocked by `.htaccess` rules. All form submissions go through a CSRF token check. The public events page at `events.php` shows event titles and times only, never recipient lists or account balances.

## Operator responsibilities

A few things only the operator can do, and Campfire cannot enforce them for you:

- Run Campfire over HTTPS. Admin login and the Brevo API key live in form submissions; without TLS they cross the wire in cleartext.
- Restrict access to the hosting account itself (SFTP, control panel) — anyone with that access can read the SQLite database.
- Verify your "From" address in Brevo before using it. Brevo rejects unverified senders.
- Rotate the Brevo API key if you suspect the database has been exposed.
