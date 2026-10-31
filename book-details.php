
<?php
session_start();

$pageTitle = 'Book Details — BookNest';

require_once __DIR__ . '/config/database.php';

// Get the selected book ID from the URL.
$bookId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$bookId || $bookId < 1) {
    http_response_code(404);
    exit('Book not found. Please select a valid book.');
}

// Fetch the selected active book and its category.
$stmt = $conn->prepare(
    "SELECT b.id, b.title, b.author, b.description,
            b.price, b.stock, b.image_path,
            c.name AS category_name
     FROM books b
     LEFT JOIN categories c ON c.id = b.category_id
     WHERE b.id = ? AND b.status = 'active'
     LIMIT 1"
);

$stmt->bind_param('i', $bookId);
$stmt->execute();

$book = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$book) {
    http_response_code(404);
    exit('This book could not be found or is no longer available.');
}

// Add book to cart.
$cartMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {

    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

    if (!$quantity || $quantity < 1) {
        $cartMessage = 'Please enter a valid quantity.';
    } elseif ($quantity > (int) $book['stock']) {
        $cartMessage = 'Requested quantity is not available.';
    } else {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        $currentQuantity = $_SESSION['cart'][$bookId] ?? 0;

        if ($currentQuantity + $quantity > (int) $book['stock']) {
            $cartMessage = 'You cannot add more than the available stock.';
        } else {
            $_SESSION['cart'][$bookId] = $currentQuantity + $quantity;

            header('Location: cart.php?added=1');
            exit;
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="book-details-section">
    <div class="container">
        <a href="books.php" class="back-link">← Back to Browse Books</a>

        <div class="book-details-card card">
            <div class="details-cover">
                <span>📖</span>
                <small>BOOKNEST COLLECTION</small>
            </div>

            <div class="details-content">
                <span class="book-category">
                    <?= htmlspecialchars($book['category_name'] ?? 'General', ENT_QUOTES, 'UTF-8') ?>
                </span>

                <h1><?= htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8') ?></h1>

                <p class="details-author">
                    Written by <?= htmlspecialchars($book['author'], ENT_QUOTES, 'UTF-8') ?>
                </p>

                <div class="details-divider"></div>

                <h2>About this book</h2>

                <p class="details-description">
                    <?= nl2br(htmlspecialchars($book['description'] ?? 'No description available.', ENT_QUOTES, 'UTF-8')) ?>
                </p>

                <div class="details-price">
                    ₹<?= number_format((float) $book['price'], 2) ?>
                </div>

                <?php if ((int) $book['stock'] > 0): ?>

                    <p class="details-stock in-stock">
                        <?= (int) $book['stock'] ?> copies available
                    </p>

                    <?php if ($cartMessage !== ''): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($cartMessage, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <label for="quantity" class="form-label">
                            Quantity
                        </label>

                        <input
                            type="number"
                            name="quantity"
                            id="quantity"
                            class="form-control mb-3"
                            value="1"
                            min="1"
                            max="<?= (int) $book['stock'] ?>"
                            required
                        >

                        <button type="submit" name="add_to_cart"
                                class="btn btn-primary btn-lg">
                            Add to Cart
                        </button>
                    </form>

                <?php else: ?>

                    <p class="details-stock out-of-stock">
                        Currently out of stock
                    </p>

                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
