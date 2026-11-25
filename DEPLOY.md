# Putting the Cedar Eye Clinic site live

Plain PHP 8.1+ and MySQL 5.7+ / MariaDB 10.3+ on Apache with `mod_rewrite` (any cPanel host works).
No build step is needed to deploy: everything the site serves is already in this folder.

## 1. Upload

Upload the whole folder to the web root (or a sub-folder; nothing assumes a path).
Do not upload `tools/` if you like; it is only needed to rebuild the admin CSS and is blocked from the web anyway.

## 2. Database and settings

1. Create a database and a database user with a long random password (cPanel > MySQL Databases).
2. Copy `includes/config.local.example.php` to `includes/config.local.php` and fill in:
   the database details, `SITE_URL` (for example `https://cedareyeclinic.com`), `MAIL_FROM`
   (an address on the site's own domain) and, once the SSL certificate is on, `FORCE_HTTPS => true`.
   If the site sits behind Cloudflare, also set `TRUST_PROXY => true`.
3. Open `https://your-domain/install.php`, create the owner account, then **delete `install.php`**.
   (It refuses to run again once any admin account exists, but there is no reason to leave it.)

## 3. Folder permissions

The web server must be able to write to `uploads/products/` and `uploads/thumbs/`
(product photographs and the resized copies the site makes). On cPanel that is usually `755`;
on a server where Apache runs as another user, `775` with the web server's group.
Nothing in `uploads/` can run as code.

## 4. Email alerts for new bookings

Every booking and message is emailed to the address in Admin > Clinic details >
"Send new bookings and messages to" (or the clinic email if that is empty). This uses PHP `mail()`.
Send a test booking after going live; if no email arrives, ask the host to enable mail for PHP
or to add SPF for the `MAIL_FROM` domain. Bookings are always saved in the admin panel either way.

## 6. Patient accounts

Patients can create an account (`register.php`), sign in (`login.php`), and see the
bookings they have (`account.php`). Forgot the password? `forgot.php` sends a reset
link by email (`reset.php`). Accounts and their bookings live in the `patients` and
`appointments` tables (`appointments.patient_id`); an older database is brought up to
date automatically on the first page load (`includes/migrate.php`, `SCHEMA_VERSION`),
so nothing needs to be done on an existing site.

## 7. Testing the booking wizard

Bookings are served by the 3-step wizard on `book.php`, which reads live availability
from `api/slots.php` and stores a booking with `api/book.php`. Both are plain GET/POST
endpoints: without scripts, `book.php` is one form that posts to itself and lands on
the same confirmation.

## 8. After going live

- Submit `https://your-domain/sitemap.xml` in Google Search Console, and claim the clinic's
  Google Business Profile with the same address and phone as the site.
- Take a nightly database backup (cPanel > Backups, or a cron job running `mysqldump`).
- Remove the test data left from development: the shop item "Test round frames" and the test
  bookings and messages (Admin > Shop items, Appointments, Messages).

## Changing the admin panel's look

The admin uses a pre-built stylesheet, `assets/css/admin.css`. After changing class names in
`admin/*.php`, rebuild it from this folder:

    npx tailwindcss@3 -c tools/tailwind.config.js -i tools/admin.src.css -o assets/css/admin.css --minify

Then raise `ASSET_VERSION` in `includes/config.php` so browsers fetch the new files.
The public site's styles are hand-written in `assets/css/site.css`; no build is needed.
