# Campfire

Personalized troop newsletters powered by your Scoutbook Plus calendar.

Campfire is a lightweight PHP application that sends periodic emails to troop families with upcoming events from your Scoutbook Plus calendar and per-family scout account balances.

## Features

- Pulls upcoming events from your troop's Scoutbook Plus iCal calendar feed
- Sends personalized emails to each family with their scout account balances
- Simple admin interface for managing families, members, and scout accounts
- Scheduled sends (weekly, biweekly, or monthly) via cron, plus a manual "Send Now" button
- Token-based unsubscribe for recipients
- Zero dependencies beyond PHP, SQLite, and curl
- Drop-in deployment to any shared hosting provider

## Requirements

- PHP 8.1 or later with the `sqlite3` and `curl` extensions (standard on most shared hosting)
- A [Brevo](https://www.brevo.com/) account (free tier supports 300 emails/day)
- Your troop's Scoutbook Plus calendar URL

### Finding your calendar URL

Your troop's iCal feed is available at:

```
https://api.scouting.org/advancements/events/calendar/{unit-id}
```

You can find your unit ID in the URL when viewing your troop's calendar on advancements.scouting.org.

## Setup

### 1. Upload to your server

Upload the contents of this repository to a directory on your web server. Campfire is designed to run in a subdirectory (e.g., `https://yoursite.com/campfire/`).

**If you have SSH access**, copy `deploy.conf.example` to `deploy.conf`, fill in your hostname and destination path, and run:

```bash
./deploy.sh
```

**If you don't have SSH access**, upload the files using FTP or your hosting provider's file manager. Upload everything except `deploy.sh`, `deploy.conf.example`, and the `.git` directory. Most hosting control panels (cPanel, Plesk) have a file manager with a zip upload and extract feature, which is the fastest approach.

### 2. Run the installer

Visit the URL where you uploaded Campfire in your browser. You'll see the setup page, which creates the database and your first admin account.

### 3. Configure settings

After setup, go to Settings and configure:

- **Scoutbook Plus Calendar URL** - Your troop's iCal feed URL
- **Timezone** - Your local timezone for displaying event times
- **From Name and Email** - The sender shown on outgoing emails
- **Brevo API Key** - From your Brevo account under SMTP & API > API Keys
- **App URL** - The full URL where Campfire is installed (used for unsubscribe links)
- **Send schedule** - How often and on which day to send

Your "From" email address must be verified in Brevo before you can send. Brevo will send a verification email when you first attempt to use an unverified address.

### 4. Add families and members

Go to Families and start adding your troop families. Each family can have one or more members (email recipients) and one or more scout accounts (with balances managed by the treasurer or any admin).

### 5. Set up scheduled sends

Add a cron job on your server to run `cron.php` daily. It checks whether today matches your configured send day and frequency, and only sends when it's time.

```
0 8 * * * php /path/to/campfire/cron.php
```

On shared hosting without SSH, you can usually set up cron jobs through your hosting control panel (cPanel > Cron Jobs).

You can also use the "Send Now" button on the dashboard at any time.

## Security

Campfire stores minimal data: email addresses, display names, and scout account balances. No last names, dates of birth, or BSA member IDs are stored.

The application includes CSRF protection on all forms, password hashing via `password_hash()`, and `.htaccess` rules that block direct access to application internals. The SQLite database is stored in a protected directory that is not web-accessible.

## License

MIT
