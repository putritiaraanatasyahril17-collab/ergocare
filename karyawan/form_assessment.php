<?php
include '../config/database.php';
cek_role('Karyawan');

$user_id = $_SESSION['user_id'];
$employee_id = $_SESSION['employee_id'];
$page_title = 'Form Assessment';
$page_subtitle = 'Isi assessment ergonomi & psikososial';
$active_page = 'karyawan_form';
$base_url = '../';

// ============================================================
// CEK PERIODE AKTIF
// ============================================================
$query_periode = "SELECT period_id, period_name FROM assessment_periods 
                  WHERE status = 'Aktif' 
                  AND CURDATE() BETWEEN start_date AND end_date
                  AND NOT EXISTS (
                      SELECT 1 FROM assessment_sessions s 
                      WHERE s.period_id = assessment_periods.period_id 
                      AND s.employee_id = $employee_id 
                      AND s.status = 'Submitted'
                  ) LIMIT 1";
$result_periode = mysqli_query($conn, $query_periode);
$periode = mysqli_fetch_assoc($result_periode);

if (!$periode) {
    echo "<div class='fade-in-up'>
            <div class='card-modern' style='max-width:600px; margin:40px auto; text-align:center; padding:40px;'>
                <div style='font-size:64px; margin-bottom:20px;'>📋</div>
                <h4 style='font-weight:700;'>Tidak Ada Assessment Aktif</h4>
                <p style='color:#64748b;'>Saat ini tidak ada periode assessment yang aktif untuk Anda isi.</p>
                <a href='dashboard.php' class='btn btn-primary' style='margin-top:16px; padding:10px 30px; border-radius:12px;'>
                    <i class='fas fa-arrow-left me-2'></i> Kembali ke Dashboard
                </a>
            </div>
          </div>";
    include '../includes/footer.php';
    exit();
}

$period_id = $periode['period_id'];
$period_name = $periode['period_name'];
$error = '';
$success = false;
$hasil = null;

// ============================================================
// AMBIL PERTANYAAN DARI DATABASE
// ============================================================
$query_ergo = "SELECT question_id, question_text, question_order 
               FROM assessment_questions 
               WHERE factor_id = 1 AND status = 'Aktif' 
               ORDER BY question_order ASC";
$result_ergo = mysqli_query($conn, $query_ergo);
$ergo_questions = [];
while ($row = mysqli_fetch_assoc($result_ergo)) {
    $ergo_questions[] = $row;
}

$query_psiko = "SELECT question_id, question_text, question_order 
                FROM assessment_questions 
                WHERE factor_id = 2 AND status = 'Aktif' 
                ORDER BY question_order ASC";
$result_psiko = mysqli_query($conn, $query_psiko);
$psiko_questions = [];
while ($row = mysqli_fetch_assoc($result_psiko)) {
    $psiko_questions[] = $row;
}

if (empty($ergo_questions) || empty($psiko_questions)) {
    echo "<div class='fade-in-up'>
            <div class='card-modern' style='max-width:600px; margin:40px auto; text-align:center; padding:40px;'>
                <div style='font-size:64px; margin-bottom:20px;'>⚠️</div>
                <h4 style='font-weight:700;'>Pertanyaan Belum Tersedia</h4>
                <p style='color:#64748b;'>Admin HSE belum mengatur pertanyaan assessment. Silakan hubungi tim HSE.</p>
                <a href='dashboard.php' class='btn btn-primary' style='margin-top:16px; padding:10px 30px; border-radius:12px;'>
                    <i class='fas fa-arrow-left me-2'></i> Kembali ke Dashboard
                </a>
            </div>
          </div>";
    include '../includes/footer.php';
    exit();
}

