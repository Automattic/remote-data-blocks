<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\Capgemini\Jobs;

defined( 'ABSPATH' ) || exit();

use RemoteDataBlocks\Editor\DataBinding\BlockBindings;
use RemoteDataBlocks\Editor\BlockManagement\ConfigStore;
use RemoteDataBlocks\REST\RemoteDataController;

class InteractivityStore {
	public static function get_initial_state(): array {
		$block_name = ConfigStore::get_block_name( 'Job Search Results' );
		$rest_url = RemoteDataController::get_url();

		$initial_state = [
			'blockName' => $block_name,
			'jobs' => [],
			'page' => 1,
			'restUrl' => $rest_url,
			'searchTerms' => get_query_var( 'search' ) ?? '',
			'size' => 10,
		];

		// If search terms are set via query var, fetch them in order to server-side
		// render the jobs.
		//
		// TODO:
		// 1. The query vars need to be formalized and configurable. Should it come from
		//    overrides? Those are slightly different.
		// 2. Need to support the other input variables instead of hardcoding.
		if ( ! empty( $initial_state['searchTerms'] ) ) {
			$block_context = [
				'blockName' => $block_name,
				'queryInput' => [
					'country_code' => $initial_state['country_code'],
					'page' => $initial_state['page'],
					'search' => $initial_state['searchTerms'],
					'size' => 10,
				],
			];

			$raw_results = BlockBindings::execute_query( $block_context, 'interactivity-initial-state' );
			if ( ! is_wp_error( $raw_results ) ) {
				$initial_state['jobs'] = array_map( function ( $job ) {
					return [
						'title' => $job['result']['title']['value'] ?? '',
					];
				}, $raw_results['results'] );
			}
		}

		return $initial_state;
	}

	public static function get_store_name(): string {
		return 'remote-data-blocks/capgemini-jobs';
	}
}
