<?php

//db
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'credinews');

//app settings
define('APP_NAME', 'CrediNews');
define('APP_URL', 'http://localhost/credinews');
define('EMAIL_FROM', 'noreply@credinews.com');

//
define('ENCRYPTION_KEY', 'your_secure_encryption_key_here');
define('HASH_ALGORITHM', 'sha256');

//limit ng submissions
define('MAX_SUBMISSIONS_PER_DAY', 5);
define('SUBMISSION_TIMEOUT', 3600); 

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function encryptData($data) {
    $method = "AES-256-CBC";
    $key = hash('sha256', ENCRYPTION_KEY, true);
    $iv = openssl_random_pseudo_bytes(16);
    $encrypted = openssl_encrypt($data, $method, $key, 0, $iv);
    $encryptedData = base64_encode($encrypted . '::' . base64_encode($iv));
    $timestamp = time();
    return $encryptedData . '::' . $timestamp;
}

function decryptData($data) {
    $method = "AES-256-CBC";
    $key = hash('sha256', ENCRYPTION_KEY, true);
    $parts = explode('::', $data, 2);
    if (count($parts) > 1 && is_numeric($parts[1])) {
        $data = $parts[0]; 
    }
    
    list($encrypted_data, $iv_encoded) = explode('::', base64_decode($data), 2);
    $iv = base64_decode($iv_encoded);
    
    return openssl_decrypt($encrypted_data, $method, $key, 0, $iv);
}

//digital sig ng reports
function generateSignature($data) {
    return hash(HASH_ALGORITHM, $data . ENCRYPTION_KEY);
}
function verifySignature($data, $signature) {
    return hash(HASH_ALGORITHM, $data . ENCRYPTION_KEY) === $signature;
}

//log user
function logAction($userId, $action, $entityType = null, $entityId = null) {
    global $conn;
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, entity_type, entity_id, ip_address) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $userId, $action, $entityType, $entityId, $ip);
    $stmt->execute();
    $stmt->close();
}
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}
function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}
function isAdminOrReviewer() {
    return isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'reviewer');
}
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}
function redirect($url, $message = '', $type = 'info') {
    if (!empty($message)) {
        $_SESSION['message'] = $message;
        $_SESSION['message_type'] = $type;
    }
    header("Location: $url");
    exit();
}

//smtp email config nyaaa
define('SMTP_HOST', 'sandbox.smtp.mailtrap.io');
define('SMTP_AUTH', true);
define('SMTP_USERNAME', '351820009a6cd0');
define('SMTP_PASSWORD', '8fea7d4e301523');
define('SMTP_SECURE', 'tls');
define('SMTP_PORT', 587);
?>