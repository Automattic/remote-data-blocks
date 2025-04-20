<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Logging\QueryMonitor;

use QM_Output_Html_Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'QM_Output_Html_Logger' ) ) {
	class RdbValidationIssueOutputHtml extends QM_Output_Html_Logger {
		public static string $collector_id = 'remote-data-blocks-validation';

		public function admin_menu( array $menu ): array {
			/** @var QM_Data_Logger $data */
			$data = $this->collector->get_data();
			$count = 0;

			if ( ! empty( $data->logs ) ) {
				$count = count( $data->logs );

				/* translators: %s: Number of logs that are available */
				$label = __( 'Validation issues (%s)', 'query-monitor' );
			} else {
				$label = __( 'Validation issues', 'query-monitor' );
			}

			if ( ! isset( $menu['remote-data-blocks'] ) ) {
					$menu['remote-data-blocks'] = $this->menu( [
						'title' => __( 'Remote Data Blocks', 'query-monitor' ),
					] );
			}

			$menu['remote-data-blocks']['children'][ $this->collector->id() ] = $this->menu( array(
				'id' => 'remote-data-blocks-validation',
				'title' => esc_html( sprintf(
					$label,
					number_format_i18n( $count )
				) ),
			) );

			return $menu;
		}
	}
}
