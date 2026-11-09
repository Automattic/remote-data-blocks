<?php declare(strict_types = 1);

namespace RemoteDataBlocks\IntegrationTests\Blocks;

use RDBTestCase;
use RemoteDataBlocks\Editor\BlockManagement\ConfigStore;
use RemoteDataBlocks\Editor\DataBinding\BlockBindings;
use RemoteDataBlocks\Editor\DataBinding\InlineBindings;
use WP_Block_Type_Registry;

class BindingOutputTest extends RDBTestCase {
	private const MARKDOWN = '**Allowed** <img src="https://example.com/image.png" onerror="alert(1)"> <a href="javascript:alert(1)">Link</a>';
	private const SAFE_HTML = '<p><strong>Allowed</strong> <img src="https://example.com/image.png"> <a href="alert(1)">Link</a></p>';
	private const FALLBACK = '<em>Fallback</em> <img src="https://example.com/fallback.png" onerror="alert(1)">';
	private const SAFE_FALLBACK = '<em>Fallback</em> <img src="https://example.com/fallback.png">';

	public function set_up(): void {
		parent::set_up();
		BlockBindings::init();
		ConfigStore::init();
	}

	public function tear_down(): void {
		foreach ( [ 'test-binding-output', 'test-binding-failure', 'test-inline-failure' ] as $name ) {
			$block_name = 'remote-data-blocks/' . $name;
			if ( WP_Block_Type_Registry::get_instance()->is_registered( $block_name ) ) {
				unregister_block_type( $block_name );
			}
		}

		parent::tear_down();
	}

	public function testBlockBindingSanitizesFormattedRemoteValueAndLabel(): void {
		$this->register_markdown_block();

		$value = BlockBindings::get_value( [
			'block' => 'remote-data-blocks/test-binding-output',
			'field' => 'content',
			'label' => '<em>Label</em><img src="https://example.com/label.png" onerror="alert(1)">',
		], [], 'content' );

		$this->assertSame( '<span class="rdb-block-label"><em>Label</em><img src="https://example.com/label.png"></span> ' . self::SAFE_HTML, $value );
	}

	public function testInlineBindingSanitizesFormattedRemoteValue(): void {
		$this->register_markdown_block();
		$content = 'Before ' . $this->inline_field( [
			'remoteData' => [
				'blockName' => 'remote-data-blocks/test-binding-output',
				'queryInputs' => [ [] ],
			],
			'selectedField' => 'content',
		] ) . ' After';

		$this->assertSame(
			'Before <span class="inline-field" data-remote-data-blocks-inline-field="query-success">' . self::SAFE_HTML . '</span> After',
			InlineBindings::render_frontend_fields( $content )
		);
	}

	public function testButtonTextBindingSanitizesFormattedRemoteValue(): void {
		$this->register_markdown_block();

		$this->assertSame( self::SAFE_HTML, BlockBindings::get_value( [
			'block' => 'remote-data-blocks/test-binding-output',
			'field' => 'content',
		], [], 'text' ) );
	}

	public function testBlockBindingPreservesLiveUrl(): void {
		$url = 'https://example.com/?a=1&b=2';
		$this->register_mocked_data_block( 'test-binding-output', [ 'url' => $url ], [
			'is_collection' => false,
			'type' => [
				'url' => [
					'name' => 'URL',
					'path' => '$.url',
					'type' => 'string',
				],
			],
		] );

		$this->assertSame( $url, BlockBindings::get_value( [
			'block' => 'remote-data-blocks/test-binding-output',
			'field' => 'url',
		], [], 'url' ) );
	}

	/**
	 * @dataProvider provideInlineFallbackQueries
	 */
	public function testInlineBindingSanitizesSavedFallback( array $query, string $status ): void {
		$this->register_failed_query_data_block( 'test-inline-failure' );

		$this->assertSame(
			'<span class="inline-field" data-remote-data-blocks-inline-field="' . $status . '">' . self::SAFE_FALLBACK . '</span>',
			InlineBindings::render_frontend_fields( $this->inline_field( $query, self::FALLBACK ) )
		);
	}

	public static function provideInlineFallbackQueries(): array {
		return [
			'invalid query' => [ [], 'parse-error' ],
			'failed query' => [
				[
					'remoteData' => [
						'blockName' => 'remote-data-blocks/test-inline-failure',
						'queryInputs' => [ [] ],
					],
					'selectedField' => 'content',
				],
				'query-error',
			],
		];
	}

	/**
	 * @dataProvider provideBlockFallbackSources
	 */
	public function testBlockBindingSanitizesSavedFallback( array $source_args, bool $use_serialized_result ): void {
		$this->register_failed_query_data_block( 'test-binding-failure' );
		$block = [ 'attributes' => [ 'content' => self::FALLBACK ] ];

		if ( $use_serialized_result ) {
			$block['context'][ BlockBindings::$context_name ] = [
				'results' => [ [ 'result' => [ 'content' => self::FALLBACK ] ] ],
			];
			$block['attributes']['content'] = 'Other fallback';
		}

		$this->assertSame( self::SAFE_FALLBACK, BlockBindings::get_value( $source_args, $block, 'content' ) );
	}

	public static function provideBlockFallbackSources(): array {
		return [
			'missing mapping' => [ [], false ],
			'failed query, attribute fallback' => [
				[
					'block' => 'remote-data-blocks/test-binding-failure',
					'field' => 'content',
				],
				false,
			],
			'failed query, serialized fallback' => [
				[
					'block' => 'remote-data-blocks/test-binding-failure',
					'field' => 'content',
				],
				true,
			],
		];
	}

	public function testBlockBindingSanitizesFallbackForMissingRemoteField(): void {
		$this->register_markdown_block();

		$this->assertSame( self::SAFE_FALLBACK, BlockBindings::get_value(
			[
				'block' => 'remote-data-blocks/test-binding-output',
				'field' => 'missing',
			],
			[ 'attributes' => [ 'content' => self::FALLBACK ] ],
			'content'
		) );
	}

	public function testBlockBindingPreservesNullAndScalarAttributes(): void {
		$this->register_failed_query_data_block( 'test-binding-failure' );
		$source_args = [
			'block' => 'remote-data-blocks/test-binding-failure',
			'field' => 'content',
		];

		$this->assertNull( BlockBindings::get_value( $source_args, [], 'content' ) );
		$this->assertSame( '123', BlockBindings::get_value( $source_args, [ 'attributes' => [ 'content' => 123 ] ], 'content' ) );
		$this->assertSame( 'https://example.com/?a=1&b=2', BlockBindings::get_value( $source_args, [
			'attributes' => [ 'url' => 'https://example.com/?a=1&b=2' ],
		], 'url' ) );
	}

	private function register_markdown_block(): void {
		$this->register_mocked_data_block( 'test-binding-output', [ 'content' => self::MARKDOWN ], [
			'is_collection' => false,
			'type' => [
				'content' => [
					'name' => 'Content',
					'path' => '$.content',
					'type' => 'markdown',
				],
			],
		] );
	}

	private function inline_field( array $query, string $fallback = 'Saved value' ): string {
		return sprintf(
			'<remote-data-blocks-inline-field class="inline-field" data-query="%s">%s</remote-data-blocks-inline-field>',
			esc_attr( wp_json_encode( $query ) ),
			$fallback
		);
	}
}
