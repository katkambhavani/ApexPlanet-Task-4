
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Admin access protection
if (
    !isset($_SESSION['user_id']) ||
    (int)($_SESSION['role_id'] ?? 0) !== 1
) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . '/../config/database.php';

// Dashboard statistics
$booksQuery = $conn->query("SELECT COUNT(*) AS total FROM books");
$totalBooks = (int)$booksQuery->fetch_assoc()['total'];

$usersQuery = $conn->query(
    "SELECT COUNT(*) AS total FROM users WHERE role_id = 2"
);
$totalCustomers = (int)$usersQuery->fetch_assoc()['total'];

$ordersQuery = $conn->query("SELECT COUNT(*) AS total FROM orders");
$totalOrders = (int)$ordersQuery->fetch_assoc()['total'];

$revenueQuery = $conn->query(
    "SELECT COALESCE(SUM(total_amount), 0) AS revenue
     FROM orders
     WHERE status != 'cancelled'"
);
$totalRevenue = (float)$revenueQuery->fetch_assoc()['revenue'];

// Recent orders
$recentOrders = $conn->query(
    "SELECT orders.id, users.name, orders.total_amount,
            orders.status, orders.created_at
     FROM orders
     JOIN users ON orders.user_id = users.id
     ORDER BY orders.created_at DESC
     LIMIT 5"
);

