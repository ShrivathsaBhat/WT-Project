<?php
require_once __DIR__ . '/coupon.php';
require_once __DIR__ . '/../config/cart.php';

/**
 * Assigns an intuitive visual food emoji based on dish name and veg status.
 */
function food_emoji(string $name, int $isVeg = 1): string {
    $n = strtolower($name);
    if (str_contains($n, 'pizza')) return '🍕';
    if (str_contains($n, 'burger')) return '🍔';
    if (str_contains($n, 'pasta') || str_contains($n, 'fettuccine') || str_contains($n, 'noodle') || str_contains($n, 'ramen')) return '🍝';
    if (str_contains($n, 'bread') || str_contains($n, 'stick') || str_contains($n, 'garlic')) return '🥖';
    if (str_contains($n, 'sushi') || str_contains($n, 'roll') || str_contains($n, 'nigiri')) return '🍣';
    if (str_contains($n, 'tiramisu') || str_contains($n, 'cake') || str_contains($n, 'dessert')) return '🍰';
    if (str_contains($n, 'soda') || str_contains($n, 'drink') || str_contains($n, 'coke') || str_contains($n, 'lassi')) return '🥤';
    if (str_contains($n, 'samosa')) return '🥟';
    if (str_contains($n, 'naan') || str_contains($n, 'roti') || str_contains($n, 'paratha')) return '🫓';
    if (str_contains($n, 'tikka') || str_contains($n, 'curry') || str_contains($n, 'masala') || str_contains($n, 'paneer')) return '🍛';
    if (str_contains($n, 'taco') || str_contains($n, 'quesadilla')) return '🌮';
    if (str_contains($n, 'salad')) return '🥗';
    if (str_contains($n, 'fries') || str_contains($n, 'wedge')) return '🍟';
    return $isVeg ? '🥬' : '🍗';
}

/* ===== Queries adapted for Food Dash team database schema ===== */

function q_menu_item(PDO $p, int $id) {
    $s = $p->prepare("SELECT id, restaurant_id, name, price, in_stock, is_veg FROM menu_items WHERE id=? AND in_stock=1");
    $s->execute([$id]);
    $r = $s->fetch();
    if ($r) {
        $r['emoji'] = food_emoji($r['name'], (int)$r['is_veg']);
    }
    return $r;
}

