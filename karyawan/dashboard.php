<?php
include '../config/database.php';
cek_role('Karyawan');

$user_id = $_SESSION['user_id'];
$employee_id = $_SESSION['employee_id'];
$page_title = 'Dashboard Karyawan';
$page_subtitle = 'Pantau kesehatan ergonomi & psikososial Anda';
$active_page = 'karyawan_dashboard';
$base_url = '../';

// ============================================================
// SKOR MAKSIMAL DINAMIS (berdasarkan jumlah pertanyaan aktif)
// ============================================================
$q_max_ergo = mysqli_query($conn, "SELECT COUNT(*) as total FROM assessment_questions WHERE factor_id = 1 AND status = 'Aktif'");
$data_ergo = mysqli_fetch_assoc($q_max_ergo);
$max_ergo = ($data_ergo['total'] ?? 10) * 3; // default 10 × 3 = 30

$q_max_psiko = mysqli_query($conn, "SELECT COUNT(*) as total FROM assessment_questions WHERE factor_id = 2 AND status = 'Aktif'");
$data_psiko = mysqli_fetch_assoc($q_max_psiko);
$max_psiko = ($data_psiko['total'] ?? 5) * 5; // default 5 × 5 = 25

// ============================================================
// ASSESSMENT TERAKHIR
// ============================================================
$query_terakhir = "SELECT s.*, r.ergonomic_score, r.psychosocial_score, 
                   rc_ergo.category_name as ergo_risk, rc_psiko.category_name as psiko_risk
                   FROM assessment_sessions s
                   JOIN assessment_results r ON s.session_id = r.session_id
                   JOIN risk_categories rc_ergo ON r.ergonomic_risk_id = rc_ergo.risk_category_id
                   JOIN risk_categories rc_psiko ON r.psychosocial_risk_id = rc_psiko.risk_category_id
                   WHERE s.employee_id = $employee_id AND s.status = 'Submitted'
                   ORDER BY s.submitted_at DESC LIMIT 1";
$result_terakhir = mysqli_query($conn, $query_terakhir);
$terakhir = mysqli_fetch_assoc($result_terakhir);

