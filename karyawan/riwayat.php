<?php
include '../config/database.php';
cek_role('Karyawan');

$employee_id = $_SESSION['employee_id'];
$page_title = 'Riwayat Assessment';
$page_subtitle = 'Lihat semua riwayat assessment Anda';
$active_page = 'karyawan_riwayat';
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
// AMBIL DATA RIWAYAT + STATUS TRACKING
// ============================================================
$query = "SELECT 
            s.*, 
            r.ergonomic_score, 
            r.psychosocial_score,
            rc_ergo.category_name as ergo_risk, 
            rc_psiko.category_name as psiko_risk,
            t.status as tracking_status,
            t.tindakan,
            t.updated_at as tracking_updated_at
          FROM assessment_sessions s
          JOIN assessment_results r ON s.session_id = r.session_id
          JOIN risk_categories rc_ergo ON r.ergonomic_risk_id = rc_ergo.risk_category_id
          JOIN risk_categories rc_psiko ON r.psychosocial_risk_id = rc_psiko.risk_category_id
          LEFT JOIN assessment_tracking t ON s.session_id = t.session_id
          WHERE s.employee_id = $employee_id AND s.status = 'Submitted'
          ORDER BY s.submitted_at DESC";
$result = mysqli_query($conn, $query);

include '../includes/header.php';
?>

<div class="fade-in-up">
    <div class="card-modern">
        <div class="card-header-custom">
            <h5><i class="fas fa-history me-2" style="color:#4fc3f7;"></i> Riwayat Assessment Saya</h5>
            <span class="badge" style="background:#e2e8f0; color:#475569; padding:4px 14px; border-radius:20px;">
                <?= mysqli_num_rows($result) ?> data
            </span>
        </div>
        <div class="card-body-custom" style="padding:0;">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <div style="overflow-x:auto;">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Periode</th>
                                <th style="text-align:center;">Skor Ergo</th>
                                <th style="text-align:center;">Risiko Ergo</th>
                                <th style="text-align:center;">Skor Psiko</th>
                                <th style="text-align:center;">Risiko Psiko</th>
                                <th style="text-align:center;">Status Tindak Lanjut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($result)): 
                                // Warna status tracking
                                $tracking_status = $row['tracking_status'] ?? 'Belum Diproses';
                                $status_bg = [
                                    'Belum Diproses' => '#fef3c7',
                                    'Perlu Tindak Lanjut' => '#fef3c7',
                                    'Sedang Diproses' => '#dbeafe',
                                    'Selesai' => '#d1fae5'
                                ];
                                $status_color = [
                                    'Belum Diproses' => '#d97706',
                                    'Perlu Tindak Lanjut' => '#d97706',
                                    'Sedang Diproses' => '#3b82f6',
                                    'Selesai' => '#059669'
                                ];
                                $bg = $status_bg[$tracking_status] ?? '#f1f5f9';
                                $color = $status_color[$tracking_status] ?? '#64748b';
                                
                                // Icon status
                                $status_icon = [
                                    'Belum Diproses' => '⏳',
                                    'Perlu Tindak Lanjut' => '⏳',
                                    'Sedang Diproses' => '⚙️',
                                    'Selesai' => '✅'
                                ];
                                $icon = $status_icon[$tracking_status] ?? '📌';
                            ?>
                            <tr>
                                <td><?= date('d M Y', strtotime($row['submitted_at'])) ?></td>
                                <td><?= $row['period_id'] ?></td>
                                <td style="text-align:center;">
                                    <strong><?= $row['ergonomic_score'] ?></strong> / <?= $max_ergo ?>
                                </td>
                                <td style="text-align:center;">
                                    <span class="badge-risk <?= strtolower($row['ergo_risk']) ?>">
                                        <?= $row['ergo_risk'] ?>
                                    </span>
                                </td>
                                <td style="text-align:center;">
                                    <strong><?= $row['psychosocial_score'] ?></strong> / <?= $max_psiko ?>
                                </td>
                                <td style="text-align:center;">
                                    <span class="badge-risk <?= strtolower($row['psiko_risk']) ?>">
                                        <?= $row['psiko_risk'] ?>
                                    </span>
                                </td>
                                <td style="text-align:center;">
                                    <span style="background:<?= $bg ?>; color:<?= $color ?>; padding:4px 14px; border-radius:20px; font-size:12px; font-weight:600; display:inline-block;">
                                        <?= $icon ?> <?= $tracking_status ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="text-align:center; padding:40px 20px; color:#94a3b8;">
                    <div style="font-size:48px; margin-bottom:16px;">📭</div>
                    <h5 style="font-weight:600; color:#1a1a2e;">Belum Ada Riwayat</h5>
                    <p>Anda belum pernah mengisi assessment. <a href="form_assessment.php" style="color:#2563eb;">Mulai sekarang</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>