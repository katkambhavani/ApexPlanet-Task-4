
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

// Handle Add, Edit and Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $bookId = (int)($_POST['book_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $author = trim($_POST['author'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 0);
        $categoryId = (int)($_POST['category_id'] ?? 0);

        if (
            $title === '' ||
            $author === '' ||
            $price <= 0 ||
            $stock < 0 ||
            $categoryId <= 0
        ) {
            $message = 'Please enter valid book details.';
            $messageType = 'danger';
        } else {
            if ($bookId > 0) {
                $stmt = $conn->prepare(
                    "UPDATE books
                     SET title=?, author=?, description=?, price=?,
                         stock=?, category_id=?
                     WHERE id=?"
                );

                $stmt->bind_param(
                    "sssdiii",
                    $title,
                    $author,
                    $description,
                    $price,
                    $stock,
                    $categoryId,
                    $bookId
                );

                $successMessage = 'Book updated successfully!';
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO books
                    (title, author, description, price, stock, category_id, status)
                    VALUES (?, ?, ?, ?, ?, ?, 'active')"
                );

                $stmt->bind_param(
                    "sssdii",
                    $title,
                    $author,
                    $description,
                    $price,
                    $stock,
                    $categoryId
                );

                $successMessage = 'New book added successfully!';
            }

            if ($stmt->execute()) {
                $stmt->close();
                header("Location: books.php?message=" . urlencode($successMessage));
                exit;
            } else {
                $message = 'Unable to save book: ' . $stmt->error;
                $messageType = 'danger';
                $stmt->close();
            }
        }
    }

    if ($action === 'delete') {
        $bookId = (int)($_POST['book_id'] ?? 0);

        $stmt = $conn->prepare("DELETE FROM books WHERE id = ?");
        $stmt->bind_param("i", $bookId);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $stmt->close();
            header("Location: books.php?message=" . urlencode('Book deleted successfully!'));
            exit;
        } else {
            $message = 'Could not delete this book. It may be linked to an existing order.';
            $messageType = 'danger';
        }

        $stmt->close();
    }
}

if (isset($_GET['message'])) {
    $message = $_GET['message'];
}

// Load categories
$categories = $conn->query(
    "SELECT id, name FROM categories ORDER BY name"
);

// Load book for editing
$editBook = null;

if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];

    $stmt = $conn->prepare(
        "SELECT id, title, author, description, price, stock, category_id
         FROM books
         WHERE id = ?"
    );

    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $editBook = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Load all books
$books = $conn->query(
    "SELECT books.id, books.title, books.author, books.price,
            books.stock, books.status,
            categories.name AS category_name
     FROM books
     LEFT JOIN categories ON books.category_id = categories.id
     ORDER BY books.created_at DESC"
);

