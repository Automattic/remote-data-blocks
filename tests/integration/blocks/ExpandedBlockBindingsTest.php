<?php declare(strict_types = 1);

namespace RemoteDataBlocks\IntegrationTests\Blocks;

use RDBTestCase;
use RemoteDataBlocks\Editor\DataBinding\BlockBindings;
use WP_Block;

class ExpandedBlockBindingsTest extends RDBTestCase {
	private const DYNAMIC_BLOCK = 'rdb-tests/scalar-bindings';
	private const STATIC_BLOCK = 'rdb-tests/static-bindings';

	public function testResolverKeepsRegisteredScalarTypesOnEverySupportedRuntime(): void {
		$remote_block = $this->register_scalar_data( 'scalar-resolver' );
		register_block_type( self::DYNAMIC_BLOCK, [ 'attributes' => $this->scalar_attributes() ] );

		try {
			$block = new WP_Block( [
				'blockName' => self::DYNAMIC_BLOCK,
				'attrs' => [],
				'innerBlocks' => [],
				'innerHTML' => '',
				'innerContent' => [],
			] );
			$block->context[ BlockBindings::$context_name ] = [
				'blockName' => $remote_block,
				'queryInputs' => [ [] ],
			];

			$this->assertSame( 12.5, BlockBindings::get_value( [ 'field' => 'measurement' ], $block, 'measurement' ) );
			$this->assertSame( 0, BlockBindings::get_value( [ 'field' => 'count' ], $block, 'count' ) );
			$this->assertFalse( BlockBindings::get_value( [ 'field' => 'enabled' ], $block, 'enabled' ) );
		} finally {
			unregister_block_type( self::DYNAMIC_BLOCK );
		}
	}

	public function testBothWordPressFiltersExposeRegisteredAttributesToEditorSettings(): void {
		if ( ! function_exists( 'get_block_bindings_supported_attributes' ) ) {
			$this->markTestSkipped( 'The official supported-attribute filters require WordPress 6.9.' );
		}

		register_block_type( self::DYNAMIC_BLOCK, [ 'attributes' => $this->scalar_attributes() ] );
		add_filter( 'block_bindings_supported_attributes', [ $this, 'add_generic_supported_attributes' ], 10, 2 );
		add_filter( 'block_bindings_supported_attributes_' . self::DYNAMIC_BLOCK, [ $this, 'add_block_supported_attributes' ] );

		try {
			$this->assertSame( [ 'measurement', 'count', 'enabled' ], get_block_bindings_supported_attributes( self::DYNAMIC_BLOCK ) );
			$settings = get_block_editor_settings( [], new \WP_Block_Editor_Context() );
			$this->assertSame( [ 'measurement', 'count', 'enabled' ], $settings['__experimentalBlockBindingsSupportedAttributes'][ self::DYNAMIC_BLOCK ] );
		} finally {
			remove_filter( 'block_bindings_supported_attributes', [ $this, 'add_generic_supported_attributes' ], 10 );
			remove_filter( 'block_bindings_supported_attributes_' . self::DYNAMIC_BLOCK, [ $this, 'add_block_supported_attributes' ] );
			unregister_block_type( self::DYNAMIC_BLOCK );
		}
	}

	public function testFilteredDynamicBlockReceivesTypedRemoteAttributesDuringRendering(): void {
		$this->require_generic_renderer();
		$remote_block = $this->register_scalar_data( 'scalar-dynamic-render' );
		$received_attributes = [];
		register_block_type( self::DYNAMIC_BLOCK, [
			'attributes' => $this->scalar_attributes(),
			'render_callback' => static function ( array $attributes ) use ( &$received_attributes ): string {
				$received_attributes = $attributes;
				return '<p>' . esc_html( wp_json_encode( [ $attributes['measurement'], $attributes['count'], $attributes['enabled'] ] ) ) . '</p>';
			},
		] );
		add_filter( 'block_bindings_supported_attributes', [ $this, 'add_generic_supported_attributes' ], 10, 2 );
		add_filter( 'block_bindings_supported_attributes_' . self::DYNAMIC_BLOCK, [ $this, 'add_block_supported_attributes' ] );

		try {
			$attrs = [ 'metadata' => [ 'bindings' => [] ] ];
			foreach ( [ 'measurement', 'count', 'enabled' ] as $field ) {
				$attrs['metadata']['bindings'][ $field ] = $this->binding( $field );
			}
			$content = $this->wrap_remote_block( $remote_block, sprintf( '<!-- wp:%s %s /-->', self::DYNAMIC_BLOCK, wp_json_encode( $attrs ) ) );

			$this->assertSame( '<p>[12.5,0,false]</p>', trim( do_blocks( $content ) ) );
			$this->assertSame( 12.5, $received_attributes['measurement'] );
			$this->assertSame( 0, $received_attributes['count'] );
			$this->assertFalse( $received_attributes['enabled'] );
		} finally {
			remove_filter( 'block_bindings_supported_attributes', [ $this, 'add_generic_supported_attributes' ], 10 );
			remove_filter( 'block_bindings_supported_attributes_' . self::DYNAMIC_BLOCK, [ $this, 'add_block_supported_attributes' ] );
			unregister_block_type( self::DYNAMIC_BLOCK );
		}
	}

