<?php
include '../config/database.php';
cek_role('HSE');

$user_id = $_SESSION['user_id'];
$page_title = 'Tracking Assessment';
$page_subtitle = 'Lihat perkembangan dan riwayat assessment karyawan';
$active_page = 'hse_tracking';
$base_url = '../';

// ============================================================
// PESAN DARI SESSION (dari tracking_update.php)
// ============================================================
$message = $_SESSION['tracking_message'] ?? '';
$message_type = $_SESSION['tracking_message_type'] ?? '';
unset($_SESSION['tracking_message']);
unset($_SESSION['tracking_message_type']);

// ============================================================
// AMBIL FILTER DARI GET
// ============================================================
$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : date('m');
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');
$departemen = isset($_GET['departemen']) ? (int)$_GET['departemen'] : 0;
$karyawan = isset($_GET['karyawan']) ? (int)$_GET['karyawan'] : 0;
$kategori = isset($_GET['kategori']) ? $_GET['kategori'] : 'semua';

// ============================================================
// STATISTIK TRACKING (UNTUK 3 CARD)
// ============================================================
$tracking_stats = ['Perlu Tindak Lanjut' => 0, 'Sedang Diproses' => 0, 'Selesai' => 0];
$q = mysqli_query($conn, "SELECT status, COUNT(*) as total FROM assessment_tracking GROUP BY status");
while ($row = mysqli_fetch_assoc($q)) {
    $tracking_stats[$row['status']] = $row['total'];
}

// ============================================================
// AMBIL DATA UNTUK DROPDOWN FILTER
// ============================================================

// Tahun pertama sistem dibuat
$tahun_pertama = 2026;
$tahun_sekarang = date('Y');
$tahun_list = range($tahun_sekarang, $tahun_pertama);

// Departemen
$query_dept = "SELECT department_id, department_name FROM departments WHERE department_id IN (1,2,3)";
$result_dept = mysqli_query($conn, $query_dept);

// Karyawan (untuk filter)
$query_karyawan = "SELECT employee_id, full_name FROM employees WHERE is_active = 1 ORDER BY full_name";
$result_karyawan = mysqli_query($conn, $query_karyawan);

// ============================================================
// QUERY DATA ASSESSMENT DENGAN FILTER + JOIN TRACKING
// ============================================================
$query = "SELECT 
            s.session_id,
            s.submitted_at,
            e.full_name,
            e.employee_code,
            d.department_name,
            p.period_name,
            r.ergonomic_score,
            r.psychosocial_score,
            rc1.category_name as ergo_risk,
            rc2.category_name as psiko_risk,
            r.conclusion,
            t.tracking_id,
            t.status as tracking_status,
            t.tindakan,
            t.catatan,
            t.updated_at,
            (SELECT COUNT(*) FROM assessment_answers WHERE session_id = s.session_id) as total_jawaban
          FROM assessment_sessions s
          JOIN employees e ON s.employee_id = e.employee_id
          JOIN departments d ON e.department_id = d.department_id
          JOIN assessment_periods p ON s.period_id = p.period_id
          JOIN assessment_results r ON s.session_id = r.session_id
          JOIN risk_categories rc1 ON r.ergonomic_risk_id = rc1.risk_category_id
          JOIN risk_categories rc2 ON r.psychosocial_risk_id = rc2.risk_category_id
          LEFT JOIN assessment_tracking t ON s.session_id = t.session_id
          WHERE s.status = 'Submitted'";

// Filter Bulan
if ($bulan > 0) {
    $query .= " AND MONTH(s.submitted_at) = $bulan";
}

// Filter Tahun
if ($tahun > 0) {
    $query .= " AND YEAR(s.submitted_at) = $tahun";
}

// Filter Departemen
if ($departemen > 0) {
    $query .= " AND e.department_id = $departemen";
}

// Filter Karyawan
if ($karyawan > 0) {
    $query .= " AND s.employee_id = $karyawan";
}

$query .= " ORDER BY s.submitted_at DESC";

$result = mysqli_query($conn, $query);
$total_data = mysqli_num_rows($result);

// ============================================================
// DATA UNTUK GRAFIK TREN
// ============================================================
$query_grafik = "SELECT 
                    MONTH(s.submitted_at) as bulan,
                    COUNT(*) as total,
                    AVG(r.ergonomic_score) as avg_ergo,
                    AVG(r.psychosocial_score) as avg_psiko
                  FROM assessment_sessions s
                  JOIN assessment_results r ON s.session_id = r.session_id
                  WHERE s.status = 'Submitted' AND YEAR(s.submitted_at) = $tahun";
