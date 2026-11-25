<?php
require_once __DIR__ . '/../includes/functions.php';
$me = require_admin();

const ENQ_STATUSES = ['new' => 'New', 'read' => 'Read', 'replied' => 'Replied', 'archived' => 'Archived'];

if (is_post()) {
    admin_post_guard();
    $id = (int) post('id');
    $act = post('action');
    if ($id && $act === 'delete') {
        q('DELETE FROM enquiries WHERE id = ?', 'i', [$id]);
        flash('ok', 'The message was deleted.');
    } elseif ($id && isset(ENQ_STATUSES[$act])) {
        q('UPDATE enquiries SET status = ? WHERE id = ?', 'si', [$act, $id]);
        flash('ok', 'Marked as ' . strtolower(ENQ_STATUSES[$act]) . '.');
    }
    header('Location: ' . ($_SERVER['REQUEST_URI'] ?? url('admin/enquiries.php')), true, 303);
    exit;
}

$type   = get('type');
$status = get('status');
$open   = (int) get('open');
$where = []; $types = ''; $args = [];
if (isset(ENQUIRY_TYPES[$type])) { $where[] = 'e.type = ?'; $types .= 's'; $args[] = $type; }
if (isset(ENQ_STATUSES[$status])) { $where[] = 'e.status = ?'; $types .= 's'; $args[] = $status; }
elseif ($status === '') { $where[] = "e.status <> 'archived'"; }
$sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$list = rows("SELECT e.*, p.name AS product_name FROM enquiries e LEFT JOIN products p ON p.id = e.product_id $sqlWhere ORDER BY (e.status = 'new') DESC, e.created_at DESC LIMIT 200", $types, $args);

// opening a new message marks it read
if ($open) {
    q("UPDATE enquiries SET status = 'read' WHERE id = ? AND status = 'new'", 'i', [$open]);
}

function eqs(array $change): string
{
    $q = array_filter(array_merge($_GET, ['open' => ''], $change), fn($v) => $v !== '');
    return url('admin/enquiries.php' . ($q ? '?' . http_build_query($q) : ''));
}

$admin_title = 'Messages';
$admin_nav   = 'enquiries';
require __DIR__ . '/_header.php';
?>
<header class="mb-8"><h1 class="text-4xl tracking-tight">Messages</h1><p class="text-ink-muted mt-1">From the contact page, shop questions and staff screening requests.</p></header>

<div class="flex flex-wrap gap-6 mb-6">
  <nav class="flex flex-wrap gap-2" aria-label="Type">
    <a href="<?= e(eqs(['type' => ''])) ?>" class="btn h-9 <?= $type === '' ? 'bg-ink text-white' : 'btn-ghost' ?>">All kinds</a>
    <?php foreach (ENQUIRY_TYPES as $k => $l): ?><a href="<?= e(eqs(['type' => $k])) ?>" class="btn h-9 <?= $type === $k ? 'bg-ink text-white' : 'btn-ghost' ?>"><?= e($l) ?></a><?php endforeach; ?>
  </nav>
  <nav class="flex flex-wrap gap-2" aria-label="Status">
    <a href="<?= e(eqs(['status' => ''])) ?>" class="btn h-9 <?= $status === '' ? 'bg-ink text-white' : 'btn-ghost' ?>">Open</a>
    <?php foreach (ENQ_STATUSES as $k => $l): ?><a href="<?= e(eqs(['status' => $k])) ?>" class="btn h-9 <?= $status === $k ? 'bg-ink text-white' : 'btn-ghost' ?>"><?= e($l) ?></a><?php endforeach; ?>
  </nav>
</div>

<?php if (!$list): ?>
  <div class="rounded-md bg-white border border-line p-8 text-ink-muted">No messages here.</div>
<?php else: ?>
<div class="grid gap-3">
  <?php foreach ($list as $m): $isOpen = $open === (int) $m['id']; $wa = preg_replace('/\D/', '', $m['phone']); if (str_starts_with($wa, '0')) $wa = '234' . substr($wa, 1); ?>
  <details id="m<?= (int) $m['id'] ?>" class="group rounded-md bg-white border <?= $m['status'] === 'new' ? 'border-amber-300' : 'border-line' ?>"<?= $isOpen ? ' open' : '' ?>>
    <summary class="flex items-center gap-4 px-6 py-4 cursor-pointer list-none [&::-webkit-details-marker]:hidden">
      <span class="pill s-<?= e($m['status']) ?>"><?= e(ENQUIRY_TYPES[$m['type']]) ?></span>
      <span class="min-w-0 flex-1"><span class="block font-medium truncate"><?= e($m['organisation'] ? $m['organisation'] . ' · ' . $m['name'] : $m['name']) ?></span><span class="block text-sm text-ink-muted truncate"><?= e($m['product_name'] ? $m['product_name'] . ': ' : '') ?><?= e($m['message'] ?: '(no message)') ?></span></span>
      <span class="text-sm text-ink-muted flex-none"><?= e(time_ago($m['created_at'])) ?></span>
      <svg class="w-4 h-4 flex-none transition group-open:rotate-180" viewBox="0 0 12 12" aria-hidden="true"><path d="M3 4.5l3 3 3-3" fill="none" stroke="currentColor" stroke-width="1.5"/></svg>
    </summary>
    <div class="px-6 pb-6 grid lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)] gap-6 border-t border-line pt-5">
      <div>
        <p class="whitespace-pre-line leading-relaxed"><?= e($m['message'] ?: '(no message)') ?></p>
        <?php if ($m['product_id']): ?><p class="mt-4 text-sm"><a class="text-sea hover:underline" href="<?= e(url('product.php?id=' . $m['product_id'])) ?>" target="_blank" rel="noopener">See the item on the website ↗</a></p><?php endif; ?>
      </div>
      <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 text-sm content-start">
        <dt class="text-ink-muted">Phone</dt><dd><a class="hover:text-sea" href="tel:<?= e(preg_replace('/[^0-9+]/', '', $m['phone'])) ?>"><?= e($m['phone']) ?></a></dd>
        <?php if ($m['email']): ?><dt class="text-ink-muted">Email</dt><dd class="break-all"><a class="hover:text-sea" href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a></dd><?php endif; ?>
        <?php if ($m['staff_count']): ?><dt class="text-ink-muted">People</dt><dd><?= e($m['staff_count']) ?></dd><?php endif; ?>
        <dt class="text-ink-muted">Received</dt><dd><?= e(date('j M Y, H:i', strtotime($m['created_at']))) ?></dd>
      </dl>
      <form method="post" class="lg:col-span-2 flex flex-wrap gap-2">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
        <a class="btn btn-primary" href="https://wa.me/<?= e($wa) ?>?text=<?= e(rawurlencode('Hello ' . strtok($m['name'], ' ') . ', this is Cedar Eye Clinic replying to your message.')) ?>" target="_blank" rel="noopener">Reply on WhatsApp</a>
        <?php if ($m['status'] !== 'replied'): ?><button class="btn btn-ghost" name="action" value="replied">Mark replied</button><?php endif; ?>
        <?php if ($m['status'] !== 'archived'): ?><button class="btn btn-ghost" name="action" value="archived">Archive</button><?php else: ?><button class="btn btn-ghost" name="action" value="read">Restore</button><?php endif; ?>
        <button class="btn btn-danger ml-auto" name="action" value="delete" data-confirm="Delete this message for good?">Delete</button>
      </form>
    </div>
  </details>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<?php require __DIR__ . '/_footer.php'; ?>
