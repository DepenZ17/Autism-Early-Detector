<?php
// [PERBAIKAN] Cek dulu apakah session sudah aktif sebelum memulainya
// Ini akan menghilangkan notice "session is already active"
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="/aed5/dashboard.php">
            <i class="bi bi-activity" style="color: #20c997;"></i> Autism Early Detector
        </a>
        
        <?php // Hanya tampilkan dropdown jika pengguna sudah login
        if (isset($_SESSION['username'])): ?>
            <div class="d-flex align-items-center">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        
                        <?php
                        // Logika untuk menampilkan foto hanya dari session
                        $photo_path = 'images/default-avatar.png'; // Avatar default
                        if (!empty($_SESSION['user_photo'])) {
                            $photo_path = htmlspecialchars($_SESSION['user_photo']);
                        }
                        ?>
                        <img src="/aed5/<?= $photo_path ?>" alt="Avatar" class="rounded-circle me-2" width="32" height="32" style="object-fit: cover;">
                        
                        Halo, <strong class="text-capitalize ms-1"><?= htmlspecialchars($_SESSION['username']) ?></strong>
                    </a>

                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <li><a class="dropdown-item" href="/aed5/dashboard.php"><i class="bi bi-grid-fill me-2"></i>Dashboard Admin</a></li>
                            <li><a class="dropdown-item" href="/aed5/admin/data_user.php"><i class="bi bi-people-fill me-2"></i>Manajemen User</a></li>
                        
                        <?php else: ?>
                            <li><a class="dropdown-item" href="/aed5/dashboard.php"><i class="bi bi-grid-fill me-2"></i>Dashboard</a></li>
                            <li><a class="dropdown-item" href="/aed5/backend/profil.php"><i class="bi bi-person-circle me-2"></i>Profil Saya</a></li>
                        
                        <?php endif; ?>

                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="/aed5/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
    </div>
</nav>