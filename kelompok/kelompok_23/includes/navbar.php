
<nav class="navbar navbar-expand-lg navbar-dark febmart-navbar shadow-sm">
    <div class="container-fluid px-4">

        <a class="navbar-brand fw-bold" href="<?= $baseUrl ?>/dashboard.php">
            <i class="bi bi-cart3 me-2"></i>FEBMart
        </a>

        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarContent">

            <ul class="navbar-nav ms-auto align-items-lg-center">

                <li class="nav-item">
                    <span class="nav-link">
                        <i class="bi bi-person-circle me-1"></i>
                        <?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?>
                    </span>
                </li>

                <li class="nav-item">
                    <a class="nav-link febmart-logout"
                       href="<?= $baseUrl ?>/auth/logout.php">
                        <i class="bi bi-box-arrow-right me-1"></i>
                        Logout
                    </a>
                </li>

            </ul>

        </div>
    </div>
</nav>
