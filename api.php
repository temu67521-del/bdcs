<?php
// ============================================================
// BDCS — PHP Backend (PostgreSQL / Render-compatible)
// ============================================================
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, x-admin-token');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

// ---------- DATABASE ----------
$DB_HOST = getenv('DB_HOST');
$DB_PORT = getenv('DB_PORT') ?: '5432';
$DB_NAME = getenv('DB_NAME');
$DB_USER = getenv('DB_USER');
$DB_PASS = getenv('DB_PASS');

$DATABASE_URL = getenv('DATABASE_URL');
if ($DATABASE_URL) {
    $u = parse_url($DATABASE_URL);
    if ($u) {
        $DB_HOST = $u['host'] ?? $DB_HOST;
        $DB_PORT = $u['port'] ?? $DB_PORT;
        $DB_USER = $u['user'] ?? $DB_USER;
        $DB_PASS = $u['pass'] ?? $DB_PASS;
        $DB_NAME = ltrim($u['path'] ?? '', '/') ?: $DB_NAME;
    }
}

try {
    $pdo = new PDO(
        "pgsql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME",
        $DB_USER, $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// ---------- Admin credentials (from env or defaults) ----------
$ADMIN_USERNAME = getenv('ADMIN_USERNAME') ?: 'admin';
$ADMIN_PASSWORD = getenv('ADMIN_PASSWORD') ?: '12345678';

$stmt = $pdo->query("SELECT COUNT(*) AS n FROM admins");
$row  = $stmt->fetch();
if ((int)$row['n'] === 0) {
    $hash = password_hash($ADMIN_PASSWORD, PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO admins(username, password_hash) VALUES(?, ?)")
        ->execute([$ADMIN_USERNAME, $hash]);
}

function json_out($data, $code = 200) { http_response_code($code); echo json_encode($data, JSON_UNESCAPED_UNICODE); exit; }
function err($msg, $code = 400) { json_out(['error' => $msg], $code); }
function body() { return json_decode(file_get_contents('php://input'), true) ?: []; }
function rand_hex($n) { return bin2hex(random_bytes($n)); }

$TOKEN_FILE = __DIR__ . '/tokens.json';
function load_tokens() { global $TOKEN_FILE; return file_exists($TOKEN_FILE) ? (json_decode(file_get_contents($TOKEN_FILE), true) ?: []) : []; }
function save_tokens($t) { global $TOKEN_FILE; @file_put_contents($TOKEN_FILE, json_encode($t)); }
function require_auth() {
    $headers = getallheaders();
    $token = $headers['x-admin-token'] ?? $headers['X-Admin-Token'] ?? '';
    $tokens = load_tokens();
    if (!isset($tokens[$token]) || $tokens[$token] < time()) err('Unauthorized', 401);
}

$route  = $_GET['route'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$input  = body();

// ==================== PUBLIC ====================
if ($method === 'GET' && $route === '/api/courses') {
    $cats = $pdo->query("SELECT id, name, slug, icon FROM categories ORDER BY id")->fetchAll();
    $sql = "SELECT c.*, cat.slug AS category, cat.name AS category_name, cat.icon AS category_icon,
                   t.full_name AS teacher_name, t.title AS teacher_title, t.photo_url, t.bio AS teacher_bio
            FROM courses c
            JOIN categories cat ON cat.id = c.category_id
            LEFT JOIN teachers t ON t.id = c.teacher_id
            WHERE c.active = TRUE
            ORDER BY c.category_id, c.id";
    $courses = $pdo->query($sql)->fetchAll();
    foreach ($courses as &$c) {
        $c['final_price']  = (float)$c['fee'] - (float)$c['discount'];
        $c['teacher_id']   = $c['teacher_id'] ?: null;
        $c['enrolled']     = (int)$c['enrolled'];
        $c['max_students'] = (int)$c['max_students'];
        $c['active']       = (bool)$c['active'];
    }
    json_out(['categories' => $cats, 'courses' => $courses]);
}

if ($method === 'GET' && preg_match('#^/api/courses/by-category/(.+)$#', $route, $m)) {
    $sql = "SELECT c.*, cat.slug AS category, cat.name AS category_name, cat.icon AS category_icon,
                   t.full_name AS teacher_name, t.title AS teacher_title, t.photo_url, t.bio AS teacher_bio
            FROM courses c
            JOIN categories cat ON cat.id = c.category_id
            LEFT JOIN teachers t ON t.id = c.teacher_id
            WHERE c.active = TRUE AND cat.slug = ?
            ORDER BY c.id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$m[1]]);
    $list = $stmt->fetchAll();
    foreach ($list as &$c) {
        $c['final_price']  = (float)$c['fee'] - (float)$c['discount'];
        $c['teacher_id']   = $c['teacher_id'] ?: null;
        $c['enrolled']     = (int)$c['enrolled'];
        $c['max_students'] = (int)$c['max_students'];
        $c['active']       = (bool)$c['active'];
    }
    json_out($list);
}

if ($method === 'GET' && preg_match('#^/api/course(s)?/(\d+)$#', $route, $m)) {
    $sql = "SELECT c.*, cat.slug AS category, cat.name AS category_name, cat.icon AS category_icon,
                   t.full_name AS teacher_name, t.title AS teacher_title, t.photo_url, t.bio AS teacher_bio
            FROM courses c
            JOIN categories cat ON cat.id = c.category_id
            LEFT JOIN teachers t ON t.id = c.teacher_id
            WHERE c.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$m[2]]);
    $c = $stmt->fetch();
    if (!$c) err('Course not found', 404);
    $c['final_price']  = (float)$c['fee'] - (float)$c['discount'];
    $c['teacher_id']   = $c['teacher_id'] ?: null;
    $c['enrolled']     = (int)$c['enrolled'];
    $c['max_students'] = (int)$c['max_students'];
    $c['active']       = (bool)$c['active'];
    json_out($c);
}

