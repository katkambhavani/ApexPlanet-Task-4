
<?php
session_start();

$pageTitle = 'Browse Books — BookNest';

require_once __DIR__ . '/config/database.php';

// ==========================================
// ADD BOOK DIRECTLY TO CART
// ==========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {

    $bookId = filter_input(INPUT_POST, 'book_id', FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

    if (!$bookId || $bookId < 1 || !$quantity || $quantity < 1) {
        header('Location: books.php?error=' . urlencode('Please select a valid book and quantity.'));
        exit;
    }

    // Check book availability.
    $cartStmt = $conn->prepare(
        "SELECT stock FROM books
         WHERE id = ? AND status = 'active'
         LIMIT 1"
    );

    $cartStmt->bind_param('i', $bookId);
    $cartStmt->execute();

    $availableBook = $cartStmt->get_result()->fetch_assoc();
    $cartStmt->close();

    if (!$availableBook) {
        header('Location: books.php?error=' . urlencode('Book not found.'));
        exit;
    }

    if ($quantity > (int)$availableBook['stock']) {
        header('Location: books.php?error=' . urlencode('Requested quantity is not available.'));
        exit;
    }

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $currentQuantity = $_SESSION['cart'][$bookId] ?? 0;

    if ($currentQuantity + $quantity > (int)$availableBook['stock']) {
        header('Location: books.php?error=' . urlencode('You cannot add more than the available stock.'));
        exit;
    }

    $_SESSION['cart'][$bookId] = $currentQuantity + $quantity;

    header('Location: cart.php?added=1');
    exit;
}

// ==========================================
// SEARCH AND CATEGORY FILTERS
// ==========================================

$search = trim($_GET['search'] ?? '');

$categoryId = filter_input(
    INPUT_GET,
    'category',
    FILTER_VALIDATE_INT
);

if (!$categoryId || $categoryId < 1) {
    $categoryId = 0;
}

// Load categories.
$categoryResult = $conn->query(
    "SELECT id, name FROM categories ORDER BY name"
);

$categories = $categoryResult->fetch_all(MYSQLI_ASSOC);

// ==========================================
// PAGINATION
// ==========================================

$perPage = 8;

$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);

if (!$page || $page < 1) {
    $page = 1;
}

// ==========================================
// BUILD BOOK QUERY
// ==========================================

$where = ["b.status = 'active'"];
$params = [];
$types = '';

if ($search !== '') {

    $where[] = '(b.title LIKE ? OR b.author LIKE ?)';

    $term = '%' . $search . '%';

    $params[] = $term;
    $params[] = $term;

    $types .= 'ss';
}

if ($categoryId > 0) {

    $where[] = 'b.category_id = ?';

    $params[] = $categoryId;

    $types .= 'i';
}

$whereSql = implode(' AND ', $where);

// Count matching books.
$countSql = "
    SELECT COUNT(*) AS total
    FROM books b
    WHERE $whereSql
";

$countStmt = $conn->prepare($countSql);

if ($types !== '') {
    $countStmt->bind_param($types, ...$params);
}

$countStmt->execute();

$totalBooks = (int)$countStmt
    ->get_result()
    ->fetch_assoc()['total'];

$countStmt->close();

$totalPages = max(
    1,
    (int)ceil($totalBooks / $perPage)
);

$page = min($page, $totalPages);

$offset = ($page - 1) * $perPage;

// Fetch books including image paths.
$sql = "
    SELECT
        b.id,
        b.title,
        b.author,
        b.description,
        b.price,
        b.stock,
        b.image_path,
        c.name AS category_name
    FROM books b
    LEFT JOIN categories c ON c.id = b.category_id
    WHERE $whereSql
    ORDER BY b.created_at DESC
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);

$queryParams = $params;
$queryTypes = $types . 'ii';

$queryParams[] = $perPage;
$queryParams[] = $offset;

$stmt->bind_param($queryTypes, ...$queryParams);

$stmt->execute();

$books = $stmt
    ->get_result()
    ->fetch_all(MYSQLI_ASSOC);

$stmt->close();

require_once __DIR__ . '/includes/header.php';
?>

<style>

/* ==========================================
   BOOK COVER AREA
========================================== */

.book-cover-area {
    height: 250px;
    background: #f4eee5;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 18px;
    overflow: hidden;
}

.book-cover-design {
    width: 155px;
    height: 215px;
    position: relative;
    overflow: hidden;
    padding: 18px 12px;

    display: flex;
    flex-direction: column;
    justify-content: space-between;
    align-items: center;

    text-align: center;
    color: white;

    border-left: 6px solid rgba(0,0,0,.2);

    box-shadow: 4px 6px 12px rgba(0,0,0,.28);

    transition: transform .3s ease;
}

.book-cover-design:hover {
    transform: scale(1.04);
}

