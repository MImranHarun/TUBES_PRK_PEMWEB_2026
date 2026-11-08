
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

$pageTitle = 'Dashboard';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/helper.php';
?>

<?php include __DIR__ . '/includes/navbar.php'; ?>

<div class="app-layout d-flex flex-grow-1">

    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main-content flex-grow-1 p-4">

        <div class="mb-4">
            <h3 class="fw-bold">Dashboard</h3>
            <p class="text-muted">
                Selamat datang di sistem manajemen FEBMart.
            </p>
        </div>

        <?php showFlash(); ?>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">

                <h4 class="fw-bold mb-3">
                    Selamat Datang,
                    <?= htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?>!
                </h4>

                <p class="text-muted">
                    Anda berhasil login ke sistem manajemen FEBMart.
                </p>

                <hr>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <small class="text-muted">Username</small>
                        <p class="fw-semibold">
                            <?= htmlspecialchars($_SESSION['username'] ?? '-'); ?>
                        </p>
                    </div>

                    <div class="col-md-6 mb-3">
                        <small class="text-muted">Role</small>
                        <p class="fw-semibold">
                            <?= htmlspecialchars($_SESSION['role'] ?? '-'); ?>
                        </p>
                    </div>
                </div>

            </div>
        </div>

    </main>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
