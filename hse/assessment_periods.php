<?php
include '../config/database.php';
cek_role('HSE');

$user_id = $_SESSION['user_id'];
$page_title = 'Kelola Periode Assessment';
$page_subtitle = 'Buat dan kelola periode assessment untuk karyawan';
$active_page = 'hse_periods';
$base_url = '../';

$message = '';
$message_type = '';

// ============================================================
// PROSES TAMBAH PERIODE
// ============================================================
if ($_POST && isset($_POST['action']) && $_POST['action'] == 'tambah') {
    $period_name = mysqli_real_escape_string($conn, $_POST['period_name']);
    $start_date = mysqli_real_escape_string($conn, $_POST['start_date']);
    $end_date = mysqli_real_escape_string($conn, $_POST['end_date']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    // Validasi tanggal
    if ($start_date > $end_date) {
        $message = "❌ Tanggal mulai tidak boleh lebih besar dari tanggal selesai!";
        $message_type = 'danger';
    } else {
        $query = "INSERT INTO assessment_periods (period_name, start_date, end_date, status, created_by) 
                  VALUES ('$period_name', '$start_date', '$end_date', '$status', $user_id)";
        
        if (mysqli_query($conn, $query)) {
            $message = "✅ Periode <strong>$period_name</strong> berhasil ditambahkan!";
            $message_type = 'success';
        } else {
            $message = "❌ Gagal menambahkan periode: " . mysqli_error($conn);
            $message_type = 'danger';
        }
    }
}

// ============================================================
// PROSES UBAH STATUS PERIODE
// ============================================================
if (isset($_GET['status']) && is_numeric($_GET['id'])) {
    $period_id = (int)$_GET['id'];
    $new_status = mysqli_real_escape_string($conn, $_GET['status']);
    
    // Validasi status hanya boleh Aktif/Ditutup/Draft/Diarsipkan
    $allowed_status = ['Aktif', 'Ditutup', 'Draft', 'Diarsipkan'];
    if (!in_array($new_status, $allowed_status)) {
        $message = "❌ Status tidak valid!";
        $message_type = 'danger';
    } else {
        $query = "UPDATE assessment_periods SET status = '$new_status' WHERE period_id = $period_id";
        if (mysqli_query($conn, $query)) {
            $message = "✅ Status periode berhasil diubah menjadi: <strong>$new_status</strong>";
            $message_type = 'success';
        } else {
            $message = "❌ Gagal mengubah status: " . mysqli_error($conn);
            $message_type = 'danger';
        }
    }
}

// ============================================================
// PROSES HAPUS PERIODE
// ============================================================
if (isset($_GET['hapus']) && is_numeric($_GET['hapus'])) {
    $period_id = (int)$_GET['hapus'];
    
    // Cek apakah periode sudah memiliki session (data assessment)
    $cek = mysqli_query($conn, "SELECT session_id FROM assessment_sessions WHERE period_id = $period_id LIMIT 1");
    if (mysqli_num_rows($cek) > 0) {
        $message = "❌ Periode tidak bisa dihapus karena sudah ada data assessment!";
        $message_type = 'danger';
    } else {
        $query = "DELETE FROM assessment_periods WHERE period_id = $period_id";
        if (mysqli_query($conn, $query)) {
            $message = "✅ Periode berhasil dihapus!";
            $message_type = 'success';
        } else {
            $message = "❌ Gagal menghapus periode: " . mysqli_error($conn);
            $message_type = 'danger';
        }
    }
}

// ============================================================
// AMBIL DATA PERIODE
// ============================================================
$query_periode = "SELECT p.*, u.username as created_by_name 
                  FROM assessment_periods p
                  JOIN users u ON p.created_by = u.user_id
                  ORDER BY p.period_id DESC";
$result_periode = mysqli_query($conn, $query_periode);

// Cek periode aktif
$query_aktif = "SELECT period_id, period_name FROM assessment_periods WHERE status = 'Aktif' AND CURDATE() BETWEEN start_date AND end_date";
$result_aktif = mysqli_query($conn, $query_aktif);
$periode_aktif = mysqli_fetch_assoc($result_aktif);

include '../includes/header.php';
?>

<div class="fade-in-up">
    <!-- PESAN NOTIFIKASI -->
    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert" style="border-radius:12px;">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- INFO PERIODE AKTIF -->
    <?php if ($periode_aktif): ?>
        <div class="alert alert-success" style="border-radius:12px; border-left:4px solid #059669;">
            <i class="fas fa-check-circle me-2" style="color:#059669;"></i>
            <strong>Periode Aktif:</strong> <?= $periode_aktif['period_name'] ?> 
            <span class="badge" style="background:#d1fae5; color:#059669; padding:4px 14px; border-radius:20px; margin-left:8px;">
                <i class="fas fa-circle me-1" style="font-size:8px;"></i> AKTIF
            </span>
        </div>
    <?php else: ?>
        <div class="alert alert-warning" style="border-radius:12px; border-left:4px solid #d97706;">
            <i class="fas fa-exclamation-triangle me-2" style="color:#d97706;"></i>
            <strong>Tidak ada periode aktif!</strong> Karyawan tidak bisa mengisi assessment. Buat periode baru dan set status <strong>Aktif</strong>.
        </div>
    <?php endif; ?>

    <!-- TOMBOL TAMBAH -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0" style="color:#1a1a2e;">
                <i class="fas fa-calendar-alt me-2" style="color:#4fc3f7;"></i> Daftar Periode Assessment
            </h5>
            <small style="color:#64748b;">Total: <?= mysqli_num_rows($result_periode) ?> periode</small>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahPeriode" style="border-radius:12px; padding:10px 24px;">
            <i class="fas fa-plus me-2"></i> Buat Periode Baru
        </button>
    </div>

    <!-- TABEL PERIODE -->
    <div class="card-modern">
        <div class="card-body-custom" style="padding:0;">
            <div style="overflow-x:auto;">
                <table class="table-modern" id="tabelPeriode">
                    <thead>
                        <tr>
                            <th>Nama Periode</th>
                            <th>Tanggal Mulai</th>
                            <th>Tanggal Selesai</th>
                            <th>Status</th>
                            <th>Dibuat Oleh</th>
                            <th style="text-align:center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result_periode) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result_periode)): ?>
                                <?php
                                $status_badge = [
                                    'Aktif' => ['bg' => '#d1fae5', 'color' => '#059669', 'icon' => 'fa-circle'],
                                    'Ditutup' => ['bg' => '#fee2e2', 'color' => '#dc2626', 'icon' => 'fa-circle'],
                                    'Draft' => ['bg' => '#fef3c7', 'color' => '#d97706', 'icon' => 'fa-pen'],
                                    'Diarsipkan' => ['bg' => '#f1f5f9', 'color' => '#64748b', 'icon' => 'fa-archive']
                                ];
                                $badge = $status_badge[$row['status']] ?? $status_badge['Draft'];
                                ?>
                                <tr>
                                    <td><strong><?= $row['period_name'] ?></strong></td>
                                    <td><?= date('d M Y', strtotime($row['start_date'])) ?></td>
                                    <td><?= date('d M Y', strtotime($row['end_date'])) ?></td>
                                    <td>
                                        <span class="badge" style="background:<?= $badge['bg'] ?>; color:<?= $badge['color'] ?>; padding:4px 14px; border-radius:20px;">
                                            <i class="fas <?= $badge['icon'] ?> me-1" style="font-size:8px;"></i> <?= $row['status'] ?>
                                        </span>
                                    </td>
                                    <td><?= $row['created_by_name'] ?></td>
                                    <td style="text-align:center;">
                                        <div class="d-flex gap-1 justify-content-center">
                                            <!-- Ubah Status -->
                                            <?php if ($row['status'] != 'Aktif'): ?>
                                                <a href="?id=<?= $row['period_id'] ?>&status=Aktif" 
                                                   class="btn btn-sm btn-outline-success"
                                                   title="Aktifkan periode"
                                                   onclick="return confirm('Aktifkan periode <?= $row['period_name'] ?>?')">
                                                    <i class="fas fa-play"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($row['status'] != 'Ditutup' && $row['status'] != 'Diarsipkan'): ?>
                                                <a href="?id=<?= $row['period_id'] ?>&status=Ditutup" 
                                                   class="btn btn-sm btn-outline-danger"
                                                   title="Tutup periode"
                                                   onclick="return confirm('Tutup periode <?= $row['period_name'] ?>?')">
                                                    <i class="fas fa-stop"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($row['status'] != 'Diarsipkan'): ?>
                                                <a href="?id=<?= $row['period_id'] ?>&status=Diarsipkan" 
                                                   class="btn btn-sm btn-outline-secondary"
                                                   title="Arsipkan periode"
                                                   onclick="return confirm('Arsipkan periode <?= $row['period_name'] ?>?')">
                                                    <i class="fas fa-archive"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <!-- Hapus (hanya jika belum ada data) -->
                                            <a href="?hapus=<?= $row['period_id'] ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               title="Hapus periode"
                                               onclick="return confirm('Yakin hapus periode <?= $row['period_name'] ?>?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align:center; padding:40px; color:#94a3b8;">
                                    <div style="font-size:48px; margin-bottom:12px;">📅</div>
                                    Belum ada periode assessment. Klik tombol <strong>"Buat Periode Baru"</strong> untuk mulai.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     MODAL TAMBAH PERIODE
     ============================================================ -->
<div class="modal fade" id="modalTambahPeriode" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:16px; border:none; box-shadow:0 20px 60px rgba(0,0,0,0.2);">
            <div class="modal-header" style="border-bottom:1px solid #f1f5f9; padding:20px 24px;">
                <h5 class="modal-title fw-bold" style="color:#1a1a2e;">
                    <i class="fas fa-calendar-plus me-2" style="color:#4fc3f7;"></i> Buat Periode Baru
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body" style="padding:24px;">
                    <input type="hidden" name="action" value="tambah">
                    
                   <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Periode <span class="text-danger">*</span></label>
                        <input type="text" name="period_name" class="form-control" style="border-radius:10px; padding:10px 14px;" 
                             placeholder="Contoh: Semester 1 - 2027" required>
                        </div>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tanggal Mulai <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="form-control" style="border-radius:10px; padding:10px 14px;" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tanggal Selesai <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" class="form-control" style="border-radius:10px; padding:10px 14px;" required>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" style="border-radius:10px; padding:10px 14px;" required>
                            <option value="Draft">Draft (Belum aktif)</option>
                            <option value="Aktif" selected>Aktif (Karyawan bisa mengisi)</option>
                        </select>
                        <small style="color:#94a3b8; font-size:11px;">
                            Jika status <strong>Aktif</strong>, karyawan bisa langsung mengisi assessment selama periode berlangsung.
                        </small>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f1f5f9; padding:16px 24px;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-radius:10px; padding:8px 24px;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="border-radius:10px; padding:8px 30px;">
                        <i class="fas fa-save me-2"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>