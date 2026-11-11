<?php
require_once __DIR__ . '/images.php';
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}
require_once __DIR__ . '/db.php';

// ---------- basic helpers ----------
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function url(string $p = ''): string { return BASE_URL . '/' . ltrim($p, '/'); }
function asset(string $p): string { $f = __DIR__ . '/../' . ltrim($p, '/'); return url($p) . '?v=' . (is_file($f) ? filemtime($f) : time()); }
function redirect(string $p): void { header('Location: ' . (preg_match('#^https?://#', $p) ? $p : url($p))); exit; }
function money($n): string { return CURRENCY . number_format((float)$n); }
function fdate($d, $f = 'D, d M Y'): string { return date($f, strtotime($d)); }
function ftime($t): string { return date('h:i A', strtotime($t)); }
function post(string $k, $d = ''): string { return trim((string)($_POST[$k] ?? $d)); }
function is_post(): bool { return $_SERVER['REQUEST_METHOD'] === 'POST'; }
function q(string $sql, array $p = []): PDOStatement { $s = db()->prepare($sql); $s->execute($p); return $s; }
function rows(string $sql, array $p = []): array { return q($sql, $p)->fetchAll(); }
function row(string $sql, array $p = []) { return q($sql, $p)->fetch(); }
function val(string $sql, array $p = []) { return q($sql, $p)->fetchColumn(); }
function insert_id(): int { return (int)db()->lastInsertId(); }
function excerpt(string $t, int $n = 100): string { return strlen($t) > $n ? rtrim(substr($t, 0, $n)) . '…' : $t; }
function initials(string $n): string { $p = preg_split('/\s+/', trim($n)); return strtoupper(substr($p[0], 0, 1) . (isset($p[1]) ? substr($p[1], 0, 1) : '')); }

// ---------- flash + csrf ----------
function flash(string $type, string $msg): void { $_SESSION['flash'][] = [$type, $msg]; }
function flashes(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(16)); }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf() . '">'; }
function csrf_check(): void {
    if (!is_post()) return;
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(419); die('Session expired. Please go back, refresh the page and try again.'); }
}

// ---------- auth ----------
function user(): ?array {
    static $u = false;
    if ($u === false) $u = isset($_SESSION['uid']) ? (row('SELECT * FROM users WHERE id=? AND status=1', [$_SESSION['uid']]) ?: null) : null;
    return $u;
}
function login_user(array $u): void { session_regenerate_id(true); $_SESSION['uid'] = $u['id']; }
function is_staff(?array $u = null): bool { $u ??= user(); return $u && in_array($u['role'], ['admin', 'receptionist', 'stylist']); }
function home_for(array $u): string { return is_staff($u) ? 'admin/dashboard.php' : 'user/dashboard.php'; }
function require_login(): array {
    $u = user();
    if (!$u) { flash('warning', 'Please login or create an account to access this page.'); redirect('login.php?next=' . urlencode($_SERVER['REQUEST_URI'])); }
    return $u;
}
function require_client(): array { $u = require_login(); if (is_staff($u)) redirect('admin/dashboard.php'); return $u; }
function require_role(string ...$roles): array {
    $u = require_login();
    if (!is_staff($u)) redirect('user/dashboard.php');
    if (!in_array($u['role'], $roles)) { flash('error', 'You do not have permission to open that page.'); redirect('admin/dashboard.php'); }
    return $u;
}
function safe_next(?string $n): string {
    if ($n && $n[0] === '/' && substr($n, 0, 2) !== '//' && (BASE_URL === '' || strpos($n, BASE_URL) === 0)) return $n;
    return '';
}

// ---------- notifications / mail ----------
function notify(int $uid, string $title, string $msg, string $type = 'info'): void {
    q('INSERT INTO notifications (user_id,title,message,type) VALUES (?,?,?,?)', [$uid, $title, $msg, $type]);
    $u = row('SELECT email FROM users WHERE id=?', [$uid]);
    if ($u) @file_put_contents(__DIR__ . '/../database/mail.log', '[' . date('Y-m-d H:i') . "] To: {$u['email']} | $title | $msg\n", FILE_APPEND);
}

