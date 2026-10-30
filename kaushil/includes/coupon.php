<?php
// Module 3 Coupon validation helper (compatible with Module 5 Offers)
// Shape: ['valid'=>bool, 'discount'=>float, 'free_delivery'=>bool, 'message'=>string]
require_once __DIR__ . '/../config/cart.php';

function validate_coupon(PDO $pdo, string $code, float $subtotal): array {
    $r = ['valid' => false, 'discount' => 0.0, 'free_delivery' => false, 'message' => ''];
    $code = strtoupper(trim($code));
    if ($code === '') {
        $r['message'] = 'Please enter a coupon code';
        return $r;
    }

    // Check for coupon in coupons table
    $s = $pdo->prepare("SELECT * FROM coupons WHERE UPPER(code)=? AND (status='Active' OR status='active') AND (expiry_date IS NULL OR expiry_date>=CURDATE())");
    $s->execute([$code]);
    $c = $s->fetch();

    if (!$c) {
        $r['message'] = 'Invalid or expired coupon code: ' . htmlspecialchars($code);
        return $r;
    }

    $minOrder = (float)($c['min_order'] ?? 0);
    if ($subtotal < $minOrder) {
        $needed = round($minOrder - $subtotal, 2);
        $r['message'] = 'Add items worth ' . CURRENCY . number_format($needed, 2) . ' more to use ' . htmlspecialchars($code);
        return $r;
    }

    $r['valid'] = true;
    $type = strtoupper($c['discount_type'] ?? 'FLAT');
    $val = (float)($c['discount_value'] ?? 0);
    $maxDisc = (float)($c['max_discount'] ?? 0);

    if ($type === 'PERCENT') {
        $calc = round($subtotal * ($val / 100), 2);
        if ($maxDisc > 0) {
            $calc = min($calc, $maxDisc);
        }
        $r['discount'] = min($calc, $subtotal);
    } elseif ($type === 'FLAT') {
        $r['discount'] = min($val, $subtotal);
    } elseif ($type === 'DELIVERY') {
        $r['free_delivery'] = true;
        $r['discount'] = 0.0;
    } else {
        $r['discount'] = min($val, $subtotal);
    }

    return $r;
}
