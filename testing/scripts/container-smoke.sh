#!/bin/sh
set -eu

wp_path=/var/www/html

wp core is-installed --allow-root --path="$wp_path"
wp plugin is-active mcp-adapter --allow-root --path="$wp_path"
wp plugin is-active kiln-mcp --allow-root --path="$wp_path"

stdio_diagnostics="$( mktemp )"
stdio_response="$(
    printf '%s\n' \
        '{"jsonrpc":"2.0","id":7,"method":"initialize","params":{"protocolVersion":"2025-11-25","capabilities":{},"clientInfo":{"name":"kiln-mcp-test","version":"1.0.0"}}}' \
        '{"jsonrpc":"2.0","method":"notifications/initialized","params":{}}' \
        '{"jsonrpc":"2.0","id":8,"method":"tools/list","params":{}}' | \
        wp mcp-adapter serve --allow-root --path="$wp_path" --server=kiln-mcp --user=kiln-admin 2>"$stdio_diagnostics"
)"
if ! printf '%s' "$stdio_response" | php -r '
$lines = preg_split( "/\\R/", trim( stream_get_contents( STDIN ) ) );
foreach ( $lines as $line ) {
    $response = json_decode( $line, true );
    if ( is_array( $response ) && 8 === ( $response["id"] ?? null ) && isset( $response["result"]["tools"] ) && is_array( $response["result"]["tools"] ) ) {
        exit( 0 );
    }
}
exit( 1 );
'; then
    cat "$stdio_diagnostics" >&2
    rm -f "$stdio_diagnostics"
    echo "Kiln M8 STDIO transport did not return a JSON-RPC tools response." >&2
    exit 1
fi
rm -f "$stdio_diagnostics"

http_password="$( wp user application-password create kiln-author "Kiln M8 disposable HTTP test" --porcelain --allow-root --path="$wp_path" | tr -d ' ' )"
if ! KILN_MCP_HTTP_PASSWORD="$http_password" php <<'PHP'
<?php
declare(strict_types=1);

function kiln_mcp_http_request( ?string $authorization ): array {
    $headers = array( 'Content-Type: application/json' );
    if ( null !== $authorization ) {
        $headers[] = 'Authorization: ' . $authorization;
    }

    $context = stream_context_create(
        array(
            'http' => array(
                'method'        => 'POST',
                'header'        => implode( "\r\n", $headers ),
                'content'       => json_encode(
                    array(
                        'jsonrpc' => '2.0',
                        'id'      => 9,
                        'method'  => 'initialize',
                        'params'  => array(
                            'protocolVersion' => '2025-11-25',
                            'capabilities'    => array(),
                            'clientInfo'      => array(
                                'name'    => 'kiln-mcp-test',
                                'version' => '1.0.0',
                            ),
                        ),
                    )
                ),
                'ignore_errors' => true,
                'timeout'       => 10,
            ),
        )
    );

    $body    = file_get_contents( 'http://wordpress/wp-json/kiln-mcp/mcp', false, $context );
    $headers = $http_response_header ?? array();

    return array( $body, $headers );
}

list( $unauthenticated_body, $unauthenticated_headers ) = kiln_mcp_http_request( null );
$unauthenticated_status = $unauthenticated_headers[0] ?? '';
$unauthenticated_response = is_string( $unauthenticated_body ) ? json_decode( $unauthenticated_body, true ) : null;
if ( ! is_array( $unauthenticated_response ) || ! isset( $unauthenticated_response['error'] ) ) {
    $response_shape = is_array( $unauthenticated_response )
        ? implode( ',', array_keys( $unauthenticated_response ) )
        : gettype( $unauthenticated_response );
    file_put_contents( 'php://stderr', 'Kiln M8 unauthenticated request was not refused; HTTP status=' . $unauthenticated_status . '; response-shape=' . $response_shape . PHP_EOL );
    exit( 1 );
}
foreach ( $unauthenticated_headers as $header ) {
    if ( 'access-control-allow-origin: *' === strtolower( $header ) ) {
        file_put_contents( 'php://stderr', 'Kiln M8 refused wildcard CORS response header.' . PHP_EOL );
        exit( 1 );
    }
}

