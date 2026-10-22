<?php

declare(strict_types=1);

$required_files = array(
    'specs/registry.yml',
    'specs/requirements/m0-foundation.md',
    'docs/constitution.md',
    'docs/architecture.md',
    'docs/threat-model.md',
    'docs/testing-strategy.md',
    'docs/manual-testing.md',
);

foreach ( $required_files as $file ) {
    if ( ! is_file( dirname( __DIR__ ) . '/' . $file ) ) {
        fwrite( STDERR, "Missing required foundation artifact: {$file}\\n" );
        exit( 1 );
    }
}

$registry = file_get_contents( dirname( __DIR__ ) . '/specs/registry.yml' );
if ( false === $registry || ! str_contains( $registry, 'REQ-M0-001' ) ) {
    fwrite( STDERR, "Specification registry does not include the M0 baseline.\\n" );
    exit( 1 );
}

echo "Specification checks passed.\\n";
