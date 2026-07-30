<?php
include '../config/database.php';
cek_role('HSE');

$user_id = $_SESSION['user_id'];

if ($_POST && isset($_POST['session_id']) && isset($_POST['status'])) {
    $session_id = (int)$_POST['session_id'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    // Validasi status
    $allowed_status = ['Perlu Tindak Lanjut', 'Sedang Diproses', 'Selesai'];
    if (!in_array($status, $allowed_status)) {
        $_SESSION['tracking_message'] = "❌ Status tidak valid!";
        $_SESSION['tracking_message_type'] = 'danger';
        header('Location: tracking.php');
        exit();
    }
    
    // Cek apakah tracking sudah ada
    $cek = mysqli_query($conn, "SELECT tracking_id FROM assessment_tracking WHERE session_id = $session_id");
    
    if (mysqli_num_rows($cek) > 0) {
        // Jika sudah ada, update
        $data = mysqli_fetch_assoc($cek);
        $tracking_id = $data['tracking_id'];
        
        $query = "UPDATE assessment_tracking 
                  SET status = '$status', updated_by = $user_id 
                  WHERE tracking_id = $tracking_id";
        
        if (mysqli_query($conn, $query)) {
            $_SESSION['tracking_message'] = "✅ Status berhasil diupdate menjadi: <strong>$status</strong>";
            $_SESSION['tracking_message_type'] = 'success';
        } else {
            $_SESSION['tracking_message'] = "❌ Gagal update status: " . mysqli_error($conn);
            $_SESSION['tracking_message_type'] = 'danger';
        }
    } else {
        // Jika belum ada, buat baru
        $query_insert = "INSERT INTO assessment_tracking (session_id, status, catatan, updated_by) 
                         VALUES ($session_id, '$status', 'Tracking dibuat otomatis dari halaman tracking', $user_id)";
        
        if (mysqli_query($conn, $query_insert)) {
            $_SESSION['tracking_message'] = "✅ Tracking berhasil dibuat dengan status: <strong>$status</strong>";
            $_SESSION['tracking_message_type'] = 'success';
        } else {
            $_SESSION['tracking_message'] = "❌ Gagal membuat tracking: " . mysqli_error($conn);
            $_SESSION['tracking_message_type'] = 'danger';
        }
    }
} else {
    $_SESSION['tracking_message'] = "❌ Data tidak lengkap!";
    $_SESSION['tracking_message_type'] = 'danger';
}

header('Location: tracking.php');
exit();
?>