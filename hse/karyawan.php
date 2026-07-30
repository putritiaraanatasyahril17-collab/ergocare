<?php
include '../config/database.php';
cek_role('HSE');

$user_id = $_SESSION['user_id'];
$page_title = 'Kelola Karyawan';
$page_subtitle = 'Tambah, edit, dan kelola akun karyawan';
$active_page = 'hse_karyawan';
$base_url = '../';

$message = '';
$message_type = '';

// ============================================================
// PESAN DARI SESSION (dari karyawan_edit.php)
// ============================================================
$message = $_SESSION['karyawan_message'] ?? '';
$message_type = $_SESSION['karyawan_message_type'] ?? '';
unset($_SESSION['karyawan_message']);
unset($_SESSION['karyawan_message_type']);

// ============================================================
// PROSES TAMBAH KARYAWAN
// ============================================================
if ($_POST && isset($_POST['action']) && $_POST['action'] == 'tambah') {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone'] ?? '');
    $department_id = (int)$_POST['department_id'];
    $gender = $_POST['gender'];
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password_default = 'password123';
    
    if (!in_array($department_id, [1, 2, 3])) {
        $message = "❌ Departemen tidak valid!";
        $message_type = 'danger';
    }
    elseif (mysqli_num_rows(mysqli_query($conn, "SELECT user_id FROM users WHERE username = '$username'")) > 0) {
        $message = "❌ Username '$username' sudah digunakan!";
        $message_type = 'danger';
    } else {
        // GENERATE EMPLOYEE_CODE YANG UNIK
        $dept_map = [1 => 'QHSE', 2 => 'OPR', 3 => 'SUP'];
        $dept_code = $dept_map[$department_id];

        $existing_query = "SELECT employee_code FROM employees WHERE department_id = $department_id";
        $existing_result = mysqli_query($conn, $existing_query);
        $existing_codes = [];
        while ($row = mysqli_fetch_assoc($existing_result)) {
            $existing_codes[] = $row['employee_code'];
        }

        $max_counter = 0;
        foreach ($existing_codes as $code) {
            if (strpos($code, $dept_code) === 0) {
                $num = (int)substr($code, strlen($dept_code));
                if ($num > $max_counter) {
                    $max_counter = $num;
                }
            }
        }

        $counter = $max_counter + 1;
        $employee_code = $dept_code . str_pad($counter, 3, '0', STR_PAD_LEFT);

        while (in_array($employee_code, $existing_codes)) {
            $counter++;
            $employee_code = $dept_code . str_pad($counter, 3, '0', STR_PAD_LEFT);
        }

        $query_emp = "INSERT INTO employees (department_id, employee_code, full_name, gender, phone, email, employment_status, is_active) 
                      VALUES ($department_id, '$employee_code', '$full_name', '$gender', '$phone', '$email', 'Aktif', 1)";
        
        if (mysqli_query($conn, $query_emp)) {
            $employee_id = mysqli_insert_id($conn);
            $hashed_password = password_hash($password_default, PASSWORD_DEFAULT);
            
            $query_user = "INSERT INTO users (employee_id, username, password, role, status) 
                           VALUES ($employee_id, '$username', '$hashed_password', 'Karyawan', 'Aktif')";
            
            if (mysqli_query($conn, $query_user)) {
                $message = "✅ Karyawan <strong>$full_name</strong> berhasil ditambahkan!<br>
                           Kode: <strong>$employee_code</strong> | Username: <strong>$username</strong> | Password: <strong>$password_default</strong>";
                $message_type = 'success';
            } else {
                $message = "❌ Gagal membuat akun: " . mysqli_error($conn);
                $message_type = 'danger';
            }
        } else {
            $message = "❌ Gagal menyimpan data karyawan: " . mysqli_error($conn);
            $message_type = 'danger';
        }
    }
}

