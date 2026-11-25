<?php
require_once __DIR__ . '/includes/functions.php';

$page_title       = 'About Cedar Eye Clinic, GRA Phase 3, Port Harcourt';
$page_description = 'An optometry clinic inside ' . setting('building') . ': eye examinations, refraction, eye-health checks and optical dispensing for individuals, families and organisations.';
$canonical        = 'about.php';
$nav              = 'about';
include __DIR__ . '/includes/header.php';
?>
  <section class="page-head" aria-labelledby="page-h">
    <div class="wrap split split--rev">
      <div class="page-head__inner" style="align-self: end">
        <h1 id="page-h" class="rise">An eye clinic inside <?= e(setting('building')) ?></h1>
        <p class="lead rise rise-2">Cedar Eye Clinic is an optometry practice on <?= e(setting('street')) ?>, <?= e(setting('district')) ?>. We examine eyes, measure prescriptions, check eye health and dispense glasses for individuals, families and organisations.</p>
      </div>
      <figure class="rise rise-3">
        <div class="photo photo--portrait"><?= pic('img/frames-tall.jpg', 'The lit wall of optical frames in the clinic', 450, 'fetchpriority="high" loading="eager" style="object-position: 50% 30%"', '(max-width: 959px) 100vw, 450px') ?></div>
        <figcaption>The frame wall in the clinic's shop.</figcaption>
      </figure>
    </div>
  </section>

  <section class="section section--ink" aria-labelledby="work-h">
    <div class="wrap statement">
      <h2 class="label" id="work-h">How we work</h2>
      <p class="statement__text focus-in">We take time to explain your results, look for eye conditions early, and recommend only what fits <em>your eyes and your budget</em>.</p>
    </div>
  </section>

  <section class="section" aria-labelledby="care-h">
    <div class="wrap split">
      <div class="section-head sticky"><h2 id="care-h">Optometrists from the first test to the final fitting</h2></div>
      <div class="prose">
        <p class="lead">Every visit is handled by optometrists, from the first test to the final fitting of your glasses.</p>
        <p>When something needs treatment we do not give, such as surgery, we refer you to an ophthalmologist or hospital and tell you why.</p>
        <p><b>Our vision:</b> to become a trusted provider of eye care, contributing to better sight and eye health in our community.</p>
        <p><b>Our mission:</b> accessible, professional, patient-centred care through clinical standards, ethical practice and patient education.</p>
      </div>
    </div>
  </section>

  <section class="section" aria-labelledby="values-h">
    <div class="wrap split">
      <div class="section-head sticky"><h2 id="values-h">The standards behind every test</h2><p>What you can hold us to, at the desk and in the examination room.</p></div>
      <dl class="values">
        <div><dt>Professional standards</dt><dd>High standards in clinical work and in how we treat you at the desk.</dd></div>
        <div><dt>Open dealing</dt><dd>Clear prices, clear results and no lenses you do not need.</dd></div>
        <div><dt>Respect</dt><dd>Every patient treated with dignity, whatever their age or plan.</dd></div>
        <div><dt>Sound clinical practice</dt><dd>Decisions supported by current optometric guidance.</dd></div>
        <div><dt>Patient safety</dt><dd>The right assessment, care and referral, every time.</dd></div>
        <div><dt>Learning</dt><dd>We keep training and improving the service.</dd></div>
      </dl>
    </div>
  </section>
<?php include __DIR__ . '/includes/footer.php'; ?>
