<?php
require __DIR__ . '/../includes/auth.php';
require_admin();

require __DIR__ . '/../config/database.php';

$sql = "SELECT
            l.*,
            u.full_name,
            u.email
        FROM admin_logs l
        LEFT JOIN users u ON u.id = l.admin_id
        ORDER BY l.created_at DESC
        LIMIT 300";

$rows = db()->query($sql)->fetchAll();

$pageTitle = 'Admin activity log';

require __DIR__ . '/../includes/header.php';
?>

<div class="page-heading">
    <div>
        <div class="eyebrow">AUDIT TRAIL</div>
        <h1>Activity log</h1>
        <p class="muted">
            Recent admin actions.
        </p>
    </div>

    <a class="button secondary" href="orders.php">
        All orders
    </a>
</div>

<section class="panel">

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Administrator</th>
                    <th>Action</th>
                    <th>Details</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($rows as $l): ?>
                    <tr>
                        <td>
                            <?= h(
                                date(
                                    'd M Y, h:i:s A',
                                    strtotime($l['created_at'])
                                )
                            ) ?>
                        </td>

                        <td>
                            <?= h($l['full_name'] ?? 'Former admin') ?>

                            <div class="muted small">
                                <?= h($l['email'] ?? '') ?>
                            </div>
                        </td>

                        <td>
                            <b><?= h($l['action']) ?></b>
                        </td>

                        <td>
                            <?= h($l['details'] ?? '') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="4" class="empty">
                            No admin actions recorded yet.
                            Updating an order status will create
                            the first log entry.
                        </td>
                    </tr>
                <?php endif; ?>

            </tbody>
        </table>
    </div>

</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>