if ($departemen > 0) {
    $query_grafik .= " AND s.employee_id IN (SELECT employee_id FROM employees WHERE department_id = $departemen)";
}
$query_grafik .= " GROUP BY MONTH(s.submitted_at) ORDER BY bulan";
$result_grafik = mysqli_query($conn, $query_grafik);
$grafik_data = [];
while ($row = mysqli_fetch_assoc($result_grafik)) {
    $grafik_data[$row['bulan']] = $row;
}

// Data untuk chart
$bulan_labels = [];
$ergo_scores = [];
$psiko_scores = [];
for ($i = 1; $i <= 12; $i++) {
    $bulan_labels[] = date('M', mktime(0, 0, 0, $i, 1));
    $ergo_scores[] = isset($grafik_data[$i]) ? round($grafik_data[$i]['avg_ergo'], 1) : 0;
    $psiko_scores[] = isset($grafik_data[$i]) ? round($grafik_data[$i]['avg_psiko'], 1) : 0;
}

// ============================================================
// STATISTIK RINGKASAN
// ============================================================
$total_ergo = 0;
$total_psiko = 0;
$count = 0;
$tinggi = 0;
$sedang = 0;
$rendah = 0;

// Reset pointer result
mysqli_data_seek($result, 0);
while ($row = mysqli_fetch_assoc($result)) {
    $total_ergo += $row['ergonomic_score'];
    $total_psiko += $row['psychosocial_score'];
    $count++;
    
    if ($row['ergo_risk'] == 'Tinggi' || $row['psiko_risk'] == 'Tinggi') {
        $tinggi++;
    } elseif ($row['ergo_risk'] == 'Sedang' || $row['psiko_risk'] == 'Sedang') {
        $sedang++;
    } else {
        $rendah++;
    }
}

$avg_ergo = $count > 0 ? round($total_ergo / $count, 1) : 0;
$avg_psiko = $count > 0 ? round($total_psiko / $count, 1) : 0;

include '../includes/header.php';
?>

