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

// Thêm bài viết (có thể kèm tạo chuyên mục mới)
if ($loggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_post'])) {
    if (!hash_equals($_SESSION['csrf'], (string) ($_POST['csrf'] ?? ''))) {
        $error = 'Phiên không hợp lệ, hãy thử lại';
    } else {
        $title = trim((string) ($_POST['title'] ?? ''));
        $content = trim((string) ($_POST['content'] ?? ''));
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $newCat = trim(preg_replace('/\s+/u', ' ', (string) ($_POST['new_category'] ?? '')));
        $newCatLen = function_exists('mb_strlen') ? mb_strlen($newCat, 'UTF-8') : strlen($newCat);

        if ($newCatLen > 100) {
            $error = 'Tên chuyên mục tối đa 100 ký tự';
        } elseif ($title === '' || $content === '' || ($newCat === '' && $categoryId <= 0)) {
            $error = 'Vui lòng nhập đầy đủ thông tin';
        } else {
            try {
                $pdo->beginTransaction();
                $createdCat = false;

                if ($newCat !== '') {
                    // Nếu tên đã có (không phân biệt hoa thường) thì dùng lại chuyên mục đó
                    $lower = function ($t) {
                        return function_exists('mb_strtolower') ? mb_strtolower($t, 'UTF-8') : strtolower($t);
                    };
                    $found = 0;
                    foreach ($pdo->query('SELECT id, name FROM categories')->fetchAll(PDO::FETCH_ASSOC) as $row) {
                        if ($lower($row['name']) === $lower($newCat)) {
                            $found = (int) $row['id'];
                            break;
                        }
                    }
                    if ($found > 0) {
                        $categoryId = $found;
                    } else {
                        $pdo->prepare('INSERT INTO categories (name) VALUES (?)')->execute([$newCat]);
                        $categoryId = (int) $pdo->lastInsertId();
                        $createdCat = true;
                    }
                }

                $pdo->prepare('INSERT INTO posts (title, content, category_id) VALUES (?, ?, ?)')
                    ->execute([$title, $content, $categoryId]);
                $pdo->commit();

                $message = 'Đã đăng bài viết thành công';
                if ($createdCat) {
                    $message .= ' (đã tạo chuyên mục mới: ' . $newCat . ')';
                }
            } catch (Throwable $ex) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Không thể đăng bài, vui lòng thử lại';
            }
        }
    }
}

$categories = $loggedIn ? $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll(PDO::FETCH_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - News Portal</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>

<header class="site-header">
    <div class="container">
        <a class="brand" href="/">News <span>Portal</span></a>
        <nav class="nav">
            <a href="/">Trang chủ</a>
            <a href="/admin.php">Quản trị</a>
        </nav>
    </div>
</header>

<main class="container">
    <div class="form-card">
        <h1 class="page-title">Quản trị News Portal</h1>

        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($message): ?><div class="alert alert-ok"><?= htmlspecialchars($message) ?></div><?php endif; ?>

        <?php if (!$loggedIn): ?>
            <div class="card">
                <h2>Đăng nhập</h2>
                <form method="post">
                    <label for="username">Tài khoản</label>
                    <input id="username" type="text" name="username" required>
                    <label for="password">Mật khẩu</label>
                    <input id="password" type="password" name="password" required>
                    <button class="btn" name="login" value="1">Đăng nhập</button>
                </form>
            </div>
        <?php else: ?>
            <div class="admin-bar">
                <a href="/">Xem trang chủ</a>
                <a href="/admin.php?logout=1">Đăng xuất</a>
            </div>
            <div class="card">
                <h2>Đăng bài viết mới</h2>
                <form method="post">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                    <label for="title">Tiêu đề</label>
                    <input id="title" type="text" name="title" required>
                    <label for="category_id">Chuyên mục</label>
                    <select id="category_id" name="category_id" required>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= (int) $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="new_category">Hoặc tạo chuyên mục mới (không bắt buộc)</label>
                    <input id="new_category" type="text" name="new_category" maxlength="100" placeholder="Ví dụ: Giáo dục">
                    <div class="field-hint">Nếu nhập tên ở đây, bài viết sẽ được đăng vào chuyên mục mới này thay cho chuyên mục đã chọn ở trên.</div>
                    <label for="content">Nội dung</label>
                    <textarea id="content" name="content" required></textarea>
                    <button class="btn" name="add_post" value="1">Đăng bài</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</main>

<footer class="site-footer">
    News Portal &middot; Đề 19 - Triển khai và Quản trị Hệ thống Phần mềm
</footer>

</body>
</html>
