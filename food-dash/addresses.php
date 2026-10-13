<?php
require_once 'includes/functions.php';
$user = require_login();
$uid  = (int)$user['user_id'];

$errors = [];
$form = ['address_id' => 0, 'label' => 'Home', 'house' => '', 'locality' => '', 'city' => '', 'state' => '', 'pincode' => '', 'is_default' => 0];

function user_address(PDO $pdo, int $uid, int $id): ?array {
    $stmt = $pdo->prepare('SELECT * FROM addresses WHERE address_id = ? AND user_id = ?');
    $stmt->execute([$id, $uid]);
    return $stmt->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $form = [
            'address_id' => (int)($_POST['address_id'] ?? 0),
            'label'      => trim($_POST['label'] ?? ''),
            'house'      => trim($_POST['house'] ?? ''),
            'locality'   => trim($_POST['locality'] ?? ''),
            'city'       => trim($_POST['city'] ?? ''),
            'state'      => trim($_POST['state'] ?? ''),
            'pincode'    => trim($_POST['pincode'] ?? ''),
            'is_default' => isset($_POST['is_default']) ? 1 : 0,
        ];
        if ($form['label'] === '') $form['label'] = 'Home';

        if (strlen($form['label']) > 30)          $errors['label']    = 'Label can be at most 30 characters.';
        if (!valid_line($form['house']))          $errors['house']    = 'Enter house or building details (3-100 characters).';
        if (!valid_line($form['locality']))       $errors['locality'] = 'Enter street or locality (3-100 characters).';
        if (!valid_place($form['city']))          $errors['city']     = 'Enter a valid city name (letters only).';
        if (!valid_place($form['state']))         $errors['state']    = 'Enter a valid state name (letters only).';
        if (!valid_pincode($form['pincode']))     $errors['pincode']  = 'Enter a 6-digit PIN code that does not start with 0.';

        if (!$errors) {
            $existing = $form['address_id'] ? user_address($pdo, $uid, $form['address_id']) : null;
            if ($form['address_id'] && !$existing) {
                flash('error', 'That address was not found.');
                redirect('addresses.php');
            }
            $count = $pdo->prepare('SELECT COUNT(*) FROM addresses WHERE user_id = ?');
            $count->execute([$uid]);
            $isFirst = (int)$count->fetchColumn() === 0;
            // The default address cannot be unset; choose another address as default instead
            $makeDefault = $form['is_default'] || $isFirst || (bool)($existing['is_default'] ?? 0);

            $pdo->beginTransaction();
            if ($makeDefault) {
                $pdo->prepare('UPDATE addresses SET is_default = 0 WHERE user_id = ?')->execute([$uid]);
            }
            if ($existing) {
                $pdo->prepare('UPDATE addresses SET label=?, house=?, locality=?, city=?, state=?, pincode=?, is_default=? WHERE address_id=? AND user_id=?')
                    ->execute([$form['label'], $form['house'], $form['locality'], $form['city'], $form['state'], $form['pincode'],
                               $makeDefault ? 1 : 0, $existing['address_id'], $uid]);
                flash('success', 'Address updated.');
            } else {
                $pdo->prepare('INSERT INTO addresses (user_id, label, house, locality, city, state, pincode, is_default) VALUES (?,?,?,?,?,?,?,?)')
                    ->execute([$uid, $form['label'], $form['house'], $form['locality'], $form['city'], $form['state'], $form['pincode'], $makeDefault ? 1 : 0]);
                flash('success', 'Address added.');
            }
            $pdo->commit();
            redirect('addresses.php');
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['address_id'] ?? 0);
        $addr = user_address($pdo, $uid, $id);
        if ($addr) {
            $pdo->beginTransaction();
            $pdo->prepare('DELETE FROM addresses WHERE address_id = ? AND user_id = ?')->execute([$id, $uid]);
            if ($addr['is_default']) {
                $pdo->prepare('UPDATE addresses SET is_default = 1 WHERE user_id = ? ORDER BY address_id LIMIT 1')->execute([$uid]);
            }
            $pdo->commit();
            flash('success', 'Address deleted.');
        }
        redirect('addresses.php');
    }

    if ($action === 'make_default') {
        $id = (int)($_POST['address_id'] ?? 0);
        if (user_address($pdo, $uid, $id)) {
            $pdo->beginTransaction();
            $pdo->prepare('UPDATE addresses SET is_default = 0 WHERE user_id = ?')->execute([$uid]);
            $pdo->prepare('UPDATE addresses SET is_default = 1 WHERE address_id = ? AND user_id = ?')->execute([$id, $uid]);
            $pdo->commit();
            flash('success', 'Default address changed.');
        }
        redirect('addresses.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit'])) {
    $toEdit = user_address($pdo, $uid, (int)$_GET['edit']);
    if ($toEdit) $form = $toEdit;
}

