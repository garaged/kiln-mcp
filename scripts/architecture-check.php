<?php

declare(strict_types=1);

$domain_path = dirname( __DIR__ ) . '/src/Domain';
$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $domain_path ) );

foreach ( $iterator as $file ) {
    if ( ! $file instanceof SplFileInfo || 'php' !== $file->getExtension() ) {
        continue;
    }

    $contents = file_get_contents( $file->getPathname() );
    if ( false === $contents ) {
        fwrite( STDERR, "Cannot inspect {$file->getPathname()}\\n" );
        exit( 1 );
    }

    if ( preg_match( '/\\b(add_action|apply_filters|get_option|update_option|wp_|WP_)/', $contents ) ) {
        fwrite( STDERR, "Domain code may not call WordPress APIs: {$file->getPathname()}\\n" );
        exit( 1 );
    }
}

echo "Architecture checks passed.\\n";
