
<?php
session_start();

$pageTitle = 'Shopping Cart — BookNest';

require_once __DIR__ . '/config/database.php';

// Process cart actions.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $bookId = filter_input(INPUT_POST, 'book_id', FILTER_VALIDATE_INT);

    if ($bookId && isset($_SESSION['cart'][$bookId])) {

        if ($action === 'remove') {
            unset($_SESSION['cart'][$bookId]);
            $_SESSION['cart_message'] = 'Book removed from your cart.';
            $_SESSION['cart_message_type'] = 'success';

        } elseif ($action === 'update') {
            $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

            if (!$quantity || $quantity < 1) {
                $_SESSION['cart_message'] = 'Please enter a valid quantity.';
                $_SESSION['cart_message_type'] = 'danger';

            } else {
                $stmt = $conn->prepare(
                    "SELECT stock FROM books
                     WHERE id = ? AND status = 'active'"
                );

                $stmt->bind_param('i', $bookId);
                $stmt->execute();
                $book = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$book) {
                    unset($_SESSION['cart'][$bookId]);
                    $_SESSION['cart_message'] = 'This book is no longer available.';
                    $_SESSION['cart_message_type'] = 'danger';

                } elseif ($quantity > (int) $book['stock']) {
                    $_SESSION['cart_message'] =
                        'Only ' . (int) $book['stock'] . ' copies are available.';
                    $_SESSION['cart_message_type'] = 'danger';

                } else {
                    $_SESSION['cart'][$bookId] = $quantity;
                    $_SESSION['cart_message'] = 'Cart quantity updated successfully.';
                    $_SESSION['cart_message_type'] = 'success';
                }
            }
        }
    }

    header('Location: cart.php');
    exit;
}

// Read cart items.
$cart = $_SESSION['cart'] ?? [];
$cartBooks = [];
$total = 0;

if (!empty($cart)) {
    $stmt = $conn->prepare(
        "SELECT id, title, author, price, stock
         FROM books
         WHERE id = ? AND status = 'active'"
    );

    foreach ($cart as $bookId => $quantity) {
        $bookId = (int) $bookId;

        $stmt->bind_param('i', $bookId);
        $stmt->execute();

        $book = $stmt->get_result()->fetch_assoc();

        if ($book) {
            $book['quantity'] = (int) $quantity;
            $book['subtotal'] =
                (float) $book['price'] * $book['quantity'];

            $cartBooks[] = $book;
            $total += $book['subtotal'];
        } else {
            unset($_SESSION['cart'][$bookId]);
        }
    }

    $stmt->close();
}

// Read and clear one-time message.
$cartMessage = $_SESSION['cart_message'] ?? '';
$cartMessageType = $_SESSION['cart_message_type'] ?? 'success';

unset($_SESSION['cart_message'], $_SESSION['cart_message_type']);

require_once __DIR__ . '/includes/header.php';
?>

<section class="cart-section">
    <div class="container">

        <h1 class="section-title mb-4">Your Shopping Cart</h1>

        <?php if ($cartMessage !== ''): ?>
            <div class="alert alert-<?= htmlspecialchars($cartMessageType) ?>">
                <?= htmlspecialchars($cartMessage, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if (empty($cartBooks)): ?>

            <div class="card p-4 text-center">
                <h2>Your cart is empty</h2>
                <p>Discover books and add your favorites here.</p>
                <a href="books.php" class="btn btn-primary">
                    Browse Books
                </a>
            </div>

        <?php else: ?>

            <div class="card p-4">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Book</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($cartBooks as $book): ?>
                                <tr>
                                    <td>
                                        <strong>
                                            <?= htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8') ?>
                                        </strong>
                                        <br>
                                        <small>
                                            <?= htmlspecialchars($book['author'], ENT_QUOTES, 'UTF-8') ?>
                                        </small>
                                    </td>

                                    <td>
                                        ₹<?= number_format((float) $book['price'], 2) ?>
                                    </td>

                                    <td>
                                        <form method="POST" action="cart.php"
                                              class="d-flex gap-2 align-items-center">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="book_id"
                                                   value="<?= (int) $book['id'] ?>">

                                            <input
                                                type="number"
                                                name="quantity"
                                                class="form-control"
                                                value="<?= (int) $book['quantity'] ?>"
                                                min="1"
                                                max="<?= (int) $book['stock'] ?>"
                                                style="width: 85px;"
                                                required
                                            >

                                            <button type="submit"
                                                    class="btn btn-outline-primary btn-sm">
                                                Update
                                            </button>
                                        </form>
                                    </td>

                                    <td>
                                        ₹<?= number_format($book['subtotal'], 2) ?>
                                    </td>

                                    <td>
                                        <form method="POST" action="cart.php"
                                              onsubmit="return confirm('Remove this book from your cart?');">
                                            <input type="hidden" name="action" value="remove">
                                            <input type="hidden" name="book_id"
                                                   value="<?= (int) $book['id'] ?>">

                                            <button type="submit"
                                                    class="btn btn-outline-danger btn-sm">
                                                Remove
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="text-end mt-3">
                    <h3>Total: ₹<?= number_format($total, 2) ?></h3>

                  <div class="d-flex flex-wrap justify-content-end gap-2">
    <a href="books.php" class="btn btn-outline-secondary">
        Continue Shopping
    </a>

    <a href="checkout.php" class="btn btn-primary">
        Proceed to Checkout
    </a>
</div>
                </div>
            </div>

        <?php endif; ?>

    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
