
<?php
session_start();

$pageTitle = 'BookNest — Discover Your Next Favorite Book';

require_once __DIR__ . '/config/database.php';

// Fetch featured books
$stmt = $conn->prepare(
    "SELECT id, title, author, description, price, image_path
     FROM books
     WHERE status = 'active'
     ORDER BY created_at DESC
     LIMIT 4"
);

$stmt->execute();
$featuredBooks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Icons for books without uploaded cover images
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

require_once __DIR__ . '/includes/header.php';
?>

<style>
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
    }

    .section-heading {
        margin-bottom: 25px;
    }

    .book-card {
        overflow: hidden;
        border-radius: 15px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .book-card:hover,
    .benefit-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 22px rgba(23, 37, 84, 0.09);
    }

    @media (max-width: 576px) {
        .hero-section {
            padding: 22px 0 35px;
        }

        .benefits-section {
            padding: 15px 0 25px;
        }

        .featured-section {
            padding: 30px 0 40px;
        }
    }
</style>

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
                    <a href="books.php" class="btn btn-primary btn-lg">
                        Explore Books
                    </a>

                    <a href="register.php" class="btn btn-outline-primary btn-lg">
                        Join BookNest
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

<!-- FEATURED BOOKS SECTION -->
<section class="featured-section">
    <div class="container">

        <div class="section-heading">
            <div>
                <span class="eyebrow">YOUR NEXT READ</span>

                <h2 class="section-title mt-2">
                    A world of books awaits
                </h2>

                <p>Browse our collection and discover something new.</p>
            </div>

            <a href="books.php" class="text-link">
                Browse all books →
            </a>
        </div>

        <div class="row g-4">

            <?php if (empty($featuredBooks)): ?>

                <div class="col-12">
                    <div class="empty-books card">
                        <div class="empty-books-icon">📚</div>
                        <h3>No books available yet</h3>
                        <p>
                            Please check back soon for new additions
                            to our collection.
                        </p>
                    </div>
                </div>

            <?php else: ?>

                <?php foreach ($featuredBooks as $book): ?>

                    <div class="col-sm-6 col-lg-3">
                        <article class="book-card card h-100">

                            <div class="book-cover-area">

                                <?php if (!empty($book['image_path'])): ?>

                                    <img
                                        src="<?= htmlspecialchars($book['image_path'], ENT_QUOTES, 'UTF-8') ?>"
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

                                    <span class="book-status">Available</span>
                                </div>
                            </div>

                        </article>
                    </div>

                <?php endforeach; ?>

            <?php endif; ?>

        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
