
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

$message = '';
$messageType = 'success';

// Handle customer actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = (int)($_POST['user_id'] ?? 0);

    if ($userId <= 0) {
        $message = 'Invalid customer.';
        $messageType = 'danger';
    } else {
        // Confirm this is a customer, not an admin
        $check = $conn->prepare(
            "SELECT id FROM users WHERE id = ? AND role_id = 2"
        );

        $check->bind_param("i", $userId);
        $check->execute();

        $customerExists = $check->get_result()->num_rows > 0;
        $check->close();

        if (!$customerExists) {
            $message = 'Customer not found or action not allowed.';
            $messageType = 'danger';

        } elseif ($action === 'status') {
            $newStatus = $_POST['status'] ?? '';

            if (!in_array($newStatus, ['active', 'inactive'], true)) {
                $message = 'Invalid status.';
                $messageType = 'danger';
            } else {
                $stmt = $conn->prepare(
                    "UPDATE users
                     SET status = ?
                     WHERE id = ? AND role_id = 2"
                );

                $stmt->bind_param("si", $newStatus, $userId);

                if ($stmt->execute()) {
                    $stmt->close();

                    header(
                        "Location: users.php?message=" .
                        urlencode('Customer status updated successfully!')
                    );
                    exit;
                } else {
                    $message = 'Unable to update customer status.';
                    $messageType = 'danger';
                    $stmt->close();
                }
            }

        } elseif ($action === 'delete') {
            // Preserve customers who have existing orders
            $orderCheck = $conn->prepare(
                "SELECT COUNT(*) AS total
                 FROM orders
                 WHERE user_id = ?"
            );

            $orderCheck->bind_param("i", $userId);
            $orderCheck->execute();

            $orderCount = (int)$orderCheck
                ->get_result()
                ->fetch_assoc()['total'];

            $orderCheck->close();

            if ($orderCount > 0) {
                $message = 'This customer has order history. Deletion is blocked to preserve order records.';
                $messageType = 'warning';
            } else {
                $stmt = $conn->prepare(
                    "DELETE FROM users
                     WHERE id = ? AND role_id = 2"
                );

                $stmt->bind_param("i", $userId);

                if ($stmt->execute() && $stmt->affected_rows > 0) {
                    $stmt->close();

                    header(
                        "Location: users.php?message=" .
                        urlencode('Customer deleted successfully!')
                    );
                    exit;
                } else {
                    $message = 'Unable to delete customer.';
                    $messageType = 'danger';
                }

                $stmt->close();
            }
        }
    }
}

if (isset($_GET['message'])) {
    $message = $_GET['message'];
}

// Fetch customers and their order counts
$customers = $conn->query(
    "SELECT users.id, users.name, users.email, users.status,
            users.created_at, COUNT(orders.id) AS total_orders
     FROM users
     LEFT JOIN orders ON users.id = orders.user_id
     WHERE users.role_id = 2
     GROUP BY users.id, users.name, users.email,
              users.status, users.created_at
     ORDER BY users.created_at DESC"
);

