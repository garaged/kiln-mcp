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

php -r '
$response = @file_get_contents("http://wordpress/wp-json/");
if (false === $response) {
    fwrite(STDERR, "Unable to retrieve the WordPress REST index.\n");
    exit(1);
}

$index = json_decode($response, true);
if (! is_array($index) || ! isset($index["routes"]) || ! is_array($index["routes"])) {
    fwrite(STDERR, "WordPress REST index was not valid route data: " . json_last_error_msg() . "\n");
    fwrite(STDERR, "Response preview: " . substr($response, 0, 300) . "\n");
    exit(1);
}

foreach (array_keys($index["routes"]) as $route) {
    if (false !== strpos($route, "kiln-mcp")) {
        fwrite(STDERR, "Kiln MCP must not expose an MCP route in Milestone 0.\n");
        exit(1);
    }
}
'

echo "Container smoke checks passed."
