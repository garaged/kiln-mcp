#!/bin/sh
set -eu

wp_path=/var/www/html

wp core is-installed --allow-root --path="$wp_path"
wp plugin is-active kiln-mcp --allow-root --path="$wp_path"

response="$(php -r 'echo file_get_contents("http://wordpress/wp-json/");')"
printf '%s' "$response" | grep -q '"namespaces"'

if printf '%s' "$response" | grep -q '"kiln-mcp'; then
  echo "Kiln MCP must not expose an MCP namespace in Milestone 0." >&2
  exit 1
fi

echo "Container smoke checks passed."