function q_menu_all(PDO $p): array {
    $rows = $p->query("SELECT m.id, m.restaurant_id, m.name, m.price, m.is_veg, m.description, r.name AS restaurant
        FROM menu_items m
        JOIN restaurants r ON r.id = m.restaurant_id
        WHERE m.in_stock=1
        ORDER BY r.id, m.id")->fetchAll();
    foreach ($rows as &$r) {
        $r['emoji'] = food_emoji($r['name'], (int)$r['is_veg']);
    }
    return $rows;
}

function q_restaurant(PDO $p, int $id) {
    $s = $p->prepare("SELECT id, name, cuisine, rating, delivery_time, address, phone FROM restaurants WHERE id=?");
    $s->execute([$id]);
    $r = $s->fetch();
    if ($r) {
        // Parse delivery_mins from delivery_time string (e.g. '25-30 min' -> 30)
        if (preg_match('/(\d+)\s*-\s*(\d+)/', $r['delivery_time'] ?? '', $m)) {
            $r['delivery_mins'] = (int)$m[2];
        } elseif (preg_match('/(\d+)/', $r['delivery_time'] ?? '', $m)) {
            $r['delivery_mins'] = (int)$m[1];
        } else {
            $r['delivery_mins'] = 30;
        }
    }
    return $r;
}

function q_extras(PDO $p, int $rid, array $ids): array {
    $in = implode(',', array_map('intval', $ids)) ?: '0';
    $rows = $p->query("SELECT id, name, price, is_veg FROM menu_items WHERE restaurant_id=" . (int)$rid . " AND in_stock=1 AND id NOT IN ($in) LIMIT 4")->fetchAll();
    foreach ($rows as &$r) {
        $r['emoji'] = food_emoji($r['name'], (int)$r['is_veg']);
    }
    return $rows;
}

function q_cart_rows(PDO $p, int $u): array {
    $s = $p->prepare("SELECT m.id, m.name, m.price, m.restaurant_id, m.is_veg, c.qty AS quantity, c.instruction AS instructions
        FROM cart_items c
        JOIN menu_items m ON m.id = c.menu_item_id
        WHERE c.user_id = ? AND m.in_stock = 1
        ORDER BY c.id");
    $s->execute([$u]);
    $rows = $s->fetchAll();
    foreach ($rows as &$r) {
        $r['emoji'] = food_emoji($r['name'], (int)$r['is_veg']);
    }
    return $rows;
}

function q_addresses(PDO $p, int $u): array {
    $s = $p->prepare("SELECT id, user_id, label, house, house AS line1, locality, city, state, pincode, is_default FROM addresses WHERE user_id=? ORDER BY is_default DESC, id");
    $s->execute([$u]);
    return $s->fetchAll();
}

function q_address(PDO $p, int $id, int $u) {
    $s = $p->prepare("SELECT id, user_id, label, house, house AS line1, locality, city, state, pincode, is_default FROM addresses WHERE id=? AND user_id=?");
    $s->execute([$id, $u]);
    return $s->fetch();
}

function q_address_insert(PDO $p, int $u, array $f): int {
    $n = $p->prepare("SELECT COUNT(*) FROM addresses WHERE user_id=?");
    $n->execute([$u]);
    $isDefault = ((int)$n->fetchColumn() === 0) ? 1 : 0;
    $label = in_array($f['label'] ?? '', ['Home', 'Work', 'Other'], true) ? $f['label'] : 'Home';
    $house = trim($f['house'] ?? ($f['line1'] ?? ''));
    $p->prepare("INSERT INTO addresses (user_id, label, house, locality, city, state, pincode, is_default) VALUES (?,?,?,?,?,?,?,?)")
      ->execute([$u, $label, $house, $f['locality'] ?? '', $f['city'] ?? '', $f['state'] ?? '', $f['pincode'] ?? '', $isDefault]);
    return (int)$p->lastInsertId();
}

function q_coupons(PDO $p): array {
    return $p->query("SELECT code, title, description, discount_type, discount_value, min_order, badge
        FROM coupons
        WHERE (status='Active' OR status='active') AND (expiry_date IS NULL OR expiry_date>=CURDATE())
        ORDER BY id")->fetchAll();
}

/* ===== Server-side authoritative billing computation ===== */
function bill(PDO $pdo, array $rows, ?string $code): array {
    $sub = 0.0;
    foreach ($rows as $r) {
        $sub += ((float)$r['price']) * ((int)$r['quantity']);
    }
    $sub = round($sub, 2);
    $disc = 0.0;
    $free = false;
    $err = null;

    if ($code && $sub > 0) {
        $v = validate_coupon($pdo, $code, $sub);
        if ($v['valid']) {
            $disc = min($v['discount'], $sub);
            $free = $v['free_delivery'];
        } else {
            $err = $v['message'];
        }
    }

    $pack = ($sub > 0) ? PACKAGING_FEE : 0;
    $taxable = max(0, $sub - $disc);
    $tax = round($taxable * TAX_RATE, 2);
    $del = ($sub > 0 && !$free) ? DELIVERY_FEE : 0;
    $total = round(max(0, $sub - $disc + $pack + $tax + $del), 2);

    return [
        'subtotal' => $sub,
        'discount' => round($disc, 2),
        'packaging' => $pack,
        'tax' => $tax,
        'delivery' => $del,
        'total' => $total,
        'coupon' => $err ? null : $code,
        'coupon_error' => $err
    ];
}

function cartData(PDO $pdo, int $u): array {
    $rows = q_cart_rows($pdo, $u);
    $rest = null;
    $extras = [];
    if ($rows) {
        $rest = q_restaurant($pdo, (int)$rows[0]['restaurant_id']);
        $extras = q_extras($pdo, (int)$rest['id'], array_column($rows, 'id'));
    }
    $b = bill($pdo, $rows, $_SESSION['coupon'] ?? null);
    if (!empty($b['coupon_error'])) {
        unset($_SESSION['coupon']);
    }
    return [
        'items' => $rows,
        'rest' => $rest,
        'extras' => $extras,
        'b' => $b,
        'addresses' => q_addresses($pdo, $u),
        'coupons' => q_coupons($pdo)
    ];
}
