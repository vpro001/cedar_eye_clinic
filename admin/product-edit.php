<?php
require_once __DIR__ . '/../includes/functions.php';
$me = require_admin();

$id = (int) get('id');
$p  = $id ? row('SELECT * FROM products WHERE id = ?', 'i', [$id]) : null;
if ($id && !$p) {
    flash('error', 'That item no longer exists.');
    redirect('admin/products.php');
}
$errors = [];

/** Stores an uploaded photograph, re-encoded as JPEG or WebP, and returns its path, or null with $error set. */
function store_upload(array $f, ?string &$error): ?string
{
    if ($f['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($f['error'] !== UPLOAD_ERR_OK) { $error = 'The upload failed. Try a smaller photograph.'; return null; }
    if ($f['size'] > UPLOAD_MAX_BYTES) { $error = 'The photograph is over ' . (UPLOAD_MAX_BYTES / 1048576) . ' MB.'; return null; }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $ext  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
    if (!$ext || !getimagesize($f['tmp_name'])) { $error = 'Upload a JPEG, PNG or WebP photograph.'; return null; }
    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
    $name = date('Ymd') . '-' . bin2hex(random_bytes(8));
    // re-encode with GD when it is there: strips anything hidden in the file and caps the size at 1600px
    if (function_exists('imagecreatefromstring')) {
        $img = @imagecreatefromstring(file_get_contents($f['tmp_name']));
        if ($img) {
            $w = imagesx($img); $h = imagesy($img); $max = 1600;
            if ($w > $max || $h > $max) {
                $r = min($max / $w, $max / $h);
                $dst = imagecreatetruecolor((int) ($w * $r), (int) ($h * $r));
                imagealphablending($dst, false); imagesavealpha($dst, true);
                imagecopyresampled($dst, $img, 0, 0, 0, 0, (int) ($w * $r), (int) ($h * $r), $w, $h);
                $img = $dst;
            }
            $file = $name . '.jpg';
            $bg = imagecreatetruecolor(imagesx($img), imagesy($img));
            imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
            imagecopy($bg, $img, 0, 0, 0, 0, imagesx($img), imagesy($img));
            if (!@imagejpeg($bg, UPLOAD_DIR . '/' . $file, 84)) { $error = 'The photograph could not be saved. Check the uploads folder can be written.'; return null; }
            return 'uploads/products/' . $file;
        }
    }
    $file = $name . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], UPLOAD_DIR . '/' . $file)) { $error = 'The photograph could not be saved. Check the uploads folder can be written.'; return null; }
    return 'uploads/products/' . $file;
}

function remove_upload(?string $path): void
{
    if ($path && str_starts_with($path, 'uploads/products/')) {
        $full = realpath(__DIR__ . '/../' . $path);
        $dir  = realpath(UPLOAD_DIR);
        if ($full && $dir && str_starts_with($full, $dir)) @unlink($full);
    }
}

