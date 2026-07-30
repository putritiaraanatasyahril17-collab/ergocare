<?php
include '../config/database.php';
cek_role('HSE');

$session_id = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
if ($session_id == 0) {
    $_SESSION['tracking_message'] = "❌ Session ID tidak valid!";
    $_SESSION['tracking_message_type'] = 'danger';
    header('Location: tracking.php');
    exit();
}

// Cek apakah sudah ada tracking
$cek = mysqli_query($conn, "SELECT tracking_id FROM assessment_tracking WHERE session_id = $session_id");
if (mysqli_num_rows($cek) > 0) {
    $_SESSION['tracking_message'] = "⚠️ Tracking sudah ada untuk assessment ini.";
    $_SESSION['tracking_message_type'] = 'warning';
    header('Location: tracking.php');
    exit();
}

$query = "INSERT INTO assessment_tracking (session_id, status, catatan, updated_by) 
          VALUES ($session_id, 'Perlu Tindak Lanjut', 'Assessment selesai, perlu review HSE', {$_SESSION['user_id']})";
if (mysqli_query($conn, $query)) {
    $_SESSION['tracking_message'] = "✅ Tracking berhasil dibuat!";
    $_SESSION['tracking_message_type'] = 'success';
} else {
    $_SESSION['tracking_message'] = "❌ Gagal membuat tracking: " . mysqli_error($conn);
    $_SESSION['tracking_message_type'] = 'danger';
}
header('Location: tracking.php');
exit();
?>