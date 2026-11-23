<?php
/**
 * Plugin Name: RDB Bindings Demo (E2E only)
 * Description: Deterministic local queries and filter-enabled blocks. Never ship this test fixture.
 */

declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\E2E;

use RemoteDataBlocks\Config\DataSource\HttpDataSource;
use RemoteDataBlocks\Config\QueryRunner\QueryRunner;
use WP_Error;

defined( 'ABSPATH' ) || exit();

add_action( 'init', static function (): void {
	if ( ! function_exists( 'register_remote_data_block' ) ) {
		return;
	}

	// No request ever leaves WordPress: preserve the real parser, schema and query pipeline.
	$query_runner = new class() extends QueryRunner {
		// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Override must match QueryRunner; values are intentionally deterministic.
		protected function get_raw_response_data( array $request_details, array $input_variables ): array|WP_Error {
			return [
				'input_variables' => $input_variables,
				'metadata' => [
					'status_code' => 200,
					'age' => 0,
				],
				'response_data' => [
					'title' => 'Local garden collection',
					'caption' => 'Fresh data, familiar blocks.',
					'date' => '2026-11-23T12:00:00',
					'button_url' => 'https://example.org/garden',
					'plain_url' => 'https://example.org/garden/plain-string',
					'button_text' => 'Explore the garden',
					'image_url' => plugins_url( 'garden.svg', __FILE__ ),
					'image_alt' => 'A deterministic illustrated garden',
					'quantity' => 0,
					'available' => false,
				],
			];
		}
	};

	$fields = [
		'title' => [
			'name' => 'Garden title',
			'type' => 'title',
		],
		'caption' => [
			'name' => 'Garden caption',
			'type' => 'string',
		],
		'date' => [
			'name' => 'Garden date',
			'type' => 'string',
		],
		'button_url' => [
			'name' => 'Garden link',
			'type' => 'button_url',
		],
		'plain_url' => [
			'name' => 'Garden URL (plain string)',
			'type' => 'string',
		],
		'button_text' => [
			'name' => 'Garden button text',
			'type' => 'button_text',
		],
		'image_url' => [
			'name' => 'Garden illustration',
			'type' => 'image_url',
		],
		'image_alt' => [
			'name' => 'Garden image description',
			'type' => 'image_alt',
		],
		'quantity' => [
			'name' => 'Remaining plants',
			'type' => 'integer',
		],
		'available' => [
			'name' => 'Available today',
			'type' => 'boolean',
		],
	];
	foreach ( $fields as $key => &$field ) {
		$field['path'] = '$.' . $key;
	}
	unset( $field );

	register_remote_data_block( [
		'title' => 'Bindings Garden',
		'render_query' => [
			'query' => [
				'data_source' => HttpDataSource::from_array( [
					'__version' => 1,
					'display_name' => 'Local bindings fixture',
					'endpoint' => 'https://example.invalid/never-requested',
				] ),
				'query_runner' => $query_runner,
				'output_schema' => [
					'is_collection' => false,
					'type' => $fields,
				],
			],
		],
	] );

	wp_register_script(
		'rdb-bindings-demo-editor',
		plugins_url( 'editor.js', __FILE__ ),
		[ 'wp-blocks', 'wp-block-editor', 'wp-element', 'wp-i18n' ],
		(string) filemtime( __DIR__ . '/editor.js' ),
		true
	);

	wp_add_inline_script( 'rdb-bindings-demo-editor', 'window.RDB_BINDINGS_DEMO = ' . wp_json_encode( [ 'wordpressVersion' => get_bloginfo( 'version' ) ] ) . ';', 'before' );

	/** @psalm-suppress InvalidArgument WordPress api_version is an integer; the register_block_type stub incorrectly declares string. */
	register_block_type( 'rdb-bindings-demo/static-card', [
		'api_version' => 3,
		'editor_script_handles' => [ 'rdb-bindings-demo-editor' ],
		'attributes' => [
			'botanicalNote' => [
				'type' => 'string',
				'source' => 'html',
				'selector' => 'p',
				'default' => 'Choose a remote field',
			],
		],
	] );

	/** @psalm-suppress InvalidArgument WordPress api_version is an integer; the register_block_type stub incorrectly declares string. */
	register_block_type( 'rdb-bindings-demo/stock-card', [
		'api_version' => 3,
		'editor_script_handles' => [ 'rdb-bindings-demo-editor' ],
		'attributes' => [
			'plantCount' => [
				'type' => 'number',
				'default' => 12,
			],
			'isAvailable' => [
				'type' => 'boolean',
				'default' => true,
			],
		],
		'render_callback' => static function ( array $attributes ): string {
			return sprintf(
				'<div class="wp-block-rdb-bindings-demo-stock-card"><p>Remaining plants: <strong class="plant-count">%s</strong></p><p>Available today: <strong class="plant-available">%s</strong></p></div>',
				esc_html( (string) $attributes['plantCount'] ),
				$attributes['isAvailable'] ? 'true' : 'false'
			);
		},
	] );
} );

// Both official WordPress extension points are intentionally exercised.
add_filter( 'block_bindings_supported_attributes', static function ( array $attributes, ?string $block_type ): array {
	if ( 'rdb-bindings-demo/static-card' === $block_type ) {
		$attributes[] = 'botanicalNote';
	}
	return $attributes;
}, 10, 2 );

add_filter( 'block_bindings_supported_attributes_rdb-bindings-demo/stock-card', static function ( array $attributes ): array {
	return array_merge( $attributes, [ 'plantCount', 'isAvailable' ] );
} );
