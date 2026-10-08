# News Portal - Triển khai và Quản trị Hệ thống Phần mềm

Đề 19: Website Tin tức / Cổng thông tin
Mã số sinh viên: DTC245200933

Hệ thống gồm website tin tức (bài viết, chuyên mục, trang admin đăng bài), cơ sở dữ liệu MySQL kèm phpMyAdmin, Nginx làm reverse proxy HTTPS, giám sát bằng Prometheus + Grafana, log tập trung bằng Loki + Promtail, và các biện pháp hardening. Tất cả chạy bằng Docker Compose.

## Kiến trúc

    Trình duyệt --HTTPS--> Nginx --> PHP/Apache (web) --> MySQL
                                                 phpMyAdmin --> MySQL

    Giám sát:  cAdvisor (container), nginx-exporter (web server),
               mysqld-exporter (database) --> Prometheus --> Grafana
    Log:       Nginx ghi file log --> Promtail --> Loki --> Grafana (LogQL)

Hai mạng Docker: `frontend-net` (Nginx, web, các công cụ giám sát) và `backend-net` (MySQL, web, phpMyAdmin, mysqld-exporter).

## Yêu cầu

- Docker Desktop (hoặc Docker Engine) và Docker Compose v2
- openssl, bash

## Chạy hệ thống

    git clone https://github.com/dtc245200933-hue/news-portal.git
    cd news-portal
    ./setup.sh

`setup.sh` tự tạo các file bí mật không nằm trong repo (`.env` với mật khẩu ngẫu nhiên, chứng chỉ HTTPS tự ký, `mysql/exporter.my.cnf`), khởi động toàn bộ dịch vụ và thu hẹp quyền database. Chạy lại nhiều lần vẫn an toàn, file nào đã có thì được giữ nguyên.

Lần chạy đầu, `setup.sh` in ra tài khoản admin của website và mật khẩu Grafana. Các mật khẩu này cũng nằm trong file `.env`. Grafana và Loki khởi động chậm, có thể cần 1 đến 3 phút.

## Địa chỉ truy cập

| Dịch vụ | Địa chỉ | Ghi chú |
|---|---|---|
| Website | https://localhost | Chứng chỉ tự ký, trình duyệt sẽ cảnh báo, chọn tiếp tục |
| Trang admin | https://localhost/admin.php | Tài khoản `ADMIN_USER` / `ADMIN_PASSWORD` trong `.env` |
| phpMyAdmin | http://localhost:8081 | Đăng nhập bằng `MYSQL_USER` / `MYSQL_PASSWORD` trong `.env` |
| Grafana | http://localhost:3001 | Tài khoản `admin`, mật khẩu `GRAFANA_ADMIN_PASSWORD` trong `.env` |
| Prometheus | http://localhost:9090 | Xem trang Status > Targets |
| Loki | http://localhost:3100/ready | Trả về `ready` khi sẵn sàng |

Các dịch vụ quản trị chỉ mở cho máy chạy Docker (`127.0.0.1`). Chỉ Nginx mở cổng 80 và 443.

## Kiểm tra nhanh

    docker compose ps
    curl -k -I https://localhost
    curl -s http://localhost:9090/api/v1/targets | grep -o '"health":"[a-z]*"' | sort | uniq -c

Kết quả đúng: các container `Up`, response có security headers, 4 target Prometheus `up`.

## Giám sát (Prometheus + Grafana)

Prometheus thu thập số liệu từ 4 nguồn: `prometheus`, `cadvisor`, `nginx` (qua nginx-exporter) và `mysql` (qua mysqld-exporter). Grafana tự kết nối Prometheus và Loki nhờ file `grafana/provisioning/datasources/datasources.yml`.

Dashboard nhập thủ công trong Grafana: Dashboards > New > Import, nhập mã rồi chọn nguồn Prometheus:

| Giám sát | Mã dashboard |
|---|---|
| Container (cAdvisor) | 14282 |
| Nginx | 12708 |
| MySQL | 14057 |

Ghi chú: trên Docker Desktop, cAdvisor cần gắn `docker.sock` và `containerd.sock` (đã cấu hình trong `docker-compose.yml`) để hiện được tên container.

## Log tập trung (Loki + Promtail)

Nginx ghi log ra `/var/log/nginx-app/` (volume dùng chung), Promtail đọc và gửi vào Loki. Trong Grafana, vào Explore, chọn nguồn Loki, chế độ Code và chạy các truy vấn LogQL:

    {job="nginx"}
    {job="nginx"} |= "404"
    sum(count_over_time({job="nginx"}[15m]))

Ý nghĩa: xem toàn bộ log Nginx; lọc các dòng có mã 404; đếm số dòng log trong 15 phút gần nhất.

## Hardening

1. Đóng cổng không cần thiết: MySQL không mở cổng ra máy, phpMyAdmin/Prometheus/Grafana/Loki chỉ lắng nghe `127.0.0.1`.
2. Nginx: ẩn phiên bản, chỉ cho TLS 1.2 trở lên, các header `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Content-Security-Policy`, `Permissions-Policy`.
3. Hạn chế quyền database: tài khoản của website chỉ có `SELECT` và `INSERT`; tài khoản `exporter` chỉ có quyền đọc số liệu.
4. Container web chạy non-root (`www-data`, cổng 8080).
5. Mật khẩu mạnh sinh ngẫu nhiên, nằm trong `.env` (không đưa lên Git); tách hai mạng `frontend-net` và `backend-net`.

## Cấu trúc thư mục

    docker-compose.yml        Toàn bộ dịch vụ
    setup.sh                  Tạo file bí mật và khởi động hệ thống
    web/                      Website PHP (index.php, admin.php), Dockerfile
    mysql/init.sql            Tạo bảng và dữ liệu mẫu
    nginx/                    Cấu hình reverse proxy và chứng chỉ
    prometheus/               Cấu hình thu thập số liệu
    grafana/provisioning/     Nguồn dữ liệu Grafana tự cấu hình
    loki/, promtail/          Cấu hình log tập trung

## Lịch sử commit

1. Triển khai website, MySQL, phpMyAdmin, Nginx HTTPS reverse proxy
2. Thêm Prometheus, Grafana, cAdvisor và các exporter
3. Thêm Loki, Promtail và truy vấn LogQL qua Grafana
4. Hardening hệ thống
5. Thêm setup.sh và cập nhật README