if (is_post()) {
    admin_post_guard();
    if (post('action') === 'delete' && $p) {
        $used = (int) val('SELECT COUNT(*) FROM enquiries WHERE product_id = ?', 'i', [$p['id']]);
        if ($used) {
            q('UPDATE products SET active = 0 WHERE id = ?', 'i', [$p['id']]);
            flash('ok', '"' . $p['name'] . '" has shop questions linked to it, so it was hidden instead of deleted.');
        } else {
            q('DELETE FROM products WHERE id = ?', 'i', [$p['id']]);
            remove_upload($p['image']);
            flash('ok', '"' . $p['name'] . '" was deleted.');
        }
        redirect('admin/products.php');
    }

    $v = [
        'name'        => clip(post('name'), 140),
        'category'    => post('category'),
        'description' => clip(post('description'), 2000),
        'price'       => str_replace([',', '₦', ' '], '', post('price')),
        'in_stock'    => isset($_POST['in_stock']) ? 1 : 0,
        'featured'    => isset($_POST['featured']) ? 1 : 0,
        'active'      => isset($_POST['active']) ? 1 : 0,
        'sort'        => (int) post('sort', '0'),
    ];
    if ($v['name'] === '') $errors['name'] = 'Write the item\'s name.';
    if (!isset(CATEGORIES[$v['category']])) $errors['category'] = 'Choose a category.';
    if ($v['price'] !== '' && (!is_numeric($v['price']) || (float) $v['price'] < 0)) $errors['price'] = 'Write the price in naira as a number, like 25000, or leave it empty.';
    $upErr = null;
    $newImg = !$errors && isset($_FILES['image']) ? store_upload($_FILES['image'], $upErr) : null;
    if ($upErr) $errors['image'] = $upErr;
    if (!$p && !$newImg && !$errors) $errors['image'] = 'Add a photograph of the item.';

    if (!$errors) {
        $price = $v['price'] === '' ? null : number_format((float) $v['price'], 2, '.', '');
        if ($p) {
            $img = $newImg ?: $p['image'];
            q('UPDATE products SET name = ?, category = ?, description = ?, price = ?, image = ?, in_stock = ?, featured = ?, active = ?, sort = ? WHERE id = ?',
                'sssssiiiii', [$v['name'], $v['category'], $v['description'], $price, $img, $v['in_stock'], $v['featured'], $v['active'], $v['sort'], $p['id']]);
            if ($newImg) remove_upload($p['image']);
            flash('ok', '"' . $v['name'] . '" was saved.');
            redirect('admin/product-edit.php?id=' . $p['id']);
        }
        q('INSERT INTO products (name, category, description, price, image, in_stock, featured, active, sort) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            'sssssiiii', [$v['name'], $v['category'], $v['description'], $price, $newImg, $v['in_stock'], $v['featured'], $v['active'], $v['sort']]);
        flash('ok', '"' . $v['name'] . '" was added to the shop.');
        redirect('admin/product-edit.php?id=' . last_id());
    }
    $p = array_merge($p ?? ['id' => 0, 'image' => ''], $v);
}

$p = $p ?? ['id' => 0, 'name' => '', 'category' => get('cat') ?: 'frames', 'description' => '', 'price' => '', 'image' => '', 'in_stock' => 1, 'featured' => 0, 'active' => 1, 'sort' => 0];
$err = fn($k) => isset($errors[$k]) ? '<p class="mt-1.5 text-sm text-red-700">' . e($errors[$k]) . '</p>' : '';

$admin_title = $p['id'] ? 'Edit ' . $p['name'] : 'Add a shop item';
$admin_nav   = 'products';
require __DIR__ . '/_header.php';
?>
<p class="mb-4"><a href="<?= e(url('admin/products.php')) ?>" class="text-sm text-ink-muted hover:text-ink">← Shop items</a></p>
<header class="mb-8 flex flex-wrap items-end justify-between gap-4">
  <h1 class="text-4xl tracking-tight"><?= $p['id'] ? e($p['name']) : 'Add a shop item' ?></h1>
  <?php if ($p['id'] && $p['active']): ?><a class="btn btn-ghost" href="<?= e(url('product.php?id=' . $p['id'])) ?>" target="_blank" rel="noopener">See it on the website ↗</a><?php endif; ?>
</header>

<form method="post" enctype="multipart/form-data" class="grid xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)] gap-8 items-start" novalidate>
  <?= csrf_field() ?>
  <div class="rounded-md bg-white border border-line p-6 lg:p-8 grid sm:grid-cols-2 gap-5">
    <div class="sm:col-span-2"><label class="lbl" for="name">Name</label><input class="inp" id="name" name="name" value="<?= e($p['name']) ?>" maxlength="140" required><?= $err('name') ?></div>
    <div><label class="lbl" for="category">Category</label><select class="inp" id="category" name="category"><?php foreach (CATEGORIES as $k => $c): ?><option value="<?= e($k) ?>"<?= $p['category'] === $k ? ' selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select><?= $err('category') ?></div>
    <div><label class="lbl" for="price">Price in naira (optional)</label><input class="inp" id="price" name="price" inputmode="decimal" value="<?= e($p['price'] !== null && $p['price'] !== '' ? rtrim(rtrim((string) $p['price'], '0'), '.') : '') ?>" placeholder="Empty shows “Ask for price”"><?= $err('price') ?></div>
    <div class="sm:col-span-2"><label class="lbl" for="description">Description</label><textarea class="inp" id="description" name="description" maxlength="2000"><?= e($p['description']) ?></textarea></div>
    <div><label class="lbl" for="sort">Order in the list</label><input class="inp" id="sort" name="sort" type="number" value="<?= (int) $p['sort'] ?>"><p class="mt-1.5 text-sm text-ink-muted">Lower numbers show first.</p></div>
    <fieldset class="grid gap-3 content-start"><legend class="lbl">On the website</legend>
      <label class="flex items-center gap-3"><input type="checkbox" name="active" class="w-5 h-5 accent-[#2c7469]"<?= $p['active'] ? ' checked' : '' ?>> Show in the shop</label>
      <label class="flex items-center gap-3"><input type="checkbox" name="in_stock" class="w-5 h-5 accent-[#2c7469]"<?= $p['in_stock'] ? ' checked' : '' ?>> In stock</label>
      <label class="flex items-center gap-3"><input type="checkbox" name="featured" class="w-5 h-5 accent-[#2c7469]"<?= $p['featured'] ? ' checked' : '' ?>> Show first in its category</label>
    </fieldset>
  </div>
  <div class="rounded-md bg-white border border-line p-6 lg:p-8 grid gap-5">
    <div class="aspect-[4/3] rounded overflow-hidden bg-paper-2 grid place-items-center">
      <?php if ($p['image']): ?><img id="preview" src="<?= e(img_url($p['image'])) ?>" alt="" class="w-full h-full object-cover"><?php else: ?><img id="preview" alt="" class="w-full h-full object-cover hidden"><span id="noimg" class="text-ink-muted text-sm">No photograph yet</span><?php endif; ?>
    </div>
    <div><label class="lbl" for="image"><?= $p['image'] ? 'Replace the photograph' : 'Photograph' ?></label><input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm file:mr-3 file:btn file:btn-ghost file:h-10 file:border file:border-line file:bg-white file:rounded-lg file:px-4"><p class="mt-1.5 text-sm text-ink-muted">JPEG, PNG or WebP, up to <?= UPLOAD_MAX_BYTES / 1048576 ?> MB. Landscape works best.</p><?= $err('image') ?></div>
    <div class="flex flex-wrap gap-2 justify-between">
      <button class="btn btn-primary" name="action" value="save"><?= $p['id'] ? 'Save changes' : 'Add to the shop' ?></button>
      <?php if ($p['id']): ?><button class="btn btn-danger" name="action" value="delete" data-confirm="Delete this item from the shop?">Delete</button><?php endif; ?>
    </div>
  </div>
</form>
<?php require __DIR__ . '/_footer.php'; ?>
