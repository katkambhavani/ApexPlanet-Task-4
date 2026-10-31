
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'My Dashboard — BookNest';
require_once __DIR__ . '/config/database.php';

$name = $_SESSION['name'] ?? 'Reader';
$userId = (int) $_SESSION['user_id'];

// Get checkout success message once
$checkoutSuccess = $_SESSION['checkout_success'] ?? '';
$_SESSION['checkout_success'] = 'TEST: Your order was placed successfully!';
unset($_SESSION['checkout_success']);

// Get total orders
$stmt = $conn->prepare(
    'SELECT COUNT(*) AS total_orders
     FROM orders
     WHERE user_id = ?'
);
$stmt->bind_param('i', $userId);
$stmt->execute();
$orderResult = $stmt->get_result()->fetch_assoc();
$totalOrders = (int) ($orderResult['total_orders'] ?? 0);
$stmt->close();

require_once __DIR__ . '/includes/header.php';
?>

<section class="hero-section">
    <div class="container">
        <span class="eyebrow">YOUR READING SPACE</span>
        <h1>Welcome, <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>!</h1>
        <p>Explore books, discover new ideas, and keep track of your orders.</p>
        <a href="books.php" class="btn btn-primary">Browse Books</a>
    </div>
</section>

<section class="container pb-5">

    <?php if ($checkoutSuccess): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Order placed successfully!</strong>
            <?= htmlspecialchars($checkoutSuccess, ENT_QUOTES, 'UTF-8') ?>
            <a href="my-orders.php" class="alert-link">View your orders</a>.
            <button type="button" class="btn-close" data-bs-dismiss="alert"
                    aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <h2 class="section-title mb-4">Your Dashboard</h2>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card p-4 h-100">
                <h3 class="h5">My Orders</h3>
                <p class="display-6 fw-bold">
                    <?= $totalOrders ?>
                </p>
                <a href="my-orders.php">View order history</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card p-4 h-100">
                <h3 class="h5">Browse Books</h3>
                <p>Find your next favorite story.</p>
                <a href="books.php">Explore the collection</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card p-4 h-100">
                <h3 class="h5">Shopping Cart</h3>
                <p>Continue exploring books to purchase.</p>
                <a href="cart.php">View your cart</a>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