// ============================================================
// STATISTIK RIWAYAT
// ============================================================
$stat_ergo = ['Rendah' => 0, 'Sedang' => 0, 'Tinggi' => 0];
$q_ergo = mysqli_query($conn, "SELECT rc_ergo.category_name, COUNT(*) as total
                               FROM assessment_sessions s
                               JOIN assessment_results r ON s.session_id = r.session_id
                               JOIN risk_categories rc_ergo ON r.ergonomic_risk_id = rc_ergo.risk_category_id
                               WHERE s.employee_id = $employee_id AND s.status = 'Submitted'
                               GROUP BY rc_ergo.category_name");
while ($row = mysqli_fetch_assoc($q_ergo)) {
    $stat_ergo[$row['category_name']] = $row['total'];
}

$stat_psiko = ['Rendah' => 0, 'Sedang' => 0, 'Tinggi' => 0];
$q_psiko = mysqli_query($conn, "SELECT rc_psiko.category_name, COUNT(*) as total
                                FROM assessment_sessions s
                                JOIN assessment_results r ON s.session_id = r.session_id
                                JOIN risk_categories rc_psiko ON r.psychosocial_risk_id = rc_psiko.risk_category_id
                                WHERE s.employee_id = $employee_id AND s.status = 'Submitted'
                                GROUP BY rc_psiko.category_name");
while ($row = mysqli_fetch_assoc($q_psiko)) {
    $stat_psiko[$row['category_name']] = $row['total'];
}

// ============================================================
// PERIODE AKTIF
// ============================================================
$query_periode_aktif = "SELECT p.period_id, p.period_name 
                        FROM assessment_periods p
                        WHERE p.status = 'Aktif' 
                        AND CURDATE() BETWEEN p.start_date AND p.end_date
                        AND NOT EXISTS (
                            SELECT 1 FROM assessment_sessions s 
                            WHERE s.period_id = p.period_id 
                            AND s.employee_id = $employee_id 
                            AND s.status = 'Submitted'
                        )";
$periode_aktif = mysqli_query($conn, $query_periode_aktif);
$ada_periode = mysqli_num_rows($periode_aktif) > 0;

// ============================================================
// DATA TREN (5 TERAKHIR) — PASTIKAN DATA ADA
// ============================================================
$query_tren = "SELECT r.ergonomic_score, r.psychosocial_score, DATE(s.submitted_at) as tanggal
               FROM assessment_sessions s
               JOIN assessment_results r ON s.session_id = r.session_id
               WHERE s.employee_id = $employee_id AND s.status = 'Submitted'
               ORDER BY s.submitted_at DESC LIMIT 5";
$result_tren = mysqli_query($conn, $query_tren);
$tren = [];
while ($row = mysqli_fetch_assoc($result_tren)) {
    $tren[] = $row;
}
$tren = array_reverse($tren);
$ada_tren = count($tren) > 0;
$is_single = $ada_tren && count($tren) === 1;

include '../includes/header.php';
?>

<div class="fade-in-up">

    <!-- 4 STAT CARD -->
    <div class="stat-cards">
        <div class="stat-card fade-in-up delay-1">
            <div class="stat-icon blue"><i class="fas fa-clipboard-check"></i></div>
            <h4 class="stat-number"><?= $terakhir ? '✅' : '❌' ?></h4>
            <p class="stat-label">Status Assessment Terakhir</p>
            <?php if ($terakhir): ?>
                <div class="stat-change up"><?= date('d M Y', strtotime($terakhir['submitted_at'])) ?></div>
            <?php else: ?>
                <div class="stat-change down">Belum pernah mengisi</div>
            <?php endif; ?>
        </div>
        
        <div class="stat-card fade-in-up delay-2">
            <div class="stat-icon <?= $terakhir && $terakhir['ergo_risk'] == 'Tinggi' ? 'red' : ($terakhir && $terakhir['ergo_risk'] == 'Sedang' ? 'yellow' : 'green') ?>">
                <i class="fas fa-user-injured"></i>
            </div>
            <h4 class="stat-number"><?= $terakhir ? $terakhir['ergo_risk'] : '-' ?></h4>
            <p class="stat-label">Risiko Ergonomi</p>
            <?php if ($terakhir): ?>
                <div class="stat-change">Skor: <?= $terakhir['ergonomic_score'] ?> / <?= $max_ergo ?></div>
            <?php endif; ?>
        </div>
        
        <div class="stat-card fade-in-up delay-3">
            <div class="stat-icon <?= $terakhir && $terakhir['psiko_risk'] == 'Tinggi' ? 'red' : ($terakhir && $terakhir['psiko_risk'] == 'Sedang' ? 'yellow' : 'green') ?>">
                <i class="fas fa-brain"></i>
            </div>
            <h4 class="stat-number"><?= $terakhir ? $terakhir['psiko_risk'] : '-' ?></h4>
            <p class="stat-label">Risiko Psikososial</p>
            <?php if ($terakhir): ?>
                <div class="stat-change">Skor: <?= $terakhir['psychosocial_score'] ?> / <?= $max_psiko ?></div>
            <?php endif; ?>
        </div>
        
        <div class="stat-card fade-in-up delay-4">
            <div class="stat-icon <?= $ada_periode ? 'yellow' : 'green' ?>">
                <i class="fas fa-calendar-check"></i>
            </div>
            <h4 class="stat-number"><?= $ada_periode ? '📢' : '✅' ?></h4>
            <p class="stat-label">Assessment Aktif</p>
            <?php if ($ada_periode): ?>
                <div class="stat-change down">Ada periode aktif! <a href="form_assessment.php" style="color:#2563eb; text-decoration:none;">Isi sekarang</a></div>
            <?php else: ?>
                <div class="stat-change up">Tidak ada periode aktif</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- GRAFIK + RINGKASAN -->
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card-modern fade-in-up delay-2">
                <div class="card-header-custom">
                    <h5><i class="fas fa-chart-line me-2" style="color:#4fc3f7;"></i> Tren Risiko Anda</h5>
                    <span class="badge" style="background:#e2e8f0; color:#475569; padding:4px 14px; border-radius:20px;">
                        <?= $ada_tren ? count($tren) . ' assessment terakhir' : 'Belum ada data' ?>
                    </span>
                </div>
                <div class="card-body-custom" style="min-height:360px;">
                    <?php if ($ada_tren): ?>
                        <canvas id="trenChart" height="390" style="width:100%;"></canvas>
                        <?php if ($is_single): ?>
                            <div style="text-align:center; margin-top:8px; font-size:13px; color:#94a3b8;">
                                💡 Isi assessment lagi untuk melihat tren garis.
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div style="text-align:center; padding:40px; color:#94a3b8;">
                            <div style="font-size:48px; margin-bottom:12px;">📊</div>
                            <h5 style="font-weight:600; color:#1a1a2e;">Belum Ada Data</h5>
                            <p>Isi assessment pertama Anda untuk melihat tren.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- RINGKASAN RIWAYAT -->
        <div class="col-lg-4">
            <div class="card-modern fade-in-up delay-3">
                <div class="card-header-custom">
                    <h5><i class="fas fa-chart-pie me-2" style="color:#8b5cf6;"></i> Ringkasan Riwayat</h5>
                    <a href="riwayat.php" class="card-action">Lihat Semua <i class="fas fa-arrow-right ms-1"></i></a>
                </div>
                <div class="card-body-custom" style="padding:16px 20px;">
                    <!-- Ergonomi -->
                    <div style="margin-bottom:14px;">
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                            <span style="font-size:16px;">🟠</span>
                            <span style="font-weight:600; color:#1a1a2e; font-size:14px;">Ergonomi</span>
                        </div>
                        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:6px;">
                            <div style="background:#d1fae5; border-radius:6px; padding:6px 8px; text-align:center;">
                                <div style="font-size:11px; color:#059669;">Rendah</div>
                                <div style="font-weight:700; font-size:16px; color:#059669;"><?= $stat_ergo['Rendah'] ?? 0 ?></div>
                            </div>
                            <div style="background:#fef3c7; border-radius:6px; padding:6px 8px; text-align:center;">
                                <div style="font-size:11px; color:#d97706;">Sedang</div>
                                <div style="font-weight:700; font-size:16px; color:#d97706;"><?= $stat_ergo['Sedang'] ?? 0 ?></div>
                            </div>
                            <div style="background:#fee2e2; border-radius:6px; padding:6px 8px; text-align:center;">
                                <div style="font-size:11px; color:#dc2626;">Tinggi</div>
                                <div style="font-weight:700; font-size:16px; color:#dc2626;"><?= $stat_ergo['Tinggi'] ?? 0 ?></div>
                            </div>
                        </div>
                    </div>
                    <!-- Psikososial -->
                    <div>
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                            <span style="font-size:16px;">🟣</span>
                            <span style="font-weight:600; color:#1a1a2e; font-size:14px;">Psikososial</span>
                        </div>
                        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:6px;">
                            <div style="background:#d1fae5; border-radius:6px; padding:6px 8px; text-align:center;">
                                <div style="font-size:11px; color:#059669;">Rendah</div>
                                <div style="font-weight:700; font-size:16px; color:#059669;"><?= $stat_psiko['Rendah'] ?? 0 ?></div>
                            </div>
                            <div style="background:#fef3c7; border-radius:6px; padding:6px 8px; text-align:center;">
                                <div style="font-size:11px; color:#d97706;">Sedang</div>
                                <div style="font-weight:700; font-size:16px; color:#d97706;"><?= $stat_psiko['Sedang'] ?? 0 ?></div>
                            </div>
                            <div style="background:#fee2e2; border-radius:6px; padding:6px 8px; text-align:center;">
                                <div style="font-size:11px; color:#dc2626;">Tinggi</div>
                                <div style="font-weight:700; font-size:16px; color:#dc2626;"><?= $stat_psiko['Tinggi'] ?? 0 ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
CHART.JS — LINE CHART (GARIS)
============================================================ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($ada_tren): ?>
    const canvas = document.getElementById('trenChart');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const labels = <?= json_encode(array_column($tren, 'tanggal')) ?>;
    const ergoData = <?= json_encode(array_column($tren, 'ergonomic_score')) ?>;
    const psikoData = <?= json_encode(array_column($tren, 'psychosocial_score')) ?>;
    const isSingle = <?= $is_single ? 'true' : 'false' ?>;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Skor Ergonomi',
                    data: ergoData,
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    borderWidth: 3,
                    pointBackgroundColor: '#2563eb',
                    pointRadius: isSingle ? 8 : 5,
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    fill: true,
                    tension: 0.3,
                    showLine: true,
                    spanGaps: true,
                },
                {
                    label: 'Skor Psikososial',
                    data: psikoData,
                    borderColor: '#7c3aed',
                    backgroundColor: 'rgba(124, 58, 237, 0.1)',
                    borderWidth: 3,
                    pointBackgroundColor: '#7c3aed',
                    pointRadius: isSingle ? 8 : 5,
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    fill: true,
                    tension: 0.3,
                    showLine: true,
                    spanGaps: true,
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
                        padding: 20,
                        font: { size: 13, weight: 'bold' }
                    }
                },
                tooltip: {
                    callbacks: {
                        afterBody: function() {
                            if (isSingle) {
                                return '💡 Isi assessment lagi untuk melihat tren garis.';
                            }
                            return '';
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 12 } }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.05)' },
                    ticks: { font: { size: 12 } }
                }
            }
        }
    });
    <?php endif; ?>
});
</script>

<?php include '../includes/footer.php'; ?>