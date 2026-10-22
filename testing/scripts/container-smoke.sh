#!/bin/sh
set -eu

wp_path=/var/www/html

if ! wp core is-installed --allow-root --path="$wp_path"; then
  echo "WordPress is not installed in the shared test volume." >&2
  exit 1
fi

if ! wp plugin is-active kiln-mcp --allow-root --path="$wp_path"; then
  echo "Kiln MCP is not active in the disposable WordPress site." >&2
  exit 1
fi

if ! response="$(php -r '$response = @file_get_contents("http://wordpress/wp-json/"); if (false === $response) { fwrite(STDERR, "Unable to retrieve the WordPress REST index.\\n"); exit(1); } echo $response;')"; then
  exit 1
fi

if ! printf '%s' "$response" | grep -q '"namespaces"'; then
  echo "WordPress REST index did not contain namespaces." >&2
  exit 1
fi

if printf '%s' "$response" | grep -q '"kiln-mcp'; then
  echo "Kiln MCP must not expose an MCP namespace in Milestone 0." >&2
  exit 1
fi

echo "Container smoke checks passed."
