#!/bin/sh
set -eu

wp_path=/var/www/html

if [ ! -f "$wp_path/wp-load.php" ]; then
  cp -a /usr/src/wordpress/. "$wp_path/"
fi

wp config create --allow-root --path="$wp_path" --dbname="$WORDPRESS_DB_NAME" --dbuser="$WORDPRESS_DB_USER" --dbpass="$WORDPRESS_DB_PASSWORD" --dbhost="$WORDPRESS_DB_HOST" --skip-check --force

wp core install --allow-root --path="$wp_path" --url="$WORDPRESS_URL" --title="Kiln MCP Test Site" --admin_user=kiln-admin --admin_password=kiln_test_admin_password --admin_email=kiln@example.test --skip-email

wp plugin install mcp-adapter --version=0.7.1 --activate --allow-root --path="$wp_path"
wp plugin is-active mcp-adapter --allow-root --path="$wp_path"

mkdir -p "$wp_path/wp-content/plugins/kiln-mcp"
cp -a /opt/kiln/plugin/. "$wp_path/wp-content/plugins/kiln-mcp/"

wp plugin activate kiln-mcp --allow-root --path="$wp_path"
wp plugin is-active kiln-mcp --allow-root --path="$wp_path"

wp user create kiln-subscriber kiln-subscriber@example.test --role=subscriber --user_pass=kiln_test_subscriber_password --allow-root --path="$wp_path"

echo "WordPress bootstrap completed."