$password = getenv( 'KILN_MCP_HTTP_PASSWORD' );
if ( ! is_string( $password ) || '' === $password ) {
    exit( 1 );
}
$authorization = 'Basic ' . base64_encode( 'kiln-author:' . $password );
list( $authenticated_body, $authenticated_headers ) = kiln_mcp_http_request( $authorization );
$authenticated_status = $authenticated_headers[0] ?? '';
$response = is_string( $authenticated_body ) ? json_decode( $authenticated_body, true ) : null;
if ( ! is_string( $authenticated_status ) || ! preg_match( '/ HTTP\/1\.1 2[0-9][0-9] /', $authenticated_status ) || ! is_array( $response ) || 9 !== ( $response['id'] ?? null ) || ! isset( $response['result'] ) ) {
    file_put_contents( 'php://stderr', 'Kiln M8 HTTP statuses: unauthenticated=' . $unauthenticated_status . '; authenticated=' . $authenticated_status . PHP_EOL );
    exit( 1 );
}
PHP
then
    echo "Kiln M8 HTTP transport did not satisfy its authenticated contract." >&2
    exit 1
fi

wp eval --allow-root --path="$wp_path" --user=kiln-admin '
$ability = wp_get_ability( "kiln/site-health-summary" );
if ( null === $ability ) {
    file_put_contents( 'php://stderr', "Kiln site health summary was not registered.\n" );
    exit( 1 );
}

$result = $ability->execute( array() );
if ( is_wp_error( $result ) ) {
    file_put_contents( 'php://stderr', "Administrator could not execute Kiln site health summary.\n" );
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
    file_put_contents( 'php://stderr', "Kiln site health summary returned an unexpected contract.\n" );
    exit( 1 );
}
'


wp eval --allow-root --path="$wp_path" --user=kiln-admin '
$ability = wp_get_ability( "kiln-ext/fixture--facts" );
$result = null === $ability ? null : $ability->execute( array() );
if ( is_wp_error( $result ) || array( "status" ) !== array_keys( $result ) || "available" !== $result["status"] ) {
    file_put_contents( 'php://stderr', "Administrator could not execute the admitted M7 extension.\\n" );
    exit( 1 );
}

if ( null !== wp_get_ability( "kiln-ext/failed-fixture--partial" ) ) {
    file_put_contents( 'php://stderr', "A throwing M7 extension admitted a partial ability.\\n" );
    exit( 1 );
}
'

wp eval --allow-root --path="$wp_path" --user=kiln-author '
$ability = wp_get_ability( "kiln/list-posts" );
if ( null === $ability ) {
    file_put_contents( 'php://stderr', "Kiln post discovery was not registered.\n" );
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
    file_put_contents( 'php://stderr', "Author could not list published posts: " . $published->get_error_code() . ": " . $published->get_error_message() . "\n" );
    exit( 1 );
}
if ( is_wp_error( $drafts ) ) {
    file_put_contents( 'php://stderr', "Author could not list drafts: " . $drafts->get_error_code() . ": " . $drafts->get_error_message() . "\n" );
    exit( 1 );
}

$expected_item_keys = array( "id", "title", "status", "modified_at", "permalink" );
if ( count( $published["items"] ) > 20 || count( $drafts["items"] ) > 20 ) {
    file_put_contents( 'php://stderr', "Kiln post discovery exceeded its result bound.\n" );
    exit( 1 );
}

$draft_titles = array();
foreach ( array_merge( $published["items"], $drafts["items"] ) as $item ) {
    if ( $expected_item_keys !== array_keys( $item ) ) {
        file_put_contents( 'php://stderr', "Kiln post discovery returned an unexpected item contract.\n" );
        exit( 1 );
    }

    if ( "private" === $item["status"] || false !== strpos( $item["title"], "Kiln private fixture" ) ) {
        file_put_contents( 'php://stderr', "Kiln post discovery exposed a private post.\n" );
        exit( 1 );
    }
}
foreach ( $drafts["items"] as $item ) {
    $draft_titles[] = $item["title"];
}
if ( ! in_array( "Kiln author draft fixture", $draft_titles, true ) || in_array( "Kiln admin draft fixture", $draft_titles, true ) ) {
    file_put_contents( 'php://stderr', "Author draft discovery did not enforce ownership.\n" );
    exit( 1 );
}
'

