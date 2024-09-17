<?php
session_start();
include 'header.php';
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_SPECIAL_CHARS);

    if (empty($username) || empty($password)) {
        $_SESSION['message'] = 'username and password are required.';
        header('Location: login.php');
        exit;
    }

    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['username'] = $username;
        header('Location: home.php');
        exit;
    } else {
        $_SESSION['message'] = 'invalid username or password.';
        header('Location: login.php');
        exit;
    }
}
?>

<section class="section">
    <div class="container">

        <?php if (isset($_SESSION['message'])): ?>
        <div class="notification is-info">
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
                        <button class="button is-primary is-rounded" type="submit" name="submit">login</button>
                    </div>
                </div>
                <div class="column">
                    <p>new? <a href="register.php">sign up</a> here!</p>
                </div>
            </div>
        </form>

    </div>
</section>
</body>

</html>