SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL
) CHARACTER SET utf8mb4;

CREATE TABLE IF NOT EXISTS posts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  content TEXT NOT NULL,
  category_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id)
) CHARACTER SET utf8mb4;

INSERT INTO categories (name) VALUES
  ('Thời sự'), ('Công nghệ'), ('Thể thao');

INSERT INTO posts (title, content, category_id) VALUES
  ('Khai trương cổng thông tin tin tức', 'Cổng thông tin chính thức đi vào hoạt động, cập nhật tin tức mỗi ngày.', 1),
  ('Docker giúp triển khai ứng dụng nhanh hơn', 'Container hóa giúp đóng gói ứng dụng và chạy giống nhau trên mọi môi trường.', 2),
  ('Giải bóng đá sinh viên khởi tranh', 'Vòng bảng giải bóng đá sinh viên bắt đầu với nhiều trận đấu hấp dẫn.', 3);