// ============================================================
// PROSES FORM
// ============================================================
if ($_POST) {
    $ergonomi_answers = isset($_POST['ergonomi']) ? $_POST['ergonomi'] : [];
    $psikososial_answers = isset($_POST['psikososial']) ? $_POST['psikososial'] : [];
    
    $skor_ergonomi = array_sum($ergonomi_answers);
    $skor_psikososial = array_sum($psikososial_answers);
    
    // KATEGORI ERGONOMI (berdasarkan jumlah pertanyaan)
    $max_ergo = count($ergo_questions) * 3;
    $persen_ergo = $max_ergo > 0 ? ($skor_ergonomi / $max_ergo) * 100 : 0;
    
    if ($persen_ergo >= 70) {
        $ergo_kategori = 'Tinggi';
        $ergo_risk_id = 3;
    } elseif ($persen_ergo >= 40) {
        $ergo_kategori = 'Sedang';
        $ergo_risk_id = 2;
    } else {
        $ergo_kategori = 'Rendah';
        $ergo_risk_id = 1;
    }
    
    // KATEGORI PSIKOSOSIAL (berdasarkan jumlah pertanyaan)
    $max_psiko = count($psiko_questions) * 5;
    $persen_psiko = $max_psiko > 0 ? ($skor_psikososial / $max_psiko) * 100 : 0;
    
    if ($persen_psiko >= 70) {
        $psiko_kategori = 'Tinggi';
        $psiko_risk_id = 6;
    } elseif ($persen_psiko >= 40) {
        $psiko_kategori = 'Sedang';
        $psiko_risk_id = 5;
    } else {
        $psiko_kategori = 'Rendah';
        $psiko_risk_id = 4;
    }
    
    // INSERT SESSION
    $query_session = "INSERT INTO assessment_sessions (period_id, employee_id, method_id, started_at, submitted_at, status) 
                      VALUES ($period_id, $employee_id, 0, NOW(), NOW(), 'Submitted')";
    
    if (mysqli_query($conn, $query_session)) {
        $session_id = mysqli_insert_id($conn);
        
        // INSERT JAWABAN ERGONOMI
        foreach ($ergonomi_answers as $index => $nilai) {
            $q_id = $ergo_questions[$index]['question_id'] ?? 0;
            $query_answer = "INSERT INTO assessment_answers (session_id, question_id, answer_score) 
                             VALUES ($session_id, $q_id, $nilai)";
            mysqli_query($conn, $query_answer);
        }
        
        // INSERT JAWABAN PSIKOSOSIAL
        foreach ($psikososial_answers as $index => $nilai) {
            $q_id = $psiko_questions[$index]['question_id'] ?? 0;
            $query_answer = "INSERT INTO assessment_answers (session_id, question_id, answer_score) 
                             VALUES ($session_id, $q_id, $nilai)";
            mysqli_query($conn, $query_answer);
        }
        
        // INSERT HASIL
        $conclusion = "Skor Ergonomi: $skor_ergonomi ($ergo_kategori) | Skor Psikososial: $skor_psikososial ($psiko_kategori)";
        $query_result = "INSERT INTO assessment_results (session_id, ergonomic_score, psychosocial_score, ergonomic_risk_id, psychosocial_risk_id, conclusion) 
                         VALUES ($session_id, $skor_ergonomi, $skor_psikososial, $ergo_risk_id, $psiko_risk_id, '$conclusion')";
        
        if (mysqli_query($conn, $query_result)) {
            // Auto create tracking
            $query_tracking = "INSERT INTO assessment_tracking (session_id, status, catatan, updated_by) 
                               VALUES ($session_id, 'Perlu Tindak Lanjut', 'Assessment selesai, perlu review HSE', 1)";
            mysqli_query($conn, $query_tracking);
            
            $success = true;
            $hasil = [
                'skor_ergonomi' => $skor_ergonomi,
                'skor_psikososial' => $skor_psikososial,
                'ergo_kategori' => $ergo_kategori,
                'psiko_kategori' => $psiko_kategori,
                'max_ergo' => $max_ergo,
                'max_psiko' => $max_psiko,
                'persen_ergo' => round($persen_ergo),
                'persen_psiko' => round($persen_psiko)
            ];
        } else {
            $error = "❌ Gagal menyimpan hasil: " . mysqli_error($conn);
        }
    } else {
        $error = "❌ Gagal menyimpan session: " . mysqli_error($conn);
    }
}

include '../includes/header.php';
?>

