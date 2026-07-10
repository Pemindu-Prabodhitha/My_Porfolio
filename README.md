<<<<<<< HEAD
# Portfolio Website

A personal portfolio site built with HTML, CSS, JavaScript, and PHP. Includes
a Hero, About, experience/education Timeline, filterable Projects grid,
Skills section, and a working Contact form — plus a full admin panel that
can edit nearly every piece of content on the site (text, timeline entries,
skills, and projects) without touching code.

## Folder structure

```
portfolio/
├── index.php              Main page (loads projects from DB, or JSON if DB is off)
├── css/
│   └── style.css          All styling
├── js/
│   └── main.js             Nav toggle, scroll reveal, project filter, form handling
├── php/
│   ├── contact.php         Contact form handler (DB insert + email + file-log backup)
│   ├── config.php          Site settings — email address AND database credentials
│   ├── db.php              PDO database connection helper (with safe fallback)
│   └── admin/              Admin panel — log in to manage every editable part of the site
│       ├── login.php
│       ├── dashboard.php
│       ├── content.php        Edit hero/about/contact/footer text
│       ├── timeline.php       List timeline entries
│       ├── timeline-edit.php  Add/edit a timeline entry
│       ├── timeline-delete.php
│       ├── skills.php         List skills by category
│       ├── skills-edit.php    Add/edit a skill
│       ├── skills-delete.php
│       ├── projects.php
│       ├── project-edit.php
│       ├── project-delete.php
│       ├── messages.php
│       ├── change-password.php
│       ├── logout.php
│       └── auth.php         Session handling + CSRF protection (shared by all admin pages)
├── database/
│   └── schema.sql          Creates the database, all tables, seed data, and default admin login
├── data/
│   ├── projects.json        Fallback project data, used only if the database is off/unreachable
│   └── contact-log.txt      Backup log of contact form submissions (auto-created)
├── assets/
│   ├── images/             Placeholder SVGs — swap in your own photos/screenshots
│   └── resume.pdf          Add your résumé here (referenced by the "Download" button)
└── README.md
```

## Running it locally

This site needs PHP to run (because `index.php` reads `projects.json` and
`contact.php` handles form submissions). You have two options:

**Option A — PHP's built-in server (simplest)**
1. Install PHP if you don't have it: https://www.php.net/manual/en/install.php
2. Open a terminal in the `portfolio/` folder and run:
   ```
   php -S localhost:8000
   ```
3. Open `http://localhost:8000` in your browser.

**Option B — XAMPP / MAMP**
1. Install XAMPP (Windows/Linux) or MAMP (Mac).
2. Copy the `portfolio/` folder into the `htdocs` (XAMPP) or equivalent MAMP folder.
3. Start Apache from the control panel.
4. Visit `http://localhost/portfolio/` in your browser.

## Setting up the database (optional but recommended)

The site works fine without a database — it just reads `data/projects.json`
instead. But storing projects and contact messages in MySQL makes them
easier to manage and query as the site grows.

1. **Create the database and tables.** From a terminal:
   ```
   mysql -u root -p < database/schema.sql
   ```
   (Or import `database/schema.sql` through phpMyAdmin / your host's database
   tool.) This creates a `portfolio` database with tables for site content,
   timeline entries, skills, projects, contact messages, and the admin
   login — seeded with starter data so the site works immediately.

2. **Add your credentials in `php/config.php`:**
   ```php
   define('DB_ENABLED', true);
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'portfolio');
   define('DB_USER', 'root');       // your MySQL username
   define('DB_PASS', '');           // your MySQL password
   ```