.book-cover-design::before {
    content: "";
    position: absolute;

    width: 150px;
    height: 150px;

    border: 1px solid rgba(255,255,255,.25);
    border-radius: 50%;

    top: 35px;
    left: 5px;
}

.book-cover-design::after {
    content: "";
    position: absolute;

    width: 110px;
    height: 110px;

    border: 1px solid rgba(255,255,255,.2);
    border-radius: 50%;

    top: 55px;
    left: 25px;
}

.book-cover-design strong,
.book-cover-design small,
.book-cover-design span {
    position: relative;
    z-index: 1;
}

.book-cover-design strong {
    font-family: Georgia, serif;
    font-size: 21px;
    line-height: 1.2;
}

.book-cover-design small {
    font-size: 10px;
    letter-spacing: 2px;
}

.book-cover-design span {
    font-size: 30px;
}

/* ==========================================
   INDIVIDUAL BOOK COVER THEMES
========================================== */

/* The Silent Library */
.cover-1 {
    background: linear-gradient(
        145deg,
        #071c2c,
        #24516a,
        #0b1828
    );
}

/* Beyond the Stars */
.cover-2 {
    background: linear-gradient(
        145deg,
        #17144b,
        #593b91,
        #ed8a91
    );
}

/* Learning PHP */
.cover-3 {
    background: linear-gradient(
        145deg,
        #061d35,
        #087b9e,
        #031326
    );
}

/* Database Basics */
.cover-4 {
    background: linear-gradient(
        145deg,
        #101d32,
        #31516c,
        #071526
    );
}

/* Small Steps, Big Changes */
.cover-5 {
    background: linear-gradient(
        145deg,
        #f3dfbd,
        #e9c28e,
        #b7c99c
    );

    color: #463c2c;
}

/* Focus Every Day */
.cover-6 {
    background: linear-gradient(
        145deg,
        #f6d6a0,
        #d88952,
        #344f67
    );
}

/* Computer Science Essentials */
.cover-8 {
    background: linear-gradient(
        145deg,
        #07152b,
        #075b91,
        #06182e
    );
}

/* Web Development Fundamentals */
.cover-9 {
    background: linear-gradient(
        145deg,
        #111827,
        #34495e,
        #101827
    );
}

/* Actual uploaded images */
.book-cover-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.book-card .book-cart-form {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 16px;
    width: 100%;
}

.book-card .quantity-label {
    font-size: 13px;
    font-weight: 600;
    color: #172554;
}

.book-card .quantity-input {
    width: 100%;
    height: 42px;
    text-align: center;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    color: #172554;
}

.book-card .add-cart-button {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    min-height: 45px;
    padding: 10px 12px;
    font-size: 14px;
    font-weight: 600;
    white-space: normal;
    border-radius: 8px;
    background: #172554;
    border-color: #172554;
}

.book-card .add-cart-button:hover {
    background: #243e70;
    border-color: #243e70;
}
</style>

<!-- ==========================================
     BOOK CATALOG
========================================== -->