wp eval --allow-root --path="$wp_path" --user=kiln-admin '
$ability = wp_get_ability( "kiln/read-post" );
$post_id = (int) get_option( "kiln_mcp_fixture_published_id" );
$result  = null === $ability ? null : $ability->execute( array( "post_id" => $post_id ) );
$expected_keys = array( "id", "title", "status", "modified_at", "modified_token", "permalink", "content_text", "content_truncated", "untrusted_data_notice" );

if ( is_wp_error( $result ) || $expected_keys !== array_keys( $result ) || $post_id !== $result["id"] || false !== strpos( $result["content_text"], "<" ) || false !== strpos( $result["content_text"], "[gallery]" ) || true !== str_contains( $result["untrusted_data_notice"], "untrusted data" ) ) {
    file_put_contents( 'php://stderr', "Kiln post read did not return the required redacted contract.\n" );
    exit( 1 );
}

$private_id = (int) get_option( "kiln_mcp_fixture_private_id" );
$private_result = $ability->execute( array( "post_id" => $private_id ) );
if ( ! is_wp_error( $private_result ) ) {
    file_put_contents( 'php://stderr', "Kiln post read exposed a private post.\n" );
    exit( 1 );
}
'

wp eval --allow-root --path="$wp_path" --user=kiln-author '
$ability = wp_get_ability( "kiln/read-post" );
$own_id  = (int) get_option( "kiln_mcp_fixture_author_draft_id" );
$result  = null === $ability ? null : $ability->execute( array( "post_id" => $own_id ) );
if ( is_wp_error( $result ) || $own_id !== $result["id"] ) {
    file_put_contents( 'php://stderr', "Author could not read an owned draft.\n" );
    exit( 1 );
}
'

wp eval --allow-root --path="$wp_path" --user=kiln-author '
$create = wp_get_ability( "kiln/create-post-draft" );
$update = wp_get_ability( "kiln/update-post-draft" );
if ( null === $create || null === $update ) {
    file_put_contents( 'php://stderr', "Kiln M4 draft authoring abilities were not registered.\\n" );
    exit( 1 );
}

$created = $create->execute(
    array(
        "title"   => "Kiln author created draft",
        "content" => "First authoring body",
    )
);
$expected_keys = array( "id", "title", "status", "modified_at", "modified_token", "permalink" );
if ( is_wp_error( $created ) || $expected_keys !== array_keys( $created ) || "draft" !== $created["status"] || get_current_user_id() !== (int) get_post_field( "post_author", $created["id"] ) ) {
    file_put_contents( 'php://stderr', "Author could not create an owned Kiln draft.\\n" );
    exit( 1 );
}

$updated = $update->execute(
    array(
        "post_id"                 => $created["id"],
        "expected_modified_token" => $created["modified_token"],
        "title"                   => "Kiln author updated draft",
        "content"                 => "Second authoring body",
    )
);
if ( is_wp_error( $updated ) || "draft" !== $updated["status"] || $created["modified_token"] === $updated["modified_token"] ) {
    file_put_contents( 'php://stderr', "Author could not update a current draft version.\\n" );
    exit( 1 );
}

$stale = $update->execute(
    array(
        "post_id"                 => $created["id"],
        "expected_modified_token" => $created["modified_token"],
        "title"                   => "This stale write must not apply",
        "content"                 => "This stale body must not apply",
    )
);
if ( ! is_wp_error( $stale ) || "Kiln author updated draft" !== get_post_field( "post_title", $created["id"] ) ) {
    file_put_contents( 'php://stderr', "Kiln draft authoring did not refuse a stale update.\\n" );
    exit( 1 );
}

$invalid = $create->execute(
    array(
        "title"   => "Unsafe draft",
        "content" => "<script>alert(1)</script>",
    )
);
if ( ! is_wp_error( $invalid ) ) {
    file_put_contents( 'php://stderr', "Kiln draft authoring accepted markup-like content.\\n" );
    exit( 1 );
}

update_option( "kiln_mcp_m4_author_draft_id", $created["id"], false );
'

