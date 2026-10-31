
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

// Admin access protection
if (
    !isset($_SESSION['user_id']) ||
    (int)($_SESSION['role_id'] ?? 0) !== 1
) {
    header("Location: ../login.php");
    exit;
}

$message = '';
$messageType = 'success';

$allowedStatuses = [
    'pending',
    'confirmed',
    'shipped',
    'delivered',
    'cancelled'
];

// Update order status
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'update_status'
) {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';

    if ($orderId <= 0 || !in_array($newStatus, $allowedStatuses, true)) {
        $message = 'Invalid order or status.';
        $messageType = 'danger';
    } else {
        $stmt = $conn->prepare(
            "UPDATE orders SET status = ? WHERE id = ?"
        );

        $stmt->bind_param("si", $newStatus, $orderId);

        if ($stmt->execute()) {
            $stmt->close();

            header("Location: orders.php?message=" .
                urlencode('Order status updated successfully!'));
            exit;
        } else {
            $message = 'Unable to update order status.';
            $messageType = 'danger';
            $stmt->close();
        }
    }
}

if (isset($_GET['message'])) {
    $message = $_GET['message'];
}

// Fetch orders
$orders = $conn->query(
    "SELECT
        orders.id,
        orders.total_amount,
        orders.status,
        orders.created_at,
        orders.phone,
        orders.delivery_address,
        orders.city,
        users.name AS customer_name,
        users.email AS customer_email,
        GROUP_CONCAT(
            CONCAT(books.title, ' (Qty: ', order_items.quantity, ')')
            SEPARATOR ', '
        ) AS purchased_books
     FROM orders
     JOIN users ON orders.user_id = users.id
     LEFT JOIN order_items ON orders.id = order_items.order_id
     LEFT JOIN books ON order_items.book_id = books.id
     GROUP BY
        orders.id,
        orders.total_amount,
        orders.status,
        orders.created_at,
        orders.phone,
        orders.delivery_address,
        orders.city,
        users.name,
        users.email
     ORDER BY orders.created_at DESC"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>BookNest.Admin | Manage Orders</title>

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
            margin: 0 !important;
            padding: 0 !important;
            background: #FFF9F0;
            color: #243047;
            font-family: 'Inter', Arial, sans-serif;
        }

        /* Admin Navigation */

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

        /* Page spacing */

        main.page-container {
            width: 100%;
            max-width: 1320px;
            margin: 0 auto !important;
            padding: 8px 22px 60px !important;
        }

        .page-top {
            margin: 0 0 16px !important;
            padding: 0 !important;
        }

        .eyebrow {
            color: #b77908;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.4px;
            margin: 0 0 5px;
        }

        .page-heading {
            color: #172554;
            font-size: 28px;
            font-weight: 800;
            margin: 0 0 6px;
        }

        .page-description {
            color: #64748b;
            font-size: 14px;
            margin: 0;
        }

        /* Content card */

        .content-card {
            background: white;
            border: 1px solid #f1eadf;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 18px rgba(23,37,84,0.04);
            margin: 0 !important;
        }

        .section-title {
            color: #172554;
            font-size: 18px;
            font-weight: 800;
            margin-bottom: 5px;
        }

        .section-description {
            color: #64748b;
            font-size: 12px;
            margin-bottom: 22px;
        }

        /* Table */

        .inventory-table {
            margin: 0;
        }

        .inventory-table thead th {
            background: #f8f7f3;
            color: #64748b;
            border: none;
            padding: 15px 13px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .4px;
            white-space: nowrap;
        }

        .inventory-table tbody td {
            color: #334155;
            font-size: 13px;
            padding: 15px 13px;
            vertical-align: middle;
            border-color: #f1f5f9;
        }

        .order-details {
            min-width: 230px;
            max-width: 320px;
            white-space: normal;
        }

        /* Status badges */

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-confirmed {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-shipped {
            background: #ede9fe;
            color: #6d28d9;
        }

        .status-delivered {
            background: #dcfce7;
            color: #15803d;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #b91c1c;
        }

        .table .badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 800;
        }

        /* Form controls */

        .form-select {
            border-color: #e2e8f0;
            border-radius: 8px;
            padding: 9px 10px;
            font-size: 12px;
        }

        .form-select:focus {
            border-color: #F59E0B;
            box-shadow: 0 0 0 3px rgba(245,158,11,0.12);
        }

        /* Buttons */

        .btn-booknest {
            background: #F59E0B;
            color: #172554;
            border: none;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
        }

        .btn-booknest:hover {
            background: #d97706;
            color: white;
        }

        @media (max-width: 576px) {
            main.page-container {
                padding: 8px 14px 40px !important;
            }

            .page-heading {
                font-size: 23px;
            }

            .content-card {
                padding: 17px;
            }
        }
    </style>
