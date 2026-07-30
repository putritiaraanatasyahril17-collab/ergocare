<?php
include '../config/database.php';
cek_role('HSE');

$user_id = $_SESSION['user_id'];
$page_title = 'Tambah Pertanyaan';
$page_subtitle = 'Buat pertanyaan baru untuk assessment';
$active_page = 'hse_questions';
$base_url = '../';

$filter = isset($_GET['filter']) ? (int)$_GET['filter'] : 1;
$error = '';

if ($_POST) {
    $question_text = mysqli_real_escape_string($conn, $_POST['question_text']);
    $factor_id = (int)$_POST['factor_id'];
    $question_order = (int)$_POST['question_order'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    if (empty($question_text)) {
        $error = "❌ Pertanyaan tidak boleh kosong!";
    } else {
        $query = "INSERT INTO assessment_questions (factor_id, question_text, question_order, status) 
                  VALUES ($factor_id, '$question_text', $question_order, '$status')";
        if (mysqli_query($conn, $query)) {
            $_SESSION['questions_message'] = "✅ Pertanyaan berhasil ditambahkan!";
            $_SESSION['questions_message_type'] = 'success';
            header("Location: questions.php?filter=$factor_id");
            exit();
        } else {
            $error = "❌ Gagal menambahkan pertanyaan: " . mysqli_error($conn);
        }
    }
}

include '../includes/header.php';
?>

<div class="fade-in-up">
    <div class="card-modern" style="max-width:700px; margin:0 auto;">
        <div class="card-header-custom">
            <h5><i class="fas fa-plus-circle me-2" style="color:#4fc3f7;"></i> Tambah Pertanyaan</h5>
            <a href="questions.php?filter=<?= $filter ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>
        </div>
        <div class="card-body-custom">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Kategori <span class="text-danger">*</span></label>
                    <select name="factor_id" class="form-select" style="border-radius:10px; padding:10px 14px;" required>
                        <option value="1" <?= $filter == 1 ? 'selected' : '' ?>>Ergonomi</option>
                        <option value="2" <?= $filter == 2 ? 'selected' : '' ?>>Psikososial</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Pertanyaan <span class="text-danger">*</span></label>
                    <input type="text" name="question_text" class="form-control" style="border-radius:10px; padding:10px 14px;" placeholder="Masukkan teks pertanyaan..." required>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Urutan</label>
                        <input type="number" name="question_order" class="form-control" style="border-radius:10px; padding:10px 14px;" value="0">
                        <small style="color:#94a3b8;">Semakin kecil angka, semakin atas</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select" style="border-radius:10px; padding:10px 14px;">
                            <option value="Aktif">Aktif</option>
                            <option value="Nonaktif">Nonaktif</option>
                        </select>
                    </div>
                </div>

                <div class="mt-3" style="background:#f8fafc; border-radius:10px; padding:12px 16px;">
                    <small style="color:#64748b;">
                        <i class="fas fa-info-circle me-1"></i> 
                        Pertanyaan yang <strong>Nonaktif</strong> tidak akan muncul di form assessment karyawan.
                    </small>
                </div>

                <div class="mt-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary" style="border-radius:10px; padding:10px 30px;">
                        <i class="fas fa-save me-2"></i> Simpan
                    </button>
                    <a href="questions.php?filter=<?= $filter ?>" class="btn btn-outline-secondary" style="border-radius:10px; padding:10px 24px;">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>