	public function testFilteredStaticSourcedAttributeRendersRemoteText(): void {
		$this->require_generic_renderer();
		$this->register_mocked_data_block( 'static-binding-render', [ 'message' => 'Remote fixture text' ], [
			'is_collection' => false,
			'type' => [
				'message' => [
					'name' => 'Message',
					'path' => '$.message',
					'type' => 'string',
				],
			],
		] );
		register_block_type( self::STATIC_BLOCK, [
			'attributes' => [
				'fixtureMessage' => [
					'type' => 'string',
					'source' => 'html',
					'selector' => 'p',
				],
			],
		] );
		$filter = static function ( array $attributes ): array {
			return array_merge( $attributes, [ 'fixtureMessage' ] );
		};
		add_filter( 'block_bindings_supported_attributes_' . self::STATIC_BLOCK, $filter );

		try {
			$attrs = [ 'metadata' => [ 'bindings' => [ 'fixtureMessage' => $this->binding( 'message' ) ] ] ];
			$inner = sprintf( '<!-- wp:%1$s %2$s --><p>Saved fallback</p><!-- /wp:%1$s -->', self::STATIC_BLOCK, wp_json_encode( $attrs ) );
			$content = $this->wrap_remote_block( 'remote-data-blocks/static-binding-render', $inner );

			$this->assertSame( '<p>Remote fixture text</p>', trim( do_blocks( $content ) ) );
		} finally {
			remove_filter( 'block_bindings_supported_attributes_' . self::STATIC_BLOCK, $filter );
			unregister_block_type( self::STATIC_BLOCK );
		}
	}

	public function add_generic_supported_attributes( array $attributes, string $block_name ): array {
		return self::DYNAMIC_BLOCK === $block_name ? array_merge( $attributes, [ 'measurement' ] ) : $attributes;
	}

	public function add_block_supported_attributes( array $attributes ): array {
		return array_merge( $attributes, [ 'count', 'enabled' ] );
	}

	private function require_generic_renderer(): void {
		if ( ! function_exists( 'get_block_bindings_supported_attributes' ) ) {
			$this->markTestSkipped( 'Filter-enabled custom binding rendering requires the WordPress 6.9 supported-attributes API; no renderer is backported.' );
		}
	}

	private function scalar_attributes(): array {
		return [
			'measurement' => [
				'type' => 'number',
				'default' => 99,
			],
			'count' => [
				'type' => 'integer',
				'default' => 99,
			],
			'enabled' => [
				'type' => 'boolean',
				'default' => true,
			],
		];
	}

	private function register_scalar_data( string $title ): string {
		$output_schema = [];
		foreach ( [
			'measurement' => 'number',
			'count' => 'integer',
			'enabled' => 'boolean',
		] as $name => $type ) {
			$output_schema[ $name ] = [
				'name' => $name,
				'path' => '$.' . $name,
				'type' => $type,
			];
		}
		$response = [
			'measurement' => 12.5,
			'count' => 0,
			'enabled' => false,
		];
		$this->register_mocked_data_block( $title, $response, [
			'is_collection' => false,
			'type' => $output_schema,
		] );

		return 'remote-data-blocks/' . $title;
	}

	private function binding( string $field ): array {
		return [
			'source' => BlockBindings::$binding_source,
			'args' => [ 'field' => $field ],
		];
	}

	private function wrap_remote_block( string $remote_block, string $inner ): string {
		return sprintf(
			'<!-- wp:%1$s %2$s -->%3$s<!-- /wp:%1$s -->',
			$remote_block,
			wp_json_encode( [
				'remoteData' => [
					'blockName' => $remote_block,
					'queryInputs' => [ [] ],
				],
			] ),
			$inner
		);
	}
}
