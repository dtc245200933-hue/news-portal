<?php

require_once '/var/www/config/database.php';
require_once '/var/www/config/layout.php';

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);

$catId = (int) ($_GET['cat'] ?? 0);
$currentCat = null;
foreach ($categories as $c) {
    if ((int) $c['id'] === $catId) {
        $currentCat = $c;
    }
}

$posts = [];
$notFound = ($catId > 0 && $currentCat === null);

if ($notFound) {
    http_response_code(404);
} else {
    $sql = 'SELECT posts.id, posts.title, posts.content, posts.created_at, '
         . 'posts.category_id, categories.name AS category_name '
         . 'FROM posts INNER JOIN categories ON posts.category_id = categories.id';
    $params = [];
    if ($currentCat !== null) {
        $sql .= ' WHERE posts.category_id = ?';
        $params[] = $catId;
    }
    $sql .= ' ORDER BY posts.created_at DESC, posts.id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$heading = $currentCat !== null ? 'Chuyên mục: ' . $currentCat['name'] : 'Tin mới nhất';

render_header($currentCat !== null ? $currentCat['name'] . ' - News Portal' : 'News Portal');
?>

<div class="chips">
    <a class="chip<?= ($currentCat === null && !$notFound) ? ' active' : '' ?>" href="/">Tất cả</a>
    <?php foreach ($categories as $c): ?>
        <a class="chip<?= ($currentCat !== null && (int) $c['id'] === (int) $currentCat['id']) ? ' active' : '' ?>"
           href="/?cat=<?= (int) $c['id'] ?>"><?= e($c['name']) ?></a>
    <?php endforeach; ?>
</div>

<?php if ($notFound): ?>

    <div class="card empty">Chuyên mục không tồn tại. <a href="/">Về trang chủ</a></div>

<?php else: ?>

    <h1 class="page-title"><?= e($heading) ?></h1>

    <?php if (empty($posts)): ?>

        <div class="card empty">Chưa có bài viết nào.</div>

    <?php else: ?>

        <?php foreach ($posts as $post): ?>

            <article class="card">
                <div class="meta">
                    <a class="badge" href="/?cat=<?= (int) $post['category_id'] ?>"><?= e($post['category_name']) ?></a>
                    <span class="date"><?= e(format_date($post['created_at'])) ?></span>
                </div>
                <h2><a href="/article.php?id=<?= (int) $post['id'] ?>"><?= e($post['title']) ?></a></h2>
                <p><?= e(excerpt($post['content'])) ?></p>
                <a class="read-more" href="/article.php?id=<?= (int) $post['id'] ?>">Đọc tiếp &rarr;</a>
            </article>

        <?php endforeach; ?>

    <?php endif; ?>

<?php endif; ?>

<?php render_footer(); ?>
