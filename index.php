<?php
/*
 * Home: the eye chart and what to do, today's hours and where to find the clinic, then the services,
 * the reason to test, the visit, the shop, staff screening, questions and the booking form.
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/forms.php';

$featured = rows('SELECT * FROM services WHERE active = 1 AND featured = 1 ORDER BY sort, id LIMIT 6');
$total    = (int) val('SELECT COUNT(*) FROM services WHERE active = 1');
$counts   = array_column(rows('SELECT category, COUNT(*) n FROM products WHERE active = 1 GROUP BY category'), 'n', 'category');
$state    = booking_defaults();
$status   = clinic_status();
$words    = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve'];

$page_title       = 'Cedar Eye Clinic: eye tests and glasses in GRA Phase 3, Port Harcourt';
$page_description = 'Optometrists inside ' . setting('building') . ' on ' . setting('street') . ', Port Harcourt. Eye examinations, glaucoma screening, children\'s eye tests, HMO eye care and an optical shop.';
$canonical        = '';
$home             = true;
$wizard           = true;
include __DIR__ . '/includes/header.php';

$chart = [['E', '6/60'], ['F P', '6/36'], ['T O Z', '6/24'], ['L P E D', '6/18'], ['P E C F D', '6/12'], ['E D F C Z P', '6/9'], ['F E L O P Z D', '6/6']];
$tiles = [
  'frames'      => ['img/frames-classic.jpg', 'Rectangular prescription frames in black and blue'],
  'sunglasses'  => ['img/sun-tort.jpg', 'Tortoiseshell sunglasses on a white card'],
  'safety'      => ['img/safety-3.jpg', 'Clear safety glasses with yellow grips'],
  'accessories' => ['img/chain-2.jpg', 'Marbled acetate eyewear chains in blue, amber and white'],
];
?>
<section class="hero" aria-labelledby="hero-h">
  <div class="wrap hero__grid">
    <div class="hero__text">
      <p class="label hero__where rise"><?= e(setting('district')) ?>, <?= e(setting('city')) ?></p>
      <h1 class="display rise rise-2" id="hero-h">Eye tests, glasses and eye&#8209;health checks on <?= e(setting('street')) ?>.</h1>
      <p class="lead rise rise-3">Cedar is an optometry clinic inside <?= e(setting('building')) ?>. We measure your sight, check the health of each eye and fit your glasses, all in one visit, with the results explained in plain words.</p>
      <div class="btn-row rise rise-4">
        <a class="btn btn--primary btn--lg" href="#book"><?= MAIN_ACTION ?></a>
        <a class="btn btn--ghost btn--lg" href="<?= e(wa_link('Hello Cedar Eye Clinic, I would like to book an eye test.')) ?>" rel="noopener" target="_blank">WhatsApp us</a>
      </div>
    </div>
    <div class="hero__chart rise rise-3">
      <figure class="chart" aria-label="An eye chart. The bottom line, 6/6, is normal sight at six metres.">
        <div class="chart__top mono" aria-hidden="true"><span>Cedar Eye Clinic</span><span>6 m</span></div>
        <div class="chart__rows" aria-hidden="true">
          <?php foreach ($chart as [$letters, $acuity]): ?>
            <div class="chart__row"><span class="chart__letters"><?= e($letters) ?></span><span class="chart__acuity"><?= e($acuity) ?></span></div>
          <?php endforeach; ?>
        </div>
        <div class="chart__duo" aria-hidden="true"><span>O Z</span><span>H V</span></div>
      </figure>
      <p class="chart-note">The bottom line is 6/6: normal sight at six metres. If it blurs, it is time for a test.</p>
    </div>
  </div>
</section>

<section class="today" aria-label="Opening hours and address">
  <div class="wrap today__grid">
    <div class="today__cell">
      <p class="today__main"><span class="dot<?= $status['open'] ? ' dot--open' : '' ?>" aria-hidden="true"></span><?= e($status['text']) ?></p>
      <p class="today__sub mono"><?= e(hours_compact()) ?></p>
    </div>
    <div class="today__cell">
      <p class="today__main"><?= address_html() ?></p>
      <p class="today__sub">Inside <?= e(setting('building')) ?>. <a class="link" href="<?= e(map_link()) ?>" rel="noopener" target="_blank">Directions</a></p>
    </div>
    <div class="today__cell">
      <p class="today__main"><a href="<?= e(phone_href()) ?>"><?= e(phone_display()) ?></a></p>
      <p class="today__sub">Call, or <a class="link" href="<?= e(wa_link('Hello Cedar Eye Clinic, ')) ?>" rel="noopener" target="_blank">message us on WhatsApp</a></p>
    </div>
  </div>
</section>

<section class="section" id="services" aria-labelledby="services-h">
  <div class="wrap split">
    <div class="section-head sticky">
      <h2 id="services-h">Eye care for adults, children and HMO members</h2>
      <p><?= e($words[$total] ?? (string) $total) ?> services in one clinic. Most visits start with a full eye examination; everything else follows from what it finds.</p>
      <p><a class="link arrow" href="<?= e(url('services.php')) ?>">All <?= $total ?> services</a></p>
    </div>
    <ol class="index">
      <?php foreach ($featured as $i => $s): ?>
        <li class="index__item">
          <a class="index__link" href="<?= e(url('services.php#' . $s['slug'])) ?>">
            <span class="index__num"><?= sprintf('%02d', $i + 1) ?></span>
            <span class="index__name"><?= e($s['name']) ?></span>
            <span class="index__end"><?= e($s['price_note']) ?></span>
            <span class="index__sum"><?= e($s['summary']) ?></span>
          </a>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>

<section class="section section--ink" aria-labelledby="why-h">
  <div class="wrap statement">
    <h2 class="label" id="why-h">Why test when you see well</h2>
    <p class="statement__text focus-in">Glaucoma takes sight slowly and often without a single early symptom. A full eye test every one to two years finds it while there is still time to act, <em>even when you see well</em>.</p>
    <div class="statement__foot">
      <a class="link arrow" href="<?= e(url('services.php#glaucoma-screening')) ?>">Glaucoma screening and monitoring</a>
    </div>
  </div>
</section>

<section class="section" id="visit" aria-labelledby="visit-h">
  <div class="wrap split split--rev">
    <div class="stack-lg">
      <div class="section-head">
        <h2 id="visit-h">What happens at your visit</h2>
        <p>No referral needed. Bring your current glasses and, if you have one, your HMO card.</p>
      </div>
      <ol class="steps">
        <li class="step"><span class="step__n">1</span>
          <h3>Book, or walk in</h3>
          <p>Send the form below, message us on WhatsApp, or come in during opening hours. Booking first means a shorter wait.</p>
        </li>
        <li class="step"><span class="step__n">2</span>
          <h3>Your eyes are examined</h3>
          <p>We measure your sight and prescription and look at the health of the front and back of each eye, then explain what we found.</p>
        </li>
        <li class="step"><span class="step__n">3</span>
          <h3>Glasses, or a referral</h3>
          <p>Choose frames and lenses in our shop on the same visit. If you need treatment we do not give, we refer you and tell you why.</p>
        </li>
      </ol>
    </div>
    <figure>
      <div class="photo photo--land"><img src="<?= e(url('assets/img/photo-f102557208-960.webp')) ?>" srcset="<?= e(url('assets/img/photo-f102557208-500.webp')) ?> 500w, <?= e(url('assets/img/photo-f102557208-960.webp')) ?> 960w, <?= e(url('assets/img/photo-f102557208.webp')) ?> 1280w" sizes="(max-width: 959px) 100vw, 560px" width="960" height="640" alt="An optometrist looking into a patient's eye with an ophthalmoscope" loading="lazy" decoding="async"></div>
    </figure>
  </div>
</section>

<section class="section section--band" id="shop" aria-labelledby="shop-h">
  <div class="wrap">
    <div class="section-head section-head--row">
      <div>
        <h2 id="shop-h">An optical shop in the same room</h2>
        <p>Frames fitted to your new prescription, sunglasses, safety eyewear and the small things that keep glasses safe. Stock changes often, so ask about any piece.</p>
      </div>
      <a class="link arrow" href="<?= e(url('shop.php')) ?>">See the shop</a>
    </div>
    <div class="tiles">
      <?php foreach (CATEGORIES as $key => $c): ?>
        <a class="tile" href="<?= e(url('shop.php?cat=' . $key)) ?>">
          <div class="photo"><?= pic($tiles[$key][0], $tiles[$key][1], 320, '', '(max-width: 959px) 50vw, 300px') ?></div>
          <span class="tile__name"><span><?= e($c['name']) ?></span><span class="mono"><?= (int) ($counts[$key] ?? 0) ?></span></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section" id="organisations" aria-labelledby="org-h">
  <div class="wrap split">
    <div class="stack-lg">
      <div class="section-head">
        <h2 id="org-h">Eye screening for companies, schools and HMOs</h2>
        <p>We screen staff and pupils on site or at the clinic, teach safe screen and eye habits, and supply prescription safety eyewear for the work they do.</p>
      </div>
      <p><a class="btn btn--ink" href="<?= e(url('corporate.php#request')) ?>">Request a staff screening</a></p>
    </div>
    <figure>
      <div class="photo photo--wide"><?= pic('img/safety-1.jpg', 'Black prescription safety glasses with orange side shields', 640, '', '(max-width: 959px) 100vw, 680px') ?></div>
      <figcaption>Prescription safety glasses, from the clinic's shop.</figcaption>
    </figure>
  </div>
</section>

<section class="section" id="questions" aria-labelledby="questions-h">
  <div class="wrap split">
    <div class="section-head sticky">
      <h2 id="questions-h">Before you come</h2>
      <p>Anything else, ask on WhatsApp and the clinic replies during opening hours.</p>
      <p><a class="link arrow" href="<?= e(wa_link('Hello Cedar Eye Clinic, I have a question.')) ?>" rel="noopener" target="_blank">Ask on WhatsApp</a></p>
    </div>
    <div class="faq">
      <details open>
        <summary>Do you accept HMO plans?</summary>
        <p>Yes, under plans we have an active agreement with, and subject to your cover. Send us the name of your HMO and plan before you come and we confirm what is covered.</p>
      </details>
      <details>
        <summary>How often should I have an eye test?</summary>
        <p>Every one to two years, even when you see well. Come sooner if your sight changes, your eyes hurt, or you see flashes or new floaters.</p>
      </details>
      <details>
        <summary>Can I walk in without booking?</summary>
        <p>Yes, during opening hours. Booking first means a shorter wait.</p>
      </details>
      <details>
        <summary>Do you perform eye surgery?</summary>
        <p>No. We examine, prescribe and fit glasses, and refer you to an eye surgeon or hospital when you need treatment.</p>
      </details>
      <details>
        <summary>Do you fit contact lenses?</summary>
        <p>Where lens stock allows. We first check whether lenses suit your eyes, then fit them and show you how to wear and clean them.</p>
      </details>
      <details>
        <summary>Do you see children?</summary>
        <p>Yes. Children's tests suit their age, and we explain the results to the parent.</p>
      </details>
    </div>
  </div>
</section>

<section class="section section--band" id="book" aria-labelledby="book-h">
  <div class="wrap book-layout">
    <div class="book-layout__head">
      <div class="section-head">
        <h2 id="book-h">Book an eye test</h2>
        <p>Three short steps: what you need, when suits you, and how to reach you. We call you to confirm the exact time.</p>
      </div>
    </div>
    <div class="book-layout__info stack-lg">
      <?= hours_dl() ?>
      <div class="contact-lines">
        <a href="<?= e(phone_href()) ?>"><span>Book by phone</span><span class="mono"><?= e(phone_display()) ?></span></a>
        <a href="<?= e(wa_link('Hello Cedar Eye Clinic, I would like to book an eye test.')) ?>" rel="noopener" target="_blank"><span>Book on WhatsApp</span><span class="mono">&rarr;</span></a>
      </div>
    </div>
    <div class="book-layout__form">
      <div class="slip">
        <div class="slip__head"><span class="rx" aria-hidden="true">Rx</span><span class="label">Booking request</span></div>
        <div class="slip__body"><?= booking_wizard($state) ?></div>
      </div>
    </div>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>