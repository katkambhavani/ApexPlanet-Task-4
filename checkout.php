
<?php
session_start();

$pageTitle = 'Checkout — BookNest';

require_once __DIR__ . '/config/database.php';

// Only logged-in customers can checkout.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ((int) ($_SESSION['role_id'] ?? 0) !== 2) {
    header('Location: dashboard.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

$errors = [];
$successMessage = '';

$phone = '';
$deliveryAddress = '';
$city = '';

// Read cart and book details from the database.
$cart = $_SESSION['cart'] ?? [];
$cartBooks = [];
$total = 0;

if (!empty($cart)) {
    $stmt = $conn->prepare(
        "SELECT id, title, price, stock
         FROM books
         WHERE id = ? AND status = 'active'"
    );

    foreach ($cart as $bookId => $quantity) {
        $bookId = (int) $bookId;
        $quantity = (int) $quantity;

        if ($bookId < 1 || $quantity < 1) {
            continue;
        }

        $stmt->bind_param('i', $bookId);
        $stmt->execute();

        $book = $stmt->get_result()->fetch_assoc();

        if ($book) {
            $book['quantity'] = $quantity;
            $book['subtotal'] = (float) $book['price'] * $quantity;

            $cartBooks[] = $book;
            $total += $book['subtotal'];
        } else {
            unset($_SESSION['cart'][$bookId]);
        }
    }

    $stmt->close();
}

// Process checkout form.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone'] ?? '');
    $deliveryAddress = trim($_POST['delivery_address'] ?? '');
    $city = trim($_POST['city'] ?? '');

    if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
        $errors[] = 'Please enter a valid phone number.';
    }

    if ($deliveryAddress === '' || strlen($deliveryAddress) > 255) {
        $errors[] = 'Please enter a valid delivery address.';
    }

    if ($city === '' || strlen($city) > 100) {
        $errors[] = 'Please enter a valid city.';
    }

    if (empty($cartBooks)) {
        $errors[] = 'Your cart is empty. Please add books before checkout.';
    }

    if (empty($errors)) {
        try {
            $conn->begin_transaction();

            // Recheck stock and current prices inside the transaction.
            $verifiedBooks = [];
            $verifiedTotal = 0;

            $stockStmt = $conn->prepare(
                "SELECT id, title, price, stock
                 FROM books
                 WHERE id = ? AND status = 'active'
                 FOR UPDATE"
            );

            foreach ($cartBooks as $item) {
                $bookId = (int) $item['id'];
                $quantity = (int) $item['quantity'];

                $stockStmt->bind_param('i', $bookId);
                $stockStmt->execute();

                $book = $stockStmt->get_result()->fetch_assoc();

                if (!$book) {
                    throw new Exception('A book in your cart is no longer available.');
                }

                if ((int) $book['stock'] < $quantity) {
                    throw new Exception(
                        'Only ' . (int) $book['stock'] .
                        ' copies of "' . $book['title'] . '" are available.'
                    );
                }

                $book['quantity'] = $quantity;
                $book['subtotal'] = (float) $book['price'] * $quantity;

                $verifiedBooks[] = $book;
                $verifiedTotal += $book['subtotal'];
            }

            $stockStmt->close();

            // Save the order.
            $orderStmt = $conn->prepare(
                "INSERT INTO orders
                 (user_id, total_amount, status, phone, delivery_address, city)
                 VALUES (?, ?, 'pending', ?, ?, ?)"
            );

            $orderStmt->bind_param(
                'idsss',
                $userId,
                $verifiedTotal,
                $phone,
                $deliveryAddress,
                $city
            );

            $orderStmt->execute();
            $orderId = $conn->insert_id;
            $orderStmt->close();

            // Save each book and reduce its stock.
            $itemStmt = $conn->prepare(
                "INSERT INTO order_items
                 (order_id, book_id, quantity, unit_price)
                 VALUES (?, ?, ?, ?)"
            );

            $updateStockStmt = $conn->prepare(
                "UPDATE books
                 SET stock = stock - ?
                 WHERE id = ? AND stock >= ?"
            );

            foreach ($verifiedBooks as $book) {
                $bookId = (int) $book['id'];
                $quantity = (int) $book['quantity'];
                $unitPrice = (float) $book['price'];

                $itemStmt->bind_param(
                    'iiid',
                    $orderId,
                    $bookId,
                    $quantity,
                    $unitPrice
                );

                $itemStmt->execute();

                $updateStockStmt->bind_param(
                    'iii',
                    $quantity,
                    $bookId,
                    $quantity
                );

                $updateStockStmt->execute();

                if ($updateStockStmt->affected_rows !== 1) {
                    throw new Exception('Stock could not be updated. Please try again.');
                }
            }

            $itemStmt->close();
            $updateStockStmt->close();

            $conn->commit();

            // Clear the cart only after the order is saved successfully.
            unset($_SESSION['cart']);

            $_SESSION['checkout_success'] =
                'Your order #' . $orderId . ' has been placed successfully!';

            header('Location: dashboard.php');
            exit;

        } catch (Throwable $e) {
            $conn->rollback();

            $errors[] = $e instanceof Exception
                ? $e->getMessage()
                : 'Something went wrong while placing your order. Please try again.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="cart-section">
    <div class="container">

        <h1 class="section-title mb-4">Checkout</h1>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li>
                            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (empty($cartBooks)): ?>

            <div class="card p-4 text-center">
                <h2>Your cart is empty</h2>
                <p>Add some books before proceeding to checkout.</p>
                <a href="books.php" class="btn btn-primary">
                    Browse Books
                </a>
            </div>

        <?php else: ?>

            <div class="row g-4">

                <div class="col-lg-7">
                    <div class="card p-4">

                        <h2 class="h4 mb-4">Delivery Details</h2>

                        <form method="POST" action="checkout.php">

                            <div class="mb-3">
                                <label for="phone" class="form-label">
                                    Phone Number
                                </label>

                                <input
                                    type="tel"
                                    id="phone"
                                    name="phone"
                                    class="form-control"
                                    value="<?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?>"
                                    maxlength="20"
                                    required
                                >
                            </div>

                            <div class="mb-3">
                                <label for="delivery_address" class="form-label">
                                    Delivery Address
                                </label>

                                <textarea
                                    id="delivery_address"
                                    name="delivery_address"
                                    class="form-control"
                                    rows="3"
                                    maxlength="255"
                                    required
                                ><?= htmlspecialchars($deliveryAddress, ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>

                            <div class="mb-4">
                                <label for="city" class="form-label">
                                    City
                                </label>

                                <input
                                    type="text"
                                    id="city"
                                    name="city"
                                    class="form-control"
                                    value="<?= htmlspecialchars($city, ENT_QUOTES, 'UTF-8') ?>"
                                    maxlength="100"
                                    required
                                >
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                Place Order
                            </button>

                        </form>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card p-4">

                        <h2 class="h4 mb-4">Order Summary</h2>

                        <?php foreach ($cartBooks as $book): ?>
                            <div class="d-flex justify-content-between gap-3 mb-3">
                                <div>
                                    <strong>
                                        <?= htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8') ?>
                                    </strong>
                                    <br>
                                    <small>
                                        Quantity: <?= (int) $book['quantity'] ?>
                                    </small>
                                </div>

                                <span>
                                    ₹<?= number_format($book['subtotal'], 2) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>

                        <hr>

                        <div class="d-flex justify-content-between">
                            <strong>Total Amount</strong>
                            <strong>
                                ₹<?= number_format($total, 2) ?>
                            </strong>
                        </div>

                    </div>
                </div>

            </div>

        <?php endif; ?>

    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
