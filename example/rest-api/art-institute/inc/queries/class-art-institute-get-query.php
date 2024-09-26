<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\ArtInstituteOfChicago;

use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;

class ArtInstituteOfChicagoGetArtQuery extends HttpQueryContext {
	public function get_input_schema(): array {
		return [
			'id' => [
				'type' => 'id',
			],
		];
	}

	public function get_output_schema(): array {
		return [
			'is_collection' => false,
			'mappings' => [
				'id' => [
					'name' => 'ID',
					'path' => '$.data.id',
					'type' => 'id',
				],
				'title' => [
					'name' => 'Title',
					'path' => '$.data.title',
					'type' => 'string',
				],
				'image_id' => [
					'name' => 'Image ID',
					'path' => '$.data.image_id',
					'type' => 'id',
				],
				'image_url' => [
					'name' => 'Image URL',
					'generate' => function ( $data ) {
						return 'https://www.artic.edu/iiif/2/' . $data['data']['image_id'] . '/full/843,/0/default.jpg';
					},
					'type' => 'image_url',
				],
			],
		];
	}

	public function get_endpoint( $input_variables ): string {
		return $this->get_datasource()->get_endpoint() . '/' . $input_variables['id'];
	}
}
