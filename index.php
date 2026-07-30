<?php
include 'config/database.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'HSE') {
        header('Location: hse/dashboard.php');
    } else {
        header('Location: karyawan/dashboard.php');
    }
    exit();
}
header('Location: login.php');
?>