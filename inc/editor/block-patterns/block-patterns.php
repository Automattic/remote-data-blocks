<?php

namespace RemoteDataBlocks\Editor;

defined( 'ABSPATH' ) || exit();

use RemoteDataBlocks\Config\QueryContext;

use function register_block_pattern;

class BlockPatterns {
	private static $templates = [];

	private static function load_templates() {
		if ( ! empty( self::$templates ) ) {
			return;
		}

		self::$templates['heading']   = file_get_contents( __DIR__ . '/templates/heading.html', false );
		self::$templates['image']     = file_get_contents( __DIR__ . '/templates/image.html', false );
		self::$templates['paragraph'] = file_get_contents( __DIR__ . '/templates/paragraph.html', false );
	}

	private static function generate_attribute_bindings( array $bindings ): string {
		$attributes = [
			'metadata' => [
				'bindings' => [],
			],
		];

		foreach ( $bindings as $attribute => $binding ) {
			if ( null === $binding || ! is_array( $binding ) || count( $binding ) !== 2 ) {
				continue;
			}

			$attributes['metadata']['bindings'][ $attribute ] = [
				'source' => 'remote-data/binding',
				'args'   => [
					'field' => $binding[0],
				],
			];

			// TODO: Create a name that reflects multiple bindings (e.g., "image URL + image alt").
			$attributes['metadata']['name'] = $binding[1];
		}

		return wp_json_encode( $attributes );
	}

	public static function register_default_block_pattern( string $block_name, string $block_title, QueryContext $display_query ): void {
		// If there are no mappings, we can't generate a pattern.
		if ( empty( $display_query->output_variables['mappings'] ) ) {
			return;
		}

		self::load_templates();

		// Loop through output variables and generate a pattern. Each text field will
		// result in a paragraph block. If a field name looks like a title, target a
		// single heading block. If a field is an image URL, target a single image block.

		$bindings = [
			'heading'    => [
				'content' => null,
			],
			'image'      => [
				'alt' => null,
				'url' => null,
			],
			'paragraphs' => [],
		];

		foreach ( $display_query->output_variables['mappings'] as $field => $var ) {
			$name = isset( $var['name'] ) ? $var['name'] : $field;

			switch ( $var['type'] ) {
				case 'price':
				case 'string':
					// Attempt to autodetect headings.
					$normalized_name = trim( strtolower( $name ) );
					if ( in_array( $normalized_name, [ 'head', 'heading', 'name', 'title' ], true ) ) {
						$bindings['heading']['content'] = [ $field, $name ];
						break;
					}

					$bindings['paragraphs'][] = [
						'content' => [ $field, $name ],
					];
					break;

				case 'image_alt':
					$bindings['image']['alt'] = [ $field, $name ];
					break;

				case 'image_url':
					$bindings['image']['url'] = [ $field, $name ];
					break;
			}
		}

		$content = '';

		if ( ! empty( $bindings['heading']['content'] ) ) {
			$content .= sprintf( self::$templates['heading'], self::generate_attribute_bindings( $bindings['heading'] ) );
		}

		if ( ! empty( $bindings['image']['url'] ) ) {
			$content .= sprintf( self::$templates['image'], self::generate_attribute_bindings( $bindings['image'] ) );
		}

		foreach ( $bindings['paragraphs'] as $paragraph ) {
			$content .= sprintf( self::$templates['paragraph'], self::generate_attribute_bindings( $paragraph ) );
		}

		register_block_pattern(
			sprintf( '%s/pattern', $block_name ),
			[
				'title'      => sprintf( '%s Data', $block_title ),
				'blockTypes' => [ $block_name ],
				'categories' => [ 'Remote Data' ],
				'content'    => $content,
				'inserter'   => true,
				'source'     => 'plugin',
			]
		);
	}
}
