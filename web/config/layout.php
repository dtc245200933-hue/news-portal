<?php
// Ham dung chung cho cac trang cong khai (trang chu, chi tiet bai viet)

function e($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

// Gio trong MySQL la UTC, doi sang gio Viet Nam khi hien thi
function format_date($ts)
{
    try {
        $d = new DateTime((string) $ts, new DateTimeZone('UTC'));
        $d->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'));
        return $d->format('d/m/Y H:i');
    } catch (Exception $ex) {
        return '';
    }
}

// Cat doan trich ngan tu noi dung bai viet
function excerpt($text, $len = 160)
{
    $text = trim(preg_replace('/\s+/u', ' ', (string) $text));
    if (function_exists('mb_strlen')) {
        if (mb_strlen($text, 'UTF-8') <= $len) {
            return $text;
        }
        return rtrim(mb_substr($text, 0, $len, 'UTF-8')) . '...';
    }
    return strlen($text) <= $len ? $text : substr($text, 0, $len) . '...';
}

function render_header($title)
{
    ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
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
<?php
}

function render_footer()
{
    ?>
</main>

<footer class="site-footer">
    News Portal &middot; Đề 19 - Triển khai và Quản trị Hệ thống Phần mềm
</footer>

</body>
</html>
<?php
}