$stmt = $pdo->prepare('SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, address_id');
$stmt->execute([$uid]);
$addresses = $stmt->fetchAll();
$editing = $form['address_id'] > 0;

$pageTitle = 'Delivery Addresses';
$pageMode  = 'account';
include 'includes/header.php';
?>
<div class="page-head">
  <div>
    <h1>Delivery Addresses</h1>
    <p class="page-sub">Manage multiple delivery locations for checkout.</p>
  </div>
  <a class="btn btn-primary" href="addresses.php#address-form">+ Add New Address</a>
</div>

<div class="grid-2">
  <section class="addr-list">
    <?php if (!$addresses): ?>
      <div class="card"><p class="muted">No saved addresses yet. Add your first one using the form.</p></div>
    <?php endif; ?>

    <?php foreach ($addresses as $a): ?>
      <article class="addr-card <?= $a['is_default'] ? 'primary' : '' ?>">
        <strong><?= e($a['label']) ?></strong>
        <?php if ($a['is_default']): ?><span class="pill primary">Primary</span><?php endif; ?>
        <p>
          <?= e($a['house']) ?>, <?= e($a['locality']) ?><br>
          <?= e($a['city']) ?>, <?= e($a['state']) ?> - <?= e($a['pincode']) ?>
        </p>
        <div class="actions">
          <a href="addresses.php?edit=<?= (int)$a['address_id'] ?>#address-form">Edit</a>
          <?php if (!$a['is_default']): ?>
            <form method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="make_default">
              <input type="hidden" name="address_id" value="<?= (int)$a['address_id'] ?>">
              <button class="link-btn">Set as Default</button>
            </form>
          <?php endif; ?>
          <form method="post" data-confirm="Delete this address? This cannot be undone.">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="address_id" value="<?= (int)$a['address_id'] ?>">
            <button class="link-btn danger">Delete</button>
          </form>
        </div>
      </article>
    <?php endforeach; ?>
  </section>

  <section class="card" id="address-form">
    <h2><?= $editing ? 'Edit Address' : 'Add a New Address' ?></h2>
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="address_id" value="<?= (int)$form['address_id'] ?>">

      <?= input_field('label', 'Label (Home, Work, ...)', $form['label'], $errors, 'text', 'maxlength="30"') ?>
      <?= input_field('house', 'House / Building', $form['house'], $errors, 'text', 'maxlength="100" data-validate="line" required') ?>
      <?= input_field('locality', 'Street / Locality', $form['locality'], $errors, 'text', 'maxlength="100" data-validate="line" required') ?>
      <div class="form-grid">
        <?= input_field('city', 'City', $form['city'], $errors, 'text', 'maxlength="50" data-validate="place" required') ?>
        <?= input_field('state', 'State', $form['state'], $errors, 'text', 'maxlength="50" data-validate="place" required') ?>
      </div>
      <?= input_field('pincode', 'PIN Code', $form['pincode'], $errors, 'text', 'maxlength="6" inputmode="numeric" data-validate="pin" required') ?>

      <label class="check">
        <input type="checkbox" name="is_default" <?= $form['is_default'] ? 'checked' : '' ?>>
        <span>Use as my default address</span>
      </label>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary"><?= $editing ? 'Save Address' : 'Add Address' ?></button>
        <?php if ($editing): ?><a class="btn" href="addresses.php">Cancel</a><?php endif; ?>
      </div>
    </form>
  </section>
</div>
<?php include 'includes/footer.php'; ?>
