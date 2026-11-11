<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/audience.php';

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $opt = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
    try {
        if (DB_DRIVER === 'sqlite') {
            $pdo = new PDO('sqlite:' . SQLITE_FILE, null, null, $opt);
            $pdo->exec('PRAGMA foreign_keys = OFF');
        } else {
            $tmp = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS, $opt);
            $tmp->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, $opt);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        $hint = 'XAMPP me Apache aur MySQL start karein. Agar error \"could not find driver\" aaye, XAMPP Control Panel > Apache > Config > PHP (php.ini) khol kar extension=pdo_mysql aur extension=mysqli ki lines enable karein, phir Apache restart karein. Agar pdo_mysql available nahi lekin pdo_sqlite available hai, config.php automatic SQLite use karega.';
        die('<body style="font-family:sans-serif;padding:40px"><h2>Database connection failed</h2><p>' . htmlspecialchars($hint) . '</p><pre>' . htmlspecialchars($e->getMessage()) . '</pre></body>');
    }
    install_schema($pdo);
    return $pdo;
}

function schema_sql(string $driver): array {
    $pk = $driver === 'sqlite' ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT AUTO_INCREMENT PRIMARY KEY';
    $eng = $driver === 'sqlite' ? '' : ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
    return [
"CREATE TABLE IF NOT EXISTS users (id $pk, name VARCHAR(120) NOT NULL, username VARCHAR(60) NOT NULL UNIQUE, email VARCHAR(150) NOT NULL UNIQUE, phone VARCHAR(30), password VARCHAR(255) NOT NULL, role VARCHAR(20) NOT NULL DEFAULT 'client', gender VARCHAR(10), address VARCHAR(255), dob VARCHAR(12), pref_stylist_id INT NULL, pref_service_id INT NULL, notes TEXT, status INT NOT NULL DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)$eng",
"CREATE TABLE IF NOT EXISTS stylist_profiles (user_id INT PRIMARY KEY, title VARCHAR(100), specialties VARCHAR(255), bio TEXT, photo VARCHAR(255), experience INT DEFAULT 1, commission_rate INT DEFAULT 20, work_days VARCHAR(30) DEFAULT '1,2,3,4,5,6', start_time VARCHAR(5) DEFAULT '10:00', end_time VARCHAR(5) DEFAULT '19:00', shift_note VARCHAR(255))$eng",
"CREATE TABLE IF NOT EXISTS services (id $pk, name VARCHAR(120) NOT NULL, category VARCHAR(60), description TEXT, benefits VARCHAR(255), price INT NOT NULL, duration INT NOT NULL DEFAULT 60, image VARCHAR(255), icon VARCHAR(40), audience VARCHAR(10) NOT NULL DEFAULT 'women', active INT NOT NULL DEFAULT 1)$eng",
"CREATE TABLE IF NOT EXISTS stylist_services (stylist_id INT NOT NULL, service_id INT NOT NULL, PRIMARY KEY (stylist_id, service_id))$eng",
"CREATE TABLE IF NOT EXISTS appointments (id $pk, ref VARCHAR(20), client_id INT NOT NULL, stylist_id INT NOT NULL, service_id INT NOT NULL, appt_date VARCHAR(10) NOT NULL, appt_time VARCHAR(5) NOT NULL, end_time VARCHAR(5) NOT NULL, price INT NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'confirmed', notes TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)$eng",
"CREATE TABLE IF NOT EXISTS payments (id $pk, appointment_id INT NOT NULL, invoice_no VARCHAR(20), amount INT NOT NULL, method VARCHAR(20) DEFAULT 'cash', status VARCHAR(20) DEFAULT 'paid', paid_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)$eng",
"CREATE TABLE IF NOT EXISTS feedback (id $pk, appointment_id INT NOT NULL UNIQUE, client_id INT NOT NULL, rating INT NOT NULL, comment TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)$eng",
"CREATE TABLE IF NOT EXISTS notifications (id $pk, user_id INT NOT NULL, title VARCHAR(150), message TEXT, type VARCHAR(20) DEFAULT 'info', is_read INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)$eng",
"CREATE TABLE IF NOT EXISTS suppliers (id $pk, name VARCHAR(120) NOT NULL, phone VARCHAR(30), email VARCHAR(150), address VARCHAR(255))$eng",
"CREATE TABLE IF NOT EXISTS inventory (id $pk, name VARCHAR(120) NOT NULL, category VARCHAR(60), sku VARCHAR(30), quantity INT NOT NULL DEFAULT 0, reorder_level INT NOT NULL DEFAULT 5, unit_cost INT NOT NULL DEFAULT 0, supplier_id INT NULL)$eng",
"CREATE TABLE IF NOT EXISTS purchase_orders (id $pk, item_id INT NOT NULL, supplier_id INT NULL, qty INT NOT NULL, total_cost INT NOT NULL, status VARCHAR(20) DEFAULT 'ordered', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)$eng",
"CREATE TABLE IF NOT EXISTS messages (id $pk, name VARCHAR(120), email VARCHAR(150), subject VARCHAR(150), message TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)$eng",
    ];
}

function install_schema(PDO $pdo): void {
    try { $pdo->query('SELECT 1 FROM users LIMIT 1'); $has = true; } catch (Throwable $e) { $has = false; }
    if (!$has) foreach (schema_sql(DB_DRIVER) as $q) $pdo->exec($q);
    if ((int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0) { require_once __DIR__ . '/seed.php'; seed_demo($pdo); }
    migrate_audience($pdo); // adds Men/Women support to older databases
    require_once __DIR__ . '/images.php'; migrate_images($pdo);
}