3. **That's it.** `index.php` automatically tries the database first, and
   only falls back to `data/projects.json` if `DB_ENABLED` is `false` or the
   connection fails for any reason (e.g. before you've imported the schema).
   You can check which source is active by viewing the page source and
   looking for the HTML comment just above the Projects section.

4. **Managing projects going forward:** once the database is set up, add,
   edit, or reorder projects with SQL (or a database GUI) instead of editing
   `projects.json`:
   ```sql
   INSERT INTO projects (title, description, tags, image, live_url, github_url, sort_order)
   VALUES ('New Project', 'What it does.', 'JavaScript,CSS', 'assets/images/new.jpg',
           'https://example.com', 'https://github.com/you/new-project', 5);
   ```

5. **Contact messages** submitted through the form are now saved to the
   `contact_messages` table (in addition to the `data/contact-log.txt`
   backup). Query them any time:
   ```sql
   SELECT * FROM contact_messages ORDER BY created_at DESC;
   ```

**On shared hosting:** most providers give you database credentials through
a control panel (e.g. cPanel's "MySQL Databases" section) — create a
database there, import `schema.sql` via phpMyAdmin, and use the host,
username, password, and database name it gives you in `config.php`.

## What to customize first

The fastest path is: set up the database (next section), log into the admin
panel, then edit everything through the Site Content / Timeline / Skills /
Projects pages — no code editing required for text content.

If you'd rather edit files directly (e.g. before setting up a database):

1. **`index.php`** — search for the `$siteContentDefaults`, `$timelineDefaults`,
   and `$skillsDefaults` arrays near the top of the file. These are the
   fallback values used when the database isn't connected, so editing them
   updates what visitors see until you set up the database.
2. **`data/projects.json`** — replace the sample projects with your own.
   Each entry needs: `title`, `description`, `tags` (array), `image` path,
   `live` URL (optional, leave as `""` if none), and `github` URL. (Skip this
   if you're using the database instead.)
3. **`assets/images/`** — add your real photos and project screenshots here.
   **Important:** use `.jpg`, `.png`, or `.webp` — `.heic` files (the default
   format for iPhone photos) don't display in most browsers (Chrome, Firefox,
   Edge). If you're on a Mac or iPhone, convert with Preview (File → Export)
   or an online converter before uploading. The site includes a fallback
   that swaps in a placeholder graphic if an image fails to load, so a
   missing/incompatible photo won't break the layout — but you'll still want
   real photos showing.
4. **`assets/`** — add your actual résumé PDF here (the hero section links
   to whatever path is set in `resume_path`).
5. **`php/config.php`** — set `CONTACT_RECEIVER_EMAIL` to your real email
   address.

## About the contact form

- The form submits via JavaScript (`fetch`) to `php/contact.php`, which
  validates the input server-side, checks a hidden honeypot field to catch
  simple bots, tries to send you an email with PHP's `mail()` function, and
  always logs a backup copy to `data/contact-log.txt`.
- **On localhost, `mail()` almost never works** because most local
  environments don't have a mail server configured. That's expected — check
  `data/contact-log.txt` to confirm submissions are being received while
  testing locally.
- **On a real host**, `mail()` may or may not work depending on your
  provider. For reliable delivery, consider using
  [PHPMailer](https://github.com/PHPMailer/PHPMailer) configured with your
  host's SMTP credentials, or a transactional email API (e.g. SendGrid,
  Mailgun) instead of the built-in `mail()` call in `php/contact.php`.

## Admin panel

Once the database is set up (see above), you can manage everything through
a web-based admin panel instead of editing files or writing SQL by hand.

**Log in:** visit `php/admin/login.php` (e.g. `http://localhost:8000/php/admin/login.php`)

**Default login** (created by `database/schema.sql`):
```
Username: admin
Password: ChangeMe123!
```

**⚠️ Change this password immediately after your first login** — go to
"Change Password" in the admin nav bar. Anyone who knows the default
credentials could otherwise log in and edit your site.

**What you can do from the admin panel:**
- **Dashboard** — quick stats: total projects, timeline entries, skills, messages, and unread messages
- **Site Content** — edit almost every piece of text on the site without touching code: browser tab title, nav logo, hero tagline/heading/intro, hero photo and résumé paths, About section bio and quick facts, Contact section intro and links (email/GitHub/LinkedIn/WhatsApp), and the footer name
- **Timeline** — add, edit, delete, and reorder your experience/education entries. Each entry has a title, organization, date range, description, and an icon type (education = graduation cap, work = briefcase) that controls which icon shows on the timeline
- **Skills** — add, edit, delete your skill chips, grouped by category (e.g. Frontend, Backend). Use the same category name on multiple skills to group them together
- **Projects** — add, edit, delete, and reorder your project cards
- **Messages** — view every contact form submission, mark messages as read, and delete old ones
- **Change Password** — update your own admin password

Every one of these pages writes straight to the database that `index.php`
reads from, so changes appear on the live site immediately — no file
editing, re-uploading, or redeploying needed.

**A note on the Hero Heading and Intro fields:** these two fields in Site
Content allow a small set of safe HTML tags — `<br>` for line breaks,
`<span class="accent">word</span>` to highlight a word in amber, and
`<strong>`/`<em>` for bold/italic. Anything else you type gets stripped out
automatically when you save, so you can't accidentally break the page
layout. Every other field on the site is plain text only.

**Security notes:**
- Passwords are hashed with PHP's `password_hash()` — never stored in plain text.
- All forms that change data are protected with CSRF tokens.
- Sessions regenerate their ID on login to prevent session fixation.
- There's currently a single admin account. If you want multiple admin
  users, you can add more rows to `admin_users` directly in the database
  (hash new passwords with `password_hash('yourpassword', PASSWORD_DEFAULT)`
  in a small PHP snippet, then insert the username + hash).
- The admin panel has no rate-limiting on login attempts. For a small
  personal site this is a reasonable trade-off, but if you want extra
  protection, consider adding a login-attempt lockout, or restricting
  `php/admin/` by IP at the web server level.

## Deploying

1. Choose PHP-friendly hosting (most shared hosting supports PHP by
   default — e.g. Hostinger, InfinityFree, or your own VPS).
2. Upload the entire `portfolio/` folder contents to your host (often via
   FTP or a file manager) into the public web root.
3. Make sure `data/` is writable by the web server (needed for the contact
   log file) — usually fine by default, but check folder permissions if
   submissions fail.
4. Test the live contact form specifically, since email delivery behaves
   differently in production than on localhost.
5. Double-check all links (GitHub, LinkedIn, live project demos) and the
   mobile view before sharing the link.

## Browser support notes

- Uses `IntersectionObserver` for scroll-reveal animations, with a fallback
  that simply shows all content if it's unavailable.
- Respects `prefers-reduced-motion` — animations are disabled for users who
  have that system setting turned on.
  >>>>>>> 088799478d410e166b288284ea9808b913b7881f
=======