// Inventory statistics
$inventoryStats = $conn->query(
    "SELECT
        COUNT(*) AS total,
        COALESCE(SUM(CASE WHEN stock > 0 THEN 1 ELSE 0 END), 0) AS available,
        COALESCE(SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END), 0) AS out_of_stock
     FROM books"
)->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>BookNest.Admin | Manage Books</title>

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
            box-shadow: 0 3px 15px rgba(23, 37, 84, 0.10);
        }

        .site-header .navbar {
            padding: 15px 0;
        }

        .brand-name {
            color: #fff !important;
            font-size: 1.4rem;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .brand-name span {
            color: #F59E0B;
        }

        .site-header .nav-link {
            color: #fff;
            font-size: 0.88rem;
            font-weight: 600;
            padding: 9px 10px;
            transition: color 0.2s ease;
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
            border-color: rgba(255,255,255,0.5);
        }

        .navbar-toggler-icon {
            filter: brightness(0) invert(1);
        }

        @media (max-width: 991px) {
            .site-header .navbar-nav {
                align-items: flex-start !important;
                gap: 5px !important;
                padding-top: 15px;
            }
        }

        /* PAGE */

        .page-container {
            max-width: 1320px;
            margin: auto;
            padding: 38px 22px 60px;
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

        .eyebrow {
            color: #b77908;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.4px;
            margin-bottom: 8px;
        }

        /* SUMMARY CARDS */

        .summary-card {
            background: white;
            border: 1px solid #f1eadf;
            border-radius: 14px;
            padding: 21px;
            height: 100%;
            box-shadow: 0 5px 18px rgba(23,37,84,0.04);
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

        /* CONTENT CARDS */

        .content-card {
            background: white;
            border: 1px solid #f1eadf;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 18px rgba(23,37,84,0.04);
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
            margin-bottom: 22px;
        }

        .form-label {
            color: #334155;
            font-size: 13px;
            font-weight: 600;
        }

        .form-control,
        .form-select {
            border-color: #e2e8f0;
            border-radius: 8px;
            padding: 11px 12px;
            font-size: 13px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #F59E0B;
            box-shadow: 0 0 0 3px rgba(245,158,11,0.12);
        }

        /* BUTTONS */

        .btn-booknest {
            background: #F59E0B;
            color: #172554;
            border: none;
            padding: 11px 19px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
        }

        .btn-booknest:hover {
            background: #d97706;
            color: white;
        }

        .btn-cancel {
            background: #f1f5f9;
            color: #334155;
            border: none;
            padding: 11px 17px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
        }

        /* INVENTORY TABLE */

        .inventory-table {
            margin: 0;
            white-space: nowrap;
        }

        .inventory-table thead th {
            background: #f8f7f3;
            color: #64748b;
            border: none;
            padding: 15px 13px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .4px;
        }

        .inventory-table tbody td {
            color: #334155;
            font-size: 13px;
            padding: 15px 13px;
            vertical-align: middle;
            border-color: #f1f5f9;
        }

        .book-title {
            color: #172554;
            font-weight: 700;
        }

        .stock-badge,
        .status-badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 800;
        }

        .stock-good,
        .status-active {
            background: #dcfce7;
            color: #15803d;
        }

        .stock-low {
            background: #fff2d8;
            color: #a16207;
        }

        .stock-empty,
        .status-inactive {
            background: #fee2e2;
            color: #b91c1c;
        }

        .btn-edit {
            background: #e8edff;
            color: #1d4ed8;
            border: none;
            font-size: 11px;
            font-weight: 700;
        }

        .btn-edit:hover {
            background: #dbeafe;
            color: #1e40af;
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

        @media (max-width: 576px) {
            .page-container {
                padding: 25px 14px 40px;
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

            <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#adminNav"
                aria-controls="adminNav"
                aria-expanded="false"
                aria-label="Toggle navigation"
            >
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="adminNav">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">

                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            Dashboard
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="books.php">
                            Manage Books
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="users.php">
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

    <div class="mb-4">
        <p class="eyebrow">BOOKSTORE MANAGEMENT</p>
        <h1 class="page-heading">Manage Books</h1>
        <p class="page-description">
            Manage your bookstore collection, prices and inventory.
        </p>
    </div>

    <!-- ALERT MESSAGE -->

    <?php if ($message): ?>
        <div class="alert alert-<?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?> alert-dismissible fade show">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- INVENTORY SUMMARY -->

    <div class="row g-3 mb-4">

        <div class="col-12 col-sm-4">
            <div class="summary-card">
                <div class="summary-icon" style="background:#e8edff;">📚</div>
                <p>Total Books</p>
                <h3><?= number_format((int)$inventoryStats['total']) ?></h3>
            </div>
        </div>

        <div class="col-12 col-sm-4">
            <div class="summary-card">
                <div class="summary-icon" style="background:#dcfce7;">✅</div>
                <p>Books Available</p>
                <h3><?= number_format((int)$inventoryStats['available']) ?></h3>
            </div>
        </div>

        <div class="col-12 col-sm-4">
            <div class="summary-card">
                <div class="summary-icon" style="background:#fee2e2;">⚠️</div>
                <p>Out of Stock</p>
                <h3><?= number_format((int)$inventoryStats['out_of_stock']) ?></h3>
            </div>
        </div>

    </div>

    <!-- ADD / EDIT BOOK FORM -->

    <section class="content-card">

        <div class="mb-4">
            <h4 class="section-title">
                <?= $editBook ? 'Edit Book Details' : 'Add a New Book' ?>
            </h4>

            <p class="section-description">
                <?= $editBook
                    ? 'Update the selected book information.'
                    : 'Enter the details below to add a new book to your store.' ?>
            </p>
        </div>

        <form method="POST" action="books.php">

            <input type="hidden" name="action" value="save">
            <input type="hidden" name="book_id" value="<?= (int)($editBook['id'] ?? 0) ?>">

            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label">Book Title *</label>
                    <input
                        type="text"
                        name="title"
                        class="form-control"
                        placeholder="Enter book title"
                        required
                        maxlength="255"
                        value="<?= htmlspecialchars($editBook['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label">Author *</label>
                    <input
                        type="text"
                        name="author"
                        class="form-control"
                        placeholder="Enter author name"
                        required
                        maxlength="255"
                        value="<?= htmlspecialchars($editBook['author'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label">Category *</label>
                    <select name="category_id" class="form-select" required>
                        <option value="">Select a category</option>

                        <?php while ($category = $categories->fetch_assoc()): ?>
                            <option
                                value="<?= (int)$category['id'] ?>"
                                <?= (int)($editBook['category_id'] ?? 0) === (int)$category['id'] ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endwhile; ?>

                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Price (₹) *</label>
                    <input
                        type="number"
                        name="price"
                        class="form-control"
                        placeholder="0.00"
                        min="0.01"
                        step="0.01"
                        required
                        value="<?= htmlspecialchars((string)($editBook['price'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    >
                </div>

                <div class="col-md-3">
                    <label class="form-label">Stock Quantity *</label>
                    <input
                        type="number"
                        name="stock"
                        class="form-control"
                        placeholder="Available copies"
                        min="0"
                        required
                        value="<?= htmlspecialchars((string)($editBook['stock'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    >
                </div>

                <div class="col-12">
                    <label class="form-label">Book Description</label>
                    <textarea
                        name="description"
                        class="form-control"
                        rows="3"
                        placeholder="Write a short description about this book..."
                    ><?= htmlspecialchars($editBook['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="col-12 d-flex flex-wrap gap-2 mt-4">

                    <button type="submit" class="btn btn-booknest">
                        <?= $editBook ? '✓ Update Book' : '+ Add Book' ?>
                    </button>

                    <?php if ($editBook): ?>
                        <a href="books.php" class="btn-cancel">
                            Cancel Editing
                        </a>
                    <?php endif; ?>

                </div>

            </div>
        </form>

    </section>

    <!-- BOOK INVENTORY TABLE -->

    <section class="content-card">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">

            <div>
                <h4 class="section-title">Book Inventory</h4>
                <p class="section-description mb-0">
                    View and manage all books currently listed in BookNest.
                </p>
            </div>

            <span class="badge rounded-pill text-bg-light px-3 py-2">
                <?= (int)$inventoryStats['total'] ?> Books
            </span>

        </div>

        <div class="table-responsive">

            <table class="table table-hover inventory-table">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>BOOK DETAILS</th>
                        <th>CATEGORY</th>
                        <th>PRICE</th>
                        <th>STOCK</th>
                        <th>STATUS</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($books && $books->num_rows > 0): ?>

                    <?php while ($book = $books->fetch_assoc()): ?>

                        <?php
                        $stock = (int)$book['stock'];

                        if ($stock === 0) {
                            $stockClass = 'stock-empty';
                            $stockLabel = 'Out of Stock';
                        } elseif ($stock <= 5) {
                            $stockClass = 'stock-low';
                            $stockLabel = 'Low Stock';
                        } else {
                            $stockClass = 'stock-good';
                            $stockLabel = 'In Stock';
                        }

                        $status = strtolower($book['status']);
                        $statusClass = $status === 'active'
                            ? 'status-active'
                            : 'status-inactive';
                        ?>

                        <tr>

                            <td>#<?= (int)$book['id'] ?></td>

                            <td>
                                <div class="book-title">
                                    <?= htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <small class="text-muted">
                                    By <?= htmlspecialchars($book['author'], ENT_QUOTES, 'UTF-8') ?>
                                </small>
                            </td>

                            <td>
                                <?= htmlspecialchars($book['category_name'] ?? 'Uncategorized', ENT_QUOTES, 'UTF-8') ?>
                            </td>

                            <td class="fw-bold">
                                ₹<?= number_format((float)$book['price'], 2) ?>
                            </td>

                            <td>
                                <span class="stock-badge <?= $stockClass ?>">
                                    <?= $stock ?> · <?= $stockLabel ?>
                                </span>
                            </td>

                            <td>
                                <span class="status-badge <?= $statusClass ?>">
                                    <?= htmlspecialchars(ucfirst($status), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>

                            <td>
                                <div class="d-flex gap-2">

                                    <a
                                        href="books.php?edit=<?= (int)$book['id'] ?>"
                                        class="btn btn-sm btn-edit"
                                    >
                                        Edit
                                    </a>

                                    <form
                                        method="POST"
                                        action="books.php"
                                        onsubmit="return confirm('Are you sure you want to delete this book?');"
                                    >
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="book_id" value="<?= (int)$book['id'] ?>">

                                        <button type="submit" class="btn btn-sm btn-delete">
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
                            No books found. Add your first book using the form above.
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

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