// Customer statistics
$customerStats = $conn->query(
    "SELECT
        COUNT(*) AS total,
        COALESCE(SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END), 0) AS active,
        COALESCE(SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END), 0) AS inactive
     FROM users
     WHERE role_id = 2"
)->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>BookNest.Admin | Manage Users</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

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

        /* ADMIN NAVBAR - SAME ON ALL ADMIN PAGES */

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

        /* MAIN PAGE */

        .page-container {
            max-width: 1320px;
            margin: 0 auto;
            padding: 38px 22px 60px;
        }

        .eyebrow {
            color: #b77908;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.4px;
            margin-bottom: 8px;
        }

        .page-heading {
            color: #172554;
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 7px;
        }

        .page-description {
            color: #64748b;
            font-size: 14px;
            margin: 0;
        }

        /* SUMMARY CARDS */

        .summary-card {
            background: white;
            border: 1px solid #f1eadf;
            border-radius: 14px;
            padding: 21px;
            height: 100%;
            box-shadow: 0 5px 18px rgba(23,37,84,.04);
            transition: transform .2s;
        }

        .summary-card:hover {
            transform: translateY(-3px);
        }

        .summary-icon {
            width: 43px;
            height: 43px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 11px;
            font-size: 21px;
            margin-bottom: 14px;
        }

        .summary-card p {
            color: #64748b;
            font-size: 12px;
            margin-bottom: 5px;
        }

        .summary-card h3 {
            color: #172554;
            font-size: 25px;
            font-weight: 800;
            margin: 0;
        }

        /* CONTENT CARD */

        .content-card {
            background: white;
            border: 1px solid #f1eadf;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 18px rgba(23,37,84,.04);
            margin-top: 25px;
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
            margin-bottom: 0;
        }

        /* SEARCH */

        .search-box {
            max-width: 320px;
            width: 100%;
        }

        .search-box input {
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            padding: 11px 13px;
            font-size: 13px;
        }

        .search-box input:focus {
            border-color: #F59E0B;
            box-shadow: 0 0 0 3px rgba(245,158,11,.12);
        }

        /* CUSTOMER TABLE */

        .customer-table {
            margin: 0;
            white-space: nowrap;
        }

        .customer-table thead th {
            background: #f8f7f3;
            color: #64748b;
            border: none;
            padding: 15px 13px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .4px;
        }

        .customer-table tbody td {
            color: #334155;
            font-size: 13px;
            padding: 15px 13px;
            vertical-align: middle;
            border-color: #f1f5f9;
        }

        .customer-name {
            color: #172554;
            font-weight: 700;
        }

        .customer-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #e8edff;
            color: #172554;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            margin-right: 9px;
            flex-shrink: 0;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 11px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 800;
        }

        .status-active {
            background: #dcfce7;
            color: #15803d;
        }

        .status-inactive {
            background: #fee2e2;
            color: #b91c1c;
        }

        .order-count {
            background: #e8edff;
            color: #1d4ed8;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 800;
        }

        .btn-activate {
            background: #e8edff;
            color: #1d4ed8;
            border: none;
            font-size: 11px;
            font-weight: 700;
        }

        .btn-activate:hover {
            background: #dbeafe;
            color: #1e40af;
        }

        .btn-deactivate {
            background: #fff2d8;
            color: #a16207;
            border: none;
            font-size: 11px;
            font-weight: 700;
        }

        .btn-deactivate:hover {
            background: #fde6b3;
            color: #854d0e;
        }

        .btn-delete {
            background: #fee2e2;
            color: #b91c1c;
            border: none;
            font-size: 11px;
            font-weight: 700;
        }

        .btn-delete:hover {
            background: #fecaca;
            color: #991b1b;
        }

        .footer-note {
            color: #94a3b8;
            font-size: 11px;
            text-align: center;
            margin-top: 30px;
        }

        @media(max-width:576px) {
            .page-container {
                padding: 25px 14px 40px;
            }

            .page-heading {
                font-size: 23px;
            }

            .content-card {
                padding: 17px;
            }

            .search-box {
                max-width: 100%;
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
                        <a class="nav-link active" aria-current="page" href="users.php">
                            Manage Users
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="orders.php">
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

    <div class="mb-3">
        <p class="eyebrow">CUSTOMER MANAGEMENT</p>

        <h1 class="page-heading">Manage Users</h1>

        <p class="page-description">
            View registered customers and manage their bookstore accounts.
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

    <!-- CUSTOMER SUMMARY -->

    <div class="row g-3 mb-4">

        <div class="col-12 col-sm-4">
            <div class="summary-card">

                <div class="summary-icon" style="background:#e8edff;">
                    👥
                </div>

                <p>Total Customers</p>

                <h3>
                    <?= number_format((int)$customerStats['total']) ?>
                </h3>

            </div>
        </div>

        <div class="col-12 col-sm-4">
            <div class="summary-card">

                <div class="summary-icon" style="background:#dcfce7;">
                    ✅
                </div>

                <p>Active Customers</p>

                <h3>
                    <?= number_format((int)$customerStats['active']) ?>
                </h3>

            </div>
        </div>

        <div class="col-12 col-sm-4">
            <div class="summary-card">

                <div class="summary-icon" style="background:#fee2e2;">
                    ⏸️
                </div>

                <p>Inactive Customers</p>

                <h3>
                    <?= number_format((int)$customerStats['inactive']) ?>
                </h3>

            </div>
        </div>

    </div>

    <!-- CUSTOMER TABLE -->

    <section class="content-card">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

            <div>
                <h4 class="section-title">Registered Customers</h4>

                <p class="section-description">
                    Manage customer accounts and view their order activity.
                </p>
            </div>

            <div class="search-box">
                <input
                    type="search"
                    id="customerSearch"
                    class="form-control"
                    placeholder="🔍 Search name or email..."
                >
            </div>

        </div>

        <div class="table-responsive">

            <table class="table table-hover customer-table">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>CUSTOMER</th>
                        <th>EMAIL</th>
                        <th>ORDERS</th>
                        <th>JOINED DATE</th>
                        <th>STATUS</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>

                <tbody id="customerTableBody">

                <?php if ($customers && $customers->num_rows > 0): ?>

                    <?php while ($customer = $customers->fetch_assoc()): ?>

                        <?php
                        $status = strtolower($customer['status']);

                        $statusClass = $status === 'active'
                            ? 'status-active'
                            : 'status-inactive';

                        $initial = strtoupper(
                            substr($customer['name'], 0, 1)
                        );
                        ?>

                        <tr class="customer-row">

                            <td>
                                #<?= (int)$customer['id'] ?>
                            </td>

                            <td>
                                <div class="d-flex align-items-center">

                                    <span class="customer-avatar">
                                        <?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?>
                                    </span>

                                    <span class="customer-name">
                                        <?= htmlspecialchars($customer['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>

                                </div>
                            </td>

                            <td>
                                <?= htmlspecialchars($customer['email'], ENT_QUOTES, 'UTF-8') ?>
                            </td>

                            <td>
                                <span class="order-count">
                                    <?= (int)$customer['total_orders'] ?> Orders
                                </span>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    date('d M Y', strtotime($customer['created_at'])),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <span class="status-badge <?= $statusClass ?>">
                                    <?= htmlspecialchars(ucfirst($status), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>

                            <td>

                                <div class="d-flex flex-wrap gap-2">

                                    <form method="POST" action="users.php">

                                        <input type="hidden"
                                               name="action"
                                               value="status">

                                        <input type="hidden"
                                               name="user_id"
                                               value="<?= (int)$customer['id'] ?>">

                                        <input type="hidden"
                                               name="status"
                                               value="<?= $status === 'active' ? 'inactive' : 'active' ?>">

                                        <button type="submit"
                                                class="btn btn-sm <?= $status === 'active' ? 'btn-deactivate' : 'btn-activate' ?>">

                                            <?= $status === 'active' ? 'Deactivate' : 'Activate' ?>

                                        </button>

                                    </form>

                                    <form method="POST"
                                          action="users.php"
                                          onsubmit="return confirm('Delete this customer? Customers with existing orders cannot be deleted.');">

                                        <input type="hidden"
                                               name="action"
                                               value="delete">

                                        <input type="hidden"
                                               name="user_id"
                                               value="<?= (int)$customer['id'] ?>">

                                        <button type="submit"
                                                class="btn btn-sm btn-delete">
                                            Delete
                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            No customers registered yet.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

            <p id="noSearchResults"
               class="text-center text-muted py-4"
               style="display:none;">
                No customers match your search.
            </p>

        </div>

    </section>

    <p class="footer-note">
        © <?= date('Y') ?> BookNest.Admin — All rights reserved.
    </p>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.getElementById('customerSearch').addEventListener('input', function () {

    const searchValue = this.value.toLowerCase().trim();
    const rows = document.querySelectorAll('.customer-row');

    let visibleCount = 0;

    rows.forEach(function (row) {

        const rowText = row.textContent.toLowerCase();
        const matches = rowText.includes(searchValue);

        row.style.display = matches ? '' : 'none';

        if (matches) {
            visibleCount++;
        }
    });

    document.getElementById('noSearchResults').style.display =
        visibleCount === 0 && rows.length > 0 ? 'block' : 'none';

});
</script>

</body>
</html>
