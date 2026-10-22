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

wp eval --allow-root --path="$wp_path" --user=kiln-author '
$ability = wp_get_ability( "kiln/list-posts" );
if ( null === $ability ) {
    fwrite( STDERR, "Kiln post discovery was not registered.\n" );
    exit( 1 );
}

$published = $ability->execute(
    array(
        "status" => "publish",
        "limit"  => 20,
        "page"   => 1,
    )
);
$drafts = $ability->execute(
    array(
        "status" => "draft",
        "limit"  => 20,
        "page"   => 1,
    )
);

if ( is_wp_error( $published ) ) {
    fwrite( STDERR, "Author could not list published posts: " . $published->get_error_code() . ": " . $published->get_error_message() . "\n" );
    exit( 1 );
}
if ( is_wp_error( $drafts ) ) {
    fwrite( STDERR, "Author could not list drafts: " . $drafts->get_error_code() . ": " . $drafts->get_error_message() . "\n" );
    exit( 1 );
}

$expected_item_keys = array( "id", "title", "status", "modified_at", "permalink" );
if ( count( $published["items"] ) > 20 || count( $drafts["items"] ) > 20 ) {
    fwrite( STDERR, "Kiln post discovery exceeded its result bound.\n" );
    exit( 1 );
}

$draft_titles = array();
foreach ( array_merge( $published["items"], $drafts["items"] ) as $item ) {
    if ( $expected_item_keys !== array_keys( $item ) ) {
        fwrite( STDERR, "Kiln post discovery returned an unexpected item contract.\n" );
        exit( 1 );
    }

    if ( "private" === $item["status"] || false !== strpos( $item["title"], "Kiln private fixture" ) ) {
        fwrite( STDERR, "Kiln post discovery exposed a private post.\n" );
        exit( 1 );
    }
}
foreach ( $drafts["items"] as $item ) {
    $draft_titles[] = $item["title"];
}
if ( ! in_array( "Kiln author draft fixture", $draft_titles, true ) || in_array( "Kiln admin draft fixture", $draft_titles, true ) ) {
    fwrite( STDERR, "Author draft discovery did not enforce ownership.\n" );
    exit( 1 );
}
'

wp eval --allow-root --path="$wp_path" --user=kiln-admin '
$ability = wp_get_ability( "kiln/read-post" );
$post_id = (int) get_option( "kiln_mcp_fixture_published_id" );
$result  = null === $ability ? null : $ability->execute( array( "post_id" => $post_id ) );
$expected_keys = array( "id", "title", "status", "modified_at", "permalink", "content_text", "content_truncated", "untrusted_data_notice" );

if ( is_wp_error( $result ) || $expected_keys !== array_keys( $result ) || $post_id !== $result["id"] || false !== strpos( $result["content_text"], "<" ) || false !== strpos( $result["content_text"], "[gallery]" ) || true !== str_contains( $result["untrusted_data_notice"], "untrusted data" ) ) {
    fwrite( STDERR, "Kiln post read did not return the required redacted contract.\n" );
    exit( 1 );
}

$private_id = (int) get_option( "kiln_mcp_fixture_private_id" );
$private_result = $ability->execute( array( "post_id" => $private_id ) );
if ( ! is_wp_error( $private_result ) ) {
    fwrite( STDERR, "Kiln post read exposed a private post.\n" );
    exit( 1 );
}
'

wp eval --allow-root --path="$wp_path" --user=kiln-author '
$ability = wp_get_ability( "kiln/read-post" );
$own_id  = (int) get_option( "kiln_mcp_fixture_author_draft_id" );
$result  = null === $ability ? null : $ability->execute( array( "post_id" => $own_id ) );
if ( is_wp_error( $result ) || $own_id !== $result["id"] ) {
    fwrite( STDERR, "Author could not read an owned draft.\n" );
    exit( 1 );
}
'

wp eval --allow-root --path="$wp_path" --user=kiln-subscriber '
foreach ( array( "kiln/site-health-summary", "kiln/list-posts", "kiln/read-post" ) as $ability_name ) {
    $ability = wp_get_ability( $ability_name );
    $result  = null === $ability ? null : $ability->execute(
        "kiln/read-post" === $ability_name
            ? array( "post_id" => 1 )
            : ( "kiln/list-posts" === $ability_name
            ? array(
                "status" => "publish",
                "limit"  => 1,
                "page"   => 1,
            )
            : array() )
    );

    if ( ! is_wp_error( $result ) ) {
        fwrite( STDERR, "Subscriber unexpectedly executed " . $ability_name . ".\n" );
        exit( 1 );
    }
}
'

wp eval --allow-root --path="$wp_path" --user=kiln-admin '
$receipts = get_option( "kiln_mcp_audit_receipts", array() );
$expected_keys = array(
    "schema_version",
    "timestamp",
    "ability",
    "outcome",
    "caller_capability_class",
    "result_count",
);
$has_allowed = false;
$has_denied  = false;

if ( ! is_array( $receipts ) ) {
    fwrite( STDERR, "Kiln audit receipts are not stored as an array.\n" );
    exit( 1 );
}

foreach ( $receipts as $receipt ) {
    if ( $expected_keys !== array_keys( $receipt ) ) {
        fwrite( STDERR, "Kiln audit receipt was not redacted to its contract.\n" );
        exit( 1 );
    }
    if ( "kiln/list-posts" === $receipt["ability"] && "allowed" === $receipt["outcome"] ) {
        $has_allowed = true;
    }
    if ( "kiln/list-posts" === $receipt["ability"] && "denied" === $receipt["outcome"] ) {
        $has_denied = true;
    }
}

if ( ! $has_allowed || ! $has_denied ) {
    fwrite( STDERR, "Kiln audit receipts did not record discovery outcomes.\n" );
    exit( 1 );
}
'

echo "Container smoke checks passed."
