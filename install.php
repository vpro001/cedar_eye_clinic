<?php
/**
 * One-time installer: creates the database and tables, adds the starting content
 * (services, shop items, clinic details) and the owner's admin account.
 * After it runs it writes includes/installed.lock and refuses to run again.
 */

require_once __DIR__ . '/includes/config.php';

$lock = __DIR__ . '/includes/installed.lock';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
session_start();
if (empty($_SESSION['install_csrf'])) {
    $_SESSION['install_csrf'] = bin2hex(random_bytes(16));
}

function h($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }

$errors = [];
$done = false;
$values = ['name' => '', 'email' => ''];

/** True when the site is already set up: the lock file, or any admin account in the database. */
function already_installed(string $lock): bool
{
    if (file_exists($lock)) {
        return true;
    }
    try {
        $l = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        $has = $l->query("SHOW TABLES LIKE 'admins'")->num_rows > 0
            && (int) $l->query('SELECT COUNT(*) FROM admins')->fetch_row()[0] > 0;
        $l->close();
    } catch (mysqli_sql_exception $e) {
        return false; // no database yet: a fresh install
    }
    if ($has) {
        @file_put_contents($lock, 'Already installed (admin accounts found) ' . date('c') . "\n");
    }
    return $has;
}

if (already_installed($lock)) {
    $done = true;
    $already = true;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['name']  = trim($_POST['name'] ?? '');
    $values['email'] = trim($_POST['email'] ?? '');
    $pass            = (string) ($_POST['password'] ?? '');

    if (!hash_equals($_SESSION['install_csrf'], (string) ($_POST['csrf'] ?? ''))) $errors['form'] = 'The form expired. Reload the page and try again.';
    if ($values['name'] === '') $errors['name'] = 'Write your name.';
    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Write an email address with an @.';
    if (strlen($pass) < 10) $errors['password'] = 'Use at least 10 characters.';

    if (!$errors) {
        try {
            $link = new mysqli(DB_HOST, DB_USER, DB_PASS, '', DB_PORT);
            $link->set_charset('utf8mb4');
            $link->query('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '', DB_NAME) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            $link->select_db(DB_NAME);
            $link->multi_query(file_get_contents(__DIR__ . '/database.sql'));
            while ($link->more_results() && $link->next_result()) { /* drain */ }

            // clinic details (all editable later in Admin > Settings)
            $hours = json_encode([
                ['label' => 'Mon to Fri', 'days' => 'mon-fri', 'open' => '9:00', 'close' => '17:00'],
                ['label' => 'Sat', 'days' => 'sat', 'open' => '10:00', 'close' => '15:00'],
                ['label' => 'Sun', 'days' => 'sun', 'open' => '', 'close' => ''],
            ]);
            $settings = [
                'phone'      => '0806 709 2463',
                'whatsapp'   => '2348067092463',
                'email'      => 'cedaeyeclinic@gmail.com',
                'building'   => 'HealthBridge Medical Consultants',
                'street'     => 'Sani Abacha Road',
                'district'   => 'GRA Phase 3',
                'city'       => 'Port Harcourt',
                'hours_json' => $hours,
                'notice'     => '',
                'facebook'   => 'https://www.facebook.com/search/top?q=Cedar%20Eye%20Clinic',
            ];
            $st = $link->prepare('INSERT IGNORE INTO settings (k, v) VALUES (?, ?)');
            foreach ($settings as $k => $v) { $st->bind_param('ss', $k, $v); $st->execute(); }

            // services
            if ((int) $link->query('SELECT COUNT(*) FROM services')->fetch_row()[0] === 0) {
                $services = [
                    ['comprehensive-eye-examination', 'Comprehensive eye examination', 'Your sight and the health of each eye, checked every one to two years even when you see well.', "A full check of how well you see far and near, how your two eyes work together, and the health of the front and back of each eye.\n\nWe explain what we find in plain words and tell you when to come back. Most visits to Cedar start here.", 1],
                    ['refraction', 'Computerised and subjective refraction', 'A computerised reading first, then lens-by-lens testing to fix your exact prescription.', "The machine gives a starting reading in seconds. Then you look through one lens after another and tell us which is clearer, until the prescription is right for your eyes and the work you do.", 1],
                    ['spectacle-dispensing', 'Spectacle prescription and dispensing', 'Frames and lenses chosen in our optical shop, fitted and adjusted before you leave.', "You get your prescription in writing and can choose frames and lenses in our shop on the same visit. We measure where your eyes sit in the frame and adjust the fit when the glasses are ready.", 1],
                    ['contact-lenses', 'Contact lens assessment and fitting', 'Whether contact lenses suit your eyes, then fitting and how to wear and clean them. Where available.', "We check the shape and surface of your eyes and your tear film, then fit trial lenses and show you how to put them in, take them out and keep them clean. Ask when you book: this service depends on lens stock.", 0],
                    ['glaucoma-screening', 'Glaucoma screening and monitoring', 'Glaucoma often has no early symptoms. Regular screening finds it early and tracks any change.', "We check the pressure in your eyes and look at the optic nerve. If anything is borderline we repeat the checks over time and refer you to a specialist when treatment is needed.", 1],
                    ['paediatric-eye-examination', 'Paediatric eye examination', 'Child-friendly tests that find sight problems which affect reading, learning and development.', "Children rarely say they cannot see the board. Our tests suit their age and we explain the results to the parent, with glasses or a referral when they are needed.", 1],
                    ['ocular-health-assessment', 'Ocular health assessment', 'A close look at the structures of the eye to find conditions early, with a referral when needed.', "We examine the lids, the front of the eye, the lens and the retina, and refer you to an ophthalmologist or hospital when a condition needs treatment we do not give.", 0],
                    ['dry-eye', 'Dry-eye assessment and management', 'For gritty, burning or tired eyes: we find the cause and give you a plan to manage it.', "Screens, air conditioning and some medicines all dry the eyes. We look at your tear film and lids, find what is causing the discomfort and give you a plan you can follow at home.", 0],
                    ['low-vision', 'Low-vision assessment and referral', 'Support when glasses alone cannot restore your sight, including referral to the right specialist.', "We measure what you can still see, suggest aids and lighting that help with reading and daily tasks, and refer you on when another specialist can do more.", 0],
                    ['hmo-eye-care', 'HMO eye care', 'Eye care under your HMO plan, subject to your cover and our active agreements.', "Enrolled with an HMO? Send us the name of your HMO and plan before your visit and we confirm what is covered, so there are no surprises at the desk.", 1],
                ];
                $st = $link->prepare('INSERT INTO services (slug, name, summary, body, featured, sort) VALUES (?, ?, ?, ?, ?, ?)');
                foreach ($services as $i => $s) {
                    $sort = ($i + 1) * 10;
                    $st->bind_param('ssssii', $s[0], $s[1], $s[2], $s[3], $s[4], $sort);
                    $st->execute();
                }
            }

            // shop items (the clinic's own photographs in img/)
            if ((int) $link->query('SELECT COUNT(*) FROM products')->fetch_row()[0] === 0) {
                $items = [
                    ['Designer optical frames', 'frames', 'frames-wall', 'Cat-eye, square and round frames in acetate and metal, from the display wall in the clinic.', 1],
                    ['Prescription safety glasses with side shields', 'safety', 'safety-1', 'Wraparound safety frames with a padded brow that take prescription lenses.', 1],
                    ['Tortoiseshell sunglasses', 'sunglasses', 'sun-tort', 'Square tortoiseshell sunglasses with brown lenses.', 1],
                    ['Hard-shell glasses cases', 'accessories', 'case-hard', 'Hinged hard cases in pink, cream, brown and silver.', 0],
                    ['Fashion frames', 'frames', 'frames-mix', 'Bold frames in purple, wine, clear and black.', 0],
                    ['Stone-link eyewear chains', 'accessories', 'chain-2', 'Marbled acetate link chains that keep your glasses round your neck.', 1],
                    ['Clear safety glasses', 'safety', 'safety-3', 'Clear wraparound glasses with yellow grips for workshop and site work.', 0],
                    ['Classic black sunglasses', 'sunglasses', 'sun-black', 'Square black sunglasses with green lenses.', 0],
                    ['Classic rectangular frames', 'frames', 'frames-classic', 'Rectangular frames in black and blue for everyday wear.', 1],
                    ['Zip pouch cases', 'accessories', 'case-zip2', 'Soft zip cases with a clip, in printed colours.', 0],
                    ['Sport protective eyewear', 'safety', 'safety-2', 'Rubber-framed sport goggles that take prescription lenses.', 0],
                    ['Frames with blue-light lenses', 'frames', 'frames-blue', 'Everyday frames fitted with lenses that cut blue light from screens.', 0],
                    ['Acetate eyewear chains', 'accessories', 'chain-1', 'Patterned acetate chains in six colourways.', 0],
                    ['Wraparound eye protection with strap', 'safety', 'safety-4', 'Wraparound protection with an elastic strap and tinted lenses.', 0],
                    ['Tortoiseshell sunglasses, wide fit', 'sunglasses', 'sun-tort2', 'Tortoiseshell sunglasses with a wider front.', 0],
                    ['Nylon eyewear cords', 'accessories', 'cord-1', 'Braided cords in twelve colours.', 0],
                    ['Printed zip cases', 'accessories', 'case-print', 'Camouflage-print zip cases with a wrist loop.', 0],
                    ['Clear safety frames', 'safety', 'safety-5', 'Clear safety frames with a tinted brow.', 0],
                    ['Everyday frames', 'frames', 'frames-blue2', 'Plain everyday frames in black, brown and clear.', 0],
                    ['Microfibre lens cloths', 'accessories', 'cloth', 'Grey microfibre cloths for cleaning lenses without scratches.', 0],
                    ['Zip cases with clip', 'accessories', 'case-zip', 'Zip cases in red, black and printed fabric, each with a clip.', 0],
                    ['Black sunglasses, slim', 'sunglasses', 'sun-black2', 'Slim black sunglasses with dark lenses.', 0],
                    ['Padded safety goggles', 'safety', 'safety-6', 'Padded goggles that seal against dust and wind.', 0],
                    ["Children's eyewear cords", 'accessories', 'cord-2', 'Bright cords with animal ends that keep glasses on small heads.', 0],
                    ['Colour hard cases', 'accessories', 'case-clear', 'Translucent hard cases in blue, pink, green and purple.', 0],
                ];
                $st = $link->prepare('INSERT INTO products (name, category, image, description, featured, sort) VALUES (?, ?, ?, ?, ?, ?)');
                foreach ($items as $i => $it) {
                    $img = 'img/' . $it[2] . '.jpg';
                    $sort = ($i + 1) * 10;
                    $st->bind_param('ssssii', $it[0], $it[1], $img, $it[3], $it[4], $sort);
                    $st->execute();
                }
            }

            // owner account
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $st = $link->prepare("INSERT INTO admins (name, email, password_hash, role) VALUES (?, ?, ?, 'owner') ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), role = 'owner', active = 1");
            $st->bind_param('sss', $values['name'], $values['email'], $hash);
            $st->execute();

            file_put_contents($lock, 'Installed ' . date('c') . "\n");
            $done = true;
        } catch (mysqli_sql_exception $e) {
            error_log('[cedar] install: ' . $e->getMessage());
            $errors['form'] = 'Could not set up the database (' . $e->getMessage() . '). Check the database settings in includes/config.local.php.';
        }
    }
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Install: Cedar Eye Clinic</title>
<link rel="stylesheet" href="assets/css/admin.css">
</head>
<body class="bg-[#f4f2ec] text-[#142120] min-h-screen grid place-items-center p-6">
<main class="w-full max-w-md">
  <img src="img/mark.png" alt="Cedar Eye Clinic" class="h-10 mb-8">
  <?php if ($done): ?>
    <h1 class="text-3xl tracking-tight mb-3"><?= isset($already) ? 'Already installed' : 'Installed' ?></h1>
    <p class="mb-6 text-[#56605e]">The database is ready. Sign in to the admin panel with the email and password you chose. For safety, delete install.php from the server.</p>
    <a href="admin/login.php" class="inline-flex h-12 items-center rounded-lg bg-[#2c7469] px-6 text-white hover:bg-[#1f5a51]">Open the admin panel</a>
  <?php else: ?>
    <h1 class="text-3xl tracking-tight mb-2">Set up the clinic website</h1>
    <p class="mb-8 text-[#56605e]">This creates the database <b><?= h(DB_NAME) ?></b>, adds the services and shop items, and makes your owner account.</p>
    <?php if (!empty($errors['form'])): ?><p class="mb-6 rounded-lg bg-red-50 p-4 text-red-800"><?= h($errors['form']) ?></p><?php endif; ?>
    <form method="post" class="space-y-5" novalidate>
      <input type="hidden" name="csrf" value="<?= h($_SESSION['install_csrf']) ?>">
      <?php foreach (['name' => ['Your name', 'text', 'name'], 'email' => ['Email for signing in', 'email', 'email'], 'password' => ['Password (10 characters or more)', 'password', 'new-password']] as $k => [$label, $type, $ac]): ?>
        <div>
          <label for="<?= $k ?>" class="block text-sm font-medium mb-1.5"><?= $label ?></label>
          <input id="<?= $k ?>" name="<?= $k ?>" type="<?= $type ?>" autocomplete="<?= $ac ?>" value="<?= $k === 'password' ? '' : h($values[$k]) ?>" class="h-12 w-full rounded-lg border border-[#bdb7a9] bg-white px-4 text-base focus:border-[#2c7469] focus:outline-none focus:ring-4 focus:ring-[#2c7469]/15">
          <?php if (!empty($errors[$k])): ?><p class="mt-1.5 text-sm text-red-700"><?= h($errors[$k]) ?></p><?php endif; ?>
        </div>
      <?php endforeach; ?>
      <button class="h-12 w-full rounded-lg bg-[#2c7469] text-white font-medium hover:bg-[#1f5a51]">Install the website</button>
    </form>
  <?php endif; ?>
</main>
</body>
</html>
