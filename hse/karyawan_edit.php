<?php
include '../config/database.php';
cek_role('HSE');

$user_id = $_SESSION['user_id'];
$page_title = 'Edit Karyawan';
$page_subtitle = 'Ubah data karyawan';
$active_page = 'hse_karyawan';
$base_url = '../';

$message = '';
$message_type = '';

// Ambil ID dari GET
$edit_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($edit_id == 0) {
    header('Location: karyawan.php');
    exit();
}

// Ambil data karyawan
$query = "SELECT u.user_id, u.username, u.status as user_status,
          e.employee_id, e.employee_code, e.full_name, e.gender, e.phone, e.email, e.department_id,
          d.department_name
          FROM users u
          JOIN employees e ON u.employee_id = e.employee_id
          JOIN departments d ON e.department_id = d.department_id
          WHERE u.user_id = $edit_id AND u.role = 'Karyawan'";
$result = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    $_SESSION['karyawan_message'] = "❌ Data karyawan tidak ditemukan!";
    $_SESSION['karyawan_message_type'] = 'danger';
    header('Location: karyawan.php');
    exit();
}

// ============================================================
// PROSES UPDATE + REDIRECT
// ============================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone'] ?? '');
    $gender = $_POST['gender'];
    $department_id = (int)$_POST['department_id'];
    
    if (!in_array($department_id, [1, 2, 3])) {
        $message = "❌ Departemen tidak valid!";
        $message_type = 'danger';
    } else {
        $query_emp = "UPDATE employees 
                      SET full_name = '$full_name', 
                          email = '$email', 
                          phone = '$phone', 
                          gender = '$gender', 
                          department_id = $department_id 
                      WHERE employee_id = {$data['employee_id']}";
        
        if (mysqli_query($conn, $query_emp)) {
            // ✅ SIMPAN PESAN KE SESSION
            $_SESSION['karyawan_message'] = "✅ Data karyawan <strong>$full_name</strong> berhasil diupdate!";
            $_SESSION['karyawan_message_type'] = 'success';
            
            // ✅ REDIRECT LANGSUNG KE KELOLA KARYAWAN
            header('Location: karyawan.php');
            exit();
        } else {
            $message = "❌ Gagal update data: " . mysqli_error($conn);
            $message_type = 'danger';
        }
    }
}

// Ambil daftar departemen untuk dropdown
$query_dept = "SELECT department_id, department_name FROM departments WHERE department_id IN (1,2,3) ORDER BY department_id";
$result_dept = mysqli_query($conn, $query_dept);

include '../includes/header.php';
?>

<div class="fade-in-up">
    <!-- PESAN ERROR (jika ada) -->
    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert" style="border-radius:12px;">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card-modern" style="max-width:700px; margin:0 auto;">
        <div class="card-header-custom">
            <h5><i class="fas fa-user-edit me-2" style="color:#4fc3f7;"></i> Edit Karyawan</h5>
            <a href="karyawan.php" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>
        </div>
        <div class="card-body-custom">
            <!-- Informasi Kode -->
            <div style="background:#f8fafc; border-radius:10px; padding:12px 16px; margin-bottom:20px;">
                <div class="row">
                    <div class="col-md-6">
                        <small style="color:#64748b;">Kode Karyawan</small>
                        <p style="font-weight:600; margin:0;"><?= $data['employee_code'] ?></p>
                    </div>
                    <div class="col-md-6">
                        <small style="color:#64748b;">Username</small>
                        <p style="font-weight:600; margin:0;"><?= $data['username'] ?> <small style="color:#94a3b8;">(tidak dapat diubah)</small></p>
                    </div>
                </div>
            </div>

            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" style="border-radius:10px; padding:10px 14px;" value="<?= $data['full_name'] ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" style="border-radius:10px; padding:10px 14px;" value="<?= $data['email'] ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nomor HP</label>
                        <input type="text" name="phone" class="form-control" style="border-radius:10px; padding:10px 14px;" value="<?= $data['phone'] ?>" placeholder="08xxxxxxxxxx">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Jenis Kelamin <span class="text-danger">*</span></label>
                        <select name="gender" class="form-select" style="border-radius:10px; padding:10px 14px;" required>
                            <option value="L" <?= $data['gender'] == 'L' ? 'selected' : '' ?>>Laki-laki</option>
                            <option value="P" <?= $data['gender'] == 'P' ? 'selected' : '' ?>>Perempuan</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Departemen <span class="text-danger">*</span></label>
                        <select name="department_id" class="form-select" style="border-radius:10px; padding:10px 14px;" required>
                            <?php 
                            mysqli_data_seek($result_dept, 0);
                            while ($dept = mysqli_fetch_assoc($result_dept)): 
                                $selected = ($dept['department_id'] == $data['department_id']) ? 'selected' : '';
                            ?>
                                <option value="<?= $dept['department_id'] ?>" <?= $selected ?>><?= $dept['department_name'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Status</label>
                        <input type="text" class="form-control" style="border-radius:10px; padding:10px 14px; background:#f8fafc;" value="<?= $data['user_status'] ?>" readonly>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" name="submit" class="btn btn-primary" style="border-radius:10px; padding:10px 30px;">
                        <i class="fas fa-save me-2"></i> Simpan Perubahan
                    </button>
                    <a href="karyawan.php" class="btn btn-outline-secondary" style="border-radius:10px; padding:10px 24px;">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>