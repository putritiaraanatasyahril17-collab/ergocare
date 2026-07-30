<?php
include '../config/database.php';
cek_role('HSE');

$user_id = $_SESSION['user_id'];
$page_title = 'Dashboard HSE';
$page_subtitle = 'Overview risiko ergonomi & psikososial seluruh karyawan';
$active_page = 'hse_dashboard';
$base_url = '../';

// ============================================================
// SKOR MAKSIMAL DINAMIS (berdasarkan jumlah pertanyaan aktif)
// ============================================================
$q_max_ergo = mysqli_query($conn, "SELECT COUNT(*) as total FROM assessment_questions WHERE factor_id = 1 AND status = 'Aktif'");
$data_ergo = mysqli_fetch_assoc($q_max_ergo);
$max_ergo = ($data_ergo['total'] ?? 10) * 3;

$q_max_psiko = mysqli_query($conn, "SELECT COUNT(*) as total FROM assessment_questions WHERE factor_id = 2 AND status = 'Aktif'");
$data_psiko = mysqli_fetch_assoc($q_max_psiko);
$max_psiko = ($data_psiko['total'] ?? 5) * 5;

// ============================================================
// STATISTIK UTAMA
// ============================================================
$total_karyawan = 0;
$q = mysqli_query($conn, "SELECT COUNT(*) as total FROM employees WHERE is_active = 1");
if ($q && $row = mysqli_fetch_assoc($q)) $total_karyawan = $row['total'];

$total_assessment = 0;
$q = mysqli_query($conn, "SELECT COUNT(*) as total FROM assessment_sessions WHERE status = 'Submitted'");
if ($q && $row = mysqli_fetch_assoc($q)) $total_assessment = $row['total'];

