<?php
// ============================================================
// CEK LOGIN
// ============================================================
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// ============================================================
// HITUNG NOTIFIKASI UNTUK BADGE
// ============================================================
$total_notif = 0;
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    $role = $_SESSION['role'];
    $target = ($role == 'HSE') ? 'HSE' : 'Karyawan';
    
    $query_notif_count = "SELECT COUNT(*) as total FROM notifications 
                          WHERE status = 'Aktif' 
                          AND (target_role = 'Semua' OR target_role = '$target')
                          AND (start_date IS NULL OR start_date <= CURDATE())
                          AND (end_date IS NULL OR end_date >= CURDATE())";
    $result_notif_count = mysqli_query($conn, $query_notif_count);
    if ($result_notif_count) {
        $data_notif = mysqli_fetch_assoc($result_notif_count);
        $total_notif = $data_notif['total'] ?? 0;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ERGONOMI &amp; PSIKOSOSIAL TES - <?= $page_title ?? 'Dashboard' ?></title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= $base_url ?? '../' ?>assets/css/style.css">
</head>
<body>

<!-- ============================================================
SIDEBAR
============================================================ -->
<nav class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <?php if (file_exists('../assets/img/logo.png')): ?>
            <img src="../assets/img/logo.png" alt="Logo" style="height:35px; margin-bottom:4px; display:block; margin-left:auto; margin-right:auto;">
        <?php endif; ?>
        
        <!-- RADIANT GROUP -->
        <small style="font-size:10px; font-weight:700; color:#4fc3f7; letter-spacing:3px; display:block; text-align:center;">
            RADIANT GROUP
        </small>
        
        <!-- ERGONOMI & PSIKOSOSIAL TES -->
        <h3 style="font-size:13px; font-weight:700; color:#fff; margin:4px 0 2px 0; text-align:center; line-height:1.2;">
            ERGONOMI &amp; PSIKOSOSIAL TES
        </h3>
        
        <!-- PT Radiant Group Cabang Duri -->
        <small style="font-size:9px; color:#94a3b8; display:block; text-align:center; letter-spacing:1px;">
            PT Radiant Group Cabang Duri
        </small>
        
        <small style="font-size:8px; color:#64748b; display:block; text-align:center; margin-top:4px;">
            SISTEM ASSESSMENT K3
        </small>
    </div>
    
    <ul class="sidebar-menu">
        <li class="menu-label">Navigasi Utama</li>
        
        <!-- ============================================================
        MENU HSE (SEMUA PAKAI $base_url)
        ============================================================ -->
        <?php if ($_SESSION['role'] == 'HSE'): ?>
        <li>
            <a href="<?= $base_url ?>hse/dashboard.php" class="<?= $active_page == 'hse_dashboard' ? 'active' : '' ?>">
                <i class="fas fa-chart-pie"></i> Dashboard
            </a>
        </li>
        <li>
            <a href="<?= $base_url ?>hse/karyawan.php" class="<?= $active_page == 'hse_karyawan' ? 'active' : '' ?>">
                <i class="fas fa-users"></i> Kelola Karyawan
            </a>
        </li>
        <li>
            <a href="<?= $base_url ?>hse/assessment_periods.php" class="<?= $active_page == 'hse_periods' ? 'active' : '' ?>">
                <i class="fas fa-calendar-alt"></i> Periode Assessment
            </a>
        </li>
        <li>
            <a href="<?= $base_url ?>hse/questions.php" class="<?= $active_page == 'hse_questions' ? 'active' : '' ?>">
                <i class="fas fa-list-ul"></i> Kelola Pertanyaan
            </a>
        </li>
        <li>
            <a href="<?= $base_url ?>hse/notifikasi.php" class="<?= $active_page == 'hse_notifikasi' ? 'active' : '' ?>">
                <i class="fas fa-bell"></i> Notifikasi
                <?php if ($total_notif > 0): ?>
                    <span class="menu-badge"><?= $total_notif ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li>
            <a href="<?= $base_url ?>hse/tracking.php" class="<?= $active_page == 'hse_tracking' ? 'active' : '' ?>">
                <i class="fas fa-clipboard-list"></i> Tracking
            </a>
        </li>
        <li>
            <a href="<?= $base_url ?>hse/report.php" class="<?= $active_page == 'hse_report' ? 'active' : '' ?>">
                <i class="fas fa-file-alt"></i> Laporan
            </a>
        </li>
        <?php endif; ?>
        
        <!-- ============================================================
        MENU KARYAWAN
        ============================================================ -->
        <?php if ($_SESSION['role'] == 'Karyawan'): ?>
        <li>
            <a href="<?= $base_url ?>karyawan/dashboard.php" class="<?= $active_page == 'karyawan_dashboard' ? 'active' : '' ?>">
                <i class="fas fa-chart-pie"></i> Dashboard
            </a>
        </li>
        <li>
            <a href="<?= $base_url ?>karyawan/notifikasi.php" class="<?= $active_page == 'karyawan_notifikasi' ? 'active' : '' ?>">
                <i class="fas fa-bell"></i> Notifikasi
                <?php if ($total_notif > 0): ?>
                    <span class="menu-badge"><?= $total_notif ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li>
            <a href="<?= $base_url ?>karyawan/form_assessment.php" class="<?= $active_page == 'karyawan_form' ? 'active' : '' ?>">
                <i class="fas fa-clipboard-check"></i> Isi Assessment
            </a>
        </li>
        <li>
            <a href="<?= $base_url ?>karyawan/riwayat.php" class="<?= $active_page == 'karyawan_riwayat' ? 'active' : '' ?>">
                <i class="fas fa-history"></i> Riwayat
            </a>
        </li>
        <?php endif; ?>
        
        <!-- ============================================================
        MENU AKUN (SEMUA ROLE)
        ============================================================ -->
        <li class="menu-label">Akun</li>
        <li>
            <a href="<?= $base_url ?>ubah_password.php" class="<?= $active_page == 'ubah_password' ? 'active' : '' ?>">
                <i class="fas fa-key"></i> Ubah Password
            </a>
        </li>
        <li>
            <a href="<?= $base_url ?>logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </li>
    </ul>
    
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="avatar"><?= strtoupper(substr($_SESSION['nama'], 0, 1)) ?></div>
            <div>
                <div class="user-name"><?= $_SESSION['nama'] ?></div>
                <div class="user-role"><?= $_SESSION['role'] ?> • <?= $_SESSION['department'] ?? 'Divisi' ?></div>
            </div>
        </div>
    </div>
</nav>

<!-- ============================================================
SIDEBAR OVERLAY (untuk klik di luar sidebar - HP)
============================================================ -->
<div class="sidebar-overlay" onclick="closeSidebar()"></div>

<!-- ============================================================
MAIN CONTENT
============================================================ -->
<div class="main-content">
    
    <!-- TOP NAVBAR -->
    <div class="top-nav">
        <div class="page-title" style="display:flex; align-items:center; gap:8px;">
            <!-- TOMBOL BURGER (muncul di HP) -->
            <button class="btn-burger" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <div>
                <h4><?= $page_title ?? 'Dashboard' ?></h4>
                <small><?= $page_subtitle ?? 'Selamat datang di ERGONOMI &amp; PSIKOSOSIAL TES' ?></small>
            </div>
        </div>
        <div class="nav-actions">
            <!-- ICON NOTIFIKASI -->
            <?php if ($_SESSION['role'] == 'HSE'): ?>
                <a href="<?= $base_url ?>hse/notifikasi.php" class="notification-bell" title="Notifikasi" style="text-decoration:none; position:relative;">
                    <i class="fas fa-bell" style="color:#64748b; font-size:18px;"></i>
                    <?php if ($total_notif > 0): ?>
                        <span class="badge-notif"><?= $total_notif ?></span>
                    <?php else: ?>
                        <span class="badge-dot"></span>
                    <?php endif; ?>
                </a>
            <?php else: ?>
                <a href="<?= $base_url ?>karyawan/notifikasi.php" class="notification-bell" title="Notifikasi" style="text-decoration:none; position:relative;">
                    <i class="fas fa-bell" style="color:#64748b; font-size:18px;"></i>
                    <?php if ($total_notif > 0): ?>
                        <span class="badge-notif"><?= $total_notif ?></span>
                    <?php else: ?>
                        <span class="badge-dot"></span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>
            
            <!-- LOGOUT -->
            <a href="<?= $base_url ?>logout.php" class="btn-logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
<!-- AKHIR MAIN CONTENT -->