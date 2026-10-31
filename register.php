
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = 'Create Account — BookNest';

require_once __DIR__ . '/config/database.php';

$errors = [];
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($name === '' || strlen($name) > 100) {
        $errors[] = 'Please enter a name with no more than 100 characters.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Your password must contain at least 8 characters.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'The passwords do not match.';
    }

    if (empty($errors)) {

        $check = $conn->prepare(
            'SELECT id FROM users WHERE email = ? LIMIT 1'
        );

        $check->bind_param('s', $email);
        $check->execute();

        $existingUser = $check->get_result()->fetch_assoc();

        $check->close();

        if ($existingUser) {

            $errors[] = 'An account with this email already exists.';

        } else {

            $roleStmt = $conn->prepare(
                "SELECT id FROM roles WHERE role_name = 'customer' LIMIT 1"
            );

            $roleStmt->execute();

            $role = $roleStmt->get_result()->fetch_assoc();

            $roleStmt->close();

            if (!$role) {

                $errors[] = 'Customer registration is temporarily unavailable.';

            } else {

                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $roleId = (int) $role['id'];

                $insert = $conn->prepare(
                    'INSERT INTO users (role_id, name, email, password_hash)
                     VALUES (?, ?, ?, ?)'
                );

                $insert->bind_param(
                    'isss',
                    $roleId,
                    $name,
                    $email,
                    $passwordHash
                );

                if ($insert->execute()) {

                    $insert->close();

                    header('Location: login.php?registered=1');
                    exit;

                }

                $insert->close();

                $errors[] = 'We could not create your account. Please try again.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<style>
    /* REGISTRATION PAGE */

    .register-section {
        min-height: 80vh;
        padding: 45px 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #FFF9F0;
    }

    .register-wrapper {
        width: 100%;
        max-width: 490px;
        margin: auto;
    }

    .register-card {
        background: #ffffff;
        border: 1px solid #f0e8dc;
        border-radius: 20px;
        padding: 38px 42px;
        box-shadow: 0 12px 35px rgba(23, 37, 84, 0.08);
    }

    .register-icon {
        width: 56px;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 20px;
        border-radius: 16px;
        background: #fff1d6;
        font-size: 26px;
    }

    .register-heading .eyebrow {
        color: #D97706;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 2px;
    }

    .register-heading h1 {
        margin-top: 12px;
        margin-bottom: 10px;
        color: #172554;
        font-size: 28px;
        font-weight: 800;
    }

    .register-heading p {
        margin-bottom: 25px;
        color: #64748b;
        font-size: 14px;
        line-height: 1.7;
    }

    .register-card .form-label {
        color: #243047;
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .register-card .form-control {
        min-height: 47px;
        padding: 11px 14px;
        border: 1px solid #e2e8f0;
        border-radius: 9px;
        background: #fff;
        font-size: 14px;
        box-shadow: none;
    }

    .register-card .form-control:focus {
        border-color: #F59E0B;
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.13);
    }
.site-footer {
    text-align: center;
}
    .register-card .form-text {
        display: block;
        margin-top: 6px;
        font-size: 12px;
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

    .register-submit {
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

    .register-submit:hover {
        background: #FBBF24;
        color: #172554;
        transform: translateY(-1px);
    }

    .register-divider {
        height: 1px;
        background: #f1f5f9;
        margin: 24px 0 18px;
    }

    .register-switch {
        text-align: center;
        color: #64748b;
        font-size: 13px;
        margin-bottom: 0;
    }

    .register-switch a {
        color: #B45309;
        font-weight: 700;
        text-decoration: none;
    }

    .register-switch a:hover {
        color: #172554;
        text-decoration: underline;
    }

    .register-card .alert {
        border-radius: 9px;
        font-size: 13px;
    }

    .register-card .alert ul {
        padding-left: 20px;
    }

    @media(max-width:576px) {
        .register-section {
            padding: 30px 15px;
        }

        .register-card {
            padding: 28px 22px;
        }

        .register-heading h1 {
            font-size: 25px;
        }
    }
</style>

<section class="register-section">

    <div class="register-wrapper">

        <div class="register-card">

            <div class="register-icon">
                📚
            </div>

            <div class="register-heading">

                <span class="eyebrow">WELCOME TO BOOKNEST</span>

                <h1>Create your account</h1>

                <p>
                    Join our reading community and discover
                    your next favorite book.
                </p>

            </div>

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

            <form method="POST" action="register.php">

                <div class="mb-3">

                    <label for="name" class="form-label">
                        Full Name
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="name"
                        name="name"
                        placeholder="Enter your full name"
                        maxlength="100"
                        autocomplete="name"
                        value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >

                </div>

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
                        maxlength="150"
                        autocomplete="email"
                        value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >

                </div>

                <div class="mb-3">

                    <label for="password" class="form-label">
                        Password
                    </label>

                    <div class="password-wrapper">

                        <input
                            type="password"
                            class="form-control"
                            id="password"
                            name="password"
                            placeholder="Create a password"
                            minlength="8"
                            autocomplete="new-password"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            data-target="password"
                            aria-label="Show password"
                        >
                            Show
                        </button>

                    </div>

                    <small class="form-text text-muted">
                        Use at least 8 characters.
                    </small>

                </div>

                <div class="mb-4">

                    <label for="confirm_password" class="form-label">
                        Confirm Password
                    </label>

                    <div class="password-wrapper">

                        <input
                            type="password"
                            class="form-control"
                            id="confirm_password"
                            name="confirm_password"
                            placeholder="Enter your password again"
                            minlength="8"
                            autocomplete="new-password"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            data-target="confirm_password"
                            aria-label="Show password"
                        >
                            Show
                        </button>

                    </div>

                </div>

                <button type="submit" class="register-submit">
                    Create Account →
                </button>

            </form>

            <div class="register-divider"></div>

            <p class="register-switch">
                Already have an account?
                <a href="login.php">Sign in</a>
            </p>

        </div>

    </div>

</section>

<script>
    document.querySelectorAll('.password-toggle').forEach(function(button) {

        button.addEventListener('click', function() {

            const input = document.getElementById(
                button.getAttribute('data-target')
            );

            if (input.type === 'password') {
                input.type = 'text';
                button.textContent = 'Hide';
                button.setAttribute('aria-label', 'Hide password');
            } else {
                input.type = 'password';
                button.textContent = 'Show';
                button.setAttribute('aria-label', 'Show password');
            }

        });

    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