<div class="fade-in-up">
    <!-- ============================================================
    PESAN NOTIFIKASI
    ============================================================ -->
    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert" style="border-radius:12px;">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ============================================================
    STATISTIK TRACKING (3 CARD)
    ============================================================ -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card stat-card" style="border-left:4px solid #f59e0b; padding:12px 16px;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1" style="font-size:12px;">🟠 Perlu Tindak Lanjut</h6>
                        <h3 class="fw-bold mb-0" style="font-size:24px;"><?= $tracking_stats['Perlu Tindak Lanjut'] ?></h3>
                    </div>
                    <div style="font-size:24px; color:#f59e0b;">⏳</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card" style="border-left:4px solid #3b82f6; padding:12px 16px;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1" style="font-size:12px;">🔵 Sedang Diproses</h6>
                        <h3 class="fw-bold mb-0" style="font-size:24px;"><?= $tracking_stats['Sedang Diproses'] ?></h3>
                    </div>
                    <div style="font-size:24px; color:#3b82f6;">⚙️</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card" style="border-left:4px solid #10b981; padding:12px 16px;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1" style="font-size:12px;">🟢 Selesai</h6>
                        <h3 class="fw-bold mb-0" style="font-size:24px;"><?= $tracking_stats['Selesai'] ?></h3>
                    </div>
                    <div style="font-size:24px; color:#10b981;">✅</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================
    FILTER
    ============================================================ -->
    <div class="card-modern mb-4">
        <div class="card-header-custom">
            <h5><i class="fas fa-filter me-2" style="color:#4fc3f7;"></i> Filter Tracking</h5>
        </div>
        <div class="card-body-custom">
            <form method="GET" action="" id="filterForm">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Bulan</label>
                        <select name="bulan" class="form-select" style="border-radius:10px; padding:10px 14px;">
                            <option value="0">Semua Bulan</option>
                            <?php for ($i = 1; $i <= 12; $i++): 
                                $selected = ($bulan == $i) ? 'selected' : '';
                                $nama_bulan = date('F', mktime(0, 0, 0, $i, 1));
                            ?>
                                <option value="<?= $i ?>" <?= $selected ?>><?= $nama_bulan ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Tahun</label>
                        <select name="tahun" class="form-select" style="border-radius:10px; padding:10px 14px;">
                            <?php foreach ($tahun_list as $thn): 
                                $selected = ($tahun == $thn) ? 'selected' : '';
                            ?>
                                <option value="<?= $thn ?>" <?= $selected ?>><?= $thn ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Departemen</label>
                        <select name="departemen" class="form-select" style="border-radius:10px; padding:10px 14px;">
                            <option value="0">Semua</option>
                            <?php 
                            mysqli_data_seek($result_dept, 0);
                            while ($row = mysqli_fetch_assoc($result_dept)): 
                                $selected = ($departemen == $row['department_id']) ? 'selected' : '';
                            ?>
                                <option value="<?= $row['department_id'] ?>" <?= $selected ?>><?= $row['department_name'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Karyawan</label>
                        <select name="karyawan" class="form-select" style="border-radius:10px; padding:10px 14px;">
                            <option value="0">Semua Karyawan</option>
                            <?php 
                            mysqli_data_seek($result_karyawan, 0);
                            while ($row = mysqli_fetch_assoc($result_karyawan)): 
                                $selected = ($karyawan == $row['employee_id']) ? 'selected' : '';
                            ?>
                                <option value="<?= $row['employee_id'] ?>" <?= $selected ?>><?= $row['full_name'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div style="display:flex; gap:10px; width:100%;">
                            <button type="submit" class="btn btn-primary" style="border-radius:10px; padding:10px 24px; flex:1;">
                                <i class="fas fa-search me-2"></i> Tampilkan
                            </button>
                            <a href="tracking.php" class="btn btn-outline-secondary" style="border-radius:10px; padding:10px 24px;">
                                <i class="fas fa-undo me-2"></i> Reset
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================
    GRAFIK TREN
    ============================================================ -->
    <div class="card-modern mb-4">
        <div class="card-header-custom">
            <h5><i class="fas fa-chart-line me-2" style="color:#4fc3f7;"></i> Tren Skor Assessment <?= $tahun ?></h5>
            <span class="badge" style="background:#e2e8f0; color:#475569; padding:4px 14px; border-radius:20px;">
                Rata-rata per bulan
            </span>
        </div>
        <div class="card-body-custom">
            <canvas id="trenChart" height="100"></canvas>
        </div>
    </div>

    <!-- ============================================================
    STATISTIK RINGKASAN
    ============================================================ -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card stat-card fade-in-up delay-1" style="border-left:4px solid #3b82f6;">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Total Assessment</h6>
                    <h3 class="fw-bold mb-0"><?= $total_data ?></h3>
                    <small style="color:#64748b;">Data ditampilkan</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card fade-in-up delay-2" style="border-left:4px solid #10b981;">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Rata-rata Skor Ergo</h6>
                    <h3 class="fw-bold mb-0"><?= $avg_ergo ?></h3>
                    <small style="color:#64748b;">dari 30</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card fade-in-up delay-3" style="border-left:4px solid #8b5cf6;">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Rata-rata Skor Psiko</h6>
                    <h3 class="fw-bold mb-0"><?= $avg_psiko ?></h3>
                    <small style="color:#64748b;">dari 25</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card fade-in-up delay-4" style="border-left:4px solid #ef4444;">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Risiko Tinggi</h6>
                    <h3 class="fw-bold mb-0 text-danger"><?= $tinggi ?></h3>
                    <small style="color:#64748b;">Perlu perhatian khusus</small>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================
    TABEL DATA + AKSI UPDATE STATUS
    ============================================================ -->
    <div class="card-modern">
        <div class="card-header-custom">
            <h5><i class="fas fa-table me-2" style="color:#4fc3f7;"></i> Data Assessment</h5>
            <span class="badge" style="background:#e2e8f0; color:#475569; padding:4px 14px; border-radius:20px;">
                <?= $total_data ?> data
            </span>
        </div>
        <div class="card-body-custom" style="padding:0;">
            <div style="overflow-x:auto;">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Karyawan</th>
                            <th>Departemen</th>
                            <th style="text-align:center;">Skor Ergo</th>
                            <th style="text-align:center;">Skor Psiko</th>
                            <th style="text-align:center;">Tracking Status</th>
                            <th style="text-align:center; width:220px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($total_data > 0): ?>
                            <?php 
                            mysqli_data_seek($result, 0);
                            while ($row = mysqli_fetch_assoc($result)): 
                                $tracking_status = $row['tracking_status'] ?? 'Belum Ada Tracking';
                                $has_tracking = !is_null($row['tracking_id']);
                                
                                $status_color = [
                                    'Belum Ada Tracking' => '#94a3b8',
                                    'Perlu Tindak Lanjut' => '#f59e0b',
                                    'Sedang Diproses' => '#3b82f6',
                                    'Selesai' => '#10b981'
                                ];
                                $status_bg = [
                                    'Belum Ada Tracking' => '#f1f5f9',
                                    'Perlu Tindak Lanjut' => '#fef3c7',
                                    'Sedang Diproses' => '#dbeafe',
                                    'Selesai' => '#d1fae5'
                                ];
                                $color = $status_color[$tracking_status] ?? '#64748b';
                                $bg = $status_bg[$tracking_status] ?? '#f1f5f9';
                                
                                $session_id = $row['session_id'];
                            ?>
                            <tr>
                                <td style="font-size:12px; color:#64748b;">
                                    <?= date('d M Y H:i', strtotime($row['submitted_at'])) ?>
                                </td>
                                <td>
                                    <strong><?= $row['full_name'] ?></strong><br>
                                    <small style="color:#94a3b8; font-size:11px;"><?= $row['employee_code'] ?></small>
                                </td>
                                <td><?= $row['department_name'] ?></td>
                                <td style="text-align:center;">
                                    <span class="badge-risk <?= strtolower($row['ergo_risk']) ?>" style="font-size:12px; padding:4px 10px;">
                                        <?= $row['ergonomic_score'] ?>/30
                                    </span>
                                </td>
                                <td style="text-align:center;">
                                    <span class="badge-risk <?= strtolower($row['psiko_risk']) ?>" style="font-size:12px; padding:4px 10px;">
                                        <?= $row['psychosocial_score'] ?>/25
                                    </span>
                                </td>
                                <td style="text-align:center;">
                                    <span style="background:<?= $bg ?>; color:<?= $color ?>; padding:4px 14px; border-radius:20px; font-size:12px; font-weight:600; display:inline-block;">
                                        <?= $tracking_status ?>
                                    </span>
                                </td>
                                <td style="text-align:center;">
                                    <form method="POST" action="tracking_update.php" style="display:flex; gap:4px; align-items:center; justify-content:center; flex-wrap:wrap;">
                                        <input type="hidden" name="session_id" value="<?= $session_id ?>">
                                        <select name="status" class="form-select" style="width:140px; padding:4px 8px; font-size:12px; border-radius:6px; border:1px solid #e2e8f0;">
                                            <option value="Perlu Tindak Lanjut" <?= $tracking_status == 'Perlu Tindak Lanjut' ? 'selected' : '' ?>>🟠 Perlu Tindak Lanjut</option>
                                            <option value="Sedang Diproses" <?= $tracking_status == 'Sedang Diproses' ? 'selected' : '' ?>>🔵 Sedang Diproses</option>
                                            <option value="Selesai" <?= $tracking_status == 'Selesai' ? 'selected' : '' ?>>🟢 Selesai</option>
                                        </select>
                                        <button type="submit" class="btn btn-sm <?= $has_tracking ? 'btn-primary' : 'btn-success' ?>" 
                                                title="<?= $has_tracking ? 'Update Status' : 'Buat Tracking & Update' ?>" 
                                                style="border-radius:6px; padding:4px 10px;">
                                            <i class="fas <?= $has_tracking ? 'fa-save' : 'fa-plus' ?>"></i>
                                        </button>
                                    </form>
                                    <?php if (!$has_tracking): ?>
                                        <small style="color:#94a3b8; font-size:9px; display:block; margin-top:2px;">Klik + untuk buat tracking</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align:center; padding:40px; color:#94a3b8;">
                                    <div style="font-size:48px; margin-bottom:12px;">📊</div>
                                    <h5 style="font-weight:600; color:#1a1a2e;">Tidak Ada Data</h5>
                                    <p>Belum ada assessment yang selesai dengan filter ini.</p>
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
CHART.JS
============================================================ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('trenChart').getContext('2d');

const bulanLabels = <?= json_encode($bulan_labels) ?>;
const ergoScores = <?= json_encode($ergo_scores) ?>;
const psikoScores = <?= json_encode($psiko_scores) ?>;

new Chart(ctx, {
    type: 'line',
    data: {
        labels: bulanLabels,
        datasets: [
            {
                label: 'Rata-rata Skor Ergonomi',
                data: ergoScores,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59,130,246,0.1)',
                fill: true,
                tension: 0.3,
                pointBackgroundColor: '#3b82f6',
                pointRadius: 4,
            },
            {
                label: 'Rata-rata Skor Psikososial',
                data: psikoScores,
                borderColor: '#8b5cf6',
                backgroundColor: 'rgba(139,92,246,0.1)',
                fill: true,
                tension: 0.3,
                pointBackgroundColor: '#8b5cf6',
                pointRadius: 4,
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: true,
                position: 'top',
                labels: { 
                    usePointStyle: true, 
                    pointStyle: 'circle', 
                    padding: 15,
                    font: { size: 12, family: 'Inter' }
                }
            }
        },
        scales: {
            x: { 
                grid: { display: false },
                title: { display: true, text: 'Bulan', color: '#64748b' }
            },
            y: { 
                beginAtZero: true,
                max: 30,
                grid: { color: 'rgba(0,0,0,0.04)' },
                title: { display: true, text: 'Skor', color: '#64748b' }
            }
        }
    }
});
</script>

<?php include '../includes/footer.php'; ?>