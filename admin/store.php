
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only administrators can access this page
if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role_id']) ||
    (int) $_SESSION['role_id'] !== 1
) {
    header('Location: ../login.php');
    exit;
}

$pageTitle = 'BookNest.Admin — View Store';

require_once __DIR__ . '/../config/database.php';

// Fetch ALL active books for the admin store preview
$stmt = $conn->prepare(
    "SELECT id, title, author, description, price, image_path
     FROM books
     WHERE status = 'active'
     ORDER BY created_at DESC"
);

$stmt->execute();
$allBooks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$bookIcons = [
    1 => '✦',
    2 => '✧',
    3 => '</>',
    4 => '▤',
    5 => '❀',
    6 => '☀',
    8 => '⌘',
    9 => '⌨'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
          rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/theme.css">
    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        body {
            background: #FFF9F0;
            font-family: 'Inter', sans-serif;
            color: #243047;
        }

        /* ADMIN NAVIGATION */

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

        /* STORE CONTENT */

        .hero-section {
            padding: 28px 0 55px;
        }

        .hero-section h1 {
            color: #172554;
            font-weight: 800;
            line-height: 1.15;
        }

        .benefits-section {
            padding: 25px 0 35px;
        }

        .featured-section {
            padding: 45px 0 60px;
            scroll-margin-top: 25px;
        }

/* ALL BOOKS HEADING - FORCE LEFT ALIGNMENT */

.featured-section .section-heading {
    display: block !important;
    width: 100% !important;
    text-align: left !important;
    margin: 0 0 30px !important;
}

.featured-section .section-heading .eyebrow,
.featured-section .section-heading h2,
.featured-section .section-heading p,
.featured-section .section-heading .book-count {
    display: block;
    text-align: left !important;
    margin-left: 0 !important;
    margin-right: 0 !important;
}

.featured-section .section-heading h2 {
    margin-top: 10px !important;
    margin-bottom: 12px !important;
}

.featured-section .section-heading p {
    max-width: 650px;
    margin-bottom: 15px !important;
    color: #64748b;
    line-height: 1.7;
}

.featured-section .section-heading .book-count {
    display: table;
    margin-top: 5px !important;
}
        /* BOOK CARDS */

        .book-card {
            overflow: hidden;
            border-radius: 15px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .book-card:hover,
        .benefit-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 22px rgba(23,37,84,0.09);
        }

        .browse-all-btn {
            display: inline-block;
            text-decoration: none;
            background: #F59E0B;
            color: #172554;
            padding: 12px 22px;
            border-radius: 9px;
            font-weight: 700;
            transition: background .2s ease;
        }

        .browse-all-btn:hover {
            background: #FBBF24;
            color: #172554;
        }

        .admin-footer {
            padding: 22px 0;
            text-align: center;
            color: #94a3b8;
            font-size: 12px;
        }

        @media(max-width:576px) {
            .hero-section {
                padding: 25px 0 40px;
            }

            .featured-section {
                padding: 35px 0 45px;
            }

            .featured-section .section-heading {
                margin-bottom: 25px;
            }
        }
    </style>
</head>

<body>

<!-- ADMIN NAVBAR -->

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
                        <a class="nav-link" href="orders.php">
                            Manage Orders
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link active"
                           aria-current="page"
                           href="store.php">
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

<main id="top">

    <!-- HERO SECTION -->

    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center g-5">

                <div class="col-lg-7">

                    <span class="hero-label">WELCOME TO BOOKNEST</span>

                    <h1 class="mt-3">
                        Every book opens a
                        <span class="highlight-text">new world.</span>
                    </h1>

                    <p class="mt-4">
                        Discover inspiring stories, explore new ideas,
                        and find the books that belong on your shelf.
                        Your next favorite read starts here.
                    </p>

                    <div class="d-flex flex-wrap gap-3 mt-4">

                        <a href="#all-books" class="btn btn-primary btn-lg">
                            Browse All Books
                        </a>

                        <a href="books.php" class="btn btn-outline-primary btn-lg">
                            Manage Inventory
                        </a>

                    </div>

                    <div class="hero-note mt-4">
                        <span class="note-dot"></span>
                        A little reading can open a big world.
                    </div>

                </div>

                <div class="col-lg-5">
                    <div class="hero-art">

                        <div class="floating-note note-top">
                            <span>📚</span> Stories worth sharing
                        </div>

                        <div class="book-stack">

                            <div class="book-cover-design cover-1">
                                <small>BOOKNEST EDITION</small>
                                <span>✦</span>
                                <strong>THE SILENT LIBRARY</strong>
                                <small>MIRA BENNETT</small>
                            </div>

                            <div class="book-cover-design cover-3 hero-book-middle">
                                <small>BOOKNEST EDITION</small>
                                <span>&lt;/&gt;</span>
                                <strong>LEARNING PHP</strong>
                                <small>DANIEL BROOKS</small>
                            </div>

                            <div class="book-cover-design cover-5">
                                <small>BOOKNEST EDITION</small>
                                <span>❀</span>
                                <strong>SMALL STEPS, BIG CHANGES</strong>
                                <small>OLIVIA JAMES</small>
                            </div>

                        </div>

                        <div class="floating-note note-bottom">
                            ✨ Find your next favorite
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- BENEFITS SECTION -->

    <section class="benefits-section">
        <div class="container">
            <div class="row g-3">

                <div class="col-md-4">
                    <div class="benefit-card">
                        <span class="benefit-icon">📖</span>
                        <div>
                            <h3>Explore</h3>
                            <p>Discover books across different categories.</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="benefit-card">
                        <span class="benefit-icon">🔎</span>
                        <div>
                            <h3>Find your match</h3>
                            <p>Search for books that interest you.</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="benefit-card">
                        <span class="benefit-icon">💛</span>
                        <div>
                            <h3>Enjoy reading</h3>
                            <p>Make space for your next great story.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ALL BOOKS COLLECTION -->

    <section class="featured-section" id="all-books">
        <div class="container">

            <div class="section-heading">

                <span class="eyebrow">EXPLORE OUR COLLECTION</span>

                <h2 class="section-title mt-2">
                    Browse All Books
                </h2>

                <p>
                    Discover every book currently available in the BookNest collection.
                </p>

                <span class="book-count">
                    <?= count($allBooks) ?>
                    <?= count($allBooks) === 1 ? 'Book' : 'Books' ?> Available
                </span>

            </div>

            <div class="row g-4">

                <?php if (empty($allBooks)): ?>

                    <div class="col-12">
                        <div class="empty-books card">
                            <div class="empty-books-icon">📚</div>

                            <h3>No books available yet</h3>

                            <p>
                                Please add active books through Manage Books
                                to display them in this collection.
                            </p>

                            <a href="books.php" class="browse-all-btn mt-3">
                                Manage Inventory
                            </a>
                        </div>
                    </div>

                <?php else: ?>

                    <?php foreach ($allBooks as $book): ?>

                        <div class="col-sm-6 col-lg-3">

                            <article class="book-card card h-100">

                                <div class="book-cover-area">

                                    <?php if (!empty($book['image_path'])): ?>

                                        <img
                                            src="../<?= htmlspecialchars(ltrim($book['image_path'], '/'), ENT_QUOTES, 'UTF-8') ?>"
                                            alt="<?= htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8') ?>"
                                            class="book-cover-image"
                                            loading="lazy"
                                        >

                                    <?php else: ?>

                                        <div class="book-cover-design cover-<?= (int)$book['id'] ?>">

                                            <small>BOOKNEST EDITION</small>

                                            <span>
                                                <?= $bookIcons[(int)$book['id']] ?? '📖' ?>
                                            </span>

                                            <strong>
                                                <?= htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8') ?>
                                            </strong>

                                            <small>
                                                <?= htmlspecialchars($book['author'], ENT_QUOTES, 'UTF-8') ?>
                                            </small>

                                        </div>

                                    <?php endif; ?>

                                </div>

                                <div class="book-card-body">

                                    <h3>
                                        <?= htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8') ?>
                                    </h3>

                                    <p class="book-author">
                                        by <?= htmlspecialchars($book['author'], ENT_QUOTES, 'UTF-8') ?>
                                    </p>

                                    <p class="book-description">
                                        <?= htmlspecialchars($book['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                    </p>

                                    <div class="book-card-bottom">

                                        <strong>
                                            ₹<?= number_format((float)$book['price'], 2) ?>
                                        </strong>

                                        <span class="book-status">
                                            Available
                                        </span>

                                    </div>

                                </div>

                            </article>

                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

            <div class="text-center mt-5">
                <a href="#top" class="text-link">
                    Back to top ↑
                </a>
            </div>

        </div>
    </section>

</main>

<footer class="admin-footer">
    <div class="container">
        © <?= date('Y') ?> BookNest.Admin — Store Preview
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