<section class="catalog-section">

    <div class="container">

        <!-- Heading -->

        <div class="catalog-heading">

            <span class="eyebrow">
                THE BOOKNEST COLLECTION
            </span>

            <h1 class="section-title mt-2">
                Find your next favorite book
            </h1>

            <p>
                Explore stories, learn new skills,
                and discover something wonderful.
            </p>

        </div>

        <!-- SEARCH AND FILTER -->

        <form
            method="GET"
            action="books.php"
            class="catalog-filters card"
        >

            <div class="filter-search">

                <label for="search" class="form-label">
                    Search books
                </label>

                <input
                    type="search"
                    class="form-control"
                    id="search"
                    name="search"
                    placeholder="Search by title or author..."
                    value="<?= htmlspecialchars(
                        $search,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

            </div>

            <div class="filter-category">

                <label for="category" class="form-label">
                    Category
                </label>

                <select
                    class="form-select"
                    id="category"
                    name="category"
                >

                    <option value="0">
                        All categories
                    </option>

                    <?php foreach ($categories as $category): ?>

                        <option
                            value="<?= (int)$category['id'] ?>"
                            <?= $categoryId === (int)$category['id']
                                ? 'selected'
                                : '' ?>
                        >

                            <?= htmlspecialchars(
                                $category['name'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="filter-actions">

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Search
                </button>

                <a
                    href="books.php"
                    class="btn btn-outline-secondary"
                >
                    Reset
                </a>

            </div>

        </form>

        <!-- ERROR MESSAGE -->

        <?php if (isset($_GET['error'])): ?>

            <div class="alert alert-danger mt-3">

                <?= htmlspecialchars(
                    $_GET['error'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </div>

        <?php endif; ?>

        <!-- BOOK COUNT -->

        <div class="catalog-results">

            <p>
                <?= $totalBooks ?>

                <?= $totalBooks === 1
                    ? 'book'
                    : 'books' ?>

                found
            </p>

        </div>

        <!-- BOOK LIST -->

        <?php if (empty($books)): ?>

            <div class="empty-books card">

                <div class="empty-books-icon">
                    📚
                </div>

                <h2>No books found</h2>

                <p>
                    Try another title, author, or category.
                </p>

            </div>

        <?php else: ?>

            <div class="row g-4">

                <?php foreach ($books as $book): ?>

                    <div class="col-sm-6 col-lg-3">

                        <article class="book-card card h-100">

                            <!-- BOOK COVER -->

                            <div class="book-cover-area">

                                <?php if (!empty($book['image_path'])): ?>

                                    <img
                                        src="<?= htmlspecialchars(
                                            $book['image_path'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        alt="<?= htmlspecialchars(
                                            $book['title'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        class="book-cover-image"
                                        loading="lazy"
                                    >

                                <?php else: ?>

                                    <div class="book-cover-design cover-<?= (int)$book['id'] ?>">

                                        <small>
                                            BOOKNEST EDITION
                                        </small>

                                        <span>

                                            <?php

                                            $icons = [
                                                1 => '✦',
                                                2 => '✧',
                                                3 => '</>',
                                                4 => '▤',
                                                5 => '❀',
                                                6 => '☀',
                                                8 => '⌘',
                                                9 => '⌨'
                                            ];

                                            echo $icons[(int)$book['id']] ?? '📖';

                                            ?>

                                        </span>

                                        <strong>

                                            <?= htmlspecialchars(
                                                $book['title'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        </strong>

                                        <small>

                                            <?= htmlspecialchars(
                                                $book['author'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        </small>

                                    </div>

                                <?php endif; ?>

                            </div>

                            <!-- BOOK INFORMATION -->

                            <div class="book-card-body">

                                <span class="book-category">

                                    <?= htmlspecialchars(
                                        $book['category_name'] ?? 'General',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </span>

                                <h2>

                                    <a href="book-details.php?id=<?= (int)$book['id'] ?>">

                                        <?= htmlspecialchars(
                                            $book['title'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </a>

                                </h2>

                                <p class="book-author">

                                    by

                                    <?= htmlspecialchars(
                                        $book['author'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </p>

                                <p class="book-description">

                                    <?= htmlspecialchars(
                                        $book['description'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </p>

                                <!-- PRICE AND STOCK -->

                                <div class="book-card-bottom">

                                    <strong>

                                        ₹<?= number_format(
                                            (float)$book['price'],
                                            2
                                        ) ?>

                                    </strong>

                                    <?php if ((int)$book['stock'] > 0): ?>

                                        <span class="book-status">
                                            In stock
                                        </span>

                                    <?php else: ?>

                                        <span class="book-out-of-stock">
                                            Out of stock
                                        </span>

                                    <?php endif; ?>

                                </div>

                                <!-- DIRECT ADD TO CART -->

                                <?php if ((int)$book['stock'] > 0): ?>

<form method="POST" action="books.php" class="book-cart-form">

    <input
        type="hidden"
        name="book_id"
        value="<?= (int)$book['id'] ?>"
    >

    <label class="quantity-label">
        Quantity
    </label>

    <input
        type="number"
        name="quantity"
        class="form-control quantity-input"
        value="1"
        min="1"
        max="<?= (int)$book['stock'] ?>"
        required
    >

    <button
        type="submit"
        name="add_to_cart"
        value="1"
        class="btn btn-primary add-cart-button"
    >
        <span>🛒</span> Add to Cart
    </button>

</form>

                                    <?php else: ?>

                                    <button
                                        class="btn btn-secondary w-100 mt-3"
                                        disabled
                                    >
                                        Out of Stock
                                    </button>

                                <?php endif; ?>

                            </div>

                        </article>

                    </div>

                <?php endforeach; ?>

            </div>

            <!-- PAGINATION -->

            <?php if ($totalPages > 1): ?>

                <nav
                    class="catalog-pagination"
                    aria-label="Book pages"
                >

                    <?php

                    $query = [
                        'search' => $search,
                        'category' => $categoryId
                    ];

                    ?>

                    <?php if ($page > 1): ?>

                        <a
                            class="page-link-custom"
                            href="?<?= http_build_query(
                                $query + ['page' => $page - 1]
                            ) ?>"
                        >

                            ← Previous

                        </a>

                    <?php endif; ?>

                    <span>
                        Page <?= $page ?> of <?= $totalPages ?>
                    </span>

                    <?php if ($page < $totalPages): ?>

                        <a
                            class="page-link-custom"
                            href="?<?= http_build_query(
                                $query + ['page' => $page + 1]
                            ) ?>"
                        >

                            Next →

                        </a>

                    <?php endif; ?>

                </nav>

            <?php endif; ?>

        <?php endif; ?>

    </div>

</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
