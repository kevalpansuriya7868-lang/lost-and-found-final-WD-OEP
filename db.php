<?php
/**
 * FindIt - Core Database, Configuration, Authentication & Helper Services
 * Consolidates configuration, database operations, session authentication,
 * utility functions, and layout renderers into a unified, high-performance module.
 */

// 1. Error Reporting & Environment Settings
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 2. Application Constants
define('APP_NAME', 'FindIt Portal');
define('APP_TAGLINE', 'Reconnecting people with their lost belongings');
define('APP_URL', '');

// Database Configuration (Default: SQLite for zero-configuration, MySQL optional)
define('DB_DRIVER', 'sqlite');
define('DB_HOST', 'localhost');
define('DB_NAME', 'lost_and_found');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_PORT', 3306);
define('SQLITE_FILE', __DIR__ . '/lost_and_found.db');

// Upload directory & file upload limits
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_WEB_PATH', 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp', 'gif']);

// Ensure uploads folder exists
if (!file_exists(UPLOAD_DIR)) {
    @mkdir(UPLOAD_DIR, 0777, true);
}

// 3. Start PHP Session Safely
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

// ==========================================
// DATABASE CONNECTION & INITIALIZATION
// ==========================================

function getDbConnection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    try {
        if (DB_DRIVER === 'sqlite') {
            $pdo = new PDO('sqlite:' . SQLITE_FILE);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->exec("PRAGMA foreign_keys = ON;");
            initSqliteDatabase($pdo);
        } else {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        }
    } catch (PDOException $e) {
        die("<div style='font-family:sans-serif;padding:24px;background:#fef2f2;border:1px solid #f87171;color:#991b1b;border-radius:10px;margin:30px auto;max-width:650px;box-shadow:0 4px 6px rgba(0,0,0,0.05);'>
            <h2 style='margin-top:0;'>Database Connection Failed</h2>
            <p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
            <p>Please make sure SQLite or MySQL is properly configured.</p>
        </div>");
    }

    return $pdo;
}

function initSqliteDatabase($pdo) {
    // 1. Create users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            role TEXT DEFAULT 'user',
            phone TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // 2. Create items table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            type TEXT NOT NULL,
            title TEXT NOT NULL,
            category TEXT NOT NULL,
            description TEXT NOT NULL,
            location TEXT NOT NULL,
            item_date TEXT NOT NULL,
            image_path TEXT,
            contact_name TEXT NOT NULL,
            contact_email TEXT NOT NULL,
            contact_phone TEXT,
            status TEXT DEFAULT 'active',
            security_secret TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        );
    ");

    // 3. Create claims table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS claims (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            item_id INTEGER NOT NULL,
            claimant_id INTEGER,
            claimant_name TEXT NOT NULL,
            claimant_email TEXT NOT NULL,
            claimant_phone TEXT,
            claim_message TEXT NOT NULL,
            status TEXT DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
            FOREIGN KEY (claimant_id) REFERENCES users(id) ON DELETE SET NULL
        );
    ");

    // Seed default users if empty
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($userCount == 0) {
        seedUsersAndData($pdo);
    }
}

