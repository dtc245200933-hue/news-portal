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
    <title>News Portal</title>
</head>
<body>

<h1>News Portal</h1>

<?php if (empty($posts)): ?>

    <p>Chưa có bài viết nào.</p>

<?php else: ?>

    <?php foreach ($posts as $post): ?>

        <article>
            <h2><?= htmlspecialchars($post['title']) ?></h2>

            <p>
                <strong>Chuyên mục:</strong>
                <?= htmlspecialchars($post['category_name']) ?>
            </p>

            <p><?= htmlspecialchars($post['content']) ?></p>
        </article>

        <hr>

    <?php endforeach; ?>

<?php endif; ?>

</body>
</html>