wp eval --allow-root --path="$wp_path" --user=kiln-editor '
$read   = wp_get_ability( "kiln/read-post" );
$update = wp_get_ability( "kiln/update-post-draft" );
$post_id = (int) get_option( "kiln_mcp_m4_author_draft_id" );
$current = null === $read ? null : $read->execute( array( "post_id" => $post_id ) );
$updated = null === $update || is_wp_error( $current ) ? null : $update->execute(
    array(
        "post_id"                 => $post_id,
        "expected_modified_token" => $current["modified_token"],
        "title"                   => "Kiln editor updated draft",
        "content"                 => "Editor authoring body",
    )
);
if ( is_wp_error( $current ) || is_wp_error( $updated ) || "draft" !== $updated["status"] || "Kiln editor updated draft" !== get_post_field( "post_title", $post_id ) ) {
    file_put_contents( 'php://stderr', "Editor could not update an admitted author draft.\\n" );
    exit( 1 );
}
'

wp eval --allow-root --path="$wp_path" --user=kiln-author '
$create   = wp_get_ability( "kiln/create-post-draft" );
$publish  = wp_get_ability( "kiln/publish-post" );
$schedule = wp_get_ability( "kiln/schedule-post-publication" );
$trash    = wp_get_ability( "kiln/trash-post" );
$restore  = wp_get_ability( "kiln/restore-post" );
$store    = new \KilnMcp\Infrastructure\WordPress\WorkflowApprovalStore();

if ( null === $create || null === $publish || null === $schedule || null === $trash || null === $restore ) {
    file_put_contents( 'php://stderr', "Kiln M5 workflow abilities were not registered.\n" );
    exit( 1 );
}

$published_draft = $create->execute(
    array(
        "title"   => "Kiln M5 publish fixture",
        "content" => "Publish fixture body",
    )
);
if ( is_wp_error( $published_draft ) ) {
    file_put_contents( 'php://stderr', "Could not create the M5 publish fixture.\n" );
    exit( 1 );
}

$publish_approval = $store->issue( "publish", $published_draft["id"], $published_draft["modified_token"] );
$published = $publish->execute(
    array(
        "post_id"                 => $published_draft["id"],
        "expected_modified_token" => $published_draft["modified_token"],
        "approval_token"          => $publish_approval,
    )
);
$expected_keys = array( "id", "title", "status", "modified_at", "modified_token", "permalink" );
if ( is_wp_error( $published ) || $expected_keys !== array_keys( $published ) || "publish" !== $published["status"] || $published_draft["modified_token"] === $published["modified_token"] ) {
    file_put_contents( 'php://stderr', "Kiln M5 did not publish one approved current draft.\n" );
    exit( 1 );
}

$publish_replay = $publish->execute(
    array(
        "post_id"                 => $published_draft["id"],
        "expected_modified_token" => $published_draft["modified_token"],
        "approval_token"          => $publish_approval,
    )
);
if ( ! is_wp_error( $publish_replay ) || "publish" !== get_post_status( $published_draft["id"] ) ) {
    file_put_contents( 'php://stderr', "Kiln M5 did not refuse approval replay.\n" );
    exit( 1 );
}

$trash_approval = $store->issue( "trash", $published["id"], $published["modified_token"] );
$trashed = $trash->execute(
    array(
        "post_id"                 => $published["id"],
        "expected_modified_token" => $published["modified_token"],
        "approval_token"          => $trash_approval,
    )
);
if ( is_wp_error( $trashed ) || "trash" !== $trashed["status"] ) {
    file_put_contents( 'php://stderr', "Kiln M5 did not trash an approved published post.\n" );
    exit( 1 );
}

$restore_approval = $store->issue( "restore", $trashed["id"], $trashed["modified_token"] );
$restored = $restore->execute(
    array(
        "post_id"                 => $trashed["id"],
        "expected_modified_token" => $trashed["modified_token"],
        "approval_token"          => $restore_approval,
    )
);
if ( is_wp_error( $restored ) || "draft" !== $restored["status"] ) {
    file_put_contents( 'php://stderr', "Kiln M5 did not restore an approved trashed post as a draft.\n" );
    exit( 1 );
}

$scheduled_draft = $create->execute(
    array(
        "title"   => "Kiln M5 schedule fixture",
        "content" => "Schedule fixture body",
    )
);
$publish_at = gmdate( "c", time() + HOUR_IN_SECONDS );
$schedule_approval = $store->issue( "schedule", $scheduled_draft["id"], $scheduled_draft["modified_token"] );
$scheduled = $schedule->execute(
    array(
        "post_id"                 => $scheduled_draft["id"],
        "expected_modified_token" => $scheduled_draft["modified_token"],
        "approval_token"          => $schedule_approval,
        "publish_at"              => $publish_at,
    )
);
if ( is_wp_error( $scheduled ) || "future" !== $scheduled["status"] || get_post_time( "c", true, $scheduled["id"] ) !== $publish_at ) {
    file_put_contents( 'php://stderr', "Kiln M5 did not schedule one approved draft at the requested UTC time.\n" );
    exit( 1 );
}