function seedUsersAndData($pdo) {
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $userPassword = password_hash('password123', PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, phone) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['System Administrator', 'admin@findit.local', $adminPassword, 'admin', '+1 (555) 000-1111']);
    $adminId = $pdo->lastInsertId();

    $stmt->execute(['John Doe', 'john@example.com', $userPassword, 'user', '+1 (555) 234-5678']);
    $johnId = $pdo->lastInsertId();

    // Sample Items
    $items = [
        [
            'user_id' => $johnId,
            'type' => 'found',
            'title' => 'Apple iPhone 13 (Midnight Blue)',
            'category' => 'electronics',
            'description' => 'Found on a wooden bench near the central courtyard gazebo. Has a transparent silicone case with a holographic sticker. Battery is charged, currently stored safely.',
            'location' => 'Central Courtyard - East Pavilion',
            'item_date' => date('Y-m-d', strtotime('-2 days')),
            'image_path' => '',
            'contact_name' => 'John Doe',
            'contact_email' => 'john@example.com',
            'contact_phone' => '+1 (555) 234-5678',
            'status' => 'active',
            'security_secret' => bin2hex(random_bytes(8))
        ],
        [
            'user_id' => $johnId,
            'type' => 'lost',
            'title' => 'Brown Leather Bi-fold Wallet',
            'category' => 'wallets',
            'description' => 'Lost my vintage brown leather wallet during lunch hour. Contains driver license under name John Doe, metro pass, and some family photos. Huge sentimental value.',
            'location' => 'University Cafeteria, Booth 12',
            'item_date' => date('Y-m-d', strtotime('-3 days')),
            'image_path' => '',
            'contact_name' => 'John Doe',
            'contact_email' => 'john@example.com',
            'contact_phone' => '+1 (555) 234-5678',
            'status' => 'active',
            'security_secret' => bin2hex(random_bytes(8))
        ],
        [
            'user_id' => $adminId,
            'type' => 'found',
            'title' => 'Set of Car Keys with Honda Keychain',
            'category' => 'keys',
            'description' => 'Found black key fob with Honda logo and two silver keys attached to a red lanyard in parking sector B.',
            'location' => 'North Parking Garage Level 2',
            'item_date' => date('Y-m-d', strtotime('-1 day')),
            'image_path' => '',
            'contact_name' => 'Security Front Desk',
            'contact_email' => 'admin@findit.local',
            'contact_phone' => '+1 (555) 000-1111',
            'status' => 'active',
            'security_secret' => bin2hex(random_bytes(8))
        ],
        [
            'user_id' => $adminId,
            'type' => 'lost',
            'title' => 'Golden Retriever Puppy (Buddy)',
            'category' => 'pets',
            'description' => 'Buddy is 8 months old, wearing a blue collar with a bell. Last seen near Oakridge Park entrance. Very energetic and friendly.',
            'location' => 'Oakridge Neighborhood / Pine St',
            'item_date' => date('Y-m-d', strtotime('-1 day')),
            'image_path' => '',
            'contact_name' => 'Elena Rostova',
            'contact_email' => 'elena@example.com',
            'contact_phone' => '+1 (555) 012-7788',
            'status' => 'active',
            'security_secret' => bin2hex(random_bytes(8))
        ],
        [
            'user_id' => $adminId,
            'type' => 'found',
            'title' => 'Black Ray-Ban Aviator Sunglasses',
            'category' => 'clothing',
            'description' => 'Found in a protective hard case on table 4 at the main cafe. Gold frames with polarized dark lenses.',
            'location' => 'Main Street Coffee Bar',
            'item_date' => date('Y-m-d', strtotime('-4 days')),
            'image_path' => '',
            'contact_name' => 'Cafe Manager',
            'contact_email' => 'cafe@example.com',
            'contact_phone' => '+1 (555) 018-9900',
            'status' => 'active',
            'security_secret' => bin2hex(random_bytes(8))
        ],
        [
            'user_id' => $johnId,
            'type' => 'found',
            'title' => 'Calculus & Linear Algebra Textbook (9th Ed)',
            'category' => 'books',
            'description' => 'Hardcover university math textbook left on row 3 desk. Has yellow highlighter markings in Chapter 4.',
            'location' => 'Science Complex, Lecture Hall B',
            'item_date' => date('Y-m-d', strtotime('-5 days')),
            'image_path' => '',
            'contact_name' => 'Department Helpdesk',
            'contact_email' => 'helpdesk@example.com',
            'contact_phone' => '+1 (555) 011-3322',
            'status' => 'claimed',
            'security_secret' => bin2hex(random_bytes(8))
        ]
    ];

    $itemStmt = $pdo->prepare("
        INSERT INTO items (user_id, type, title, category, description, location, item_date, image_path, contact_name, contact_email, contact_phone, status, security_secret)
        VALUES (:user_id, :type, :title, :category, :description, :location, :item_date, :image_path, :contact_name, :contact_email, :contact_phone, :status, :security_secret)
    ");

    foreach ($items as $item) {
        $itemStmt->execute($item);
    }
}

// ==========================================
// AUTHENTICATION & ACCESS CONTROL HELPERS
// ==========================================

function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function current_user() {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'    => $_SESSION['user_id'],
        'name'  => $_SESSION['user_name'] ?? 'User',
        'email' => $_SESSION['user_email'] ?? '',
        'role'  => $_SESSION['user_role'] ?? 'user'
    ];
}

