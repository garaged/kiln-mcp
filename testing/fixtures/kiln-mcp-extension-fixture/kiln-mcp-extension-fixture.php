<?php
/**
 * Plugin Name: Kiln MCP extension fixture
 * Description: Disposable M7 conformance extension.
 * Version: 1.0.0
 * License: GPL-2.0-or-later
 */

declare(strict_types=1);

use KilnMcp\Extension\ExtensionAbility;
use KilnMcp\Extension\ExtensionManifest;
use KilnMcp\Extension\ExtensionRegistry;

add_action(
	'kiln_mcp_register_extensions',
	static function ( ExtensionRegistry $registry ): void {
		$registry->register(
			new ExtensionManifest(
				'fixture',
				'1.0.0',
				array(
					new ExtensionAbility(
						'facts',
						'Fixture facts',
						'Returns a fixed, local M7 conformance result.',
						array(
							'type'                 => 'object',
							'additionalProperties' => false,
							'properties'           => array(),
							'required'             => array(),
						),
						array(
							'type'                 => 'object',
							'additionalProperties' => false,
							'properties'           => array(
								'status' => array( 'type' => 'string' ),
							),
							'required'             => array( 'status' ),
						),
						static fn( array $input ): array => array( 'status' => 'available' )
					),
				)
			)
		);
	}
);

add_action(
	'kiln_mcp_register_extensions',
	static function ( ExtensionRegistry $registry ): void {
		$registry->register(
			new ExtensionManifest(
				'failed-fixture',
				'1.0.0',
				array(
					new ExtensionAbility(
						'partial',
						'Partial fixture',
						'Must never be admitted.',
						array(
							'type'                 => 'object',
							'additionalProperties' => false,
							'properties'           => array(),
							'required'             => array(),
						),
						array(
							'type'                 => 'object',
							'additionalProperties' => false,
							'properties'           => array(),
							'required'             => array(),
						),
						static fn( array $input ): array => array()
					),
				)
			)
		);

		throw new RuntimeException( 'Fixture extension failure.' );
	}
);
