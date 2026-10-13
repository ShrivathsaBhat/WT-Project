<?php
require_once 'includes/functions.php';
$user = require_login();
$uid  = (int)$user['user_id'];
$current = session_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'revoke') {
        $sid = $_POST['session_id'] ?? '';
        if ($sid === $current) {
            flash('info', 'To end this session, use Sign Out in the sidebar.');
        } elseif (preg_match('/^[A-Za-z0-9]{10,128}$/', $sid)) {
            $pdo->prepare('DELETE FROM user_sessions WHERE session_id = ? AND user_id = ?')->execute([$sid, $uid]);
            revoke_remember_tokens($pdo, $uid);
            flash('success', 'That device has been signed out.');
        }
    } elseif ($action === 'logout_others') {
        $pdo->prepare('DELETE FROM user_sessions WHERE user_id = ? AND session_id <> ?')->execute([$uid, $current]);
        revoke_remember_tokens($pdo, $uid);
        flash('success', 'All other devices have been signed out.');
    } elseif ($action === 'clear_cookie') {
        clear_remember_cookie($pdo);
        flash('success', 'Remember-me cookie cleared.');
    }
    redirect('sessions.php');
}

$stmt = $pdo->prepare('SELECT * FROM user_sessions WHERE user_id = ? ORDER BY last_seen DESC');
$stmt->execute([$uid]);
$sessions = $stmt->fetchAll();
$hasRemember = !empty($_COOKIE['remember']);

$pageTitle = 'Sessions & Cookies';
$pageMode  = 'account';
include 'includes/header.php';
?>
<h1>Sessions &amp; Cookies</h1>
<p class="page-sub">Devices signed in to your account and the cookies this site uses.</p>

<section class="card">
  <div class="section-head">
    <div>
      <h2>Active Sessions</h2>
      <p class="muted small">Devices currently logged into your account.</p>
    </div>
    <?php if (count($sessions) > 1): ?>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="logout_others">
        <button class="btn btn-sm">Logout Other Devices</button>
      </form>
    <?php endif; ?>
  </div>

  <?php foreach ($sessions as $s): ?>
    <?php $isCurrent = hash_equals($s['session_id'], $current); ?>
    <div class="row-item">
      <div>
        <strong><?= e($s['device']) ?></strong>
        <p>IP <?= e($s['ip']) ?> &middot; <?= $isCurrent ? 'Active now' : 'Last active ' . e(time_ago($s['last_seen'])) ?></p>
      </div>
      <?php if ($isCurrent): ?>
        <span class="pill green"><span class="dot"></span>Current Session</span>
      <?php else: ?>
        <form method="post" data-confirm="Sign out this device?">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="revoke">
          <input type="hidden" name="session_id" value="<?= e($s['session_id']) ?>">
          <button class="link-btn danger">Revoke</button>
        </form>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</section>

<section class="card">
  <h2>Cookie Preferences</h2>
  <p class="muted small">Manage how this site stores cookies in your browser.</p>
  <div class="row-item">
    <div>
      <strong>Essential Authentication Cookies</strong>
      <p>Required to keep you signed in securely.</p>
    </div>
    <span class="muted small">Always active</span>
  </div>
  <div class="row-item">
    <div>
      <strong>Remember Me Cookie</strong>
      <p><?= $hasRemember ? 'Present on this device (expires in 30 days)' : 'Not set on this device' ?></p>
    </div>
    <?php if ($hasRemember): ?>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="clear_cookie">
        <button class="btn btn-sm btn-danger">Clear Cookie</button>
      </form>
    <?php endif; ?>
  </div>
</section>
<?php include 'includes/footer.php'; ?>
