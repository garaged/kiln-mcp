#!/bin/sh
set -eu

wp_path=/var/www/html

if [ ! -f "$wp_path/wp-load.php" ]; then
  cp -a /usr/src/wordpress/. "$wp_path/"
fi

wp config create --allow-root --path="$wp_path" --dbname="$WORDPRESS_DB_NAME" --dbuser="$WORDPRESS_DB_USER" --dbpass="$WORDPRESS_DB_PASSWORD" --dbhost="$WORDPRESS_DB_HOST" --skip-check --force

install_attempt=0
until wp core install --allow-root --path="$wp_path" --url="$WORDPRESS_URL" --title="Kiln MCP Test Site" --admin_user=kiln-admin --admin_password=kiln_test_admin_password --admin_email=kiln@example.test --skip-email; do
  install_attempt=$((install_attempt + 1))
  if [ "$install_attempt" -ge 30 ]; then
    echo "WordPress could not complete core installation after 30 attempts." >&2
    exit 1
  fi
  sleep 1
done

wp plugin install mcp-adapter --version=0.7.1 --activate --allow-root --path="$wp_path"
wp plugin is-active mcp-adapter --allow-root --path="$wp_path"

mkdir -p "$wp_path/wp-content/plugins/kiln-mcp"
cp -a /opt/kiln/plugin/. "$wp_path/wp-content/plugins/kiln-mcp/"

wp plugin activate kiln-mcp --allow-root --path="$wp_path"
wp plugin is-active kiln-mcp --allow-root --path="$wp_path"

wp user create kiln-subscriber kiln-subscriber@example.test --role=subscriber --user_pass=kiln_test_subscriber_password --allow-root --path="$wp_path"
wp user create kiln-author kiln-author@example.test --role=author --user_pass=kiln_test_author_password --allow-root --path="$wp_path"

author_id="$(wp user get kiln-author --field=ID --allow-root --path="$wp_path")"

wp post create --post_type=post --post_status=publish --post_title="Kiln published fixture" --post_content="This body must never be returned by discovery." --post_author=1 --allow-root --path="$wp_path"
wp post create --post_type=post --post_status=draft --post_title="Kiln author draft fixture" --post_content="This body must never be returned by discovery." --post_author="$author_id" --allow-root --path="$wp_path"
wp post create --post_type=post --post_status=draft --post_title="Kiln admin draft fixture" --post_content="This body must never be returned by discovery." --post_author=1 --allow-root --path="$wp_path"
wp post create --post_type=post --post_status=private --post_title="Kiln private fixture" --post_content="This post must never be listed." --post_author=1 --allow-root --path="$wp_path"

echo "WordPress bootstrap completed."
