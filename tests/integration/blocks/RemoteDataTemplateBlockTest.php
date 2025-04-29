<?php declare(strict_types = 1);

namespace RemoteDataBlocks\IntegrationTests\Blocks;

use RDBTestCase;

class RemoteDataTemplateBlockTest extends RDBTestCase {
	public function testTemplateBlockIsRenderedForEachResult(): void {
		$test_api_response = [
			[
				'title' => 'Product 1',
				'content' => 'Product 1 details',
			],
			[
				'title' => 'Product 2',
				'content' => 'Product 2 details',
			],
			[
				'title' => 'Product 3',
				'content' => 'Product 3 details',
			],
		];

		$test_output_schema = [
			'is_collection' => true,
			'type' => [
				'title' => [
					'name' => 'Title',
					'path' => '$.title',
					'type' => 'string',
				],
				'content' => [
					'name' => 'Content',
					'path' => '$.content',
					'type' => 'string',
				],
			],
		];

		$this->register_mocked_data_block( 'test-template-render', $test_api_response, $test_output_schema );

		$result_html = do_blocks('
			<!-- wp:remote-data-blocks/test-template-render {"remoteData":{"blockName":"remote-data-blocks/test-template-render"}} -->
			<div>
				<!-- wp:heading {"metadata":{"bindings":{"content":{"source":"remote-data/binding","args":{"block":"remote-data-blocks/test-template-render","field":"title"}}},"name":"Title"}} -->
				<h2 id="field-title" class="wp-block-heading">Fallback title</h2>
				<!-- /wp:heading -->

				<!-- wp:remote-data-blocks/template -->
					<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"remote-data/binding","args":{"field":"content"}}},"name":"Content"}} -->
					<p class="field-content">Fallback content</p>
					<!-- /wp:paragraph -->
				<!-- /wp:remote-data-blocks/template -->
			</div>
			<!-- /wp:remote-data-blocks/test-template-render -->');

		$dom = $this->load_html( $result_html );

		// The title is outside the template block, so it should only be rendered once.
		$this->assertDomIdHasHtmlContent( $dom, 'field-title', 'Product 1' );

		// The content is inside the template block, so it should be rendered for each result.
		$content_nodes = $this->get_dom_elements_by_html_class( $dom, 'field-content' );

		$this->assertCount( 3, $content_nodes );
		$this->assertEquals( 'Product 1 details', $content_nodes[0]->textContent );
		$this->assertEquals( 'Product 2 details', $content_nodes[1]->textContent );
		$this->assertEquals( 'Product 3 details', $content_nodes[2]->textContent );
	}
}