// ---------- appointments ----------
const STEP = 30;
function slot_list(int $stylist, int $service, string $date, int $exclude = 0): array {
    $p = row('SELECT * FROM stylist_profiles WHERE user_id=?', [$stylist]);
    $s = row('SELECT duration FROM services WHERE id=?', [$service]);
    if (!$p || !$s || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $date < date('Y-m-d')) return [];
    if (!in_array(date('N', strtotime($date)), explode(',', $p['work_days']))) return [];
    $busy = rows("SELECT appt_time,end_time FROM appointments WHERE stylist_id=? AND appt_date=? AND status<>'cancelled' AND id<>?", [$stylist, $date, $exclude]);
    $out = []; $dur = (int)$s['duration'];
    $t = strtotime("$date {$p['start_time']}"); $end = strtotime("$date {$p['end_time']}");
    $now = time() + 30 * 60;
    for (; $t + $dur * 60 <= $end; $t += STEP * 60) {
        if ($t < $now) continue;
        $ok = true;
        foreach ($busy as $b) if ($t < strtotime("$date {$b['end_time']}") && $t + $dur * 60 > strtotime("$date {$b['appt_time']}")) { $ok = false; break; }
        if ($ok) $out[] = ['time' => date('H:i', $t), 'label' => date('h:i A', $t)];
    }
    return $out;
}
function create_appointment(int $client, int $stylist, int $service, string $date, string $time, string $notes = ''): int {
    $svc = row('SELECT * FROM services WHERE id=? AND active=1', [$service]);
    if (!$svc || !val('SELECT 1 FROM stylist_services WHERE stylist_id=? AND service_id=?', [$stylist, $service])) throw new Exception('This stylist does not offer the selected service.');
    if (!in_array($time, array_column(slot_list($stylist, $service, $date), 'time'))) throw new Exception('Sorry, that time slot is no longer available. Please pick another.');
    $end = date('H:i', strtotime("$time +{$svc['duration']} minutes"));
    q('INSERT INTO appointments (client_id,stylist_id,service_id,appt_date,appt_time,end_time,price,status,notes) VALUES (?,?,?,?,?,?,?,?,?)', [$client, $stylist, $service, $date, $time, $end, $svc['price'], 'confirmed', $notes]);
    $id = insert_id();
    q('UPDATE appointments SET ref=? WHERE id=?', ['ES' . date('ymd') . sprintf('%04d', $id), $id]);
    $sn = val('SELECT name FROM users WHERE id=?', [$stylist]);
    notify($client, 'Appointment Confirmed', "{$svc['name']} with $sn on " . fdate($date, 'd M Y') . ' at ' . ftime($time) . '.', 'success');
    notify($stylist, 'New Appointment', "{$svc['name']} on " . fdate($date, 'd M Y') . ' at ' . ftime($time) . '.', 'info');
    // Alert admin and reception whenever a customer books from the User Panel.
    $staff = rows("SELECT id FROM users WHERE role IN ('admin','receptionist') AND status=1");
    $clientName = val('SELECT name FROM users WHERE id=?', [$client]);
    foreach ($staff as $member) {
        notify((int)$member['id'], 'New Customer Booking', "$clientName booked {$svc['name']} with $sn on " . fdate($date, 'd M Y') . ' at ' . ftime($time) . '.', 'success');
    }
    return $id;
}
function appt_query(string $where = '1=1', string $order = 'a.appt_date DESC, a.appt_time DESC'): string {
    return "SELECT a.*, s.name AS service, s.image AS simage, c.name AS client, c.phone AS cphone, st.name AS stylist,
        (SELECT id FROM payments p WHERE p.appointment_id=a.id LIMIT 1) AS pay_id,
        (SELECT id FROM feedback f WHERE f.appointment_id=a.id LIMIT 1) AS fb_id
        FROM appointments a JOIN services s ON s.id=a.service_id JOIN users c ON c.id=a.client_id JOIN users st ON st.id=a.stylist_id
        WHERE $where ORDER BY $order";
}
function appt_is_future(array $a): bool { return strtotime($a['appt_date'] . ' ' . $a['appt_time']) > time(); }
function badge(string $s): string { return '<span class="badge b-' . e($s) . '">' . e(ucfirst($s)) . '</span>'; }
function stars(int $n): string { $o = ''; for ($i = 1; $i <= 5; $i++) $o .= '<span class="star' . ($i <= $n ? ' on' : '') . '">★</span>'; return '<span class="stars">' . $o . '</span>'; }

