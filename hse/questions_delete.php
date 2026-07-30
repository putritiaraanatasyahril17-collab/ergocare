<?php
include '../config/database.php';
cek_role('HSE');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$filter = isset($_GET['filter']) ? (int)$_GET['filter'] : 1;

if ($id == 0) {
    $_SESSION['questions_message'] = "❌ ID tidak valid!";
    $_SESSION['questions_message_type'] = 'danger';
    header('Location: questions.php');
    exit();
}

// Ambil factor_id sebelum hapus
$query = "SELECT factor_id FROM assessment_questions WHERE question_id = $id";
$result = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($result);
$filter = $data['factor_id'] ?? 1;

$query = "DELETE FROM assessment_questions WHERE question_id = $id";
if (mysqli_query($conn, $query)) {
    $_SESSION['questions_message'] = "✅ Pertanyaan berhasil dihapus!";
    $_SESSION['questions_message_type'] = 'success';
} else {
    $_SESSION['questions_message'] = "❌ Gagal menghapus pertanyaan: " . mysqli_error($conn);
    $_SESSION['questions_message_type'] = 'danger';
}

header("Location: questions.php?filter=$filter");
exit();
?>