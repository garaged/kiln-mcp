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
$expected_keys = array( "id", "title", "status", "modified_at", "modified_token", "permalink", "content_text", "content_truncated", "untrusted_data_notice" );

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

wp eval --allow-root --path="$wp_path" --user=kiln-author '
$create = wp_get_ability( "kiln/create-post-draft" );
$update = wp_get_ability( "kiln/update-post-draft" );
if ( null === $create || null === $update ) {
    fwrite( STDERR, "Kiln M4 draft authoring abilities were not registered.\\n" );
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
    fwrite( STDERR, "Author could not create an owned Kiln draft.\\n" );
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
    fwrite( STDERR, "Author could not update a current draft version.\\n" );
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
    fwrite( STDERR, "Kiln draft authoring did not refuse a stale update.\\n" );
    exit( 1 );
}

$invalid = $create->execute(
    array(
        "title"   => "Unsafe draft",
        "content" => "<script>alert(1)</script>",
    )
);
if ( ! is_wp_error( $invalid ) ) {
    fwrite( STDERR, "Kiln draft authoring accepted markup-like content.\\n" );
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
    fwrite( STDERR, "Editor could not update an admitted author draft.\\n" );
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
    fwrite( STDERR, "Kiln M5 workflow abilities were not registered.\n" );
    exit( 1 );
}

$published_draft = $create->execute(
    array(
        "title"   => "Kiln M5 publish fixture",
        "content" => "Publish fixture body",
    )
);
if ( is_wp_error( $published_draft ) ) {
    fwrite( STDERR, "Could not create the M5 publish fixture.\n" );
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
    fwrite( STDERR, "Kiln M5 did not publish one approved current draft.\n" );
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
    fwrite( STDERR, "Kiln M5 did not refuse approval replay.\n" );
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
    fwrite( STDERR, "Kiln M5 did not trash an approved published post.\n" );
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
    fwrite( STDERR, "Kiln M5 did not restore an approved trashed post as a draft.\n" );
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
    fwrite( STDERR, "Kiln M5 did not schedule one approved draft at the requested UTC time.\n" );
    exit( 1 );
}

$records = get_option( "kiln_mcp_workflow_approvals", array() );
if ( ! is_array( $records ) || false !== strpos( serialize( $records ), $publish_approval ) ) {
    fwrite( STDERR, "Kiln M5 approval storage retained a raw approval token.\n" );
    exit( 1 );
}
'

wp eval --allow-root --path="$wp_path" --user=kiln-subscriber '
foreach ( array( "kiln/site-health-summary", "kiln/list-posts", "kiln/read-post", "kiln/create-post-draft", "kiln/update-post-draft", "kiln/publish-post", "kiln/schedule-post-publication", "kiln/trash-post", "kiln/restore-post" ) as $ability_name ) {
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
    if ( ( "kiln/list-posts" === $receipt["ability"] || "kiln/create-post-draft" === $receipt["ability"] || "kiln/update-post-draft" === $receipt["ability"] ) && "allowed" === $receipt["outcome"] ) {
        $has_allowed = true;
    }
    if ( ( "kiln/list-posts" === $receipt["ability"] || "kiln/create-post-draft" === $receipt["ability"] || "kiln/update-post-draft" === $receipt["ability"] ) && "denied" === $receipt["outcome"] ) {
        $has_denied = true;
    }
}

if ( ! $has_allowed || ! $has_denied ) {
    fwrite( STDERR, "Kiln audit receipts did not record discovery outcomes.\n" );
    exit( 1 );
}
'

echo "Container smoke checks passed."
