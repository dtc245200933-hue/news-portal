# News Portal - Docker

## Kiến trúc

Browser -> HTTPS -> Nginx -> PHP/Apache -> MySQL

phpMyAdmin -> MySQL
Prometheus -> metrics
Grafana -> dashboard
Promtail -> Loki -> LogQL

## Yêu cầu

- Docker Desktop
- Docker Compose v2

## Chạy project

```bash
docker compose config
docker compose up -d --build
docker compose ps
```

Website: https://localhost
phpMyAdmin: http://localhost:8081
Prometheus: http://localhost:9090
Grafana: http://localhost:3001
Loki: http://localhost:3100/ready

Grafana mặc định:
- User: admin
- Password: admin

> Đây là mật khẩu demo cho môi trường local. Khi nộp/public repository, hãy đổi mật khẩu và không commit `.env`.

## Biến môi trường

`.env` dùng cho máy local và đã được `.gitignore` loại khỏi Git.
`.env.example` là file mẫu an toàn để đưa lên GitHub.

## Kiểm tra kết nối DB

```bash
docker compose exec web php -r 'require "/var/www/config/database.php"; echo "Database connected successfully\\n";'
```

## Kiểm tra HTTPS và security headers

```bash
curl -k -I https://localhost
```

## LogQL

Sau khi Nginx nhận request và Promtail gửi log vào Loki, có thể truy vấn với nhãn:

```logql
{job="nginx"}
```

và:

```logql
{service="news-nginx"}
```

## Lưu ý

Thư mục `.git` không được đóng gói trong bản fixed này. Hãy dùng repository Git hiện tại của bạn làm nơi quản lý commit.