// ---------- icons ----------
function icon(string $n, int $s = 20): string {
    static $i = [
     'home'=>'<path d="M3 11l9-8 9 8v10a1 1 0 01-1 1h-5v-7H9v7H4a1 1 0 01-1-1z"/>','calendar'=>'<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>',
     'user'=>'<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>','users'=>'<circle cx="9" cy="8" r="3.5"/><path d="M2 20c0-3.5 3-5.5 7-5.5s7 2 7 5.5M17 4.5a3.5 3.5 0 010 7M22 20c0-2.5-1.5-4-4-5"/>',
     'scissors'=>'<circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M20 4L8.1 15.9M14.5 14.5L20 20M8.1 8.1L12 12"/>','sparkle'=>'<path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8zM19 16l.8 2.2L22 19l-2.2.8L19 22l-.8-2.2L16 19l2.2-.8z"/>',
     'hand'=>'<path d="M8 13V5a1.5 1.5 0 013 0v6m0-7a1.5 1.5 0 013 0v7m0-5a1.5 1.5 0 013 0v9a7 7 0 01-7 7h-1a6 6 0 01-5-3l-3-5a1.5 1.5 0 012.5-1.5L8 15"/>','foot'=>'<path d="M7 20c-2 0-3-1-3-3 0-3 2-4 2-8 0-3 2-5 5-5s5 2 5 5-1 4-1 6 3 1 3 3-1 2-3 2z"/>',
     'razor'=>'<path d="M3 16l9-9 5 5-9 9z"/><path d="M12 7l3-3 5 5-3 3M7 14l3 3"/>','drop'=>'<path d="M12 3s7 7.5 7 12a7 7 0 01-14 0c0-4.5 7-12 7-12z"/>','leaf'=>'<path d="M5 19C5 9 11 4 20 4c0 9-5 15-15 15zM5 19l8-8"/>','crown'=>'<path d="M3 8l4.5 4L12 5l4.5 7L21 8l-2 11H5z"/>',
     'lotus'=>'<path d="M12 20c-5 0-8-3-9-8 4 0 7 1.5 9 4 2-2.500 5-4 9-4-1 5-4 8-9 8zM12 16c-2-2-3-5 0-11 3 6 2 9 0 11z"/>','feather'=>'<path d="M20 4a8 8 0 00-11 0L4 9v7l-1 5 5-1h7l5-5a8 8 0 000-11zM15 9L3 21"/>',
     'clock'=>'<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>','check'=>'<path d="M4 12l5 5L20 6"/>','x'=>'<path d="M6 6l12 12M18 6L6 18"/>','menu'=>'<path d="M3 6h18M3 12h18M3 18h18"/>',
     'bell'=>'<path d="M6 9a6 6 0 0112 0c0 7 3 7 3 9H3c0-2 3-2 3-9zM10 21a2 2 0 004 0"/>','lock'=>'<rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 018 0v3"/>',
     'logout'=>'<path d="M9 4H5a1 1 0 00-1 1v14a1 1 0 001 1h4M16 8l4 4-4 4M20 12H9"/>','phone'=>'<path d="M5 3h4l2 5-2.500 1.500a11 11 0 006 6L16 13l5 2v4a2 2 0 01-2 2A16 16 0 013 5a2 2 0 012-2z"/>',
     'mail'=>'<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>','pin'=>'<path d="M12 21s7-6 7-11a7 7 0 00-14 0c0 5 7 11 7 11z"/><circle cx="12" cy="10" r="2.500"/>',
     'star'=>'<path d="M12 3l2.800 5.800 6.200.9-4.500 4.400 1.100 6.200L12 17.300 6.400 20.300l1.100-6.200L3 9.700l6.200-.9z"/>','plus'=>'<path d="M12 5v14M5 12h14"/>','edit'=>'<path d="M4 20h4L19 9l-4-4L4 16zM14 6l4 4"/>',
     'trash'=>'<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>','box'=>'<path d="M3 7l9-4 9 4v10l-9 4-9-4zM3 7l9 4 9-4M12 11v10"/>','chart'=>'<path d="M4 20V4M4 20h16M8 16v-4M12 16V8M16 16v-6"/>',
     'card'=>'<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h4"/>','file'=>'<path d="M6 3h8l5 5v13H6zM14 3v5h5M9 13h6M9 17h6"/>','message'=>'<path d="M4 5h16v11H9l-5 4z"/>','settings'=>'<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M5 5l2 2M17 17l2 2M19 5l-2 2M7 17l-2 2"/>',
     'arrow'=>'<path d="M5 12h14M13 6l6 6-6 6"/>','eye'=>'<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>','print'=>'<path d="M7 9V3h10v6M7 17H4v-7h16v7h-3M7 14h10v7H7z"/>','money'=>'<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="3"/>','alert'=>'<path d="M12 3l10 18H2zM12 10v5M12 18v.5"/>','refresh'=>'<path d="M20 11a8 8 0 00-14-4L4 9M4 4v5h5M4 13a8 8 0 0014 4l2-2M20 20v-5h-5"/>',
    ];
    return '<svg class="ic" width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">' . ($i[$n] ?? '') . '</svg>';
}
function aud_label(?string $a): string { return ['men' => 'Men', 'women' => 'Women'][$a ?? ''] ?? 'Women & Men'; }
function aud_badge(?string $a): string { $a = in_array($a, ['men', 'women'], true) ? $a : 'unisex'; return '<span class="aud aud-' . $a . '"><i></i>' . aud_label($a) . '</span>'; }
function img_tag(?string $src, string $alt = '', string $cls = ''): string {
    return '<img src="' . e($src) . '" alt="' . e($alt) . '" class="' . e($cls) . '" loading="lazy" onerror="this.classList.add(\'img-fail\')">';
}
function days_label(string $csv): string { $n = [1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat',7=>'Sun']; return implode(', ', array_map(fn($d) => $n[(int)$d] ?? '', explode(',', $csv))); }