<div class="fade-in-up">
    <?php if ($success && $hasil): ?>
        <!-- ============================================================
        TAMPILAN HASIL ASSESSMENT + REKOMENDASI
        ============================================================ -->
        <div class="card-modern" style="max-width:800px; margin:20px auto; padding:30px;">
            <div style="text-align:center; margin-bottom:24px;">
                <div style="font-size:64px; margin-bottom:10px;">🎉</div>
                <h3 style="font-weight:700; color:#1a1a2e;">Assessment Berhasil!</h3>
                <p style="color:#64748b;">Terima kasih telah mengisi assessment untuk <strong><?= $period_name ?></strong></p>
            </div>
            
            <!-- SKOR -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="card" style="border:1px solid #e2e8f0; border-radius:12px; padding:16px 20px; border-left:4px solid <?= $hasil['ergo_kategori'] == 'Tinggi' ? '#ef4444' : ($hasil['ergo_kategori'] == 'Sedang' ? '#f59e0b' : '#10b981') ?>;">
                        <small style="color:#64748b;">Skor Ergonomi</small>
                        <h4 style="font-weight:700;"><?= $hasil['skor_ergonomi'] ?> / <?= $hasil['max_ergo'] ?></h4>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:4px;">
                            <span class="badge-risk <?= strtolower($hasil['ergo_kategori']) ?>">
                                <?= $hasil['ergo_kategori'] ?>
                            </span>
                            <small style="color:#94a3b8; font-size:11px;"><?= $hasil['persen_ergo'] ?>%</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card" style="border:1px solid #e2e8f0; border-radius:12px; padding:16px 20px; border-left:4px solid <?= $hasil['psiko_kategori'] == 'Tinggi' ? '#ef4444' : ($hasil['psiko_kategori'] == 'Sedang' ? '#f59e0b' : '#10b981') ?>;">
                        <small style="color:#64748b;">Skor Psikososial</small>
                        <h4 style="font-weight:700;"><?= $hasil['skor_psikososial'] ?> / <?= $hasil['max_psiko'] ?></h4>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:4px;">
                            <span class="badge-risk <?= strtolower($hasil['psiko_kategori']) ?>">
                                <?= $hasil['psiko_kategori'] ?>
                            </span>
                            <small style="color:#94a3b8; font-size:11px;"><?= $hasil['persen_psiko'] ?>%</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- REKOMENDASI & SOLUSI -->
            <div style="background:#f8fafc; border-radius:12px; padding:20px 24px; margin-bottom:16px; border-left:4px solid #4fc3f7;">
                <h6 style="font-weight:700; color:#1a1a2e; margin-bottom:12px;">
                    <i class="fas fa-lightbulb me-2" style="color:#f59e0b;"></i> Rekomendasi & Solusi
                </h6>

                <!-- Rekomendasi Ergonomi -->
                <?php if ($hasil['ergo_kategori'] == 'Tinggi'): ?>
                <div style="background:#fef2f2; border-radius:8px; padding:12px 16px; margin-bottom:10px; border-left:3px solid #ef4444;">
                    <strong style="color:#dc2626;">🔴 Ergonomi - Risiko Tinggi</strong>
                    <p style="color:#475569; margin:4px 0 0 0; font-size:14px;">
                        <strong>Penyebab:</strong> Postur kerja tidak ergonomis, posisi duduk/monitor tidak sesuai, kurang peregangan, atau pekerjaan berulang.
                    </p>
                    <p style="color:#475569; margin:4px 0 0 0; font-size:14px;">
                        <strong>Solusi:</strong> 
                        <ul style="margin:4px 0 0 18px; color:#475569; font-size:13px;">
                            <li>Atur posisi duduk dengan lutut 90° dan punggung tegak</li>
                            <li>Posisikan monitor setinggi mata, jarak satu lengan</li>
                            <li>Lakukan peregangan ringan setiap 30-60 menit</li>
                            <li>Gunakan kursi dengan sandaran punggung dan lengan</li>
                            <li>Konsultasikan dengan tim HSE untuk intervensi lebih lanjut</li>
                        </ul>
                    </p>
                </div>
                <?php elseif ($hasil['ergo_kategori'] == 'Sedang'): ?>
                <div style="background:#fffbeb; border-radius:8px; padding:12px 16px; margin-bottom:10px; border-left:3px solid #f59e0b;">
                    <strong style="color:#d97706;">🟡 Ergonomi - Risiko Sedang</strong>
                    <p style="color:#475569; margin:4px 0 0 0; font-size:14px;">
                        <strong>Penyebab:</strong> Kurang variasi gerakan, posisi kerja mulai tidak nyaman, atau durasi duduk/berdiri terlalu lama.
                    </p>
                    <p style="color:#475569; margin:4px 0 0 0; font-size:14px;">
                        <strong>Solusi:</strong> 
                        <ul style="margin:4px 0 0 18px; color:#475569; font-size:13px;">
                            <li>Variasikan posisi kerja (duduk, berdiri, jalan kecil)</li>
                            <li>Lakukan peregangan ringan setiap 1-2 jam</li>
                            <li>Atur ketinggian meja dan kursi agar nyaman</li>
                            <li>Istirahat sejenak setiap 2 jam</li>
                        </ul>
                    </p>
                </div>
                <?php else: ?>
                <div style="background:#ecfdf5; border-radius:8px; padding:12px 16px; margin-bottom:10px; border-left:3px solid #10b981;">
                    <strong style="color:#059669;">🟢 Ergonomi - Risiko Rendah</strong>
                    <p style="color:#475569; margin:4px 0 0 0; font-size:14px;">
                        <strong>Penyebab:</strong> Postur kerja sudah baik dan tidak ada keluhan signifikan.
                    </p>
                    <p style="color:#475569; margin:4px 0 0 0; font-size:14px;">
                        <strong>Solusi:</strong> 
                        <ul style="margin:4px 0 0 18px; color:#475569; font-size:13px;">
                            <li>Pertahankan posisi kerja yang baik</li>
                            <li>Tetap lakukan peregangan rutin setiap 2 jam</li>
                            <li>Jaga kebersihan dan kerapian area kerja</li>
                        </ul>
                    </p>
                </div>
                <?php endif; ?>

                <!-- Rekomendasi Psikososial -->
                <?php if ($hasil['psiko_kategori'] == 'Tinggi'): ?>
                <div style="background:#fef2f2; border-radius:8px; padding:12px 16px; margin-bottom:10px; border-left:3px solid #ef4444;">
                    <strong style="color:#dc2626;">🔴 Psikososial - Risiko Tinggi</strong>
                    <p style="color:#475569; margin:4px 0 0 0; font-size:14px;">
                        <strong>Penyebab:</strong> Beban kerja berlebihan, target tidak realistis, kurang dukungan atasan, atau lingkungan kerja yang tidak kondusif.
                    </p>
                    <p style="color:#475569; margin:4px 0 0 0; font-size:14px;">
                        <strong>Solusi:</strong> 
                        <ul style="margin:4px 0 0 18px; color:#475569; font-size:13px;">
                            <li>Komunikasikan beban kerja ke atasan untuk evaluasi</li>
                            <li>Kelola waktu dengan skala prioritas</li>
                            <li>Cari dukungan dari rekan kerja atau tim HSE</li>
                            <li>Luangkan waktu untuk relaksasi dan hobi</li>
                            <li>Konsultasikan dengan tim HSE/HRD jika diperlukan</li>
                        </ul>
                    </p>
                </div>
                <?php elseif ($hasil['psiko_kategori'] == 'Sedang'): ?>
                <div style="background:#fffbeb; border-radius:8px; padding:12px 16px; margin-bottom:10px; border-left:3px solid #f59e0b;">
                    <strong style="color:#d97706;">🟡 Psikososial - Risiko Sedang</strong>
                    <p style="color:#475569; margin:4px 0 0 0; font-size:14px;">
                        <strong>Penyebab:</strong> Stres ringan, target mulai terasa berat, atau kurang istirahat yang cukup.
                    </p>
                    <p style="color:#475569; margin:4px 0 0 0; font-size:14px;">
                        <strong>Solusi:</strong> 
                        <ul style="margin:4px 0 0 18px; color:#475569; font-size:13px;">
                            <li>Buat jadwal kerja yang teratur</li>
                            <li>Ambil waktu istirahat cukup di sela-sela kerja</li>
                            <li>Lakukan relaksasi/meditasi ringan</li>
                            <li>Bicarakan dengan rekan kerja jika ada kendala</li>
                        </ul>
                    </p>
                </div>
                <?php else: ?>
                <div style="background:#ecfdf5; border-radius:8px; padding:12px 16px; margin-bottom:10px; border-left:3px solid #10b981;">
                    <strong style="color:#059669;">🟢 Psikososial - Risiko Rendah</strong>
                    <p style="color:#475569; margin:4px 0 0 0; font-size:14px;">
                        <strong>Penyebab:</strong> Kondisi mental dan lingkungan kerja sudah baik.
                    </p>
                    <p style="color:#475569; margin:4px 0 0 0; font-size:14px;">
                        <strong>Solusi:</strong> 
                        <ul style="margin:4px 0 0 18px; color:#475569; font-size:13px;">
                            <li>Pertahankan keseimbangan kerja dan kehidupan</li>
                            <li>Jaga hubungan baik dengan rekan kerja</li>
                            <li>Tetap evaluasi diri secara rutin</li>
                        </ul>
                    </p>
                </div>
                <?php endif; ?>
            </div>

            <!-- TIPS TAMBAHAN -->
            <div style="background:#eff6ff; border-radius:12px; padding:12px 16px; margin-bottom:16px;">
                <h6 style="font-weight:700; color:#1a1a2e; font-size:14px;">
                    <i class="fas fa-star me-2" style="color:#f59e0b;"></i> Tips Tambahan untuk Semua Karyawan:
                </h6>
                <ul style="color:#475569; font-size:13px; margin:0; padding-left:20px;">
                    <li>Lakukan peregangan ringan setiap 1-2 jam sekali (gerakan peregangan leher, bahu, punggung)</li>
                    <li>Atur posisi duduk dan monitor dengan ergonomis (layar setinggi mata, duduk tegak)</li>
                    <li>Kelola stres dengan teknik pernapasan atau meditasi singkat</li>
                    <li>Jangan ragu berkonsultasi dengan tim HSE jika ada keluhan fisik atau mental</li>
                    <li>Pastikan Anda minum air yang cukup dan makan teratur</li>
                </ul>
            </div>
            
            <div style="text-align:center; margin-top:20px;">
                <a href="dashboard.php" class="btn btn-primary" style="padding:10px 30px; border-radius:12px;">
                    <i class="fas fa-arrow-left me-2"></i> Kembali ke Dashboard
                </a>
                <button onclick="window.print()" class="btn btn-outline-secondary" style="padding:10px 24px; border-radius:12px; margin-left:8px;">
                    <i class="fas fa-print me-2"></i> Cetak Hasil
                </button>
            </div>
        </div>
        
    <?php else: ?>
        <!-- ============================================================
        FORM ASSESSMENT
        ============================================================ -->
        <div class="card-modern" style="max-width:800px; margin:0 auto;">
            <div class="card-header-custom">
                <h5><i class="fas fa-clipboard-list me-2" style="color:#4fc3f7;"></i> Form Assessment</h5>
                <span class="badge" style="background:#dbeafe; color:#2563eb; padding:6px 16px; border-radius:20px;"><?= $period_name ?></span>
            </div>
            <div class="card-body-custom">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    
                    <!-- ERGONOMI (Mobile Friendly - Card List) -->
                    <div style="background:#f8fafc; border-radius:12px; padding:16px; margin-bottom:24px;">
                        <h6 style="font-weight:700; margin-bottom:12px;">
                            <span style="background:#ef4444; color:#fff; padding:2px 10px; border-radius:20px; font-size:12px; margin-right:8px;">A</span>
                            Keluhan Ergonomi
                        </h6>
                        <p style="color:#64748b; font-size:13px; margin-bottom:16px;">
                            Pilih tingkat ketidaknyamanan selama 1 minggu terakhir.
                        </p>
                        
                        <?php foreach ($ergo_questions as $index => $q): ?>
                        <div style="background:#fff; border-radius:10px; padding:12px 14px; margin-bottom:10px; border:1px solid #e2e8f0;">
                            <p style="font-weight:600; font-size:14px; margin-bottom:8px;"><?= $index+1 ?>. <?= htmlspecialchars($q['question_text']) ?></p>
                            <div style="display:flex; gap:8px; flex-wrap:wrap; font-size:13px;">
                                <label style="display:flex; align-items:center; gap:4px; cursor:pointer; padding:4px 8px; border-radius:6px; background:#f1f5f9;">
                                    <input type="radio" name="ergonomi[<?= $index ?>]" value="0" checked> 0 - Tidak
                                </label>
                                <label style="display:flex; align-items:center; gap:4px; cursor:pointer; padding:4px 8px; border-radius:6px; background:#f1f5f9;">
                                    <input type="radio" name="ergonomi[<?= $index ?>]" value="1"> 1 - Agak
                                </label>
                                <label style="display:flex; align-items:center; gap:4px; cursor:pointer; padding:4px 8px; border-radius:6px; background:#f1f5f9;">
                                    <input type="radio" name="ergonomi[<?= $index ?>]" value="2"> 2 - Sakit
                                </label>
                                <label style="display:flex; align-items:center; gap:4px; cursor:pointer; padding:4px 8px; border-radius:6px; background:#f1f5f9;">
                                    <input type="radio" name="ergonomi[<?= $index ?>]" value="3"> 3 - Sangat
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- PSIKOSOSIAL (Mobile Friendly - Card List) -->
                    <div style="background:#f8fafc; border-radius:12px; padding:16px; margin-bottom:24px;">
                        <h6 style="font-weight:700; margin-bottom:12px;">
                            <span style="background:#8b5cf6; color:#fff; padding:2px 10px; border-radius:20px; font-size:12px; margin-right:8px;">B</span>
                            Faktor Psikososial
                        </h6>
                        <p style="color:#64748b; font-size:13px; margin-bottom:16px;">
                            Pilih tingkat persetujuan terhadap pernyataan berikut.
                        </p>
                        
                        <?php foreach ($psiko_questions as $index => $q): ?>
                        <div style="background:#fff; border-radius:10px; padding:12px 14px; margin-bottom:10px; border:1px solid #e2e8f0;">
                            <p style="font-weight:600; font-size:14px; margin-bottom:8px;"><?= $index+1 ?>. <?= htmlspecialchars($q['question_text']) ?></p>
                            <div style="display:flex; gap:6px; flex-wrap:wrap; font-size:12px;">
                                <label style="display:flex; align-items:center; gap:3px; cursor:pointer; padding:3px 6px; border-radius:6px; background:#f1f5f9;">
                                    <input type="radio" name="psikososial[<?= $index ?>]" value="1" checked> 1 - STS
                                </label>
                                <label style="display:flex; align-items:center; gap:3px; cursor:pointer; padding:3px 6px; border-radius:6px; background:#f1f5f9;">
                                    <input type="radio" name="psikososial[<?= $index ?>]" value="2"> 2 - TS
                                </label>
                                <label style="display:flex; align-items:center; gap:3px; cursor:pointer; padding:3px 6px; border-radius:6px; background:#f1f5f9;">
                                    <input type="radio" name="psikososial[<?= $index ?>]" value="3"> 3 - Netral
                                </label>
                                <label style="display:flex; align-items:center; gap:3px; cursor:pointer; padding:3px 6px; border-radius:6px; background:#f1f5f9;">
                                    <input type="radio" name="psikososial[<?= $index ?>]" value="4"> 4 - S
                                </label>
                                <label style="display:flex; align-items:center; gap:3px; cursor:pointer; padding:3px 6px; border-radius:6px; background:#f1f5f9;">
                                    <input type="radio" name="psikososial[<?= $index ?>]" value="5"> 5 - SS
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <small style="color:#94a3b8; font-size:12px;">STS=Sangat Tidak Setuju | TS=Tidak Setuju | S=Setuju | SS=Sangat Setuju</small>
                    </div>
                    
                    <div style="display:flex; gap:12px;">
                        <button type="submit" class="btn btn-primary" style="padding:10px 30px; border-radius:12px; font-weight:600;">
                            <i class="fas fa-paper-plane me-2"></i> Kirim Assessment
                        </button>
                        <a href="dashboard.php" class="btn btn-outline-secondary" style="padding:10px 24px; border-radius:12px;">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>