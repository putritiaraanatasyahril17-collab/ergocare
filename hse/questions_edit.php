<?php
include '../config/database.php';
cek_role('HSE');

$user_id = $_SESSION['user_id'];
$page_title = 'Edit Pertanyaan';
$page_subtitle = 'Ubah pertanyaan assessment';
$active_page = 'hse_questions';
$base_url = '../';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$filter = isset($_GET['filter']) ? (int)$_GET['filter'] : 1;

if ($id == 0) {
    header('Location: questions.php');
    exit();
}

$query = "SELECT * FROM assessment_questions WHERE question_id = $id";
$result = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    $_SESSION['questions_message'] = "❌ Data tidak ditemukan!";
    $_SESSION['questions_message_type'] = 'danger';
    header('Location: questions.php');
    exit();
}

$error = '';

if ($_POST) {
    $question_text = mysqli_real_escape_string($conn, $_POST['question_text']);
    $factor_id = (int)$_POST['factor_id'];
    $question_order = (int)$_POST['question_order'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    if (empty($question_text)) {
        $error = "❌ Pertanyaan tidak boleh kosong!";
    } else {
        $query = "UPDATE assessment_questions 
                  SET factor_id = $factor_id, question_text = '$question_text', question_order = $question_order, status = '$status' 
                  WHERE question_id = $id";
        if (mysqli_query($conn, $query)) {
            $_SESSION['questions_message'] = "✅ Pertanyaan berhasil diupdate!";
            $_SESSION['questions_message_type'] = 'success';
            header("Location: questions.php?filter=$factor_id");
            exit();
        } else {
            $error = "❌ Gagal update pertanyaan: " . mysqli_error($conn);
        }
    }
}

include '../includes/header.php';
?>

<div class="fade-in-up">
    <div class="card-modern" style="max-width:700px; margin:0 auto;">
        <div class="card-header-custom">
            <h5><i class="fas fa-edit me-2" style="color:#f59e0b;"></i> Edit Pertanyaan</h5>
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
                        <option value="1" <?= $data['factor_id'] == 1 ? 'selected' : '' ?>>Ergonomi</option>
                        <option value="2" <?= $data['factor_id'] == 2 ? 'selected' : '' ?>>Psikososial</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Pertanyaan <span class="text-danger">*</span></label>
                    <input type="text" name="question_text" class="form-control" style="border-radius:10px; padding:10px 14px;" value="<?= htmlspecialchars($data['question_text']) ?>" required>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Urutan</label>
                        <input type="number" name="question_order" class="form-control" style="border-radius:10px; padding:10px 14px;" value="<?= $data['question_order'] ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select" style="border-radius:10px; padding:10px 14px;">
                            <option value="Aktif" <?= $data['status'] == 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                            <option value="Nonaktif" <?= $data['status'] == 'Nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                        </select>
                    </div>
                </div>

                <div class="mt-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary" style="border-radius:10px; padding:10px 30px;">
                        <i class="fas fa-save me-2"></i> Simpan Perubahan
                    </button>
                    <a href="questions.php?filter=<?= $filter ?>" class="btn btn-outline-secondary" style="border-radius:10px; padding:10px 24px;">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>