<?php
include '../config/database.php';
cek_role('HSE');

$user_id = $_SESSION['user_id'];
$page_title = 'Kelola Notifikasi';
$page_subtitle = 'Buat dan kelola pengumuman untuk karyawan';
$active_page = 'hse_notifikasi';
$base_url = '../';

$message = '';
$message_type = '';

// ============================================================
// PROSES TAMBAH NOTIFIKASI
// ============================================================
if ($_POST && isset($_POST['action']) && $_POST['action'] == 'tambah') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $message_text = mysqli_real_escape_string($conn, $_POST['message']);
    $target_role = mysqli_real_escape_string($conn, $_POST['target_role']);
    $start_date = !empty($_POST['start_date']) ? "'" . mysqli_real_escape_string($conn, $_POST['start_date']) . "'" : "NULL";
    $end_date = !empty($_POST['end_date']) ? "'" . mysqli_real_escape_string($conn, $_POST['end_date']) . "'" : "NULL";
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    $query = "INSERT INTO notifications (title, message, target_role, start_date, end_date, status, created_by) 
              VALUES ('$title', '$message_text', '$target_role', $start_date, $end_date, '$status', $user_id)";
    
    if (mysqli_query($conn, $query)) {
        $message = "✅ Notifikasi berhasil dibuat!";
        $message_type = 'success';
    } else {
        $message = "❌ Gagal membuat notifikasi: " . mysqli_error($conn);
        $message_type = 'danger';
    }
}

// ============================================================
// PROSES HAPUS NOTIFIKASI
// ============================================================
if (isset($_GET['hapus']) && is_numeric($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $query = "DELETE FROM notifications WHERE notification_id = $id";
    if (mysqli_query($conn, $query)) {
        $message = "✅ Notifikasi berhasil dihapus!";
        $message_type = 'success';
    }
}

// ============================================================
// AMBIL DATA NOTIFIKASI
// ============================================================
$query_notif = "SELECT n.*, u.username 
                FROM notifications n
                JOIN users u ON n.created_by = u.user_id
                ORDER BY n.created_at DESC";
$result_notif = mysqli_query($conn, $query_notif);

include '../includes/header.php';
?>

<div class="fade-in-up">
    <!-- PESAN -->
    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- FORM TAMBAH NOTIFIKASI -->
        <div class="col-lg-5">
            <div class="card-modern">
                <div class="card-header-custom">
                    <h5><i class="fas fa-plus-circle me-2" style="color:#4fc3f7;"></i> Buat Notifikasi Baru</h5>
                </div>
                <div class="card-body-custom">
                    <form method="POST">
                        <input type="hidden" name="action" value="tambah">
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Judul <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" style="border-radius:10px; padding:10px 14px;" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Pesan <span class="text-danger">*</span></label>
                            <textarea name="message" class="form-control" rows="3" style="border-radius:10px; padding:10px 14px;" required></textarea>
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Target</label>
                                <select name="target_role" class="form-select" style="border-radius:10px; padding:10px 14px;">
                                    <option value="Semua">Semua</option>
                                    <option value="Karyawan">Karyawan</option>
                                    <option value="HSE">HSE</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Status</label>
                                <select name="status" class="form-select" style="border-radius:10px; padding:10px 14px;">
                                    <option value="Aktif">Aktif</option>
                                    <option value="Nonaktif">Nonaktif</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Tanggal Mulai (opsional)</label>
                                <input type="date" name="start_date" class="form-control" style="border-radius:10px; padding:10px 14px;">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Tanggal Selesai (opsional)</label>
                                <input type="date" name="end_date" class="form-control" style="border-radius:10px; padding:10px 14px;">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mt-3" style="border-radius:10px; padding:10px;">
                            <i class="fas fa-paper-plane me-2"></i> Kirim Notifikasi
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- DAFTAR NOTIFIKASI -->
        <div class="col-lg-7">
            <div class="card-modern">
                <div class="card-header-custom">
                    <h5><i class="fas fa-bell me-2" style="color:#f59e0b;"></i> Daftar Notifikasi</h5>
                    <span class="badge" style="background:#e2e8f0; color:#475569; padding:4px 14px; border-radius:20px;">
                        <?= mysqli_num_rows($result_notif) ?> notifikasi
                    </span>
                </div>
                <div class="card-body-custom" style="padding:0;">
                    <?php if (mysqli_num_rows($result_notif) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result_notif)): ?>
                            <div class="d-flex justify-content-between align-items-start p-3" style="border-bottom:1px solid #f1f5f9;">
                                <div style="flex:1;">
                                    <div class="d-flex align-items-center gap-2">
                                        <h6 style="font-weight:700; margin:0;"><?= htmlspecialchars($row['title']) ?></h6>
                                        <span class="badge <?= $row['status'] == 'Aktif' ? 'bg-success' : 'bg-secondary' ?>" style="font-size:10px;">
                                            <?= $row['status'] ?>
                                        </span>
                                    </div>
                                    <p style="color:#475569; font-size:13px; margin:4px 0;"><?= htmlspecialchars($row['message']) ?></p>
                                    <small style="color:#94a3b8; font-size:11px;">
                                        Target: <?= $row['target_role'] ?> | Dibuat oleh: <?= $row['username'] ?> | <?= date('d M Y H:i', strtotime($row['created_at'])) ?>
                                    </small>
                                </div>
                                <a href="?hapus=<?= $row['notification_id'] ?>" 
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Hapus notifikasi ini?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="text-align:center; padding:40px 20px; color:#94a3b8;">
                            <div style="font-size:48px; margin-bottom:12px;">🔔</div>
                            <p>Belum ada notifikasi.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>