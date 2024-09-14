<?php

namespace RemoteDataBlocks\Editor\BlockPatterns;

defined( 'ABSPATH' ) || exit();

use RemoteDataBlocks\Config\QueryContext\QueryContextInterface;

use function register_block_pattern;

class BlockPatterns {
	private static $templates = [];

	private static function load_templates() {
		if ( ! empty( self::$templates ) ) {
			return;
		}

		self::$templates['columns']   = file_get_contents( __DIR__ . '/templates/columns.html', false );
		self::$templates['heading']   = file_get_contents( __DIR__ . '/templates/heading.html', false );
		self::$templates['image']     = file_get_contents( __DIR__ . '/templates/image.html', false );
		self::$templates['paragraph'] = file_get_contents( __DIR__ . '/templates/paragraph.html', false );
	}

	private static function generate_attribute_bindings( string $block_name, array $bindings ): array {
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
					'block' => $block_name,
					'field' => $binding[0],
				],
			];

			// TODO: Create a name that reflects multiple bindings (e.g., "image URL + image alt").
			$attributes['metadata']['name'] = $binding[1];
		}

		return $attributes;
	}

	private static function populate_template( string $template_name, array $attributes ): string {
		if ( ! isset( self::$templates[ $template_name ] ) ) {
			return '';
		}

		return sprintf( self::$templates[ $template_name ], wp_json_encode( $attributes ) );
	}

	public static function register_default_block_pattern( string $block_name, string $block_title, QueryContextInterface $display_query ): void {
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
				case 'string':
					// Attempt to autodetect headings.
					$normalized_name = trim( strtolower( $name ) );
					$heading_names   = [ 'head', 'header', 'heading', 'name', 'title' ];
					if ( null === $bindings['heading']['content'] && in_array( $normalized_name, $heading_names, true ) ) {
						$bindings['heading']['content'] = [ $field, $name ];
						break;
					}

					$bindings['paragraphs'][] = [
						'content' => [ $field, $name ],
					];
					break;

				case 'base64':
				case 'price':
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

		// If there is no heading, use the first paragraph.
		if ( empty( $bindings['heading']['content'] ) && ! empty( $bindings['paragraphs'] ) ) {
			$bindings['heading']['content'] = array_shift( $bindings['paragraphs'] )['content'];
		}

		if ( ! empty( $bindings['heading']['content'] ) ) {
			$content .= self::populate_template( 'heading', self::generate_attribute_bindings( $block_name, $bindings['heading'] ) );
		}

		foreach ( $bindings['paragraphs'] as $paragraph ) {
			$content .= self::populate_template( 'paragraph', self::generate_attribute_bindings( $block_name, $paragraph ) );
		}

		// If there is an image URL, create two-column layout with left-aligned image.
		if ( ! empty( $bindings['image']['url'] ) ) {
			$image_bindings = self::generate_attribute_bindings( $block_name, $bindings['image'] );
			$image_content  = self::populate_template( 'image', $image_bindings );
			$content        = sprintf( self::$templates['columns'], $image_content, $content );
		}

		register_block_pattern(
			sprintf( '%s/pattern', $block_name ),
			[
				'title'      => sprintf( '%s Data', $block_title ),
				'blockTypes' => [ $block_name ],
				'categories' => [ 'Remote Data Blocks' ],
				'content'    => $content,
				'inserter'   => true,
				'source'     => 'plugin',
			]
		);
	}
}
