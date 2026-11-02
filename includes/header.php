
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = $pageTitle ?? 'BookNest — Your Online Bookstore';

$isLoggedIn = isset($_SESSION['user_id']);

$isAdmin = $isLoggedIn
    && isset($_SESSION['role_id'])
    && (int) $_SESSION['role_id'] === 1;

$isCustomer = $isLoggedIn
    && isset($_SESSION['role_id'])
    && (int) $_SESSION['role_id'] === 2;

$currentPage = basename($_SERVER['PHP_SELF']);

$isAuthPage = in_array(
    $currentPage,
    ['login.php', 'register.php', 'forgot-password.php'],
    true
);

// Returns the active class when the current page matches.
$activeClass = function (array $pages) use ($currentPage) {
    return in_array($currentPage, $pages, true) ? 'active' : '';
};
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Discover your next favorite book at BookNest.">

    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        .site-header {
            background: #172554;
            box-shadow: 0 3px 15px rgba(23, 37, 84, 0.10);
        }

        .site-header .navbar {
            padding: 15px 0;
        }

        .site-header .brand-name {
            color: #ffffff;
            font-size: 1.45rem;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .site-header .brand-name span {
            color: #F59E0B;
        }

        .site-header .nav-link {
            color: #ffffff;
            font-size: 0.9rem;
            font-weight: 600;
            padding: 9px 12px !important;
            border-radius: 8px;
            transition: 0.2s ease;
        }

        /* Hover effect */
        .site-header .nav-link:hover {
            color: #FBBF24 !important;
            background: rgba(255, 255, 255, 0.08);
        }

        /* Orange color for the currently selected page */
        .site-header .nav-link.active,
        .site-header .nav-link.active:hover {
            color: #F59E0B !important;
            background: transparent !important;
        }

        .site-header .btn-primary {
            background: #F59E0B;
            color: #172554;
            border: 1px solid #F59E0B;
            border-radius: 8px;
            font-weight: 700;
            padding: 9px 18px;
        }

        .site-header .btn-primary:hover {
            background: #FBBF24;
            border-color: #FBBF24;
            color: #172554;
        }

        .account-label {
            display: inline-block;
            color: #FBBF24;
            font-size: 0.65rem;
            font-weight: 800;
            letter-spacing: 1.2px;
            margin-left: 5px;
            vertical-align: middle;
        }

        .site-header .navbar-toggler {
            border-color: rgba(255,255,255,0.5);
        }

        .site-header .navbar-toggler-icon {
            filter: brightness(0) invert(1);
        }

        @media (max-width: 991px) {
            .site-header .navbar-nav {
                align-items: stretch !important;
                padding-top: 15px;
                gap: 5px;
            }

            .site-header .nav-link {
                padding: 10px 12px !important;
            }
        }
    </style>
</head>

<body>

<header class="site-header">

    <nav class="navbar navbar-expand-lg">

        <div class="container">

            <?php if ($isAuthPage): ?>

                <a class="navbar-brand brand-name" href="index.php">
                    BookNest<span>.</span>
                </a>

            <?php elseif ($isAdmin): ?>

                <a class="navbar-brand brand-name" href="admin/dashboard.php">
                    BookNest<span>.</span>Admin
                    <span class="account-label">ADMIN</span>
                </a>

            <?php else: ?>

                <a class="navbar-brand brand-name" href="index.php">
                    BookNest<span>.</span>User
                </a>

            <?php endif; ?>


            <?php if (!$isAuthPage): ?>

                <button
                    class="navbar-toggler"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#mainNav"
                    aria-controls="mainNav"
                    aria-expanded="false"
                    aria-label="Toggle navigation"
                >
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="mainNav">

                    <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">

                        <?php if ($isAdmin): ?>

                            <li class="nav-item">
                                <a class="nav-link <?= $activeClass(['dashboard.php']) ?>"
                                   href="admin/dashboard.php">
                                    Dashboard
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link <?= $activeClass(['books.php']) ?>"
                                   href="admin/books.php">
                                    Manage Books
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link <?= $activeClass(['users.php']) ?>"
                                   href="admin/users.php">
                                    Manage Users
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link <?= $activeClass(['orders.php']) ?>"
                                   href="admin/orders.php">
                                    Manage Orders
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link <?= $activeClass(['store.php']) ?>"
                                   href="admin/store.php">
                                    View Store
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="btn btn-primary" href="logout.php">
                                    Logout
                                </a>
                            </li>

                        <?php elseif ($isCustomer): ?>
 <li class="nav-item">
                                <a class="nav-link <?= $activeClass(['dashboard.php']) ?>"
                                   href="dashboard.php">
                                    My Dashboard
                                </a>
                            <li class="nav-item">
                                <a class="nav-link <?= $activeClass(['index.php']) ?>"
                                   href="index.php">
                                    Home
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link <?= $activeClass(['books.php', 'book-details.php']) ?>"
                                   href="books.php">
                                    Browse Books
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link <?= $activeClass(['cart.php']) ?>"
                                   href="cart.php">
                                    Cart
                                </a>
                            </li>

                           
                            </li>

                            <li class="nav-item">
                                <a class="nav-link <?= $activeClass(['my-orders.php']) ?>"
                                   href="my-orders.php">
                                    My Orders
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="btn btn-primary" href="logout.php">
                                    Logout
                                </a>
                            </li>

                        <?php else: ?>

                            <li class="nav-item">
                                <a class="nav-link <?= $activeClass(['index.php']) ?>"
                                   href="index.php">
                                    Home
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link <?= $activeClass(['books.php', 'book-details.php']) ?>"
                                   href="books.php">
                                    Browse Books
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link <?= $activeClass(['login.php']) ?>"
                                   href="login.php">
                                    Login
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="btn btn-primary" href="register.php">
                                    Join BookNest
                                </a>
                            </li>

                        <?php endif; ?>

                    </ul>

                </div>

            <?php endif; ?>

        </div>

    </nav>

</header>

<main class="page-main">
