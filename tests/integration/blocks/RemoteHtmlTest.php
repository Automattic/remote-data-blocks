<?php declare(strict_types = 1);

namespace RemoteDataBlocks\IntegrationTests\Blocks;

use RDBTestCase;

class RemoteHtmlTest extends RDBTestCase {
	public function testRemoteHtmlBlockRenders(): void {
		$test_title = 'My Product';

		$test_api_response = [
			'title' => $test_title,
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
				<h2 id="field-title" class="wp-block-heading"></h2>
				<!-- /wp:heading -->

				<!-- wp:remote-data-blocks/remote-html {"metadata":{"bindings":{"content":{"source":"remote-data/binding","args":{"field":"content","block":"remote-data-blocks/test-html-render"}}},"name":"Content"}} /-->
			</div>
			<!-- /wp:remote-data-blocks/test-html-render -->
		');

		$dom = $this->load_html( $result_html );
		$this->assertDomIdHasHtmlContent( $dom, 'rendered-html', 'A <strong>one of a kind</strong> product!' );
	}

	public function testRemoteHtmlBlockRendersFallbackContent(): void {
		$this->register_failed_query_data_block( 'test-html-failure' );

		$result_html = do_blocks('
			<!-- wp:remote-data-blocks/test-html-failure {"remoteData":{"blockName":"remote-data-blocks/test-html-failure"}} -->
			<div>
				<!-- wp:heading {"metadata":{"bindings":{"content":{"source":"remote-data/binding","args":{"block":"remote-data-blocks/test-html-failure","field":"title"}}},"name":"Title"}} -->
				<h2 id="field-title" class="wp-block-heading"></h2>
				<!-- /wp:heading -->

				<!-- wp:remote-data-blocks/remote-html {"metadata":{"bindings":{"content":{"source":"remote-data/binding","args":{"field":"content","block":"remote-data-blocks/test-html-failure"}}},"name":"Content"}} --><div id="fallback-content"><div class="warning">Use <em>this</em> content in case of emergency!</div></div><!-- /wp:remote-data-blocks/remote-html -->
			</div>
			<!-- /wp:remote-data-blocks/test-html-failure -->
		');

		$dom = $this->load_html( $result_html );
		$this->assertDomIdHasHtmlContent( $dom, 'fallback-content', '<div class="warning">Use <em>this</em> content in case of emergency!</div>' );
	}
}
