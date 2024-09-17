<?php
session_start();
include 'header.php';
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_SPECIAL_CHARS);

    if (empty($username) || empty($password)) {
        $_SESSION['message'] = 'Username and password are required.';
        header('Location: register.php');
        exit;
    }

    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        $_SESSION['message'] = 'username already exists.';
        header('Location: register.php');
        exit;
    }

    $stmt = $pdo->prepare('INSERT INTO users (username, password_hash) VALUES (?, ?)');
    if ($stmt->execute([$username, $password_hash])) {
        $_SESSION['message'] = 'registration successful! you can now log in.';
        header('Location: login.php'); // Redirect to login page
        exit;
    } else {
        $_SESSION['message'] = 'registration failed.';
        header('Location: register.php'); // Redirect to avoid form resubmission
        exit;
    }
}
?>

<section class="section">
    <div class="container">
        <?php if (isset($_SESSION['message'])): ?>
        <div class="notification is-danger">
            <p><?php echo htmlspecialchars($_SESSION['message']); ?></p>
            <?php unset($_SESSION['message']);
                ?>
        </div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="columns is-desktop">

                <div class="field column">
                    <label class="label">username</label>
                    <div class="control">
                        <input class="input" type="text" name="username">
                    </div>
                </div>

                <div class="field column">
                    <label class="label">password</label>
                    <div class="control">
                        <input class="input" type="password" name="password">
                    </div>
                </div>
            </div>
            <div class="columns is-desktop has-text-centered">
                <div class="field column">
                    <div class="control">
                        <button class="button is-primary is-rounded" type="submit" name="submit">sign up</button>
                    </div>
                </div>
                <div class="column">
                    <p>already registered? <a href="login.php">sign in</a> here!</p>
                </div>
            </div>
        </form>
    </div>
</section>
</body>

</html>