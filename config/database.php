<?php
// ============================================================
// FILE: config/database.php
// ============================================================
session_start();

$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'ergocare_db';  // <-- Nama database baru

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Fungsi cek role
function cek_role($role_required) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login.php');
        exit();
    }
    if ($_SESSION['role'] != $role_required) {
        die("Akses ditolak! Halaman ini khusus untuk <strong>$role_required</strong>.");
    }
}

// Fungsi cek login
function cek_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login.php');
        exit();
    }
}

// Fungsi untuk mendapatkan nama karyawan dari user_id
function get_employee_name($user_id) {
    global $conn;
    $query = "SELECT e.full_name FROM users u 
              JOIN employees e ON u.employee_id = e.employee_id 
              WHERE u.user_id = $user_id";
    $result = mysqli_query($conn, $query);
    $data = mysqli_fetch_assoc($result);
    return $data['full_name'] ?? 'Tidak Diketahui';
}

// Fungsi untuk mendapatkan departemen karyawan
function get_employee_department($user_id) {
    global $conn;
    $query = "SELECT d.department_name FROM users u 
              JOIN employees e ON u.employee_id = e.employee_id 
              JOIN departments d ON e.department_id = d.department_id 
              WHERE u.user_id = $user_id";
    $result = mysqli_query($conn, $query);
    $data = mysqli_fetch_assoc($result);
    return $data['department_name'] ?? 'Tidak Diketahui';
}
?>