</head>

<body>

<!-- ADMIN NAVIGATION -->

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
                        <a class="nav-link" href="dashboard.php">
                            Dashboard
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="books.php">
                            Manage Books
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="users.php">
                            Manage Users
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link active"
                           aria-current="page"
                           href="orders.php">
                            Manage Orders
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="store.php">
                            View Store
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="btn btn-primary" href="../logout.php">
                            Logout
                        </a>
                    </li>

                </ul>
            </div>
        </div>
    </nav>
</header>

<main class="page-container">

    <!-- PAGE HEADING -->

    <br>

    <div class="page-top">
        <p class="eyebrow">BOOKSTORE MANAGEMENT</p>

        <h1 class="page-heading">Manage Orders</h1>

        <p class="page-description">
            View customer purchases, delivery information and update order status.
        </p>
    </div>

    <!-- ALERT MESSAGE -->

    <?php if ($message): ?>
        <div class="alert alert-<?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?> alert-dismissible fade show">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>

            <button type="button" class="btn-close"
                    data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- CUSTOMER ORDERS -->

    <section class="content-card">

        <div class="mb-3">
            <h4 class="section-title">All Customer Orders</h4>

            <p class="section-description">
                View and manage all books purchased by your customers.
            </p>
        </div>

        <div class="table-responsive">
            <table class="table table-hover inventory-table">

                <thead>
                    <tr>
                        <th>ORDER ID</th>
                        <th>CUSTOMER</th>
                        <th>PURCHASED BOOKS</th>
                        <th>DELIVERY DETAILS</th>
                        <th>AMOUNT</th>
                        <th>DATE</th>
                        <th>STATUS</th>
                        <th>UPDATE STATUS</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($orders && $orders->num_rows > 0): ?>

                    <?php while ($order = $orders->fetch_assoc()): ?>

                        <?php
                        $currentStatus = strtolower($order['status']);

                        $statusClass = in_array(
                            $currentStatus,
                            $allowedStatuses,
                            true
                        ) ? $currentStatus : 'pending';
                        ?>

                        <tr>

                            <td>
                                <strong>#<?= (int)$order['id'] ?></strong>
                            </td>

                            <td>
                                <div class="fw-bold">
                                    <?= htmlspecialchars($order['customer_name'], ENT_QUOTES, 'UTF-8') ?>
                                </div>

                                <small class="text-muted">
                                    <?= htmlspecialchars($order['customer_email'], ENT_QUOTES, 'UTF-8') ?>
                                </small>
                            </td>

                            <td class="order-details">
                                <?= htmlspecialchars(
                                    $order['purchased_books'] ?? 'No book details available',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td class="order-details">
                                <strong>Phone:</strong>
                                <?= htmlspecialchars($order['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>

                                <br>

                                <strong>Address:</strong>
                                <?= htmlspecialchars($order['delivery_address'] ?? '', ENT_QUOTES, 'UTF-8') ?>

                                <br>

                                <strong>City:</strong>
                                <?= htmlspecialchars($order['city'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                            </td>

                            <td class="fw-bold">
                                ₹<?= number_format((float)$order['total_amount'], 2) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($order['created_at'], ENT_QUOTES, 'UTF-8') ?>
                            </td>

                            <td>
                                <span class="badge status-<?= $statusClass ?>">
                                    <?= htmlspecialchars(ucfirst($currentStatus), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>

                            <td>
                                <form method="POST" action="orders.php">

                                    <input type="hidden"
                                           name="action"
                                           value="update_status">

                                    <input type="hidden"
                                           name="order_id"
                                           value="<?= (int)$order['id'] ?>">

                                    <select name="status"
                                            class="form-select mb-2"
                                            required>

                                        <?php foreach ($allowedStatuses as $status): ?>
                                            <option value="<?= $status ?>"
                                                <?= $currentStatus === $status ? 'selected' : '' ?>>
                                                <?= ucfirst($status) ?>
                                            </option>
                                        <?php endforeach; ?>

                                    </select>

                                    <button type="submit" class="btn btn-booknest">
                                        Update
                                    </button>

                                </form>
                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            No orders found.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>
            </table>
        </div>

    </section>

    <p class="text-center text-muted mt-4 small">
        © <?= date('Y') ?> BookNest.Admin — All rights reserved.
    </p>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
