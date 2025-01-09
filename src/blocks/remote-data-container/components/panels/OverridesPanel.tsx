import { CheckboxControl, PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { sendTracksEvent } from '@/blocks/remote-data-container/utils/tracks';
import { getBlockDataSourceType } from '@/utils/localized-block-data';

interface OverridesPanelProps {
	blockConfig: BlockConfig;
	remoteData: RemoteData;
	updateRemoteData: ( remoteData: RemoteData ) => void;
}

export function OverridesPanel( props: OverridesPanelProps ) {
	const { blockConfig, remoteData, updateRemoteData } = props;
	const { availableOverrides } = blockConfig;

	if ( ! availableOverrides.length ) {
		return null;
	}

	const enabledOverrides = new Set( remoteData.enabledOverrides );

	function updateOverrides( overrideName: string, enabled: boolean ) {
		if ( enabled ) {
			enabledOverrides.add( overrideName );
			sendTracksEvent( 'remotedatablocks_remote_data_container_override', {
				data_source_type: getBlockDataSourceType( remoteData.blockName ),
				override_type: 'unknown', // We no longer know the override type since the implementation is delegated.
				override_target: 'unknown',
			} );
		} else {
			enabledOverrides.delete( overrideName );
		}

		updateRemoteData( {
			...remoteData,
			enabledOverrides: Array.from( enabledOverrides ),
		} );
	}

	return (
		<PanelBody title={ __( 'Remote data overrides', 'remote-data-blocks' ) }>
			<p>
				{ __(
					'Override the query input at run-time using the selected strategy',
					'remote-data-blocks'
				) }
			</p>
			{ availableOverrides.map( override => (
				<CheckboxControl
					key={ override.name }
					label={ override.display_name || override.name }
					checked={ remoteData.enabledOverrides?.includes( override.name ) }
					onChange={ enabled => updateOverrides( override.name, enabled ) }
				/>
			) ) }
		</PanelBody>
	);
}
