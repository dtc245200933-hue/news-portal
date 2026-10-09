<?php

require_once '/var/www/config/database.php';

$stmt = $pdo->query("
    SELECT 
        posts.title,
        posts.content,
        categories.name AS category_name
    FROM posts
    INNER JOIN categories 
        ON posts.category_id = categories.id
    ORDER BY posts.created_at DESC
");

$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>News Portal</title>
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
    <h1 class="page-title">Tin mới nhất</h1>

    <?php if (empty($posts)): ?>

        <div class="card empty">Chưa có bài viết nào.</div>

    <?php else: ?>

        <?php foreach ($posts as $post): ?>

            <article class="card">
                <span class="badge"><?= htmlspecialchars($post['category_name']) ?></span>
                <h2><?= htmlspecialchars($post['title']) ?></h2>
                <p><?= nl2br(htmlspecialchars($post['content'])) ?></p>
            </article>

        <?php endforeach; ?>

    <?php endif; ?>
</main>

<footer class="site-footer">
    News Portal &middot; Đề 19 - Triển khai và Quản trị Hệ thống Phần mềm
</footer>

</body>
</html>