$records = get_option( "kiln_mcp_workflow_approvals", array() );
if ( ! is_array( $records ) || false !== strpos( serialize( $records ), $publish_approval ) ) {
    file_put_contents( 'php://stderr', "Kiln M5 approval storage retained a raw approval token.\n" );
    exit( 1 );
}
'

wp eval --allow-root --path="$wp_path" --user=kiln-admin '
$abilities = array(
    "kiln/list-supported-post-types",
    "kiln/list-supported-taxonomies",
    "kiln/list-navigation-menus",
    "kiln/list-categories",
    "kiln/list-tags",
    "kiln/create-category",
    "kiln/create-tag",
);
foreach ( $abilities as $ability_name ) {
    if ( null === wp_get_ability( $ability_name ) ) {
        file_put_contents( 'php://stderr', "Kiln M6 ability was not registered: " . $ability_name . "\n" );
        exit( 1 );
    }
}

$post_types = wp_get_ability( "kiln/list-supported-post-types" )->execute( array() );
$taxonomies = wp_get_ability( "kiln/list-supported-taxonomies" )->execute( array() );
$menus = wp_get_ability( "kiln/list-navigation-menus" )->execute( array() );
$categories = wp_get_ability( "kiln/list-categories" )->execute( array( "limit" => 20, "page" => 1 ) );
$tags = wp_get_ability( "kiln/list-tags" )->execute( array( "limit" => 20, "page" => 1 ) );

foreach ( array( $post_types, $taxonomies, $menus, $categories, $tags ) as $result ) {
    if ( is_wp_error( $result ) || array( "items" ) !== array_keys( $result ) || count( $result["items"] ) > 20 ) {
        file_put_contents( 'php://stderr', "Kiln M6 discovery did not return its bounded list contract.\n" );
        exit( 1 );
    }
}
foreach ( $post_types["items"] as $item ) {
    if ( array( "name", "label", "hierarchical" ) !== array_keys( $item ) ) {
        file_put_contents( 'php://stderr', "Kiln M6 post-type discovery leaked an unexpected field.\n" );
        exit( 1 );
    }
}
foreach ( $taxonomies["items"] as $item ) {
    if ( array( "name", "label", "hierarchical" ) !== array_keys( $item ) ) {
        file_put_contents( 'php://stderr', "Kiln M6 taxonomy discovery leaked an unexpected field.\n" );
        exit( 1 );
    }
}
foreach ( $menus["items"] as $item ) {
    if ( array( "id", "name", "item_count" ) !== array_keys( $item ) ) {
        file_put_contents( 'php://stderr', "Kiln M6 menu discovery leaked items, URLs, or locations.\n" );
        exit( 1 );
    }
}
foreach ( array_merge( $categories["items"], $tags["items"] ) as $item ) {
    if ( array( "id", "name", "taxonomy", "parent_id", "count" ) !== array_keys( $item ) ) {
        file_put_contents( 'php://stderr', "Kiln M6 term discovery leaked an unexpected field.\n" );
        exit( 1 );
    }
}

$create_category = wp_get_ability( "kiln/create-category" );
$create_tag      = wp_get_ability( "kiln/create-tag" );
$root = $create_category->execute( array( "name" => "Kiln M6 root", "parent_id" => 0 ) );
if ( is_wp_error( $root ) || "category" !== $root["taxonomy"] || 0 !== $root["parent_id"] ) {
    file_put_contents( 'php://stderr', "Kiln M6 could not seed one root category.\n" );
    exit( 1 );
}
$root_replay = $create_category->execute( array( "name" => "Kiln M6 root", "parent_id" => 0 ) );
if ( is_wp_error( $root_replay ) || $root["id"] !== $root_replay["id"] ) {
    file_put_contents( 'php://stderr', "Kiln M6 category seeding was not idempotent.\n" );
    exit( 1 );
}
$child = $create_category->execute( array( "name" => "Kiln M6 child", "parent_id" => $root["id"] ) );
if ( is_wp_error( $child ) || $root["id"] !== $child["parent_id"] ) {
    file_put_contents( 'php://stderr', "Kiln M6 could not seed one admitted child category.\n" );
    exit( 1 );
}
$tag = $create_tag->execute( array( "name" => "Kiln M6 tag" ) );
$tag_replay = $create_tag->execute( array( "name" => "Kiln M6 tag" ) );
if ( is_wp_error( $tag ) || is_wp_error( $tag_replay ) || "post_tag" !== $tag["taxonomy"] || $tag["id"] !== $tag_replay["id"] ) {
    file_put_contents( 'php://stderr', "Kiln M6 tag seeding was not idempotent.\n" );
    exit( 1 );
}

