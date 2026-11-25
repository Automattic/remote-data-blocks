<?php declare(strict_types = 1);

namespace RemoteDataBlocks\IntegrationTests\Blocks;

use RDBTestCase;

class RemoteHtmlBlockTest extends RDBTestCase {
	public function testRemoteHtmlBlockRenders(): void {
		$test_api_response = [
			'title' => 'My product',
			'content' => '<div id="rendered-html">A <strong>one of a kind</strong> product!</div>',
		];

		$test_output_schema = [
			'is_collection' => false,
			'type' => [
				'title' => [
					'name' => 'Title',
					'path' => '$.title',
					'type' => 'string',
				],
				'content' => [
					'name' => 'Content',
					'path' => '$.content',
					'type' => 'html',
				],
			],
		];

		$this->register_mocked_data_block( 'test-html-render', $test_api_response, $test_output_schema );

		$result_html = do_blocks('
			<!-- wp:remote-data-blocks/test-html-render {"remoteData":{"blockName":"remote-data-blocks/test-html-render"}} -->
			<div>
				<!-- wp:heading {"metadata":{"bindings":{"content":{"source":"remote-data/binding","args":{"block":"remote-data-blocks/test-html-render","field":"title"}}},"name":"Title"}} -->
				<h2 id="field-title" class="wp-block-heading">Fallback title</h2>
				<!-- /wp:heading -->

				<!-- wp:remote-data-blocks/remote-html {"metadata":{"bindings":{"content":{"source":"remote-data/binding","args":{"field":"content","block":"remote-data-blocks/test-html-render"}}},"name":"Content"}} /-->
				<!-- wp:remote-data-blocks/remote-html {"metadata":{"bindings":{"content":{"source":"test/foreign","args":{"field":"content","block":"remote-data-blocks/test-html-render"}}}}} -->
				<p id="foreign-fallback">Authored <em>fallback</em></p>
				<!-- /wp:remote-data-blocks/remote-html -->
			</div>
			<!-- /wp:remote-data-blocks/test-html-render -->
		');

		$dom = $this->load_html( $result_html );

		$this->assertDomIdHasHtmlContent( $dom, 'field-title', 'My product' );
		$this->assertDomIdHasHtmlContent( $dom, 'rendered-html', 'A <strong>one of a kind</strong> product!' );
		$this->assertSame( 1, substr_count( $result_html, 'id="rendered-html"' ) );
		$this->assertDomIdHasHtmlContent( $dom, 'foreign-fallback', 'Authored <em>fallback</em>' );
	}

	public function testRemoteHtmlBlockRendersFallbackWhenBindingArgumentsAreInvalid(): void {
		$html = do_blocks(
			'<!-- wp:remote-data-blocks/remote-html {"metadata":{"bindings":{"content":{"source":"remote-data/binding","args":"saved field name"}}}} --><p id="invalid-binding-fallback">Authored <em>fallback</em></p><!-- /wp:remote-data-blocks/remote-html -->'
		);

		$this->assertDomIdHasHtmlContent( $this->load_html( $html ), 'invalid-binding-fallback', 'Authored <em>fallback</em>' );
	}

	public function testRemoteHtmlBlockRendersFallbackContent(): void {
		$this->register_failed_query_data_block( 'test-html-failure' );

		$result_html = do_blocks('
			<!-- wp:remote-data-blocks/test-html-failure {"remoteData":{"blockName":"remote-data-blocks/test-html-failure"}} -->
			<div>
				<!-- wp:heading {"metadata":{"bindings":{"content":{"source":"remote-data/binding","args":{"block":"remote-data-blocks/test-html-failure","field":"title"}}},"name":"Title"}} -->
				<h2 id="field-title" class="wp-block-heading">Fallback title</h2>
				<!-- /wp:heading -->

				<!-- wp:remote-data-blocks/remote-html {"metadata":{"bindings":{"content":{"source":"remote-data/binding","args":{"field":"content","block":"remote-data-blocks/test-html-failure"}}},"name":"Content"}} --><div id="fallback-content"><div class="warning">Use <em>this</em> content in case of emergency!</div></div><!-- /wp:remote-data-blocks/remote-html -->
			</div>
			<!-- /wp:remote-data-blocks/test-html-failure -->
		');

		$dom = $this->load_html( $result_html );

		$this->assertDomIdHasHtmlContent( $dom, 'field-title', 'Fallback title' );
		$this->assertDomIdHasHtmlContent( $dom, 'fallback-content', '<div class="warning">Use <em>this</em> content in case of emergency!</div>' );
	}
}