function is_admin() {
    return is_logged_in() && (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');
}

/**
 * Enforces compulsory login. Redirects unauthenticated visitors to login.php.
 */
function require_login($redirect = null) {
    if (!is_logged_in()) {
        $target = $redirect ? urlencode($redirect) : urlencode($_SERVER['REQUEST_URI'] ?? 'index.php');
        set_flash('danger', 'You must log in to access this page.');
        header("Location: login.php?redirect=" . $target);
        exit;
    }
}

/**
 * Enforces admin-only access.
 */
function require_admin() {
    require_login();
    if (!is_admin()) {
        set_flash('danger', 'Access denied. Administrator privileges required.');
        header("Location: index.php");
        exit;
    }
}

function login_user($user) {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'] ?? 'user';
}

function logout_user() {
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE && ini_get("session.use_cookies") && !headers_sent()) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

/**
 * Checks whether the current user is permitted to manage (edit/delete/update status) an item.
 */
function can_manage_item($item) {
    if (!is_logged_in()) {
        return false;
    }
    $user = current_user();
    if ($user['role'] === 'admin') {
        return true;
    }
    return isset($item['user_id']) && ((int)$item['user_id'] === (int)$user['id']);
}

// ==========================================
// FLASH NOTIFICATIONS & CSRF SECURITY
// ==========================================

function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ==========================================
// SVG VECTOR ICONS SYSTEM (Zero Emojis)
// ==========================================

function get_svg_icon($name, $classes = 'svg-icon', $size = 18) {
    $classesAttr = htmlspecialchars($classes);
    $size = (int)$size;
    $s = "class='{$classesAttr}' width='{$size}' height='{$size}' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'";
    switch ($name) {
        case 'search':
            return "<svg {$s}><circle cx='11' cy='11' r='8'></circle><line x1='21' y1='21' x2='16.65' y2='16.65'></line></svg>";
        case 'map-pin':
            return "<svg {$s}><path d='M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z'></path><circle cx='12' cy='10' r='3'></circle></svg>";
        case 'calendar':
            return "<svg {$s}><rect x='3' y='4' width='18' height='18' rx='2' ry='2'></rect><line x1='16' y1='2' x2='16' y2='6'></line><line x1='8' y1='2' x2='8' y2='6'></line><line x1='3' y1='10' x2='21' y2='10'></line></svg>";
        case 'box':
        case 'package':
            return "<svg {$s}><line x1='16.5' y1='9.4' x2='7.5' y2='4.21'></line><path d='M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z'></path><polyline points='3.27 6.96 12 12.01 20.73 6.96'></polyline><line x1='12' y1='22.08' x2='12' y2='12'></line></svg>";
        case 'edit':
            return "<svg {$s}><path d='M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7'></path><path d='M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z'></path></svg>";
        case 'eye':
            return "<svg {$s}><path d='M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z'></path><circle cx='12' cy='12' r='3'></circle></svg>";
        case 'trash':
            return "<svg {$s}><polyline points='3 6 5 6 21 6'></polyline><path d='M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2'></path></svg>";
        case 'check':
            return "<svg {$s} stroke-width='2.5'><polyline points='20 6 9 17 4 12'></polyline></svg>";
        case 'close':
        case 'x':
            return "<svg {$s} stroke-width='2.5'><line x1='18' y1='6' x2='6' y2='18'></line><line x1='6' y1='6' x2='18' y2='18'></line></svg>";
        case 'refresh':
            return "<svg {$s}><polyline points='23 4 23 10 17 10'></polyline><polyline points='1 20 1 14 7 14'></polyline><path d='M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15'></path></svg>";
        case 'inbox':
            return "<svg {$s}><polyline points='22 12 16 12 14 15 10 15 8 12 2 12'></polyline><path d='M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z'></path></svg>";
        case 'users':
            return "<svg {$s}><path d='M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2'></path><circle cx='9' cy='7' r='4'></circle><path d='M23 21v-2a4 4 0 0 0-3-3.87'></path><path d='M16 3.13a4 4 0 0 1 0 7.75'></path></svg>";
        case 'user':
            return "<svg {$s}><path d='M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2'></path><circle cx='12' cy='7' r='4'></circle></svg>";
        case 'shield':
            return "<svg {$s}><path d='M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z'></path></svg>";
        case 'lock':
            return "<svg {$s}><rect x='3' y='11' width='18' height='11' rx='2' ry='2'></rect><path d='M7 11V7a5 5 0 0 1 10 0v4'></path></svg>";
        case 'camera':
            return "<svg {$s}><path d='M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z'></path><circle cx='12' cy='13' r='4'></circle></svg>";
        case 'save':
            return "<svg {$s}><path d='M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z'></path><polyline points='17 21 17 13 7 13 7 21'></polyline><polyline points='7 3 7 8 15 8'></polyline></svg>";
        case 'send':
            return "<svg {$s}><line x1='22' y1='2' x2='11' y2='13'></line><polygon points='22 2 15 22 11 13 2 9 22 2'></polygon></svg>";
        case 'sparkles':
            return "<svg {$s}><path d='m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z'></path></svg>";
        case 'handshake':
            return "<svg {$s}><path d='m11 17 2 2a1 1 0 0 0 1.4 0l4.3-4.3a1 1 0 0 0 0-1.4L16 11'></path><path d='m18 13 3.3-3.3a1 1 0 0 0 0-1.4l-4.6-4.6a1 1 0 0 0-1.4 0L12 7'></path><path d='m2 14 4.6 4.6a1 1 0 0 0 1.4 0l4-4'></path><path d='m6 10-3.3 3.3a1 1 0 0 0 0 1.4l4.6 4.6a1 1 0 0 0 1.4 0L12 16'></path></svg>";
        case 'tools':
            return "<svg {$s}><path d='M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z'></path></svg>";
        case 'clipboard':
            return "<svg {$s}><path d='M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2'></path><rect x='8' y='2' width='8' height='4' rx='1' ry='1'></rect></svg>";
        case 'sun':
            return "<svg {$s}><circle cx='12' cy='12' r='5'></circle><line x1='12' y1='1' x2='12' y2='3'></line><line x1='12' y1='21' x2='12' y2='23'></line><line x1='4.22' y1='4.22' x2='5.64' y2='5.64'></line><line x1='18.36' y1='18.36' x2='19.78' y2='19.78'></line><line x1='1' y1='12' x2='3' y2='12'></line><line x1='21' y1='12' x2='23' y2='12'></line><line x1='4.22' y1='19.78' x2='5.64' y2='18.36'></line><line x1='18.36' y1='5.64' x2='19.78' y2='4.22'></line></svg>";
        case 'moon':
            return "<svg {$s}><path d='M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z'></path></svg>";
        case 'alert':
            return "<svg {$s}><circle cx='12' cy='12' r='10'></circle><line x1='12' y1='8' x2='12' y2='12'></line><line x1='12' y1='16' x2='12.01' y2='16'></line></svg>";
        case 'info':
        default:
            return "<svg {$s}><circle cx='12' cy='12' r='10'></circle><line x1='12' y1='16' x2='12' y2='12'></line><line x1='12' y1='8' x2='12.01' y2='8'></line></svg>";
    }
}

function render_flash() {
    $flash = get_flash();
    if ($flash) {
        $type = htmlspecialchars($flash['type']);
        $msg = htmlspecialchars($flash['message']);
        $iconSvg = $type === 'success' ? get_svg_icon('check', 'alert-svg', 16) : ($type === 'danger' ? get_svg_icon('alert', 'alert-svg', 16) : get_svg_icon('info', 'alert-svg', 16));
        echo "
        <div class='alert alert-{$type}'>
            <span class='alert-icon'>{$iconSvg}</span>
            <div class='alert-content'>{$msg}</div>
            <button type='button' class='alert-close' onclick='this.parentElement.remove()' aria-label='Close alert'>&times;</button>
        </div>";
    }
}

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

function verify_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function sanitize($data) {
    return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
}

// ==========================================
// CATEGORIES & FORMATTING UTILITIES
// ==========================================

function get_categories() {
    return [
        'electronics' => ['label' => 'Electronics & Gadgets', 'icon' => ''],
        'wallets'     => ['label' => 'Wallets & Purses',     'icon' => ''],
        'keys'        => ['label' => 'Keys & Keychains',     'icon' => ''],
        'documents'   => ['label' => 'Cards & Documents',    'icon' => ''],
        'clothing'    => ['label' => 'Clothing & Eyewear',   'icon' => ''],
        'pets'        => ['label' => 'Pets & Animals',       'icon' => ''],
        'books'       => ['label' => 'Books & Notebooks',    'icon' => ''],
        'jewelry'     => ['label' => 'Jewelry & Watches',    'icon' => ''],
        'other'       => ['label' => 'Other Belongings',     'icon' => '']
    ];
}

function get_category_label($key) {
    $cats = get_categories();
    return $cats[$key]['label'] ?? ucfirst($key);
}

function get_category_icon($key) {
    return '';
}

function time_ago($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' mins ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('M d, Y', $time);
}

function handle_image_upload($file_input_name) {
    if (!isset($_FILES[$file_input_name]) || $_FILES[$file_input_name]['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'filename' => null];
    }

    $file = $_FILES[$file_input_name];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'File upload error code: ' . $file['error']];
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'File size exceeds maximum limit of 5MB.'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'error' => 'Invalid file format. Allowed: ' . implode(', ', ALLOWED_EXTENSIONS)];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($mime, $allowedMimes)) {
        return ['success' => false, 'error' => 'Invalid image content type.'];
    }

    $uniqueName = 'item_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $targetPath = UPLOAD_DIR . $uniqueName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => false, 'error' => 'Failed to save uploaded image to destination directory.'];
    }

    return ['success' => true, 'filename' => $uniqueName];
}

