<?php declare(strict_types = 1);

namespace RemoteDataBlocks\IntegrationTests\Blocks;

use RDBTestCase;
use RemoteDataBlocks\Editor\DataBinding\InlineBindings;

/**
 * Remote values are untrusted. These tests assert that unsafe markup in remote
 * values does not reach the rendered page, for both regular block
 * bindings (where WordPress Core sanitizes the bound value) and inline
 * bindings (where this plugin sanitizes the value).
 */
class BindingOutputTest extends RDBTestCase {
	private const MARKDOWN = '**Bold** <script>alert(1)</script> <img src=x onerror="alert(1)"> [link](javascript:alert(1))';
	private const MARKDOWN_RENDERED = '<p><strong>Bold</strong> alert(1) <img src="x"> <a href="alert(1)">link</a></p>';

	private const HTML = '<em>Allowed</em> <img src="https://example.com/image.png" onerror="alert(1)"><script>alert(1)</script>';
	private const HTML_RENDERED = '<em>Allowed</em> <img src="https://example.com/image.png">alert(1)';

	public function testBlockBindingSanitizesMarkdownInParagraph(): void {
		$block_name = $this->register_test_block( 'test-binding-markdown-paragraph' );
		$result_html = $this->render_bound_block( $block_name, 'core:paragraph', 'markdown' );

		$this->assertBlockInnerHtml( 'p', self::MARKDOWN_RENDERED, $result_html );
	}

	public function testBlockBindingSanitizesMarkdownInHeading(): void {
		$block_name = $this->register_test_block( 'test-binding-markdown-heading' );
		$result_html = $this->render_bound_block( $block_name, 'core:heading', 'markdown' );

		$this->assertBlockInnerHtml( 'h2', self::MARKDOWN_RENDERED, $result_html );
	}

	public function testBlockBindingSanitizesHtmlInParagraph(): void {
		$block_name = $this->register_test_block( 'test-binding-html-paragraph' );
		$result_html = $this->render_bound_block( $block_name, 'core:paragraph', 'html' );

		$this->assertBlockInnerHtml( 'p', self::HTML_RENDERED, $result_html );
	}

	public function testInlineBindingSanitizesMarkdown(): void {
		$block_name = $this->register_test_block( 'test-inline-binding-markdown' );

		$this->assertSame(
			'Before <span class="inline-field" data-remote-data-blocks-inline-field="query-success">' . self::MARKDOWN_RENDERED . '</span> After',
			InlineBindings::render_frontend_fields( 'Before ' . $this->inline_field( $block_name, 'markdown' ) . ' After' )
		);
	}

	public function testInlineBindingSanitizesHtml(): void {
		$block_name = $this->register_test_block( 'test-inline-binding-html' );

		$this->assertSame(
			'<span class="inline-field" data-remote-data-blocks-inline-field="query-success">' . self::HTML_RENDERED . '</span>',
			InlineBindings::render_frontend_fields( $this->inline_field( $block_name, 'html' ) )
		);
	}

	public function testInlineBindingIgnoresPostContextFilters(): void {
		$block_name = $this->register_test_block( 'test-inline-binding-post-filter' );
		$allow_unsafe_post_html = static function ( array $tags, string $context ): array {
			if ( 'post' === $context ) {
				$tags['script'] = [];
				$tags['img']['onerror'] = true;
			}

			return $tags;
		};

		add_filter( 'wp_kses_allowed_html', $allow_unsafe_post_html, 10, 2 );

		try {
			$this->assertSame( self::HTML, wp_kses_post( self::HTML ) );
			$this->assertSame(
				'<span class="inline-field" data-remote-data-blocks-inline-field="query-success">' . self::HTML_RENDERED . '</span>',
				InlineBindings::render_frontend_fields( $this->inline_field( $block_name, 'html' ) )
			);
		} finally {
			remove_filter( 'wp_kses_allowed_html', $allow_unsafe_post_html, 10 );
		}
	}

	/**
	 * Assert that the rendered block is a single element with the given tag and
	 * exactly the expected inner HTML. Wrapper attributes vary by WordPress
	 * version, so they are not compared.
	 */
	private function assertBlockInnerHtml( string $tag, string $expected_inner_html, string $result_html ): void {
		$pattern = sprintf( '#^<%1$s [^>]*>(.*)</%1$s>$#s', $tag );

		$this->assertMatchesRegularExpression( $pattern, $result_html );
		preg_match( $pattern, $result_html, $matches );
		$this->assertSame( $expected_inner_html, $matches[1] );
	}

	/**
	 * Register a remote data block with a markdown field and an html field, both
	 * containing unsafe markup. Returns the block name.
	 */
	private function register_test_block( string $block_title ): string {
		$response = [
			'markdown' => self::MARKDOWN,
			'html' => self::HTML,
		];

		$output_schema = [
			'is_collection' => false,
			'type' => [
				'markdown' => [
					'name' => 'Markdown',
					'path' => '$.markdown',
					'type' => 'markdown',
				],
				'html' => [
					'name' => 'HTML',
					'path' => '$.html',
					'type' => 'html',
				],
			],
		];

		$this->register_mocked_data_block( $block_title, $response, $output_schema );

		return 'remote-data-blocks/' . $block_title;
	}

	private function render_bound_block( string $block_name, string $block, string $field ): string {
		$binding = wp_json_encode( [
			'metadata' => [
				'bindings' => [
					'content' => [
						'source' => 'remote-data/binding',
						'args' => [
							'block' => $block_name,
							'field' => $field,
						],
					],
				],
			],
		] );

		$inner_block = match ( $block ) {
			'core:paragraph' => sprintf( '<!-- wp:paragraph %s --><p id="field">Fallback</p><!-- /wp:paragraph -->', $binding ),
			'core:heading' => sprintf( '<!-- wp:heading %s --><h2 id="field" class="wp-block-heading">Fallback</h2><!-- /wp:heading -->', $binding ),
		};

		return trim( do_blocks( sprintf(
			'<!-- wp:%1$s {"remoteData":{"blockName":"%1$s"}} -->%2$s<!-- /wp:%1$s -->',
			$block_name,
			$inner_block
		) ) );
	}

	private function inline_field( string $block_name, string $field ): string {
		$query = [
			'remoteData' => [
				'blockName' => $block_name,
				'queryInputs' => [ [] ],
			],
			'selectedField' => $field,
		];

		return sprintf(
			'<remote-data-blocks-inline-field class="inline-field" data-query="%s">Fallback</remote-data-blocks-inline-field>',
			esc_attr( wp_json_encode( $query ) )
		);
	}
}