$adminName = $_SESSION['name'] ?? 'Admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>BookNest.Admin | Dashboard</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
          rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/theme.css">
    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #FFF9F0;
            color: #243047;
            font-family: 'Inter', Arial, sans-serif;
        }

        /* COMMON ADMIN NAVBAR */

        .site-header {
            background: #172554;
            box-shadow: 0 3px 15px rgba(23,37,84,.10);
        }

        .site-header .navbar {
            padding: 15px 0;
        }

        .brand-name {
            color: #fff !important;
            font-size: 1.4rem;
            font-weight: 800;
            letter-spacing: -.5px;
        }

        .brand-name span {
            color: #F59E0B;
        }

        .site-header .nav-link {
            color: #fff;
            font-size: .88rem;
            font-weight: 600;
            padding: 9px 10px;
            transition: color .2s ease;
        }

        .site-header .nav-link.active,
        .site-header .nav-link[aria-current="page"],
        .site-header .nav-link:hover {
            color: #F59E0B !important;
            background: transparent !important;
        }

        .site-header .btn-primary {
            background: #F59E0B;
            border: 1px solid #F59E0B;
            color: #172554;
            border-radius: 8px;
            font-weight: 700;
            padding: 9px 17px;
        }

        .site-header .btn-primary:hover {
            background: #FBBF24;
            border-color: #FBBF24;
            color: #172554;
        }

        .navbar-toggler {
            border-color: rgba(255,255,255,.5);
        }

        .navbar-toggler-icon {
            filter: brightness(0) invert(1);
        }

        @media(max-width:991px) {
            .site-header .navbar-nav {
                align-items: flex-start !important;
                gap: 5px !important;
                padding-top: 15px;
            }
        }

        /* MAIN CONTENT */

        .main-content {
            padding: 18px 0 40px;
        }

        .welcome-section {
            margin-bottom: 25px;
        }

        .welcome-section h2 {
            font-size: 27px;
            font-weight: 800;
            color: #172554;
            margin-bottom: 8px;
        }

        .welcome-section p {
            color: #64748b;
            font-size: 14px;
            margin: 0;
        }

        /* STATISTICS */

        .stat-card {
            background: #fff;
            border: 1px solid #f1eadf;
            border-radius: 15px;
            padding: 23px;
            height: 100%;
            box-shadow: 0 5px 18px rgba(23,37,84,.04);
            transition: transform .2s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
        }

        .stat-icon {
            width: 46px;
            height: 46px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-size: 23px;
            margin-bottom: 18px;
        }

        .icon-books { background: #e8edff; }
        .icon-users { background: #e4f7eb; }
        .icon-orders { background: #fff1d8; }
        .icon-revenue { background: #f5e8ff; }

        .stat-card p {
            color: #64748b;
            font-size: 13px;
            margin-bottom: 7px;
        }

        .stat-card h3 {
            color: #172554;
            font-size: 25px;
            font-weight: 800;
            margin: 0;
            overflow-wrap: anywhere;
        }

        /* CONTENT CARDS */

        .content-card {
            background: #fff;
            border: 1px solid #f1eadf;
            border-radius: 15px;
            padding: 24px;
            box-shadow: 0 5px 18px rgba(23,37,84,.04);
            margin-top: 25px;
        }

        .section-heading {
            color: #172554;
            font-weight: 700;
            font-size: 18px;
            margin-bottom: 20px;
        }

        /* QUICK ACTIONS */

        .action-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px 17px;
            border-radius: 9px;
            background: #172554;
            color: white;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: .2s;
        }

        .action-link:hover {
            background: #293d70;
            color: white;
            transform: translateY(-2px);
        }

        .action-link.secondary {
            background: #fff2d8;
            color: #172554;
        }

        .action-link.secondary:hover {
            background: #F59E0B;
            color: #172554;
        }

        /* RECENT ORDERS */

        .table {
            margin-bottom: 0;
            white-space: nowrap;
        }

        .table thead th {
            background: #f8f7f3;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            border-bottom: 0;
            padding: 14px;
        }

        .table tbody td {
            padding: 15px 14px;
            font-size: 13px;
            color: #334155;
            vertical-align: middle;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 11px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: capitalize;
        }

        .status-pending { background: #fff2d8; color: #a16207; }
        .status-confirmed { background: #dbeafe; color: #1d4ed8; }
        .status-shipped { background: #ede9fe; color: #6d28d9; }
        .status-delivered { background: #dcfce7; color: #15803d; }
        .status-cancelled { background: #fee2e2; color: #b91c1c; }

        .view-all {
            color: #172554;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
        }

        .view-all:hover {
            color: #F59E0B;
        }

        .footer-note {
            color: #94a3b8;
            font-size: 12px;
            text-align: center;
            margin-top: 30px;
        }

        @media(max-width:576px) {
            .main-content {
                padding: 22px 0 30px;
            }

            .welcome-section h2 {
                font-size: 23px;
            }

            .content-card {
                padding: 16px;
            }
        }
    </style>
</head>

<body>

<!-- COMMON ADMIN NAVIGATION -->

<header class="site-header">
    <nav class="navbar navbar-expand-lg">
        <div class="container">

            <a class="navbar-brand brand-name" href="dashboard.php">
                BookNest<span>.</span>Admin
            </a>

            <button class="navbar-toggler" type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#adminNav"
                    aria-controls="adminNav"
                    aria-expanded="false"
                    aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="adminNav">
                <ul class="navbar-nav ms-auto gap-lg-3">

                    <li class="nav-item">
                        <a class="nav-link active"
                           aria-current="page"
                           href="dashboard.php">Dashboard</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="books.php">Manage Books</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="users.php">Manage Users</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="orders.php">Manage Orders</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="store.php">View Store</a>
                    </li>

                    <li class="nav-item">
                        <a class="btn btn-primary" href="../logout.php">Logout</a>
                    </li>

                </ul>
            </div>
        </div>
    </nav>
</header>

<!-- DASHBOARD CONTENT -->

<main class="main-content">
    <div class="container">

        <section class="welcome-section">
            <h2>
                Welcome back,
                <?= htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8') ?>! 👋
            </h2>

            <p>
                Here is what's happening with your bookstore today.
            </p>
        </section>

        <!-- STATISTICS -->

        <div class="row g-4">

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon icon-books">📚</div>
                    <p>Total Books</p>
                    <h3><?= number_format($totalBooks) ?></h3>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon icon-users">👥</div>
                    <p>Total Customers</p>
                    <h3><?= number_format($totalCustomers) ?></h3>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon icon-orders">🛒</div>
                    <p>Total Orders</p>
                    <h3><?= number_format($totalOrders) ?></h3>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon icon-revenue">💰</div>
                    <p>Total Revenue</p>
                    <h3>₹<?= number_format($totalRevenue, 2) ?></h3>
                </div>
            </div>

        </div>


        <!-- RECENT ORDERS -->

        <section class="content-card">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">

                <h4 class="section-heading mb-0">
                    Recent Orders
                </h4>

                <a href="orders.php" class="view-all">
                    View All Orders →
                </a>

            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">

                    <thead>
                        <tr>
                            <th>ORDER ID</th>
                            <th>CUSTOMER</th>
                            <th>AMOUNT</th>
                            <th>STATUS</th>
                            <th>DATE</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($recentOrders && $recentOrders->num_rows > 0): ?>

                        <?php while ($order = $recentOrders->fetch_assoc()): ?>

                            <?php
                            $status = strtolower($order['status']);

                            $allowedStatuses = [
                                'pending',
                                'confirmed',
                                'shipped',
                                'delivered',
                                'cancelled'
                            ];

                            $statusClass = in_array($status, $allowedStatuses, true)
                                ? 'status-' . $status
                                : 'status-pending';
                            ?>

                            <tr>
                                <td>
                                    <strong>#<?= (int)$order['id'] ?></strong>
                                </td>

                                <td>
                                    <?= htmlspecialchars($order['name'], ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    ₹<?= number_format((float)$order['total_amount'], 2) ?>
                                </td>

                                <td>
                                    <span class="status-badge <?= $statusClass ?>">
                                        <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        date('d M Y, h:i A', strtotime($order['created_at'])),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>
                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                No orders found yet.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>
                </table>
            </div>
        </section>

        <p class="footer-note">
            © <?= date('Y') ?> BookNest.Admin — All rights reserved.
        </p>

    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
