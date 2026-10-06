<?php
session_start();
require_once '/var/www/config/database.php';

$adminUser = (string) getenv('ADMIN_USER');
$adminPass = (string) getenv('ADMIN_PASSWORD');
$error = '';
$message = '';

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /admin.php');
    exit;
}

// Đăng nhập
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $u = (string) ($_POST['username'] ?? '');
    $p = (string) ($_POST['password'] ?? '');
    if ($adminUser !== '' && $adminPass !== '' && hash_equals($adminUser, $u) && hash_equals($adminPass, $p)) {
        session_regenerate_id(true);
        $_SESSION['admin'] = true;
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    } else {
        $error = 'Sai tài khoản hoặc mật khẩu';
    }
}

$loggedIn = !empty($_SESSION['admin']);

// Thêm bài viết
if ($loggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_post'])) {
    if (!hash_equals($_SESSION['csrf'], (string) ($_POST['csrf'] ?? ''))) {
        $error = 'Phiên không hợp lệ, hãy thử lại';
    } else {
        $title = trim((string) ($_POST['title'] ?? ''));
        $content = trim((string) ($_POST['content'] ?? ''));
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        if ($title === '' || $content === '' || $categoryId <= 0) {
            $error = 'Vui lòng nhập đầy đủ thông tin';
        } else {
            $stmt = $pdo->prepare('INSERT INTO posts (title, content, category_id) VALUES (?, ?, ?)');
            $stmt->execute([$title, $content, $categoryId]);
            $message = 'Đã đăng bài viết thành công';
        }
    }
}

$categories = $loggedIn ? $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll(PDO::FETCH_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Admin - News Portal</title>
</head>
<body>
<h1>Quản trị News Portal</h1>

<?php if ($error): ?><p style="color:red"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<?php if ($message): ?><p style="color:green"><?= htmlspecialchars($message) ?></p><?php endif; ?>

<?php if (!$loggedIn): ?>
    <h2>Đăng nhập</h2>
    <form method="post">
        <p><input name="username" placeholder="Tài khoản" required></p>
        <p><input name="password" type="password" placeholder="Mật khẩu" required></p>
        <p><button name="login" value="1">Đăng nhập</button></p>
    </form>
<?php else: ?>
    <p><a href="/">Xem trang chủ</a> | <a href="/admin.php?logout=1">Đăng xuất</a></p>
    <h2>Đăng bài viết mới</h2>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
        <p><input name="title" placeholder="Tiêu đề" size="60" required></p>
        <p>
            <select name="category_id" required>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= (int) $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <p><textarea name="content" rows="8" cols="60" placeholder="Nội dung" required></textarea></p>
        <p><button name="add_post" value="1">Đăng bài</button></p>
    </form>
<?php endif; ?>
</body>
</html>
