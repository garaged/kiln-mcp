#!/bin/sh
set -eu

wp_path=/var/www/html

wp core is-installed --allow-root --path="$wp_path"
wp plugin is-active mcp-adapter --allow-root --path="$wp_path"
wp plugin is-active kiln-mcp --allow-root --path="$wp_path"

wp eval --allow-root --path="$wp_path" --user=kiln-admin '
$ability = wp_get_ability( "kiln/site-health-summary" );
if ( null === $ability ) {
    fwrite( STDERR, "Kiln site health summary was not registered.\n" );
    exit( 1 );
}

$result = $ability->execute( array() );
if ( is_wp_error( $result ) ) {
    fwrite( STDERR, "Administrator could not execute Kiln site health summary.\n" );
    exit( 1 );
}

$expected_keys = array(
    "kiln_version",
    "wordpress_version",
    "php_version",
    "is_multisite",
    "active_theme_stylesheet",
    "status",
);
if ( $expected_keys !== array_keys( $result ) || "available" !== $result["status"] ) {
    fwrite( STDERR, "Kiln site health summary returned an unexpected contract.\n" );
    exit( 1 );
}
'

wp eval --allow-root --path="$wp_path" --user=kiln-subscriber '
$ability = wp_get_ability( "kiln/site-health-summary" );
$result  = null === $ability ? null : $ability->execute( array() );

if ( ! is_wp_error( $result ) ) {
    fwrite( STDERR, "Subscriber unexpectedly executed Kiln site health summary.\n" );
    exit( 1 );
}
'

echo "Container smoke checks passed."
