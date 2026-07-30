<?php
include '../config/database.php';
cek_role('HSE');

$page_title = 'Import Karyawan';
$active_page = 'hse_karyawan';
$base_url = '../';

$message = '';
$message_type = '';
$success_count = 0;
$error_count = 0;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file_csv'])) {
    $department_id = (int)$_POST['department_id'];
    
    if (!in_array($department_id, [1, 2, 3])) {
        $message = "❌ Departemen tidak valid!";
        $message_type = 'danger';
    } elseif ($_FILES['file_csv']['error'] != 0) {
        $message = "❌ Gagal upload file!";
        $message_type = 'danger';
    } else {
        $file_tmp = $_FILES['file_csv']['tmp_name'];
        $file_name = $_FILES['file_csv']['name'];
        $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
        
        if (strtolower($file_ext) != 'csv') {
            $message = "❌ Format file tidak didukung! Gunakan .csv";
            $message_type = 'danger';
        } else {
            // Baca file CSV
            $handle = fopen($file_tmp, 'r');
            if ($handle) {
                // Lewati header (baris pertama)
                $header = fgetcsv($handle);
                
                // Ambil semua employee_code yang sudah ada untuk cek duplikat
                $existing_codes = [];
                $q = mysqli_query($conn, "SELECT employee_code FROM employees");
                while ($row = mysqli_fetch_assoc($q)) {
                    $existing_codes[] = $row['employee_code'];
                }
                
                $dept_map = [1 => 'QHSE', 2 => 'OPR', 3 => 'SUP'];
                $dept_code = $dept_map[$department_id];
                
                // Ambil urutan terakhir di departemen ini
                $count_query = "SELECT COUNT(*) as total FROM employees WHERE department_id = $department_id";
                $count_result = mysqli_query($conn, $count_query);
                $count_data = mysqli_fetch_assoc($count_result);
                $counter = $count_data['total'] + 1;
                
                $password_default = 'password123';
                $hashed_password = password_hash($password_default, PASSWORD_DEFAULT);
                $row_number = 1;
                
                while (($row = fgetcsv($handle)) !== false) {
                    $row_number++;
                    
                    // Skip jika baris kosong
                    if (empty(array_filter($row))) continue;
                    
                    $nama = trim($row[0] ?? '');
                    $email = trim($row[1] ?? '');
                    $phone = trim($row[2] ?? '');
                    $gender = strtoupper(trim($row[3] ?? 'L'));
                    
                    // Validasi gender
                    if (!in_array($gender, ['L', 'P'])) {
                        $gender = 'L';
                    }
                    
                    // Validasi
                    if (empty($nama)) {
                        $errors[] = "Baris $row_number: Nama kosong";
                        $error_count++;
                        continue;
                    }
                    
                    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $errors[] = "Baris $row_number: Email tidak valid untuk '$nama'";
                        $error_count++;
                        continue;
                    }
                    
                    // Generate username dari email
                    $username = explode('@', $email)[0];
                    $username = preg_replace('/[^a-zA-Z0-9._-]/', '', $username);
                    
                    // Cek duplikat username
                    $cek = mysqli_query($conn, "SELECT user_id FROM users WHERE username = '$username'");
                    if (mysqli_num_rows($cek) > 0) {
                        $username = $username . rand(100, 999);
                    }
                    
                    // Generate employee_code yang UNIK
                    $employee_code = $dept_code . str_pad($counter, 3, '0', STR_PAD_LEFT);
                    
                    // Jika kode sudah ada, tambahkan angka random sampai unik
                    $loop = 0;
                    while (in_array($employee_code, $existing_codes) && $loop < 100) {
                        $employee_code = $dept_code . str_pad($counter, 3, '0', STR_PAD_LEFT) . rand(10, 99);
                        $loop++;
                    }
                    
                    // Masukkan ke array agar tidak duplikat dalam satu import
                    $existing_codes[] = $employee_code;
                    
                    // Insert ke employees
                    $query_emp = "INSERT INTO employees (department_id, employee_code, full_name, gender, phone, email, employment_status, is_active) 
                                  VALUES ($department_id, '$employee_code', '$nama', '$gender', '$phone', '$email', 'Aktif', 1)";
                    
                    if (mysqli_query($conn, $query_emp)) {
                        $employee_id = mysqli_insert_id($conn);
                        
                        $query_user = "INSERT INTO users (employee_id, username, password, role, status) 
                                       VALUES ($employee_id, '$username', '$hashed_password', 'Karyawan', 'Aktif')";
                        
                        if (mysqli_query($conn, $query_user)) {
                            $success_count++;
                        } else {
                            $errors[] = "Baris $row_number: Gagal buat akun untuk '$nama' - " . mysqli_error($conn);
                            $error_count++;
                        }
                    } else {
                        $errors[] = "Baris $row_number: Gagal simpan data '$nama' - " . mysqli_error($conn);
                        $error_count++;
                    }
                    
                    $counter++;
                }
                fclose($handle);
                
                $message = "✅ Import selesai! <strong>$success_count</strong> berhasil ditambahkan";
                if ($error_count > 0) {
                    $message .= ", <strong>$error_count</strong> gagal.";
                }
                $message_type = ($error_count > 0) ? 'warning' : 'success';
                
            } else {
                $message = "❌ Gagal membaca file CSV!";
                $message_type = 'danger';
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="fade-in-up">
    <div class="card-modern" style="max-width:700px; margin:0 auto;">
        <div class="card-header-custom">
            <h5><i class="fas fa-file-import me-2" style="color:#22c55e;"></i> Hasil Import Karyawan</h5>
            <a href="karyawan.php" class="btn btn-sm btn-primary">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>
        </div>
        <div class="card-body-custom">
            <?php if ($message): ?>
                <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert" style="border-radius:12px;">
                    <?= $message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div style="background:#fef2f2; border-radius:12px; padding:16px 20px; margin-top:12px; max-height:250px; overflow-y:auto;">
                    <h6 style="font-weight:700; color:#dc2626;"><i class="fas fa-exclamation-circle me-2"></i> Detail Error:</h6>
                    <ul style="color:#991b1b; font-size:13px; margin:0; padding-left:20px;">
                        <?php foreach ($errors as $err): ?>
                            <li><?= htmlspecialchars($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if ($success_count > 0): ?>
                <div style="background:#d1fae5; border-radius:12px; padding:16px 20px; margin-top:12px;">
                    <h6 style="font-weight:700; color:#059669;"><i class="fas fa-check-circle me-2"></i> Berhasil:</h6>
                    <p style="color:#065f46; font-size:14px; margin:0;">
                        <?= $success_count ?> karyawan berhasil ditambahkan dengan password default: <strong>password123</strong>
                    </p>
                </div>
            <?php endif; ?>
            
            <div class="mt-3 text-center">
                <a href="karyawan.php" class="btn btn-primary" style="border-radius:10px; padding:10px 30px;">
                    <i class="fas fa-users me-2"></i> Kembali ke Kelola Karyawan
                </a>
                <a href="karyawan.php#import" class="btn btn-outline-secondary" style="border-radius:10px; padding:10px 30px;">
                    <i class="fas fa-undo me-2"></i> Import Lagi
                </a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>