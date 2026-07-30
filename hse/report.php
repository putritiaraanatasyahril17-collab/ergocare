<?php
include '../config/database.php';
cek_role('HSE');

$user_id = $_SESSION['user_id'];
$page_title = 'Laporan Assessment';
$page_subtitle = 'Lihat dan cetak laporan assessment karyawan';
$active_page = 'hse_report';
$base_url = '../';

// Ambil filter dari GET
$period_id = isset($_GET['period_id']) ? (int)$_GET['period_id'] : 0;
$department_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;

// Ambil daftar periode untuk filter
$query_periode = "SELECT period_id, period_name FROM assessment_periods ORDER BY period_id DESC";
$result_periode = mysqli_query($conn, $query_periode);

// Ambil daftar departemen untuk filter
$query_dept = "SELECT department_id, department_name FROM departments WHERE department_id IN (1,2,3)";
$result_dept = mysqli_query($conn, $query_dept);

// ============================================================
// QUERY DATA LAPORAN
// ============================================================
$query = "SELECT 
            e.full_name, 
            e.employee_code,
            d.department_name,
            p.period_name,
            r.ergonomic_score,
            r.psychosocial_score,
            rc1.category_name as ergo_risk,
            rc2.category_name as psiko_risk,
            r.conclusion,
            s.submitted_at
          FROM assessment_sessions s
          JOIN employees e ON s.employee_id = e.employee_id
          JOIN departments d ON e.department_id = d.department_id
          JOIN assessment_periods p ON s.period_id = p.period_id
          JOIN assessment_results r ON s.session_id = r.session_id
          JOIN risk_categories rc1 ON r.ergonomic_risk_id = rc1.risk_category_id
          JOIN risk_categories rc2 ON r.psychosocial_risk_id = rc2.risk_category_id
          WHERE s.status = 'Submitted'";

if ($period_id > 0) {
    $query .= " AND s.period_id = $period_id";
}
if ($department_id > 0) {
    $query .= " AND e.department_id = $department_id";
}
$query .= " ORDER BY s.submitted_at DESC";

$result = mysqli_query($conn, $query);
$total_data = mysqli_num_rows($result);

include '../includes/header.php';
?>