$risiko_tinggi = 0;
$q = mysqli_query($conn, "SELECT COUNT(*) as total 
                         FROM assessment_results r 
                         JOIN assessment_sessions s ON r.session_id = s.session_id 
                         WHERE r.ergonomic_risk_id = 3 AND s.status = 'Submitted'");
if ($q && $row = mysqli_fetch_assoc($q)) $risiko_tinggi = $row['total'];

$belum_assessment = 0;
$q = mysqli_query($conn, "SELECT COUNT(*) as total 
                         FROM employees e 
                         LEFT JOIN assessment_sessions s ON e.employee_id = s.employee_id AND s.status = 'Submitted' 
                         WHERE s.session_id IS NULL AND e.is_active = 1");
if ($q && $row = mysqli_fetch_assoc($q)) $belum_assessment = $row['total'];

// ============================================================
// DATA GRAFIK ERGONOMI PER DEPARTEMEN
// ============================================================
$ergo_data = [];
$total_ergo = 0;
$q = mysqli_query($conn, "SELECT 
                            d.department_name,
                            COUNT(CASE WHEN r.ergonomic_risk_id = 3 THEN 1 END) as tinggi,
                            COUNT(CASE WHEN r.ergonomic_risk_id = 2 THEN 1 END) as sedang,
                            COUNT(CASE WHEN r.ergonomic_risk_id = 1 THEN 1 END) as rendah
                          FROM departments d
                          LEFT JOIN employees e ON d.department_id = e.department_id AND e.is_active = 1
                          LEFT JOIN assessment_sessions s ON e.employee_id = s.employee_id AND s.status = 'Submitted'
                          LEFT JOIN assessment_results r ON s.session_id = r.session_id
                          WHERE d.department_id IN (1,2,3)
                          GROUP BY d.department_id");
while ($row = mysqli_fetch_assoc($q)) {
    $ergo_data[] = $row;
    $total_ergo += $row['tinggi'] + $row['sedang'] + $row['rendah'];
}

// ============================================================
// DATA GRAFIK PSIKOSOSIAL PER DEPARTEMEN
// ============================================================
$psiko_data = [];
$total_psiko = 0;
$q = mysqli_query($conn, "SELECT 
                            d.department_name,
                            COUNT(CASE WHEN r.psychosocial_risk_id = 6 THEN 1 END) as tinggi,
                            COUNT(CASE WHEN r.psychosocial_risk_id = 5 THEN 1 END) as sedang,
                            COUNT(CASE WHEN r.psychosocial_risk_id = 4 THEN 1 END) as rendah
                          FROM departments d
                          LEFT JOIN employees e ON d.department_id = e.department_id AND e.is_active = 1
                          LEFT JOIN assessment_sessions s ON e.employee_id = s.employee_id AND s.status = 'Submitted'
                          LEFT JOIN assessment_results r ON s.session_id = r.session_id
                          WHERE d.department_id IN (1,2,3)
                          GROUP BY d.department_id");
while ($row = mysqli_fetch_assoc($q)) {
    $psiko_data[] = $row;
    $total_psiko += $row['tinggi'] + $row['sedang'] + $row['rendah'];
}

include '../includes/header.php';
?>

<div class="fade-in-up">
    <!-- ============================================================
    STATISTIK CARDS
    ============================================================ -->
    <div class="stat-cards">
        <div class="stat-card fade-in-up delay-1">
            <div class="stat-icon blue"><i class="fas fa-users"></i></div>
            <h4 class="stat-number"><?= $total_karyawan ?></h4>
            <p class="stat-label">Total Karyawan Aktif</p>
        </div>
        <div class="stat-card fade-in-up delay-2">
            <div class="stat-icon green"><i class="fas fa-clipboard-check"></i></div>
            <h4 class="stat-number"><?= $total_assessment ?></h4>
            <p class="stat-label">Assessment Terisi</p>
            <div class="stat-change up"><?= $total_karyawan > 0 ? round(($total_assessment / $total_karyawan) * 100) : 0 ?>% partisipasi</div>
        </div>
        <div class="stat-card fade-in-up delay-3">
            <div class="stat-icon red"><i class="fas fa-exclamation-triangle"></i></div>
            <h4 class="stat-number"><?= $risiko_tinggi ?></h4>
            <p class="stat-label">Risiko Tinggi (Ergonomi)</p>
            <div class="stat-change down">Perlu intervensi</div>
        </div>
        <div class="stat-card fade-in-up delay-4">
            <div class="stat-icon yellow"><i class="fas fa-hourglass-half"></i></div>
            <h4 class="stat-number"><?= $belum_assessment ?></h4>
            <p class="stat-label">Belum Mengisi Assessment</p>
            <div class="stat-change down">Segera ingatkan</div>
        </div>
    </div>

    <!-- ============================================================
    GRAFIK RISIKO ERGONOMI & PSIKOSOSIAL PER DEPARTEMEN
    ============================================================ -->
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card-modern fade-in-up delay-2">
                <div class="card-header-custom">
                    <h5><i class="fas fa-user-injured me-2" style="color:#ef4444;"></i> Risiko Ergonomi per Departemen</h5>
                    <span class="badge" style="background:#e2e8f0; color:#475569; padding:4px 14px; border-radius:20px;">
                        Stacked Bar
                    </span>
                </div>
                <div class="card-body-custom" style="min-height:200px;">
                    <?php if ($total_ergo > 0): ?>
                        <canvas id="ergoChart" height="130"></canvas>
                    <?php else: ?>
                        <div style="text-align:center; padding:40px; color:#94a3b8;">
                            <div style="font-size:48px; margin-bottom:12px;">📊</div>
                            <h5 style="font-weight:600; color:#1a1a2e;">Belum Ada Data</h5>
                            <p>Belum ada data ergonomi yang tersedia.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card-modern fade-in-up delay-3">
                <div class="card-header-custom">
                    <h5><i class="fas fa-brain me-2" style="color:#8b5cf6;"></i> Risiko Psikososial per Departemen</h5>
                    <span class="badge" style="background:#e2e8f0; color:#475569; padding:4px 14px; border-radius:20px;">
                        Stacked Bar
                    </span>
                </div>
                <div class="card-body-custom" style="min-height:200px;">
                    <?php if ($total_psiko > 0): ?>
                        <canvas id="psikoChart" height="130"></canvas>
                    <?php else: ?>
                        <div style="text-align:center; padding:40px; color:#94a3b8;">
                            <div style="font-size:48px; margin-bottom:12px;">📊</div>
                            <h5 style="font-weight:600; color:#1a1a2e;">Belum Ada Data</h5>
                            <p>Belum ada data psikososial yang tersedia.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================
    TABEL KARYAWAN RISIKO TERTINGGI
    ============================================================ -->
    <div class="card-modern fade-in-up delay-4">
        <div class="card-header-custom">
            <h5><i class="fas fa-user-md me-2" style="color:#ef4444;"></i> Karyawan Risiko Tertinggi</h5>
            <span class="badge bg-danger" style="font-size:11px; padding:4px 12px;">⚠️ Prioritas</span>
        </div>
        <div class="card-body-custom" style="padding:0;">
            <div style="overflow-x:auto;">
                <table class="table-modern" style="min-width:100%;">
                    <thead>
                        <tr>
                            <th style="width:25%;">Karyawan</th>
                            <th style="width:15%;">Departemen</th>
                            <th style="width:18%; text-align:center;">Skor Ergo</th>
                            <th style="width:18%; text-align:center;">Skor Psiko</th>
                            <th style="width:24%; text-align:center;">Status Risiko</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query_tinggi = "SELECT 
                                            e.full_name, 
                                            e.employee_code, 
                                            d.department_name, 
                                            r.ergonomic_score, 
                                            r.psychosocial_score,
                                            rc1.category_name as ergo_risk,
                                            rc2.category_name as psiko_risk
                                         FROM employees e
                                         JOIN departments d ON e.department_id = d.department_id
                                         JOIN assessment_sessions s ON e.employee_id = s.employee_id AND s.status = 'Submitted'
                                         JOIN assessment_results r ON s.session_id = r.session_id
                                         JOIN risk_categories rc1 ON r.ergonomic_risk_id = rc1.risk_category_id
                                         JOIN risk_categories rc2 ON r.psychosocial_risk_id = rc2.risk_category_id
                                         WHERE r.ergonomic_risk_id = 3 OR r.psychosocial_risk_id = 6
                                         ORDER BY r.ergonomic_score DESC, r.psychosocial_score DESC
                                         LIMIT 10";
                        $result_tinggi = mysqli_query($conn, $query_tinggi);
                        
                        if (mysqli_num_rows($result_tinggi) > 0):
                            while ($row = mysqli_fetch_assoc($result_tinggi)):
                                $status_prioritas = [];
                                if ($row['ergo_risk'] == 'Tinggi') $status_prioritas[] = '<span style="color:#ef4444;">🔴 Ergo</span>';
                                if ($row['psiko_risk'] == 'Tinggi') $status_prioritas[] = '<span style="color:#8b5cf6;">🟣 Psiko</span>';
                                $status_text = implode(' + ', $status_prioritas);
                        ?>
                        <tr>
                            <td>
                                <strong><?= $row['full_name'] ?></strong><br>
                                <small style="color:#94a3b8; font-size:11px;"><?= $row['employee_code'] ?></small>
                            </td>
                            <td><?= $row['department_name'] ?></td>
                            <td style="text-align:center;">
                                <span class="badge-risk <?= strtolower($row['ergo_risk']) ?>" style="font-size:12px; padding:4px 12px;">
                                    <?= $row['ergonomic_score'] ?>/<?= $max_ergo ?>
                                </span>
                            </td>
                            <td style="text-align:center;">
                                <span class="badge-risk <?= strtolower($row['psiko_risk']) ?>" style="font-size:12px; padding:4px 12px;">
                                    <?= $row['psychosocial_score'] ?>/<?= $max_psiko ?>
                                </span>
                            </td>
                            <td style="text-align:center;">
                                <span style="background:#fef3c7; color:#d97706; padding:4px 12px; border-radius:20px; font-size:11px; font-weight:600; display:inline-block;">
                                    <?= $status_text ?>
                                </span>
                            </td>
                        </tr>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding:30px; color:#94a3b8;">
                                <div style="font-size:36px; margin-bottom:8px;">✅</div>
                                Tidak ada karyawan dengan risiko tinggi.
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
CHART.JS SCRIPT
============================================================ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
<?php if ($total_ergo > 0): ?>
const ctxErgo = document.getElementById('ergoChart').getContext('2d');
const ergoLabels = <?= json_encode(array_column($ergo_data, 'department_name')) ?>;
const ergoTinggi = <?= json_encode(array_column($ergo_data, 'tinggi')) ?>;
const ergoSedang = <?= json_encode(array_column($ergo_data, 'sedang')) ?>;
const ergoRendah = <?= json_encode(array_column($ergo_data, 'rendah')) ?>;

new Chart(ctxErgo, {
    type: 'bar',
    data: {
        labels: ergoLabels,
        datasets: [
            { label: 'Tinggi', data: ergoTinggi, backgroundColor: '#ef4444', borderRadius: 2 },
            { label: 'Sedang', data: ergoSedang, backgroundColor: '#f59e0b', borderRadius: 2 },
            { label: 'Rendah', data: ergoRendah, backgroundColor: '#10b981', borderRadius: 2 }
        ]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: true,
                position: 'top',
                labels: { usePointStyle: true, pointStyle: 'circle', padding: 10, font: { size: 11 } }
            }
        },
        scales: {
            x: { stacked: true, grid: { display: false }, title: { display: true, text: 'Jumlah', font: { size: 10 } } },
            y: { stacked: true, grid: { display: false } }
        }
    }
});
<?php endif; ?>

<?php if ($total_psiko > 0): ?>
const ctxPsiko = document.getElementById('psikoChart').getContext('2d');
const psikoLabels = <?= json_encode(array_column($psiko_data, 'department_name')) ?>;
const psikoTinggi = <?= json_encode(array_column($psiko_data, 'tinggi')) ?>;
const psikoSedang = <?= json_encode(array_column($psiko_data, 'sedang')) ?>;
const psikoRendah = <?= json_encode(array_column($psiko_data, 'rendah')) ?>;

new Chart(ctxPsiko, {
    type: 'bar',
    data: {
        labels: psikoLabels,
        datasets: [
            { label: 'Tinggi', data: psikoTinggi, backgroundColor: '#ef4444', borderRadius: 2 },
            { label: 'Sedang', data: psikoSedang, backgroundColor: '#f59e0b', borderRadius: 2 },
            { label: 'Rendah', data: psikoRendah, backgroundColor: '#10b981', borderRadius: 2 }
        ]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: true,
                position: 'top',
                labels: { usePointStyle: true, pointStyle: 'circle', padding: 10, font: { size: 11 } }
            }
        },
        scales: {
            x: { stacked: true, grid: { display: false }, title: { display: true, text: 'Jumlah', font: { size: 10 } } },
            y: { stacked: true, grid: { display: false } }
        }
    }
});
<?php endif; ?>
</script>

<?php include '../includes/footer.php'; ?>