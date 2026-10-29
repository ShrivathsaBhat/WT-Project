<?php
require __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
function out($d, int $c = 200) { http_response_code($c); echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }
function norm(array $r): array {
    foreach (['price','rating'] as $k) if (isset($r[$k])) $r[$k] = (float)$r[$k];
    foreach (['id','calories','spice','popularity','restaurant_id'] as $k) if (isset($r[$k])) $r[$k] = (int)$r[$k];
    foreach (['contains_gluten','contains_dairy','contains_nuts','is_special'] as $k) if (isset($r[$k])) $r[$k] = (bool)$r[$k];
    return $r;
}
try {
    $db = db(); $a = $_GET['action'] ?? 'dishes';
    $q = trim((string)($_GET['q'] ?? '')); $q = mb_substr($q, 0, 60);
    $like = '%' . addcslashes($q, '%_\\') . '%';
    if ($a === 'meta') {
        $cats = $db->query("SELECT c.id,c.name,COUNT(d.id) n FROM categories c LEFT JOIN dishes d ON d.category_id=c.id GROUP BY c.id ORDER BY c.id")->fetchAll();
        $tot = $db->query("SELECT COUNT(*) n,COALESCE(SUM(is_special),0) s,MAX(price) mx,MIN(price) mn FROM dishes")->fetch();
        out(['categories'=>$cats,'total'=>(int)$tot['n'],'specials'=>(int)$tot['s'],'maxPrice'=>(int)ceil($tot['mx']/50)*50,'minPrice'=>(int)floor($tot['mn']/50)*50,
             'restaurants'=>$db->query("SELECT id,name FROM restaurants ORDER BY name")->fetchAll()]);
    }
    if ($a === 'restaurants') {
        $sort = ['rating'=>'r.rating DESC','time'=>'r.delivery_min ASC','name'=>'r.name ASC'][$_GET['sort'] ?? ''] ?? 'r.rating DESC';
        $st = $db->prepare("SELECT r.*,COUNT(d.id) featured FROM restaurants r LEFT JOIN dishes d ON d.restaurant_id=r.id WHERE r.name LIKE :a OR r.cuisine LIKE :b GROUP BY r.id ORDER BY $sort,r.name");
        $st->execute([':a'=>$like,':b'=>$like]);
        out(array_map('norm', $st->fetchAll()));
    }
    if ($a === 'restaurant') {
        $st = $db->prepare("SELECT * FROM restaurants WHERE id=:i"); $st->execute([':i'=>(int)($_GET['id'] ?? 0)]);
        $r = $st->fetch(); $r ? out(norm($r)) : out(['error'=>'Restaurant not found'], 404);
    }
    $base = "SELECT d.*,c.name category,r.name restaurant,r.id restaurant_id FROM dishes d JOIN categories c ON c.id=d.category_id JOIN restaurants r ON r.id=d.restaurant_id";
    if ($a === 'dish') {
        $st = $db->prepare("$base WHERE d.id=:i"); $st->execute([':i'=>(int)($_GET['id'] ?? 0)]);
        $r = $st->fetch(); $r ? out(norm($r)) : out(['error'=>'Dish not found'], 404);
    }
    if ($a !== 'dishes') out(['error'=>'Unknown action'], 400);
    $w = []; $p = [];
    if ($q !== '') { $w[] = '(d.name LIKE :q1 OR d.description LIKE :q2 OR r.name LIKE :q3 OR r.cuisine LIKE :q4)'; foreach (['q1','q2','q3','q4'] as $k) $p[":$k"] = $like; }
    $cat = $_GET['category'] ?? '';
    if ($cat === 'special') $w[] = 'd.is_special=1'; elseif (ctype_digit((string)$cat)) { $w[] = 'd.category_id=:c'; $p[':c'] = (int)$cat; }
    if (ctype_digit((string)($_GET['restaurant'] ?? ''))) { $w[] = 'd.restaurant_id=:r'; $p[':r'] = (int)$_GET['restaurant']; }
    $diet = $_GET['diet'] ?? '';
    if ($diet === 'veg') $w[] = "d.diet IN ('veg','vegan')"; elseif ($diet === 'nonveg') $w[] = "d.diet='nonveg'"; elseif ($diet === 'vegan') $w[] = "d.diet='vegan'";
    if (in_array($_GET['spice'] ?? '', ['1','2','3'], true)) { $w[] = 'd.spice<=:s'; $p[':s'] = (int)$_GET['spice']; }
    if (is_numeric($_GET['maxPrice'] ?? null)) { $w[] = 'd.price<=:mp'; $p[':mp'] = (float)$_GET['maxPrice']; }
    foreach (explode(',', (string)($_GET['exclude'] ?? '')) as $x) if (in_array($x, ['gluten','dairy','nuts'], true)) $w[] = "d.contains_$x=0";
    $sort = ['price_asc'=>'d.price ASC','price_desc'=>'d.price DESC','rating'=>'d.rating DESC'][$_GET['sort'] ?? ''] ?? 'd.popularity DESC';
    $st = $db->prepare($base . ($w ? ' WHERE ' . implode(' AND ', $w) : '') . " ORDER BY $sort,d.id");
    $st->execute($p);
    out(array_map('norm', $st->fetchAll()));
} catch (Throwable $e) {
    error_log($e->getMessage());
    out(['error'=>'Server error. Check database settings in config.php.'], 500);
}