// ==========================================
// GLOBAL LAYOUT RENDERERS (Header & Footer)
// ==========================================

function render_header($pageTitle = '', $activeTab = '') {
    $fullTitle = !empty($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' . APP_NAME : APP_NAME;
    $user = current_user();
    $currentPage = basename($_SERVER['PHP_SELF'] ?? 'index.php');
    $shouldPlayScan = !empty($_SESSION['login_animation']) || isset($_GET['scan']);
    if (!empty($_SESSION['login_animation'])) {
        unset($_SESSION['login_animation']);
    }
    ?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $fullTitle ?></title>
    <meta name="description" content="Centralized Lost and Found Portal - Reconnecting people with their lost belongings quickly and safely.">
    <!-- Google Fonts for World-Class Typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <script>
        // Apply saved theme immediately to prevent flash
        (function() {
            var saved = localStorage.getItem('findit_theme') || 'dark';
            document.documentElement.setAttribute('data-theme', saved);
        })();
    </script>
</head>
<body>
    <!-- 5-Second Magnifying Glass Login Reveal Animation Overlay -->
    <div id="loginScanOverlay" class="login-scan-overlay <?= $shouldPlayScan ? 'auto-trigger' : '' ?>" style="display: <?= $shouldPlayScan ? 'block' : 'none' ?>;" aria-hidden="true">
        <div class="scan-blur-backdrop"></div>
        <div class="scan-lens-unit" id="scanLensUnit">
            <div class="scan-lens-glass">
                <div class="scan-reticle"></div>
                <div class="scan-light-flare"></div>
            </div>
            <div class="scan-frame-ring"></div>
            <div class="scan-handle"></div>
            <div class="scan-hud-tag" id="scanHudTag">
                <span class="hud-pulse-dot"></span>
                <span class="hud-text">SCANNING PORTAL</span>
                <span class="hud-timer" id="scanTimerCount">5.0s</span>
            </div>
        </div>
        <div class="scan-pulse-ring" id="scanPulseRing"></div>
        <button type="button" class="scan-skip-btn" id="scanSkipBtn" title="Skip animation">Skip Scan &times;</button>
    </div>

    <!-- Main Navigation Header -->
    <header class="header">
        <div class="nav-container">
            <a href="index.php" class="logo">
                <div class="logo-icon">F</div>
                <span class="logo-text">FindIt</span>
            </a>

            <!-- Mobile Navigation Toggle -->
            <button class="mobile-toggle" id="mobileMenuBtn" aria-label="Toggle navigation">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <nav class="nav-menu" id="navMenu">
                <ul class="nav-links">
                    <li>
                        <a href="index.php" class="nav-link <?= ($currentPage === 'index.php') ? 'active' : '' ?>">Home</a>
                    </li>
                    <li>
                        <a href="browse.php" class="nav-link <?= ($currentPage === 'browse.php') ? 'active' : '' ?>">Browse &amp; Search</a>
                    </li>
                    <li>
                        <a href="report.php" class="btn btn-primary btn-sm">
                            <span class="btn-icon"><?= get_svg_icon('edit', 'svg-icon', 14) ?></span> Report Item
                        </a>
                    </li>

                    <?php if (is_logged_in()): ?>
                        <li>
                            <a href="dashboard.php" class="nav-link <?= ($currentPage === 'dashboard.php') ? 'active' : '' ?>">
                                My Dashboard
                            </a>
                        </li>
                        <?php if (is_admin()): ?>
                            <li>
                                <a href="admin.php" class="nav-link <?= ($currentPage === 'admin.php') ? 'active' : '' ?>">
                                    <span class="admin-badge">Admin</span>
                                </a>
                            </li>
                        <?php endif; ?>
                        <li class="user-pill-container">
                            <span class="user-greeting">Hi, <strong><?= htmlspecialchars($user['name']) ?></strong></span>
                            <a href="login.php?action=logout" class="btn btn-secondary btn-sm" title="Log Out">Log Out</a>
                        </li>
                    <?php else: ?>
                        <li>
                            <a href="login.php" class="nav-link <?= ($currentPage === 'login.php' && (!isset($_GET['action']) || $_GET['action'] !== 'register')) ? 'active' : '' ?>">Sign In</a>
                        </li>
                        <li>
                            <a href="login.php?action=register" class="btn btn-secondary btn-sm <?= ($currentPage === 'login.php' && isset($_GET['action']) && $_GET['action'] === 'register') ? 'active' : '' ?>">Register</a>
                        </li>
                    <?php endif; ?>

                    <!-- Theme Toggle & Animation Replay Trigger -->
                    <li class="nav-utility-item">
                        <button type="button" class="theme-toggle-btn" id="themeToggleBtn" aria-label="Toggle color theme" title="Toggle theme">
                            <span class="theme-icon-dark"><?= get_svg_icon('moon', 'svg-icon', 16) ?></span>
                            <span class="theme-icon-light"><?= get_svg_icon('sun', 'svg-icon', 16) ?></span>
                        </button>
                    </li>
                    <li class="nav-utility-item">
                        <button type="button" class="scan-replay-btn" id="replayScanBtn" title="Replay 5s Magnifying Glass Scan Animation" aria-label="Replay Scan Animation">
                            <?= get_svg_icon('search', 'svg-icon', 15) ?>
                            <span class="scan-btn-label">Scan Effect</span>
                        </button>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Global Notification Banner Container -->
    <div class="flash-wrapper">
        <?php render_flash(); ?>
    </div>
    <?php
}

function render_footer() {
    $categories = get_categories();
    ?>
    <!-- Global Footer -->
    <footer class="footer">
        <div class="footer-top">
            <div class="footer-col">
                <div class="footer-logo">
                    <span class="logo-icon">F</span>
                    <strong>FindIt Portal</strong>
                </div>
                <p class="footer-tagline">
                    An open community platform dedicated to helping people recover lost items, report discovered belongings, and connect securely.
                </p>
            </div>

            <div class="footer-col">
                <h4>Quick Navigation</h4>
                <ul class="footer-links">
                    <li><a href="index.php">Home Feed</a></li>
                    <li><a href="browse.php">Search &amp; Browse Catalog</a></li>
                    <li><a href="report.php">Report Lost/Found Item</a></li>
                    <li><a href="dashboard.php">User Dashboard</a></li>
                    <?php if (is_admin()): ?>
                        <li><a href="admin.php">Admin Management</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Top Categories</h4>
                <ul class="footer-links">
                    <?php 
                    $counter = 0;
                    foreach ($categories as $slug => $cat): 
                        if (++$counter > 5) break;
                    ?>
                        <li>
                            <a href="browse.php?category=<?= urlencode($slug) ?>">
                                <?= htmlspecialchars($cat['label']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Safety &amp; Security</h4>
                <p class="footer-text">
                    Always arrange exchanges in public, well-lit spaces like security desks or public lobbies. Verify ownership proof before handing over valuables.
                </p>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="footer-container">
                <p>&copy; <?= date('Y') ?> FindIt Lost &amp; Found Portal. All rights reserved.</p>
                <div class="footer-bottom-links">
                    <a href="browse.php?type=lost">Lost Items</a>
                    <a href="browse.php?type=found">Found Items</a>
                    <a href="browse.php?type=claimed">Resolved</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Global Scripts -->
    <script src="js/validation.js"></script>
    <script src="js/app.js"></script>
</body>
</html>
    <?php
}
