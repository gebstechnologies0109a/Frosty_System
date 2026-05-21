#!/usr/bin/env bash
# Enable HTTPS for frosty.diybizrewards.com on the Forge server.
# Run on the server as root: sudo bash /home/forge/install-frosty-ssl.sh
set -euo pipefail

CERT_DIR=/home/forge/ssl/frosty.diybizrewards.com
NGINX_SITE=/etc/nginx/sites-available/frosty.diybizrewards.com

if [[ ! -f "${CERT_DIR}/server.crt" || ! -f "${CERT_DIR}/server.key" ]]; then
    echo "Missing certificate in ${CERT_DIR}. Run acme.sh first or enable SSL in Forge."
    exit 1
fi

chmod 644 "${CERT_DIR}/server.crt"
chmod 600 "${CERT_DIR}/server.key"

cat > "${NGINX_SITE}" <<'EOF'
# FORGE CONFIG (DO NOT REMOVE!)
include forge-conf/3205937/before/*;
include forge-conf/3205937/frosty.diybizrewards.com/before/*;

server {
    listen 80;
    listen [::]:80;
    server_name frosty.diybizrewards.com;
    return 301 https://$host$request_uri;
}

server {
    http2 on;
    listen 443 ssl;
    listen [::]:443 ssl;
    server_name frosty.diybizrewards.com;
    server_tokens off;
    root /home/forge/frosty.diybizrewards.com/public;

    ssl_certificate /home/forge/ssl/frosty.diybizrewards.com/server.crt;
    ssl_certificate_key /home/forge/ssl/frosty.diybizrewards.com/server.key;

    include forge-conf/3205937/site.conf;
}

# FORGE CONFIG (DO NOT REMOVE!)
include forge-conf/3205937/after/*;
include forge-conf/3205937/frosty.diybizrewards.com/after/*;
EOF

nginx -t
service nginx reload
echo "HTTPS enabled for frosty.diybizrewards.com"
