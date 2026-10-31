
<?php
session_start();

$pageTitle = 'My Orders — BookNest';

require_once __DIR__ . '/config/database.php';

// Only logged-in customers can view their orders.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ((int) ($_SESSION['role_id'] ?? 0) !== 2) {
    header('Location: dashboard.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

// Fetch this customer's orders.
$stmt = $conn->prepare(
    "SELECT id, total_amount, status, phone,
            delivery_address, city, created_at
     FROM orders
     WHERE user_id = ?
     ORDER BY created_at DESC"
);

$stmt->bind_param('i', $userId);
$stmt->execute();

$result = $stmt->get_result();

$orders = [];

while ($order = $result->fetch_assoc()) {
    $orders[] = $order;
}

$stmt->close();

// Fetch books belonging to each order.
$itemStmt = $conn->prepare(
    "SELECT oi.quantity, oi.unit_price,
            b.title, b.author
     FROM order_items oi
     INNER JOIN books b ON oi.book_id = b.id
     WHERE oi.order_id = ?
"
);

foreach ($orders as &$order) {
    $orderId = (int) $order['id'];

    $itemStmt->bind_param('i', $orderId);
    $itemStmt->execute();

    $itemResult = $itemStmt->get_result();

    $order['items'] = [];

    while ($item = $itemResult->fetch_assoc()) {
        $order['items'][] = $item;
    }
}

unset($order);
$itemStmt->close();

require_once __DIR__ . '/includes/header.php';
?>

<section class="cart-section">
    <div class="container">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <span class="eyebrow">YOUR READING SPACE</span>
                <h1 class="section-title mb-1">My Orders</h1>
                <p class="text-muted mb-0">
                    View your BookNest purchase history.
                </p>
            </div>

            <a href="books.php" class="btn btn-primary">
                Browse Books
            </a>
        </div>

        <?php if (empty($orders)): ?>

            <div class="card p-4 text-center">
                <h2>No orders yet</h2>
                <p>You haven't purchased any books yet.</p>

                <a href="books.php" class="btn btn-primary">
                    Explore Books
                </a>
            </div>

        <?php else: ?>

            <div class="row g-4">

                <?php foreach ($orders as $order): ?>

                    <div class="col-12">
                        <div class="card p-4">

                            <div class="d-flex flex-wrap justify-content-between gap-3 mb-3">

                                <div>
                                    <h2 class="h5 mb-1">
                                        Order #<?= (int) $order['id'] ?>
                                    </h2>

                                    <small class="text-muted">
                                        <?= htmlspecialchars(
                                            date('d M Y, h:i A', strtotime($order['created_at'])),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </small>
                                </div>

                                <div>
                                    <span class="badge bg-primary">
                                        <?= htmlspecialchars(
                                            ucfirst($order['status']),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>
                                </div>

                            </div>

                            <hr>

                            <h3 class="h6 mb-3">Books Ordered</h3>

                            <?php foreach ($order['items'] as $item): ?>

                                <div class="d-flex justify-content-between gap-3 mb-3">

                                    <div>
                                        <strong>
                                            <?= htmlspecialchars(
                                                $item['title'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </strong>

                                        <br>

                                        <small class="text-muted">
                                            By <?= htmlspecialchars(
                                                $item['author'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </small>

                                        <br>

                                        <small class="text-muted">
                                            Quantity: <?= (int) $item['quantity'] ?>
                                        </small>
                                    </div>

                                    <div class="text-end">
                                        ₹<?= number_format(
                                            (float) $item['unit_price'] * (int) $item['quantity'],
                                            2
                                        ) ?>
                                    </div>

                                </div>

                            <?php endforeach; ?>

                            <hr>

                            <div class="row g-3">

                                <div class="col-md-7">
                                    <h3 class="h6">Delivery Details</h3>

                                    <p class="mb-1">
                                        <?= htmlspecialchars(
                                            $order['delivery_address'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </p>

                                    <p class="mb-1">
                                        <?= htmlspecialchars(
                                            $order['city'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </p>

                                    <p class="mb-0">
                                        Phone:
                                        <?= htmlspecialchars(
                                            $order['phone'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </p>
                                </div>

                                <div class="col-md-5 text-md-end">
                                    <span class="text-muted">Order Total</span>

                                    <h3 class="mt-1">
                                        ₹<?= number_format(
                                            (float) $order['total_amount'],
                                            2
                                        ) ?>
                                    </h3>
                                </div>

                            </div>

                        </div>
                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