// ============================================================
// PROSES RESET PASSWORD
// ============================================================
if (isset($_GET['reset']) && is_numeric($_GET['reset'])) {
    $user_id_reset = (int)$_GET['reset'];
    $password_baru = 'password123';
    $hashed = password_hash($password_baru, PASSWORD_DEFAULT);
    
    $query = "UPDATE users SET password = '$hashed' WHERE user_id = $user_id_reset AND role = 'Karyawan'";
    if (mysqli_query($conn, $query)) {
        $message = "🔑 Password berhasil di-reset menjadi: <strong>$password_baru</strong>";
        $message_type = 'success';
    } else {
        $message = "❌ Gagal reset password!";
        $message_type = 'danger';
    }
}

// ============================================================
// PROSES UBAH STATUS (AKTIF/NONAKTIF)
// ============================================================
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $user_id_toggle = (int)$_GET['toggle'];
    
    $query = "SELECT status FROM users WHERE user_id = $user_id_toggle";
    $result = mysqli_query($conn, $query);
    $data = mysqli_fetch_assoc($result);
    $new_status = ($data['status'] == 'Aktif') ? 'Nonaktif' : 'Aktif';
    
    $query_update = "UPDATE users SET status = '$new_status' WHERE user_id = $user_id_toggle";
    if (mysqli_query($conn, $query_update)) {
        $is_active = ($new_status == 'Aktif') ? 1 : 0;
        mysqli_query($conn, "UPDATE employees e JOIN users u ON e.employee_id = u.employee_id 
                             SET e.is_active = $is_active WHERE u.user_id = $user_id_toggle");
        $message = "✅ Status akun berhasil diubah menjadi: <strong>$new_status</strong>";
        $message_type = 'success';
    } else {
        $message = "❌ Gagal mengubah status!";
        $message_type = 'danger';
    }
}

