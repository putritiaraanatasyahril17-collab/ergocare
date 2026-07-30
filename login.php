<?php
include 'config/database.php';

$error = '';

if ($_POST) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    
    $query = "SELECT u.*, e.full_name, e.department_id, d.department_name 
              FROM users u 
              JOIN employees e ON u.employee_id = e.employee_id 
              JOIN departments d ON e.department_id = d.department_id 
              WHERE u.username = '$username' AND u.status = 'Aktif'";
    $result = mysqli_query($conn, $query);
    $user = mysqli_fetch_assoc($result);
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['employee_id'] = $user['employee_id'];
        $_SESSION['nama'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['department'] = $user['department_name'];
        
        mysqli_query($conn, "UPDATE users SET last_login = NOW() WHERE user_id = {$user['user_id']}");
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        mysqli_query($conn, "INSERT INTO login_logs (user_id, login_time, ip_address, user_agent, login_status) 
                             VALUES ({$user['user_id']}, NOW(), '$ip', '$agent', 'Berhasil')");
        
        if ($user['role'] == 'HSE') {
            header('Location: hse/dashboard.php');
        } else {
            header('Location: karyawan/dashboard.php');
        }
        exit();
    } else {
        $error = "Username atau password salah, atau akun Anda tidak aktif.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ERGONOMI &amp; PSIKOSOSIAL TES</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="login-page">
    <div class="login-card">
        <div class="login-brand">
            <?php if (file_exists('assets/img/logo.png')): ?>
                <img src="assets/img/logo.png" alt="Logo" style="height:60px; margin-bottom:12px;">
            <?php endif; ?>
            <h2 style="font-size:24px; font-weight:800; color:#fff; margin:0;">
                ERGONOMI &amp; PSIKOSOSIAL TES
            </h2>
            <p style="font-size:13px; color:#4fc3f7; letter-spacing:2px; margin:4px 0 0 0;">PT Radiant Group Cabang Duri</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger" style="background:rgba(239,68,68,0.15); border:1px solid rgba(239,68,68,0.2); color:#fca5a5; border-radius:12px; padding:12px 16px; font-size:14px;">
                <i class="fas fa-exclamation-circle me-2"></i> <?= $error ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label><i class="fas fa-user me-2"></i> Username</label>
                <input type="text" name="username" placeholder="Masukkan username" required>
            </div>
            <div class="form-group">
                <label><i class="fas fa-lock me-2"></i> Password</label>
                <input type="password" name="password" placeholder="Masukkan password" required>
            </div>
            <button type="submit" class="btn-login-submit">
                <i class="fas fa-arrow-right-to-bracket me-2"></i> Login
            </button>
        </form>
        
        <div class="login-footer">
            <p>ERGONOMI &amp; PSIKOSOSIAL TES </p>
            <p style="font-size:11px; opacity:0.5; margin-top:4px;">
                Demo: admin / admin123 | EMP001 / password123
            </p>
        </div>
    </div>
</div>
</body>
</html>