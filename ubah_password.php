<?php
include 'config/database.php';
cek_login();

$user_id = $_SESSION['user_id'];
$page_title = 'Ubah Password';
$page_subtitle = 'Ganti password akun Anda untuk keamanan';
$active_page = 'ubah_password';
$base_url = './';

$error = '';
$success = '';

if ($_POST) {
    $password_lama = $_POST['password_lama'];
    $password_baru = $_POST['password_baru'];
    $konfirmasi = $_POST['konfirmasi'];
    
    $query = "SELECT password FROM users WHERE user_id = $user_id";
    $result = mysqli_query($conn, $query);
    $user = mysqli_fetch_assoc($result);
    
    if (!password_verify($password_lama, $user['password'])) {
        $error = "❌ Password lama salah!";
    } elseif (strlen($password_baru) < 6) {
        $error = "❌ Password baru minimal 6 karakter!";
    } elseif ($password_baru != $konfirmasi) {
        $error = "❌ Konfirmasi password tidak cocok!";
    } else {
        $hash_baru = password_hash($password_baru, PASSWORD_DEFAULT);
        $update = "UPDATE users SET password = '$hash_baru' WHERE user_id = $user_id";
        if (mysqli_query($conn, $update)) {
            $success = "✅ Password berhasil diubah!";
        } else {
            $error = "❌ Gagal mengubah password.";
        }
    }
}

include 'includes/header.php';
?>

<div class="fade-in-up">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            <div class="card-modern">
                <div class="card-header-custom">
                    <h5><i class="fas fa-key me-2" style="color:#f59e0b;"></i> Ubah Password</h5>
                </div>
                <div class="card-body-custom">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Password Lama</label>
                            <input type="password" name="password_lama" class="form-control" placeholder="Masukkan password saat ini" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Password Baru</label>
                            <input type="password" name="password_baru" class="form-control" placeholder="Minimal 6 karakter" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Konfirmasi Password Baru</label>
                            <input type="password" name="konfirmasi" class="form-control" placeholder="Ketik ulang password baru" required>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-warning" style="border-radius:10px; padding:10px 30px;">
                                <i class="fas fa-save me-2"></i> Ubah Password
                            </button>
                            <a href="<?= ($_SESSION['role'] == 'HSE') ? 'hse/dashboard.php' : 'karyawan/dashboard.php' ?>" class="btn btn-outline-secondary" style="border-radius:10px; padding:10px 24px;">
                                Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>