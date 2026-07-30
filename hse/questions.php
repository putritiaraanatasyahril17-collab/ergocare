<?php
include '../config/database.php';
cek_role('HSE');

$user_id = $_SESSION['user_id'];
$page_title = 'Kelola Pertanyaan';
$page_subtitle = 'Atur pertanyaan assessment ergonomi & psikososial';
$active_page = 'hse_questions';
$base_url = '../';

$message = $_SESSION['questions_message'] ?? '';
$message_type = $_SESSION['questions_message_type'] ?? '';
unset($_SESSION['questions_message']);
unset($_SESSION['questions_message_type']);

$filter = isset($_GET['filter']) ? (int)$_GET['filter'] : 1;

$query = "SELECT q.*, rf.factor_name 
          FROM assessment_questions q
          JOIN risk_factors rf ON q.factor_id = rf.factor_id
          WHERE q.factor_id = $filter
          ORDER BY q.question_order ASC";
$result = mysqli_query($conn, $query);
$total_data = mysqli_num_rows($result);

include '../includes/header.php';
?>

<div class="fade-in-up">
    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert" style="border-radius:12px;">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0">
                <i class="fas fa-list-ul me-2" style="color:#4fc3f7;"></i> Daftar Pertanyaan
            </h5>
            <small style="color:#64748b;">Total: <?= $total_data ?> pertanyaan</small>
        </div>
        <a href="questions_add.php?filter=<?= $filter ?>" class="btn btn-primary" style="border-radius:12px; padding:10px 24px;">
            <i class="fas fa-plus me-2"></i> Tambah Pertanyaan
        </a>
    </div>

    <ul class="nav nav-tabs mb-3" style="border-bottom:2px solid #e2e8f0;">
        <li class="nav-item">
            <a class="nav-link <?= $filter == 1 ? 'active' : '' ?>" href="?filter=1" style="font-weight:600; color:<?= $filter == 1 ? '#2563eb' : '#64748b' ?>;">
                <i class="fas fa-user-injured me-1"></i> Ergonomi
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $filter == 2 ? 'active' : '' ?>" href="?filter=2" style="font-weight:600; color:<?= $filter == 2 ? '#2563eb' : '#64748b' ?>;">
                <i class="fas fa-brain me-1"></i> Psikososial
            </a>
        </li>
    </ul>

    <div class="card-modern">
        <div class="card-body-custom" style="padding:0;">
            <div style="overflow-x:auto;">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th style="width:50px;">No</th>
                            <th>Pertanyaan</th>
                            <th style="width:100px; text-align:center;">Urutan</th>
                            <th style="width:120px; text-align:center;">Status</th>
                            <th style="width:160px; text-align:center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($total_data > 0): ?>
                            <?php $no = 1; while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><strong><?= htmlspecialchars($row['question_text']) ?></strong></td>
                                <td style="text-align:center;"><?= $row['question_order'] ?></td>
                                <td style="text-align:center;">
                                    <?php if ($row['status'] == 'Aktif'): ?>
                                        <span class="badge" style="background:#d1fae5; color:#059669; padding:4px 14px; border-radius:20px;">
                                            <i class="fas fa-circle me-1" style="font-size:8px;"></i> Aktif
                                        </span>
                                    <?php else: ?>
                                        <span class="badge" style="background:#fee2e2; color:#dc2626; padding:4px 14px; border-radius:20px;">
                                            <i class="fas fa-circle me-1" style="font-size:8px;"></i> Nonaktif
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:center;">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <a href="questions_edit.php?id=<?= $row['question_id'] ?>&filter=<?= $filter ?>" class="btn btn-sm btn-outline-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="questions_delete.php?id=<?= $row['question_id'] ?>&filter=<?= $filter ?>" 
                                           class="btn btn-sm btn-outline-danger" 
                                           title="Hapus"
                                           onclick="return confirm('Yakin hapus pertanyaan ini?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align:center; padding:40px; color:#94a3b8;">
                                    <div style="font-size:48px; margin-bottom:12px;">📝</div>
                                    Belum ada pertanyaan untuk kategori ini.
                                    <a href="questions_add.php?filter=<?= $filter ?>" style="color:#2563eb; display:block; margin-top:8px;">Tambah sekarang</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>