foreach ( array(
    $create_category->execute( array( "name" => "<script>", "parent_id" => 0 ) ),
    $create_category->execute( array( "name" => "Kiln invalid parent", "parent_id" => 999999 ) ),
    $create_tag->execute( array( "name" => "Kiln invalid", "description" => "not allowed" ) ),
    wp_get_ability( "kiln/list-categories" )->execute( array( "limit" => 21, "page" => 1 ) ),
    wp_get_ability( "kiln/list-navigation-menus" )->execute( array( "unexpected" => true ) ),
) as $rejected ) {
    if ( ! is_wp_error( $rejected ) ) {
        file_put_contents( 'php://stderr', "Kiln M6 accepted an out-of-scope structure request.\n" );
        exit( 1 );
    }
}
'

wp eval --allow-root --path="$wp_path" --user=kiln-subscriber '
foreach ( array( "kiln/site-health-summary", "kiln/list-posts", "kiln/read-post", "kiln/create-post-draft", "kiln/update-post-draft", "kiln/publish-post", "kiln/schedule-post-publication", "kiln/trash-post", "kiln/restore-post", "kiln/list-supported-post-types", "kiln/list-supported-taxonomies", "kiln/list-navigation-menus", "kiln/list-categories", "kiln/list-tags", "kiln/create-category", "kiln/create-tag", "kiln-ext/fixture--facts" ) as $ability_name ) {
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
            : ( "kiln/create-post-draft" === $ability_name
            ? array(
                "title"   => "Subscriber denied draft",
                "content" => "Denied body",
            )
            : ( "kiln/update-post-draft" === $ability_name
            ? array(
                "post_id"                 => 1,
                "expected_modified_token" => str_repeat( "a", 64 ),
                "title"                   => "Subscriber denied update",
                "content"                 => "Denied body",
            )
            : array() ) ) )
    );

    if ( ! is_wp_error( $result ) ) {
        file_put_contents( 'php://stderr', "Subscriber unexpectedly executed " . $ability_name . ".\n" );
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
    file_put_contents( 'php://stderr', "Kiln audit receipts are not stored as an array.\n" );
    exit( 1 );
}

foreach ( $receipts as $receipt ) {
    if ( $expected_keys !== array_keys( $receipt ) ) {
        file_put_contents( 'php://stderr', "Kiln audit receipt was not redacted to its contract.\n" );
        exit( 1 );
    }
    if ( ( "kiln/list-posts" === $receipt["ability"] || "kiln/create-post-draft" === $receipt["ability"] || "kiln/update-post-draft" === $receipt["ability"] || "kiln/list-categories" === $receipt["ability"] || "kiln/create-category" === $receipt["ability"] ) && "allowed" === $receipt["outcome"] ) {
        $has_allowed = true;
    }
    if ( ( "kiln/list-posts" === $receipt["ability"] || "kiln/create-post-draft" === $receipt["ability"] || "kiln/update-post-draft" === $receipt["ability"] || "kiln/list-categories" === $receipt["ability"] || "kiln/create-category" === $receipt["ability"] ) && "denied" === $receipt["outcome"] ) {
        $has_denied = true;
    }
}

if ( false !== strpos( serialize( $receipts ), "fixture" ) || false !== strpos( serialize( $receipts ), "failed-fixture" ) ) {
    file_put_contents( 'php://stderr', "Kiln M7 audit receipts retained extension identity.\n" );
    exit( 1 );
}

if ( ! $has_allowed || ! $has_denied ) {
    file_put_contents( 'php://stderr', "Kiln audit receipts did not record discovery outcomes.\n" );
    exit( 1 );
}
'

echo "Container smoke checks passed."
