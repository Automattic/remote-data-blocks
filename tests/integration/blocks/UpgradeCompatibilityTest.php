<?php declare(strict_types = 1);

class UpgradeCompatibilityTest extends RDBTestCase {
	public function test_historical_saved_blocks_render_without_resaving_and_after_round_trip(): void {
		$this->register_mocked_data_block( 'upgrade-garden', [ 'title' => 'Live garden' ], [
			'is_collection' => false,
			'type' => [
				'title' => [
					'name' => 'Title',
					'type' => 'string',
					'path' => '$.title',
				],
			],
		] );
		$fixtures = json_decode( file_get_contents( __DIR__ . '/../../fixtures/compatibility/saved-remote-data.json' ), true, 512, JSON_THROW_ON_ERROR );

		foreach ( $fixtures as $fixture ) {
			if ( $fixture['clientOnly'] ?? false ) {
				continue;
			}
			$remote_data = $fixture['remoteData'];
			$remote_data['blockName'] = 'remote-data-blocks/upgrade-garden';
			$binding = [
				'name' => 'Existing saved name',
				'bindings' => [
					'content' => [
						'source' => 'remote-data/binding',
						'args' => [
							'block' => $remote_data['blockName'],
							'field' => 'title',
						],
					],
				],
			];
			$content = sprintf(
				'<!-- wp:remote-data-blocks/upgrade-garden %s --><div class="wp-block-remote-data-blocks-upgrade-garden rdb-container"><!-- wp:paragraph %s --><p>Saved garden</p><!-- /wp:paragraph --></div><!-- /wp:remote-data-blocks/upgrade-garden -->',
				wp_json_encode( [ 'remoteData' => $remote_data ] ),
				wp_json_encode( [ 'metadata' => $binding ] )
			);
			$this->assertStringContainsString( 'Live garden', do_blocks( $content ), $fixture['name'] );
			$blocks = parse_blocks( $content );
			$this->assertSame( $binding, $blocks[0]['innerBlocks'][0]['attrs']['metadata'] );
			$this->assertStringContainsString( 'Live garden', do_blocks( serialize_blocks( $blocks ) ), $fixture['name'] );
		}
	}
}
