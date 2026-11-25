<?php
require_once __DIR__ . '/includes/functions.php';

$page_title       = 'Privacy at Cedar Eye Clinic';
$page_description = 'What the Cedar Eye Clinic website collects when you book or send a message, and how the clinic uses it.';
$canonical        = 'privacy.php';
include __DIR__ . '/includes/header.php';
?>
  <section class="page-head" aria-labelledby="page-h">
    <div class="wrap">
      <div class="prose">
        <h1 id="page-h">Privacy</h1>
        <p class="lead">This site collects only what you type into its forms, and the clinic uses it only to answer you.</p>
        <h2>What we keep</h2>
        <ul>
          <li>Booking requests: your name, phone number, the service, the day and time of day you chose, and any note you add.</li>
          <li>Messages and shop questions: your name, phone number, email if you give one, and your message.</li>
          <li>Staff screening requests: the same, plus your organisation and how many people to screen.</li>
          <li>The internet address your request came from, to stop automated spam.</li>
          <li>Patient accounts: your name, phone number and email address, and a one-way hash of the password you chose (never the password itself). You can make one in <a class="link" href="<?= e(url('register.php')) ?>">Register</a> so your bookings are kept with you.</li>
        </ul>
        <h2>Who sees it</h2>
        <p>Only clinic staff signed in to the clinic's admin panel. A patient account shows you its own bookings; nobody else can see them, and we do not sell or share any of it, nor use it for advertising.</p>
        <h2>How long we keep it</h2>
        <p>Booking requests and messages are kept for the clinic's records and can be deleted on request. The same applies to account data while the account exists, and it is removed when you close it or ask us to.</p>
        <h2>Cookies</h2>
        <p>The site sets a session cookie that keeps you signed in and a form working while you fill it in. A patient account also uses it to keep you signed in. It sets no tracking or advertising cookies, and loads nothing from other companies' servers.</p>
        <h2>Ask or delete</h2>
        <p>Call <?= e(phone_display()) ?> or send a WhatsApp message to see or delete what we hold about you.</p>
      </div>
    </div>
  </section>
<?php include __DIR__ . '/includes/footer.php'; ?>