if ($method === 'GET' && $route === '/api/teachers') {
    json_out($pdo->query("SELECT id, full_name, title, photo_url, bio FROM teachers ORDER BY id")->fetchAll());
}

if ($method === 'GET' && $route === '/api/pay-info') {
    json_out(['Telebirr' => '0981796578', 'CBE Birr' => '1000485583657']);
}

// ---------- REGISTER ----------
if ($method === 'POST' && $route === '/api/register') {
    $name    = trim($_POST['full_name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $method_ = $_POST['payment_method'] ?? '';
    $ref     = trim($_POST['payer_ref'] ?? '');
    $cid     = (int)($_POST['course_id'] ?? 0);

    if (strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        !preg_match('/^09\d{8}$/', $phone) ||
        !in_array($method_, ['Telebirr', 'CBE Birr']) || !$ref) {
        err('Check name, email, phone (09XXXXXXXX), payment method and transaction ID.');
    }

    $stmt = $pdo->prepare("SELECT fee, discount, enrolled, max_students FROM courses WHERE id = ? AND active = TRUE");
    $stmt->execute([$cid]);
    $c = $stmt->fetch();
    if (!$c) err('Course not found', 404);
    if ((int)$c['enrolled'] >= (int)$c['max_students']) err('This course is full.', 409);

    $final = (float)$c['fee'] - (float)$c['discount'];

    $stmt = $pdo->prepare("INSERT INTO students(full_name, email, phone) VALUES(?,?,?) RETURNING id");
    $stmt->execute([$name, $email, $phone]);
    $sid = $stmt->fetch()['id'];

    $tx = 'BDCS-' . strtoupper(rand_hex(4));
    $pdo->prepare("INSERT INTO registrations
        (student_id, course_id, amount, payment_method, tx_ref, payer_ref)
        VALUES(?,?,?,?,?,?)")
        ->execute([$sid, $cid, $final, $method_, $tx, substr($ref, 0, 80)]);

    json_out([
        'ref'         => $tx,
        'receipt_url' => 'receipt.html?ref=' . $tx,
        'note'        => 'Registered. The school admin will verify your payment.',
    ]);
}

if ($method === 'GET' && preg_match('#^/api/receipt/(.+)$#', $route, $m)) {
    $sql = "SELECT r.tx_ref, r.amount, r.payment_method, r.status, r.created_at,
                   st.full_name, st.email, st.phone,
                   c.name AS course, c.duration, c.schedule
            FROM registrations r
            JOIN students st ON st.id = r.student_id
            JOIN courses c ON c.id = r.course_id
            WHERE r.tx_ref = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$m[1]]);
    $r = $stmt->fetch();
    if (!$r) err('Receipt not found', 404);
    json_out($r);
}

// ==================== ADMIN AUTH ====================
if ($method === 'POST' && $route === '/api/admin/login') {
    $stmt = $pdo->prepare("SELECT password_hash FROM admins WHERE username = ?");
    $stmt->execute([$input['username'] ?? $ADMIN_USERNAME]);
    $a = $stmt->fetch();
    if (!$a || !password_verify($input['password'] ?? '', $a['password_hash'])) {
        err('Wrong username or password', 401);
    }
    $token = rand_hex(24);
    $tokens = load_tokens();
    $tokens = array_filter($tokens, fn($e) => $e > time());
    $tokens[$token] = time() + 8 * 3600;
    save_tokens($tokens);
    json_out(['token' => $token]);
}

if ($method === 'POST' && $route === '/api/admin/logout') {
    require_auth();
    $headers = getallheaders();
    $token = $headers['x-admin-token'] ?? '';
    $tokens = load_tokens();
    unset($tokens[$token]);
    save_tokens($tokens);
    json_out(['ok' => true]);
}

// ==================== CHANGE PASSWORD ====================
if ($method === 'POST' && $route === '/api/admin/change-password') {
    require_auth();
    $current = $input['current'] ?? '';
    $new     = $input['new']     ?? '';

    if (strlen($new) < 6) err('New password must be at least 6 characters.');

    $stmt = $pdo->prepare("SELECT id, password_hash FROM admins WHERE username = ?");
    $stmt->execute([$ADMIN_USERNAME]);
    $a = $stmt->fetch();

    if (!$a || !password_verify($current, $a['password_hash'])) {
        err('Current password is wrong.', 401);
    }

    $newHash = password_hash($new, PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE admins SET password_hash = ? WHERE id = ?")
        ->execute([$newHash, $a['id']]);

    json_out(['ok' => true, 'message' => 'Password changed. Please log in again.']);
}

// ==================== ADMIN REGISTRATIONS ====================
$REG_SQL = "SELECT r.id, r.tx_ref, r.amount, r.payment_method, r.status,
                   COALESCE(r.payer_ref,'') AS payer_ref,
                   r.created_at,
                   st.full_name, st.email, st.phone,
                   c.name AS course
            FROM registrations r
            JOIN students st ON st.id = r.student_id
            JOIN courses  c  ON c.id  = r.course_id
            ORDER BY r.id DESC";

if ($method === 'GET' && $route === '/api/admin/registrations') {
    require_auth();
    json_out($pdo->query($REG_SQL)->fetchAll());
}

if ($method === 'GET' && $route === '/api/admin/stats') {
    require_auth();
    $sql = "SELECT COUNT(*) AS total,
                   COUNT(*) FILTER (WHERE status='Paid')    AS paid,
                   COUNT(*) FILTER (WHERE status='Pending') AS pending,
                   COALESCE(SUM(CASE WHEN status='Paid' THEN amount ELSE 0 END),0) AS revenue
            FROM registrations";
    json_out($pdo->query($sql)->fetch());
}

if ($method === 'POST' && preg_match('#^/api/admin/verify/(\d+)$#', $route, $m)) {
    require_auth();
    $id = (int)$m[1];
    $action = $input['action'] ?? '';

    $stmt = $pdo->prepare("SELECT course_id, status, amount, payment_method, COALESCE(payer_ref,'') AS pref FROM registrations WHERE id = ?");
    $stmt->execute([$id]);
    $g = $stmt->fetch();
    if (!$g) err('Registration not found', 404);

    if ($action === 'reject') {
        $pdo->prepare("UPDATE registrations SET status='Failed' WHERE id = ? AND status <> 'Paid'")->execute([$id]);
        json_out(['ok' => true]);
    }
    if ($action !== 'verify') err('Bad action');
    if ($g['status'] === 'Paid') json_out(['ok' => true]);

    $stmt = $pdo->prepare("SELECT enrolled, max_students FROM courses WHERE id = ?");
    $stmt->execute([$g['course_id']]);
    $c = $stmt->fetch();
    if ((int)$c['enrolled'] >= (int)$c['max_students']) err('Course is full. Cannot verify.', 409);

    $pdo->prepare("UPDATE registrations SET status='Paid' WHERE id = ?")->execute([$id]);
    $pdo->prepare("UPDATE courses SET enrolled = enrolled + 1 WHERE id = ?")->execute([$g['course_id']]);
    $pdo->prepare("INSERT INTO payments(registration_id, method, transaction_id, amount, status) VALUES(?,?,?,?,'Paid')")
        ->execute([$id, $g['payment_method'], $g['pref'], $g['amount']]);

    $stmt = $pdo->prepare("SELECT st.email, st.full_name, c.name AS course
                           FROM registrations r
                           JOIN students st ON st.id = r.student_id
                           JOIN courses c ON c.id = r.course_id
                           WHERE r.id = ?");
    $stmt->execute([$id]);
    $info = $stmt->fetch();
    if ($info && !empty($info['email'])) {
        $to      = $info['email'];
        $subject = 'BDCS — Payment verified for ' . $info['course'];
        $message = "Dear {$info['full_name']},\n\n"
                 . "Your payment for the course \"{$info['course']}\" has been verified.\n"
                 . "Your seat is confirmed. Welcome to Bahir Dar Computer School!\n\n";
        $headers = 'From: noreply@bdcs.local' . "\r\n" .
                   'Reply-To: noreply@bdcs.local' . "\r\n" .
                   'X-Mailer: PHP/' . phpversion();
        @mail($to, $subject, $message, $headers);
    }

    json_out(['ok' => true]);
}

if ($method === 'GET' && $route === '/api/admin/export.csv') {
    require_auth();
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="bdcs-registrations.csv"');
    echo "tx_ref,full_name,email,phone,course,amount,payment_method,payer_ref,status,created_at\n";
    foreach ($pdo->query($REG_SQL)->fetchAll() as $r) {
        echo implode(',', array_map(
            fn($v) => '"' . str_replace('"', '""', $v ?? '') . '"',
            [$r['tx_ref'], $r['full_name'], $r['email'], $r['phone'], $r['course'],
             $r['amount'], $r['payment_method'], $r['payer_ref'], $r['status'], $r['created_at']]
        )) . "\n";
    }
    exit;
}

// ==================== ADMIN COURSES ====================
$COURSE_SQL = "SELECT c.*, cat.slug AS category, cat.name AS category_name, cat.icon AS category_icon,
                      t.full_name AS teacher_name
               FROM courses c
               JOIN categories cat ON cat.id = c.category_id
               LEFT JOIN teachers t ON t.id = c.teacher_id";

if ($method === 'GET' && $route === '/api/admin/courses') {
    require_auth();
    $list = $pdo->query($COURSE_SQL . " ORDER BY c.id")->fetchAll();
    foreach ($list as &$c) {
        $c['final_price']  = (float)$c['fee'] - (float)$c['discount'];
        $c['teacher_id']   = $c['teacher_id'] ?: null;
        $c['enrolled']     = (int)$c['enrolled'];
        $c['max_students'] = (int)$c['max_students'];
        $c['active']       = (bool)$c['active'];
    }
    json_out($list);
}

if ($method === 'POST' && $route === '/api/admin/courses') {
    require_auth();
    $v = validate_course($input);
    if (!$v) err('Fill in name, category, course time, fee, discount (0 – fee), quota and level.');
    $pdo->prepare("INSERT INTO courses
        (category_id, name, description, duration, start_date, end_date, certificate,
         fee, discount, teacher_id, schedule, max_students, level, syllabus, active)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")->execute($v);
    json_out(['ok' => true]);
}

if ($method === 'PUT' && preg_match('#^/api/admin/courses/(\d+)$#', $route, $m)) {
    require_auth();
    $id = (int)$m[1];
    $v = validate_course($input);
    if (!$v) err('Fill in name, category, course time, fee, discount (0 – fee), quota and level.');

    $stmt = $pdo->prepare("SELECT enrolled FROM courses WHERE id = ?");
    $stmt->execute([$id]);
    $c = $stmt->fetch();
    if (!$c) err('Course not found', 404);
    if ($v[11] < (int)$c['enrolled']) err("Quota cannot be lower than {$c['enrolled']} students already enrolled.");

    $v[] = $id;
    $pdo->prepare("UPDATE courses SET
        category_id=?, name=?, description=?, duration=?, start_date=?, end_date=?, certificate=?,
        fee=?, discount=?, teacher_id=?, schedule=?, max_students=?, level=?, syllabus=?, active=?
        WHERE id=?")->execute($v);
    json_out(['ok' => true]);
}

if ($method === 'DELETE' && preg_match('#^/api/admin/courses/(\d+)$#', $route, $m)) {
    require_auth();
    $id = (int)$m[1];
    $stmt = $pdo->prepare("SELECT COUNT(*) AS n FROM registrations WHERE course_id = ?");
    $stmt->execute([$id]);
    if ((int)$stmt->fetch()['n'] > 0) {
        $pdo->prepare("UPDATE courses SET active = FALSE WHERE id = ?")->execute([$id]);
        json_out(['ok' => true, 'message' => 'Course has registrations — hidden instead of deleted.']);
    }
    $pdo->prepare("DELETE FROM courses WHERE id = ?")->execute([$id]);
    json_out(['ok' => true, 'message' => 'Course deleted.']);
}

function validate_course($b) {
    $fee  = (float)($b['fee'] ?? -1);
    $disc = (float)($b['discount'] ?? -1);
    $max  = (int)($b['max_students'] ?? 0);
    $lvl  = $b['level'] ?? '';
    if (empty(trim($b['name'] ?? '')) || empty($b['category_id']) || empty(trim($b['duration'] ?? ''))
        || $fee < 0 || $disc < 0 || $disc > $fee || $max < 1
        || !in_array($lvl, ['Beginner','Intermediate','Advanced'])) return null;

    return [
        (int)$b['category_id'],
        trim($b['name']),
        $b['description'] ?? '',
        trim($b['duration']),
        !empty($b['start_date']) ? $b['start_date'] : null,
        !empty($b['end_date'])   ? $b['end_date']   : null,
        $b['certificate'] ?: 'Certificate of Completion',
        $fee,
        $disc,
        !empty($b['teacher_id']) ? (int)$b['teacher_id'] : null,
        $b['schedule'] ?? '',
        $max,
        $lvl,
        $b['syllabus'] ?? '',
        !empty($b['active']) ? true : false,
    ];
}

// ==================== ADMIN TEACHERS ====================
if ($method === 'GET' && $route === '/api/admin/teachers') {
    require_auth();
    $sql = "SELECT t.id, t.full_name, t.title, t.photo_url, t.bio,
                   COUNT(c.id) AS course_count
            FROM teachers t
            LEFT JOIN courses c ON c.teacher_id = t.id
            GROUP BY t.id, t.full_name, t.title, t.photo_url, t.bio
            ORDER BY t.id";
    json_out($pdo->query($sql)->fetchAll());
}

if ($method === 'POST' && $route === '/api/admin/teachers') {
    require_auth();
    if (empty(trim($input['full_name'] ?? ''))) err('Teacher name is required.');
    $pdo->prepare("INSERT INTO teachers(full_name, title, photo_url, bio) VALUES(?,?,?,?)")
        ->execute([trim($input['full_name']), $input['title'] ?? '', $input['photo_url'] ?? '', $input['bio'] ?? '']);
    json_out(['ok' => true]);
}

if ($method === 'PUT' && preg_match('#^/api/admin/teachers/(\d+)$#', $route, $m)) {
    require_auth();
    if (empty(trim($input['full_name'] ?? ''))) err('Teacher name is required.');
    $pdo->prepare("UPDATE teachers SET full_name=?, title=?, photo_url=?, bio=? WHERE id=?")
        ->execute([trim($input['full_name']), $input['title'] ?? '', $input['photo_url'] ?? '', $input['bio'] ?? '', (int)$m[1]]);
    json_out(['ok' => true]);
}

if ($method === 'DELETE' && preg_match('#^/api/admin/teachers/(\d+)$#', $route, $m)) {
    require_auth();
    $id = (int)$m[1];
    $pdo->prepare("UPDATE courses SET teacher_id = NULL WHERE teacher_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM teachers WHERE id = ?")->execute([$id]);
    json_out(['ok' => true]);
}

// ==================== ADMIN CATEGORIES ====================
if ($method === 'GET' && $route === '/api/admin/categories') {
    require_auth();
    json_out($pdo->query("SELECT id, name, slug, icon FROM categories ORDER BY id")->fetchAll());
}

if ($method === 'POST' && $route === '/api/admin/categories') {
    require_auth();
    if (empty(trim($input['name'] ?? '')) || empty(trim($input['slug'] ?? ''))) err('Name and slug are required.');
    $pdo->prepare("INSERT INTO categories(name, slug, icon) VALUES(?,?,?)")
        ->execute([$input['name'], $input['slug'], $input['icon'] ?: '📘']);
    json_out(['ok' => true]);
}

if ($method === 'DELETE' && preg_match('#^/api/admin/categories/(\d+)$#', $route, $m)) {
    require_auth();
    $id = (int)$m[1];
    $stmt = $pdo->prepare("SELECT COUNT(*) AS n FROM courses WHERE category_id = ?");
    $stmt->execute([$id]);
    if ((int)$stmt->fetch()['n'] > 0) err('Category has courses — move or delete them first.');
    $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
    json_out(['ok' => true]);
}

err('Route not found: ' . $route, 404);
