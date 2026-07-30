<?php
include '../config/database.php';
cek_role('Karyawan');

$employee_id = $_SESSION['employee_id'];
$page_title = 'Notifikasi Saya';
$page_subtitle = 'Daftar pengumuman dan notifikasi untuk Anda';
$active_page = 'karyawan_notifikasi';
$base_url = '../';

// ============================================================
// AMBIL NOTIFIKASI UNTUK KARYAWAN
// ============================================================
$query_notif = "SELECT * FROM notifications 
                WHERE status = 'Aktif' 
                AND (target_role = 'Semua' OR target_role = 'Karyawan')
                AND (start_date IS NULL OR start_date <= CURDATE())
                AND (end_date IS NULL OR end_date >= CURDATE())
                ORDER BY created_at DESC";
$result_notif = mysqli_query($conn, $query_notif);
$total_notif = mysqli_num_rows($result_notif);

include '../includes/header.php';
?>

<div class="fade-in-up">
    <div class="card-modern">
        <div class="card-header-custom">
            <h5><i class="fas fa-bell me-2" style="color:#f59e0b;"></i> Notifikasi Saya</h5>
            <span class="badge" style="background:#e2e8f0; color:#475569; padding:4px 14px; border-radius:20px;">
                <?= $total_notif ?> notifikasi
            </span>
        </div>
        <div class="card-body-custom">
            <?php if ($total_notif > 0): ?>
                <?php while ($notif = mysqli_fetch_assoc($result_notif)): ?>
                    <div class="d-flex justify-content-between align-items-start p-3" style="border-bottom:1px solid #f1f5f9;">
                        <div style="flex:1;">
                            <div class="d-flex align-items-center gap-2">
                                <h6 style="font-weight:700; margin:0;"><?= htmlspecialchars($notif['title']) ?></h6>
                                <span class="badge bg-primary" style="font-size:10px; background:#dbeafe !important; color:#2563eb !important;">
                                    <?= $notif['target_role'] ?>
                                </span>
                            </div>
                            <p style="color:#475569; font-size:14px; margin:6px 0;"><?= htmlspecialchars($notif['message']) ?></p>
                            <small style="color:#94a3b8; font-size:12px;">
                                <i class="fas fa-calendar-alt me-1"></i> 
                                <?= date('d M Y H:i', strtotime($notif['created_at'])) ?>
                            </small>
                            <?php if ($notif['start_date'] && $notif['end_date']): ?>
                                <small style="color:#94a3b8; font-size:12px; display:block; margin-top:2px;">
                                    <i class="fas fa-clock me-1"></i> 
                                    Periode: <?= date('d M Y', strtotime($notif['start_date'])) ?> - <?= date('d M Y', strtotime($notif['end_date'])) ?>
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align:center; padding:60px 20px; color:#94a3b8;">
                    <div style="font-size:64px; margin-bottom:16px;">🔔</div>
                    <h5 style="font-weight:600; color:#1a1a2e;">Tidak Ada Notifikasi</h5>
                    <p>Belum ada pengumuman dari tim HSE.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>