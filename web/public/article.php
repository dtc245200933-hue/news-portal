<?php

require_once '/var/www/config/database.php';
require_once '/var/www/config/layout.php';

$id = (int) ($_GET['id'] ?? 0);
$post = false;

if ($id > 0) {
    $stmt = $pdo->prepare(
        'SELECT posts.id, posts.title, posts.content, posts.created_at, '
        . 'posts.category_id, categories.name AS category_name '
        . 'FROM posts INNER JOIN categories ON posts.category_id = categories.id '
        . 'WHERE posts.id = ?'
    );
    $stmt->execute([$id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$post) {
    http_response_code(404);
    render_header('Không tìm thấy bài viết - News Portal');
    ?>
    <div class="card empty">Không tìm thấy bài viết. <a href="/">Về trang chủ</a></div>
    <?php
    render_footer();
    exit;
}

// Bai viet cung chuyen muc
$stmt = $pdo->prepare(
    'SELECT id, title FROM posts WHERE category_id = ? AND id <> ? '
    . 'ORDER BY created_at DESC, id DESC LIMIT 3'
);
$stmt->execute([(int) $post['category_id'], (int) $post['id']]);
$related = $stmt->fetchAll(PDO::FETCH_ASSOC);

render_header($post['title'] . ' - News Portal');
?>

<nav class="breadcrumb">
    <a href="/">Trang chủ</a> &rsaquo;
    <a href="/?cat=<?= (int) $post['category_id'] ?>"><?= e($post['category_name']) ?></a>
</nav>

<article class="card article">
    <div class="meta">
        <a class="badge" href="/?cat=<?= (int) $post['category_id'] ?>"><?= e($post['category_name']) ?></a>
        <span class="date"><?= e(format_date($post['created_at'])) ?></span>
    </div>
    <h1><?= e($post['title']) ?></h1>
    <div class="article-content"><?= nl2br(e($post['content'])) ?></div>
</article>

<?php if (!empty($related)): ?>
    <section class="related">
        <h3>Bài viết cùng chuyên mục</h3>
        <ul>
            <?php foreach ($related as $r): ?>
                <li><a href="/article.php?id=<?= (int) $r['id'] ?>"><?= e($r['title']) ?></a></li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>

<p><a class="read-more" href="/">&larr; Về trang chủ</a></p>

<?php render_footer(); ?>
