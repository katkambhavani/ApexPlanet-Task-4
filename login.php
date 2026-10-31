
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = 'Login — BookNest';

require_once __DIR__ . '/config/database.php';

$errors = [];
$email = '';
$successMessage = '';

if (isset($_GET['registered']) && $_GET['registered'] === '1') {
    $successMessage = 'Your account was created successfully. Please sign in.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if ($password === '') {
        $errors[] = 'Please enter your password.';
    }

    if (empty($errors)) {

        $stmt = $conn->prepare(
            'SELECT id, name, email, password_hash, role_id, status
             FROM users
             WHERE email = ?
             LIMIT 1'
        );

        $stmt->bind_param('s', $email);
        $stmt->execute();

        $user = $stmt->get_result()->fetch_assoc();

        $stmt->close();

        if (
            $user &&
            password_verify($password, $user['password_hash']) &&
            $user['status'] === 'active'
        ) {

            session_regenerate_id(true);

            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role_id'] = (int) $user['role_id'];

            if ((int) $user['role_id'] === 1) {
                header('Location: admin/dashboard.php');
            } else {
                header('Location: dashboard.php');
            }

            exit;

        } else {
            $errors[] = 'Invalid email or password, or your account is inactive.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<style>
    /* LOGIN PAGE */

    .login-section {
        min-height: 75vh;
        padding: 65px 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #FFF9F0;
    }

    .login-wrapper {
        width: 100%;
        max-width: 460px;
        margin: auto;
    }

    .login-card {
        background: #ffffff;
        border: 1px solid #f0e8dc;
        border-radius: 20px;
        padding: 42px;
        box-shadow: 0 12px 35px rgba(23, 37, 84, 0.08);
    }

    .login-icon {
        width: 58px;
        height: 58px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 22px;
        border-radius: 16px;
        background: #fff1d6;
        font-size: 27px;
    }

    .login-heading .eyebrow {
        color: #D97706;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 2px;
    }

    .login-heading h1 {
        margin-top: 12px;
        margin-bottom: 10px;
        color: #172554;
        font-size: 29px;
        font-weight: 800;
    }

    .login-heading p {
        margin-bottom: 28px;
        color: #64748b;
        font-size: 14px;
        line-height: 1.7;
    }

    .login-card .form-label {
        color: #243047;
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .login-card .form-control {
        min-height: 49px;
        padding: 12px 14px;
        border: 1px solid #e2e8f0;
        border-radius: 9px;
        background: #fff;
        font-size: 14px;
        box-shadow: none;
    }
    .site-footer {
    text-align: center;
}

    .login-card .form-control:focus {
        border-color: #F59E0B;
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.13);
    }

    .password-wrapper {
        position: relative;
    }

    .password-wrapper .form-control {
        padding-right: 75px;
    }

    .password-toggle {
        position: absolute;
        top: 50%;
        right: 13px;
        transform: translateY(-50%);
        border: none;
        background: transparent;
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
    }

    .password-toggle:hover {
        color: #D97706;
    }

    .login-submit {
        width: 100%;
        min-height: 49px;
        margin-top: 5px;
        border: none;
        border-radius: 9px;
        background: #F59E0B;
        color: #172554;
        font-size: 15px;
        font-weight: 800;
        transition: background .2s ease, transform .2s ease;
    }

    .login-submit:hover {
        background: #FBBF24;
        color: #172554;
        transform: translateY(-1px);
    }

    .login-divider {
        height: 1px;
        background: #f1f5f9;
        margin: 26px 0 20px;
    }

    .login-links {
        text-align: center;
        font-size: 13px;
        color: #64748b;
        line-height: 2;
    }

    .login-links a {
        color: #B45309;
        font-weight: 700;
        text-decoration: none;
    }

    .login-links a:hover {
        color: #172554;
        text-decoration: underline;
    }

    .login-card .alert {
        border-radius: 9px;
        font-size: 13px;
    }

    .login-card .alert ul {
        padding-left: 20px;
    }

    @media(max-width:576px) {
        .login-section {
            padding: 35px 15px;
        }

        .login-card {
            padding: 28px 22px;
        }

        .login-heading h1 {
            font-size: 25px;
        }
    }
</style>

<section class="login-section">

    <div class="login-wrapper">

        <div class="login-card">

            <div class="login-icon">
                📚
            </div>

            <div class="login-heading">

                <span class="eyebrow">WELCOME BACK</span>

                <h1>Sign in to BookNest</h1>

                <p>
                    Your next favorite story is waiting.
                    Sign in to continue your reading journey.
                </p>

            </div>

            <?php if ($successMessage !== ''): ?>

                <div class="alert alert-success" role="status">
                    <?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?>
                </div>

            <?php endif; ?>

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

            <form method="POST" action="login.php">

                <div class="mb-3">

                    <label for="email" class="form-label">
                        Email Address
                    </label>

                    <input
                        type="email"
                        class="form-control"
                        id="email"
                        name="email"
                        placeholder="Enter your email address"
                        autocomplete="email"
                        value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >

                </div>

                <div class="mb-4">

                    <label for="password" class="form-label">
                        Password
                    </label>

                    <div class="password-wrapper">

                        <input
                            type="password"
                            class="form-control"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            id="togglePassword"
                            aria-label="Show password"
                        >
                            Show
                        </button>

                    </div>

                </div>

                <button type="submit" class="login-submit">
                    Sign In →
                </button>

            </form>

            <div class="login-divider"></div>

            <div class="login-links">

                <p class="mb-2">
                    Don't have an account?
                    <a href="register.php">Create an account</a>
                </p>

                <p class="mb-0">
                    <a href="forgot-password.php">Forgot your password?</a>
                </p>

            </div>

        </div>

    </div>

</section>

<script>
    const passwordInput = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');

    togglePassword.addEventListener('click', function () {

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            togglePassword.textContent = 'Hide';
            togglePassword.setAttribute('aria-label', 'Hide password');
        } else {
            passwordInput.type = 'password';
            togglePassword.textContent = 'Show';
            togglePassword.setAttribute('aria-label', 'Show password');
        }

    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
