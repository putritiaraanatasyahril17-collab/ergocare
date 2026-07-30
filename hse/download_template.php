<?php
// ============================================================
// DOWNLOAD TEMPLATE CSV
// ============================================================
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="template_karyawan.csv"');

$output = fopen('php://output', 'w');

// Header
fputcsv($output, ['Nama', 'Email', 'HP', 'Jenis Kelamin']);

// Contoh data
fputcsv($output, ['Budi Santoso', 'budi@company.com', '081234567890', 'L']);
fputcsv($output, ['Siti Rahayu', 'siti@company.com', '081298765432', 'P']);

fclose($output);
exit;
?>