// ============================================================
// PROSES HAPUS KARYAWAN
// ============================================================
if (isset($_GET['hapus']) && is_numeric($_GET['hapus'])) {
    $user_id_hapus = (int)$_GET['hapus'];
    
    $cek = mysqli_query($conn, "SELECT session_id FROM assessment_sessions 
                                WHERE employee_id = (SELECT employee_id FROM users WHERE user_id = $user_id_hapus)");
    if (mysqli_num_rows($cek) > 0) {
        $message = "❌ Karyawan sudah memiliki data assessment. Nonaktifkan saja jika sudah resign.";
        $message_type = 'danger';
    } else {
        $query = "DELETE FROM users WHERE user_id = $user_id_hapus AND role = 'Karyawan'";
        if (mysqli_query($conn, $query)) {
            $message = "✅ Karyawan berhasil dihapus!";
            $message_type = 'success';
        } else {
            $message = "❌ Gagal menghapus karyawan: " . mysqli_error($conn);
            $message_type = 'danger';
        }
    }
}

// ============================================================
// AMBIL DATA KARYAWAN
// ============================================================
$query_karyawan = "SELECT u.user_id, u.username, u.status as user_status, u.last_login,
                   e.employee_id, e.employee_code, e.full_name, e.gender, e.phone, e.email, e.employment_status, e.is_active,
                   d.department_id, d.department_name
                   FROM users u
                   JOIN employees e ON u.employee_id = e.employee_id
                   JOIN departments d ON e.department_id = d.department_id
                   WHERE u.role = 'Karyawan'
                   ORDER BY e.full_name ASC";
$result_karyawan = mysqli_query($conn, $query_karyawan);

// Ambil daftar departemen untuk dropdown
$query_dept = "SELECT department_id, department_name FROM departments WHERE department_id IN (1,2,3) ORDER BY department_id";
$result_dept = mysqli_query($conn, $query_dept);

include '../includes/header.php';
?>

<div class="fade-in-up">
    <!-- PESAN NOTIFIKASI -->
    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert" style="border-radius:12px;">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- TOMBOL TAMBAH & IMPORT -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0" style="color:#1a1a2e;">
                <i class="fas fa-users me-2" style="color:#4fc3f7;"></i> Daftar Karyawan
            </h5>
            <small style="color:#64748b;">Total: <?= mysqli_num_rows($result_karyawan) ?> karyawan</small>
        </div>
        <div style="display:flex; gap:10px;">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalImportExcel" style="border-radius:12px; padding:10px 24px;">
                <i class="fas fa-file-excel me-2"></i> Import Excel
            </button>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahKaryawan" style="border-radius:12px; padding:10px 24px;">
                <i class="fas fa-plus me-2"></i> Tambah Karyawan
            </button>
        </div>
    </div>

    <!-- TABEL KARYAWAN -->
    <div class="card-modern">
        <div class="card-body-custom" style="padding:0;">
            <div style="overflow-x:auto;">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>Departemen</th>
                            <th>Username</th>
                            <th>Status</th>
                            <th>Terakhir Login</th>
                            <th style="text-align:center; width:200px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result_karyawan) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result_karyawan)): ?>
                                <tr>
                                    <td>
                                        <span style="font-weight:600; color:#1a1a2e;"><?= $row['employee_code'] ?></span>
                                    </td>
                                    <td>
                                        <strong><?= $row['full_name'] ?></strong><br>
                                        <small style="color:#94a3b8; font-size:11px;"><?= $row['email'] ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        $dept_colors = [
                                            'QHSE' => ['bg' => '#dbeafe', 'color' => '#2563eb'],
                                            'Operation' => ['bg' => '#d1fae5', 'color' => '#059669'],
                                            'Support' => ['bg' => '#fef3c7', 'color' => '#d97706']
                                        ];
                                        $dept = $row['department_name'];
                                        $badge = $dept_colors[$dept] ?? ['bg' => '#e2e8f0', 'color' => '#475569'];
                                        ?>
                                        <span class="badge" style="background:<?= $badge['bg'] ?>; color:<?= $badge['color'] ?>; padding:4px 14px; border-radius:20px;">
                                            <?= $dept ?>
                                        </span>
                                    </td>
                                    <td>
                                        <code style="background:#f1f5f9; padding:2px 10px; border-radius:6px; font-size:12px;">
                                            <?= $row['username'] ?>
                                        </code>
                                    </td>
                                    <td>
                                        <?php if ($row['user_status'] == 'Aktif'): ?>
                                            <span class="badge" style="background:#d1fae5; color:#059669; padding:4px 14px; border-radius:20px;">
                                                <i class="fas fa-circle me-1" style="font-size:8px;"></i> Aktif
                                            </span>
                                        <?php else: ?>
                                            <span class="badge" style="background:#fee2e2; color:#dc2626; padding:4px 14px; border-radius:20px;">
                                                <i class="fas fa-circle me-1" style="font-size:8px;"></i> Nonaktif
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size:13px; color:#64748b;">
                                        <?= $row['last_login'] ? date('d M Y H:i', strtotime($row['last_login'])) : '-' ?>
                                    </td>
                                   <td style="text-align:center;">
                                    <div class="d-flex gap-1 justify-content-center flex-wrap" style="gap:5px;">
                                      <!-- EDIT -->
                                      <a href="karyawan_edit.php?id=<?= $row['user_id'] ?>" 
                                         class="btn btn-sm btn-outline-primary" 
                                         title="Edit"
                                         style="padding:6px 10px; font-size:13px; min-width:36px;">
                                      <i class="fas fa-edit"></i>
                                      </a>
                                      <!-- RESET PASSWORD -->
                                      <a href="?reset=<?= $row['user_id'] ?>" 
                                         class="btn btn-sm btn-outline-warning" 
                                         title="Reset Password"
                                         onclick="return confirm('Reset password untuk <?= $row['full_name'] ?>?')"
                                         style="padding:6px 10px; font-size:13px; min-width:36px;">
                                     <i class="fas fa-key"></i>
                                     </a>
                                    <!-- TOGGLE STATUS -->
                                    <a href="?toggle=<?= $row['user_id'] ?>" 
                                       class="btn btn-sm <?= $row['user_status'] == 'Aktif' ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                                       title="<?= $row['user_status'] == 'Aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>"
                                       onclick="return confirm('Ubah status <?= $row['full_name'] ?>?')"
                                       style="padding:6px 10px; font-size:13px; min-width:36px;">
                                    <i class="fas <?= $row['user_status'] == 'Aktif' ? 'fa-user-slash' : 'fa-user-check' ?>"></i>
                                    </a>
                                    <!-- HAPUS -->
                                    <a href="?hapus=<?= $row['user_id'] ?>" 
                                       class="btn btn-sm btn-outline-danger"
                                       title="Hapus"
                                       onclick="return confirm('Yakin hapus <?= $row['full_name'] ?>?')"
                                       style="padding:6px 10px; font-size:13px; min-width:36px;">
                                    <i class="fas fa-trash"></i>
                                    </a>
                                  </div>
                                </td>
                                </tr>

                                <!-- ============================================================
                                MODAL EDIT KARYAWAN (DI DALAM LOOP)
                                ============================================================ -->
                                <div class="modal fade" id="modalEditKaryawan<?= $row['user_id'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content" style="border-radius:16px; border:none; box-shadow:0 20px 60px rgba(0,0,0,0.2);">
                                            <div class="modal-header" style="border-bottom:1px solid #f1f5f9; padding:20px 24px;">
                                                <h5 class="modal-title fw-bold" style="color:#1a1a2e;">
                                                    <i class="fas fa-user-edit me-2" style="color:#4fc3f7;"></i> Edit Karyawan
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST" action="karyawan_edit.php">
                                                <div class="modal-body" style="padding:24px;">
                                                    <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
                                                    <input type="hidden" name="employee_id" value="<?= $row['employee_id'] ?>">
                                                    
                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                                                            <input type="text" name="full_name" class="form-control" style="border-radius:10px; padding:10px 14px;" value="<?= $row['full_name'] ?>" required>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                                                            <input type="email" name="email" class="form-control" style="border-radius:10px; padding:10px 14px;" value="<?= $row['email'] ?>" required>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-semibold">Nomor HP</label>
                                                            <input type="text" name="phone" class="form-control" style="border-radius:10px; padding:10px 14px;" value="<?= $row['phone'] ?>">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-semibold">Jenis Kelamin <span class="text-danger">*</span></label>
                                                            <select name="gender" class="form-select" style="border-radius:10px; padding:10px 14px;" required>
                                                                <option value="L" <?= $row['gender'] == 'L' ? 'selected' : '' ?>>Laki-laki</option>
                                                                <option value="P" <?= $row['gender'] == 'P' ? 'selected' : '' ?>>Perempuan</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-semibold">Departemen <span class="text-danger">*</span></label>
                                                            <select name="department_id" class="form-select" style="border-radius:10px; padding:10px 14px;" required>
                                                                <?php 
                                                                mysqli_data_seek($result_dept, 0);
                                                                while ($dept = mysqli_fetch_assoc($result_dept)): 
                                                                    $selected = ($dept['department_id'] == $row['department_id']) ? 'selected' : '';
                                                                ?>
                                                                    <option value="<?= $dept['department_id'] ?>" <?= $selected ?>><?= $dept['department_name'] ?></option>
                                                                <?php endwhile; ?>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-semibold">Username</label>
                                                            <input type="text" name="username" class="form-control" style="border-radius:10px; padding:10px 14px; background:#f1f5f9;" value="<?= $row['username'] ?>" readonly>
                                                            <small style="color:#94a3b8; font-size:11px;">Username tidak dapat diubah</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer" style="border-top:1px solid #f1f5f9; padding:16px 24px;">
                                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-radius:10px; padding:8px 24px;">Batal</button>
                                                    <button type="submit" class="btn btn-primary" style="border-radius:10px; padding:8px 30px;">
                                                        <i class="fas fa-save me-2"></i> Simpan Perubahan
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align:center; padding:40px; color:#94a3b8;">
                                    <div style="font-size:48px; margin-bottom:12px;">👤</div>
                                    Belum ada karyawan. Klik tombol <strong>"Tambah Karyawan"</strong> untuk mulai.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
MODAL TAMBAH KARYAWAN
============================================================ -->
<div class="modal fade" id="modalTambahKaryawan" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius:16px; border:none; box-shadow:0 20px 60px rgba(0,0,0,0.2);">
            <div class="modal-header" style="border-bottom:1px solid #f1f5f9; padding:20px 24px;">
                <h5 class="modal-title fw-bold" style="color:#1a1a2e;">
                    <i class="fas fa-user-plus me-2" style="color:#4fc3f7;"></i> Tambah Karyawan Baru
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body" style="padding:24px;">
                    <input type="hidden" name="action" value="tambah">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control" style="border-radius:10px; padding:10px 14px;" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" style="border-radius:10px; padding:10px 14px;" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nomor HP</label>
                            <input type="text" name="phone" class="form-control" style="border-radius:10px; padding:10px 14px;" placeholder="08xxxxxxxxxx">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Jenis Kelamin <span class="text-danger">*</span></label>
                            <select name="gender" class="form-select" style="border-radius:10px; padding:10px 14px;" required>
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Departemen <span class="text-danger">*</span></label>
                            <select name="department_id" class="form-select" style="border-radius:10px; padding:10px 14px;" required>
                                <option value="">-- Pilih Departemen --</option>
                                <?php 
                                mysqli_data_seek($result_dept, 0);
                                while ($dept = mysqli_fetch_assoc($result_dept)): 
                                ?>
                                    <option value="<?= $dept['department_id'] ?>"><?= $dept['department_name'] ?></option>
                                <?php endwhile; ?>
                            </select>
                            <small style="color:#94a3b8; font-size:11px;">Hanya tersedia: QHSE, Operation, Support</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control" style="border-radius:10px; padding:10px 14px;" placeholder="contoh: budi.santoso" required>
                            <small style="color:#94a3b8; font-size:11px;">Minimal 3 karakter, unik untuk setiap user</small>
                        </div>
                    </div>
                    
                    <div class="mt-3" style="background:#f8fafc; border-radius:10px; padding:12px 16px;">
                        <small style="color:#64748b;">
                            <i class="fas fa-info-circle me-1"></i> 
                            Kode karyawan akan dibuat otomatis (QHSE001, OPR001, SUP001, dst). <br>
                            Password default: <strong>password123</strong>. Karyawan wajib mengubah password setelah login pertama.
                        </small>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f1f5f9; padding:16px 24px;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-radius:10px; padding:8px 24px;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="border-radius:10px; padding:8px 30px;">
                        <i class="fas fa-save me-2"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================
MODAL IMPORT CSV
============================================================ -->
<div class="modal fade" id="modalImportExcel" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius:16px; border:none; box-shadow:0 20px 60px rgba(0,0,0,0.2);">
            <div class="modal-header" style="border-bottom:1px solid #f1f5f9; padding:20px 24px;">
                <h5 class="modal-title fw-bold" style="color:#1a1a2e;">
                    <i class="fas fa-file-import me-2" style="color:#22c55e;"></i> Import Karyawan dari CSV
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="import_karyawan.php" enctype="multipart/form-data">
                <div class="modal-body" style="padding:24px;">
                    <div class="alert alert-info" style="border-radius:12px; border-left:4px solid #0ea5e9;">
                        <h6 style="font-weight:700; color:#0c4a6e; margin-bottom:8px;">
                            <i class="fas fa-info-circle me-2"></i> Panduan Import CSV
                        </h6>
                        <ol style="margin:0 0 0 18px; font-size:13px; line-height:1.8; color:#0c4a6e;">
                            <li><strong>Buat file CSV</strong> dengan 4 kolom: <code>Nama, Email, HP, Jenis Kelamin</code></li>
                            <li><strong>Jenis Kelamin</strong> diisi <code>L</code> (Laki-laki) atau <code>P</code> (Perempuan)</li>
                            <li><strong>Password default</strong> untuk semua karyawan: <code>password123</code></li>
                            <li>Karyawan wajib mengubah password setelah login pertama</li>
                            <li>File CSV maksimal <strong>2MB</strong></li>
                            <li>Kode karyawan akan dibuat otomatis (contoh: <code>QHSE001</code>, <code>OPR001</code>, <code>SUP001</code>)</li>
                        </ol>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Contoh Format CSV</label>
                        <div style="background:#1e293b; border-radius:8px; padding:14px; font-family: 'Courier New', monospace; font-size:13px; overflow-x:auto; color:#e2e8f0;">
                            <span style="color:#fcd34d;">Nama</span><span style="color:#94a3b8;">,</span><span style="color:#34d399;">Email</span><span style="color:#94a3b8;">,</span><span style="color:#60a5fa;">HP</span><span style="color:#94a3b8;">,</span><span style="color:#f472b6;">Jenis Kelamin</span><br>
                            <span style="color:#fcd34d;">Budi Santoso</span><span style="color:#94a3b8;">,</span><span style="color:#34d399;">budi@company.com</span><span style="color:#94a3b8;">,</span><span style="color:#60a5fa;">081234567890</span><span style="color:#94a3b8;">,</span><span style="color:#f472b6;">L</span><br>
                            <span style="color:#fcd34d;">Siti Rahayu</span><span style="color:#94a3b8;">,</span><span style="color:#34d399;">siti@company.com</span><span style="color:#94a3b8;">,</span><span style="color:#60a5fa;">081298765432</span><span style="color:#94a3b8;">,</span><span style="color:#f472b6;">P</span><br>
                            <span style="color:#fcd34d;">Andi Wijaya</span><span style="color:#94a3b8;">,</span><span style="color:#34d399;">andi@company.com</span><span style="color:#94a3b8;">,</span><span style="color:#60a5fa;">081234567891</span><span style="color:#94a3b8;">,</span><span style="color:#f472b6;">L</span>
                        </div>
                        <small style="color:#94a3b8; font-size:11px;">
                            <i class="fas fa-asterisk text-danger me-1"></i> Simpan file dengan ekstensi <strong>.csv</strong> (bukan .xlsx)
                        </small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pilih File CSV <span class="text-danger">*</span></label>
                        <input type="file" name="file_csv" class="form-control" style="border-radius:10px; padding:10px 14px;" accept=".csv" required>
                        <small style="color:#94a3b8; font-size:11px;">Format: .csv | Maks: 2MB</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Departemen <span class="text-danger">*</span></label>
                        <select name="department_id" class="form-select" style="border-radius:10px; padding:10px 14px;" required>
                            <option value="">-- Pilih Departemen --</option>
                            <option value="1">QHSE</option>
                            <option value="2">Operation</option>
                            <option value="3">Support</option>
                        </select>
                        <small style="color:#94a3b8; font-size:11px;">Semua karyawan akan dimasukkan ke departemen ini</small>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f1f5f9; padding:16px 24px;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-radius:10px; padding:8px 24px;">Batal</button>
                    <button type="submit" class="btn btn-success" style="border-radius:10px; padding:8px 30px;">
                        <i class="fas fa-upload me-2"></i> Import Data
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Auto-generate username dari nama (opsional)
document.querySelector('input[name="full_name"]')?.addEventListener('input', function() {
    const usernameField = document.querySelector('input[name="username"]');
    if (usernameField && (usernameField.value === '' || usernameField.dataset.auto === 'true')) {
        const name = this.value.toLowerCase().trim();
        const parts = name.split(' ');
        if (parts.length >= 2) {
            usernameField.value = parts[0] + '.' + parts[parts.length - 1];
        } else {
            usernameField.value = name;
        }
        usernameField.dataset.auto = 'true';
    }
});
</script>

<?php include '../includes/footer.php'; ?>