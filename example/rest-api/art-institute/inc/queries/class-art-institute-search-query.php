<?php

namespace RemoteDataBlocks\Example\ArtInstituteOfChicago;

use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;

class ArtInstituteOfChicagoSearchArtQuery extends HttpQueryContext {
	public function define_input_variables(): array {
		return [
			'search_terms' => [
				'type' => 'string',
			],
		];
	}

	public function define_output_variables(): array {
		return [
			'root_path'     => '$.data[*]',
			'is_collection' => true,
			'mappings'      => [
				'id'        => [
					'name' => 'Art ID',
					'path' => '$.id',
					'type' => 'id',
				],
				'title'     => [
					'name' => 'Title',
					'path' => '$.title',
					'type' => 'string',
				],
				'image'     => [
					'name' => 'Image',
					'path' => '$.thumbnail.lqip',
					'type' => 'image_url',
				],
				'image_url' => [
					'name'     => 'Image URL',
					'generate' => function ( $data ) {
						return 'https://www.artic.edu/iiif/2/' . $data['data']['image_id'] . '/full/843,/0/default.jpg';
					},
					'type'     => 'image_url',
				],
			],
		];
	}

	public function get_endpoint( $input_variables ): string {
		$query    = $input_variables['search_terms'];
		$endpoint = $this->get_datasource()->get_endpoint() . '/search';

		return add_query_arg( [ 'q' => $query ], $endpoint );
	}
}
