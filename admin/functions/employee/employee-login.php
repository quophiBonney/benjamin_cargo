<?php
session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    include_once __DIR__ . '/../../../includes/dbconnection.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode([
            'success' => false,
            'errors' => ['Invalid request method.']
        ]);
        exit;
    }

    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        echo json_encode([
            'success' => false,
            'errors' => ['Both email and password are required.']
        ]);
        exit;
    }

    $stmt = $dbh->prepare("SELECT * FROM employees WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {
        // Make sure this function does NOT echo
        if (function_exists('log_login_attempt')) {
            log_login_attempt($dbh, $email, 'failed', 'Invalid login attempt.');
        }

        echo json_encode([
            'success' => false,
            'errors' => ['Invalid email or password.']
        ]);
        exit;
    }

    // Set session
    $_SESSION['employee_id'] = $user['employee_id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = strtolower(trim($user['role']));
    session_write_close();

    // Update last login
    $updateLogin = $dbh->prepare("UPDATE employees SET last_login = NOW() WHERE employee_id = :employee_id");
    $updateLogin->execute([':employee_id' => $user['employee_id']]);

    echo json_encode([
        'success' => true,
        'message' => 'Login successful!',
        'role' => $_SESSION['role'],
        'redirect' => in_array($_SESSION['role'], ['admin', 'manager', 'accountant'])
            ? 'dashboard.php'
            : 'staffs-dashboard.php'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'errors' => ['Server error: ' . $e->getMessage()]
    ]);
}
exit;
?>
