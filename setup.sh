#!/usr/bin/env bash
# setup.sh - Tao cac file bi mat con thieu roi khoi dong he thong.
# Chay lai nhieu lan van an toan: file nao da co thi giu nguyen.
set -euo pipefail
cd "$(dirname "$0")"

rand() { openssl rand -hex 12; }
get()  { grep "^$1=" .env | cut -d= -f2-; }

NEW_ENV=0

# 1. File .env (mat khau ngau nhien)
if [ ! -f .env ]; then
  cat > .env << ENVEOF
MYSQL_ROOT_PASSWORD=$(rand)
MYSQL_DATABASE=news_portal
MYSQL_USER=news_user
MYSQL_PASSWORD=$(rand)
ADMIN_USER=admin
ADMIN_PASSWORD=$(rand)
MYSQL_EXPORTER_PASSWORD=$(rand)
GRAFANA_ADMIN_PASSWORD=$(rand)
ENVEOF
  chmod 600 .env
  NEW_ENV=1
  echo "[1/5] Da tao .env voi mat khau ngau nhien"
else
  echo "[1/5] Da co .env, giu nguyen"
fi

# 2. Chung chi HTTPS tu ky cho Nginx
if [ ! -f nginx/certs/news-portal.key ] || [ ! -f nginx/certs/news-portal.crt ]; then
  mkdir -p nginx/certs
  CNF=$(mktemp)
  cat > "$CNF" << CNFEOF
[req]
distinguished_name = dn
x509_extensions = v3
prompt = no
[dn]
CN = localhost
[v3]
subjectAltName = DNS:localhost,IP:127.0.0.1
CNFEOF
  openssl req -x509 -nodes -newkey rsa:2048 -days 365 \
    -keyout nginx/certs/news-portal.key -out nginx/certs/news-portal.crt \
    -config "$CNF" 2>/dev/null
  rm -f "$CNF"
  chmod 600 nginx/certs/news-portal.key
  echo "[2/5] Da tao chung chi HTTPS tu ky"
else
  echo "[2/5] Da co chung chi HTTPS, giu nguyen"
fi

# 3. File cau hinh cho mysqld-exporter
if [ ! -f mysql/exporter.my.cnf ]; then
  printf '[client]\nuser=exporter\npassword=%s\n' "$(get MYSQL_EXPORTER_PASSWORD)" > mysql/exporter.my.cnf
  echo "[3/5] Da tao mysql/exporter.my.cnf"
else
  echo "[3/5] Da co mysql/exporter.my.cnf, giu nguyen"
fi

# 4. Khoi dong he thong
echo "[4/5] Dang khoi dong he thong (lan dau co the mat vai phut)..."
docker compose up -d --build

# 5. Phan quyen database
echo "[5/5] Dang cho MySQL san sang va thu hep quyen..."
for i in $(seq 1 60); do
  [ "$(docker inspect -f '{{.State.Health.Status}}' news-mysql 2>/dev/null)" = "healthy" ] && break
  sleep 5
done

DBU=$(get MYSQL_USER)
DBN=$(get MYSQL_DATABASE)
DBE=$(echo "$DBN" | sed 's/_/\\_/g')
EXP=$(get MYSQL_EXPORTER_PASSWORD)

docker compose exec -T mysql sh -c 'mysql --force -uroot -p"$MYSQL_ROOT_PASSWORD" 2>&1' << SQLEOF | grep -v 'Warning\|ERROR 1141' || true
CREATE USER IF NOT EXISTS 'exporter'@'%' IDENTIFIED BY '${EXP}' WITH MAX_USER_CONNECTIONS 3;
ALTER USER 'exporter'@'%' IDENTIFIED BY '${EXP}';
GRANT PROCESS, REPLICATION CLIENT, SELECT ON *.* TO 'exporter'@'%';
REVOKE ALL PRIVILEGES ON \`${DBE}\`.* FROM '${DBU}'@'%';
GRANT SELECT, INSERT ON \`${DBE}\`.* TO '${DBU}'@'%';
FLUSH PRIVILEGES;
SQLEOF

docker compose restart mysqld-exporter > /dev/null

echo
echo "Xong. Cac dich vu:"
echo "  Website     https://localhost   (trinh duyet se canh bao chung chi tu ky, chon tiep tuc)"
echo "  Trang admin https://localhost/admin.php"
echo "  phpMyAdmin  http://localhost:8081"
echo "  Grafana     http://localhost:3001"
echo "  Prometheus  http://localhost:9090"
echo "Grafana va Loki khoi dong cham, co the can 1-3 phut moi san sang."

if [ "$NEW_ENV" = "1" ]; then
  echo
  echo "Tai khoan vua duoc tao (cung nam trong file .env):"
  echo "  Admin web : $(get ADMIN_USER) / $(get ADMIN_PASSWORD)"
  echo "  Grafana   : admin / $(get GRAFANA_ADMIN_PASSWORD)"
fi