<div class="fade-in-up">
    <!-- FORM FILTER -->
    <div class="card-modern mb-4">
        <div class="card-header-custom">
            <h5><i class="fas fa-filter me-2" style="color:#4fc3f7;"></i> Filter Laporan</h5>
        </div>
        <div class="card-body-custom">
            <form method="GET" action="" id="filterForm">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Periode Assessment</label>
                        <select name="period_id" class="form-select" style="border-radius:10px; padding:10px 14px;">
                            <option value="0">Semua Periode</option>
                            <?php 
                            mysqli_data_seek($result_periode, 0);
                            while ($row = mysqli_fetch_assoc($result_periode)): 
                                $selected = ($period_id == $row['period_id']) ? 'selected' : '';
                            ?>
                                <option value="<?= $row['period_id'] ?>" <?= $selected ?>><?= $row['period_name'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Departemen</label>
                        <select name="department_id" class="form-select" style="border-radius:10px; padding:10px 14px;">
                            <option value="0">Semua Departemen</option>
                            <?php 
                            mysqli_data_seek($result_dept, 0);
                            while ($row = mysqli_fetch_assoc($result_dept)): 
                                $selected = ($department_id == $row['department_id']) ? 'selected' : '';
                            ?>
                                <option value="<?= $row['department_id'] ?>" <?= $selected ?>><?= $row['department_name'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div style="display:flex; gap:10px; width:100%;">
                            <button type="submit" class="btn btn-primary" style="border-radius:10px; padding:10px 24px; flex:1;">
                                <i class="fas fa-search me-2"></i> Tampilkan
                            </button>
                            <button type="button" onclick="window.print()" class="btn btn-success" style="border-radius:10px; padding:10px 24px; flex:1;">
                                <i class="fas fa-print me-2"></i> Cetak / PDF
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================
    LAPORAN DATA (untuk dicetak)
    ============================================================ -->
    <div id="laporanContainer">
        <!-- Header Laporan -->
        <div style="text-align:center; padding:20px 0; border-bottom:2px solid #1a1a2e; margin-bottom:20px;">
            <!-- LOGO -->
            <?php if (file_exists('../assets/img/logoo.png')): ?>
                <img src="../assets/img/logoo.png" alt="Logoo" style="height:50px; margin-bottom:6px;">
            <?php endif; ?>
            
            <!-- ERGONOMI & PSIKOSOSIAL TES -->
            <h1 style="font-weight:800; color:#1a1a2e; margin:4px 0 2px 0; font-size:18px;">
                ERGONOMI &amp; PSIKOSOSIAL TES
            </h1>
            
            <!-- PT Radiant Group Cabang Duri -->
            <p style="color:#94a3b8; font-size:12px; letter-spacing:1px; margin:0;">
                PT Radiant Group Cabang Duri
            </p>
            
            <p style="color:#64748b; margin:8px 0 4px 0; font-size:13px;">Sistem Assessment Ergonomi &amp; Psikososial</p>
            <p style="color:#475569; font-weight:600; margin:4px 0; font-size:15px;">
                LAPORAN ASSESSMENT
            </p>
            <p style="color:#94a3b8; font-size:12px;">
                Tanggal Cetak: <?= date('d F Y H:i:s') ?>
            </p>
            <?php
            $filter_info = [];
            if ($period_id > 0) {
                $p = mysqli_fetch_assoc(mysqli_query($conn, "SELECT period_name FROM assessment_periods WHERE period_id=$period_id"));
                $filter_info[] = "Periode: " . ($p['period_name'] ?? '');
            }
            if ($department_id > 0) {
                $d = mysqli_fetch_assoc(mysqli_query($conn, "SELECT department_name FROM departments WHERE department_id=$department_id"));
                $filter_info[] = "Departemen: " . ($d['department_name'] ?? '');
            }
            if (!empty($filter_info)) {
                echo '<p style="color:#475569; font-size:12px;"><strong>Filter:</strong> ' . implode(' | ', $filter_info) . '</p>';
            }
            ?>
            <p style="color:#475569; font-size:12px;"><strong>Total Data:</strong> <?= $total_data ?> karyawan</p>
        </div>

        <!-- Tabel Data -->
        <?php if ($total_data > 0): ?>
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:12px;">
                <thead>
                    <tr style="background:#1a1a2e; color:white;">
                        <th style="padding:8px 6px; text-align:center;">No</th>
                        <th style="padding:8px 6px; text-align:left;">Nama</th>
                        <th style="padding:8px 6px; text-align:left;">Kode</th>
                        <th style="padding:8px 6px; text-align:left;">Departemen</th>
                        <th style="padding:8px 6px; text-align:left;">Periode</th>
                        <th style="padding:8px 6px; text-align:center;">Skor Ergo</th>
                        <th style="padding:8px 6px; text-align:center;">Skor Psiko</th>
                        <th style="padding:8px 6px; text-align:center;">Risiko Ergo</th>
                        <th style="padding:8px 6px; text-align:center;">Risiko Psiko</th>
                        <th style="padding:8px 6px; text-align:center;">Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    while ($row = mysqli_fetch_assoc($result)): 
                        $ergo_color = strtolower($row['ergo_risk']) == 'tinggi' ? '#dc2626' : (strtolower($row['ergo_risk']) == 'sedang' ? '#d97706' : '#059669');
                        $psiko_color = strtolower($row['psiko_risk']) == 'tinggi' ? '#dc2626' : (strtolower($row['psiko_risk']) == 'sedang' ? '#d97706' : '#059669');
                    ?>
                    <tr style="border-bottom:1px solid #e2e8f0;">
                        <td style="padding:6px; text-align:center;"><?= $no ?></td>
                        <td style="padding:6px;"><strong><?= htmlspecialchars($row['full_name']) ?></strong></td>
                        <td style="padding:6px;"><?= $row['employee_code'] ?></td>
                        <td style="padding:6px;"><?= $row['department_name'] ?></td>
                        <td style="padding:6px;"><?= $row['period_name'] ?></td>
                        <td style="padding:6px; text-align:center;"><?= $row['ergonomic_score'] ?>/30</td>
                        <td style="padding:6px; text-align:center;"><?= $row['psychosocial_score'] ?>/25</td>
                        <td style="padding:6px; text-align:center;">
                            <span style="background:<?= $ergo_color ?>20; color:<?= $ergo_color ?>; padding:2px 10px; border-radius:12px; font-weight:600; font-size:11px;">
                                <?= $row['ergo_risk'] ?>
                            </span>
                        </td>
                        <td style="padding:6px; text-align:center;">
                            <span style="background:<?= $psiko_color ?>20; color:<?= $psiko_color ?>; padding:2px 10px; border-radius:12px; font-weight:600; font-size:11px;">
                                <?= $row['psiko_risk'] ?>
                            </span>
                        </td>
                        <td style="padding:6px; text-align:center; font-size:11px; color:#64748b;">
                            <?= date('d-m-Y', strtotime($row['submitted_at'])) ?>
                        </td>
                    </tr>
                    <?php 
                    $no++;
                    endwhile; 
                    ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <div style="text-align:center; padding:60px 20px; color:#94a3b8;">
                <div style="font-size:48px; margin-bottom:16px;">📭</div>
                <h5 style="font-weight:600; color:#1a1a2e;">Tidak Ada Data</h5>
                <p>Belum ada assessment yang selesai diisi.</p>
            </div>
        <?php endif; ?>

        <!-- Footer -->
        <div style="margin-top:30px; text-align:center; color:#94a3b8; font-size:10px; border-top:1px solid #e2e8f0; padding-top:12px;">
            <p>PT Radiant Group Cabang Duri &bull; ERGONOMI &amp; PSIKOSOSIAL TES</p>
            <p>&copy; <?= date('Y') ?> PT Radiant Group Cabang Duri</p>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>