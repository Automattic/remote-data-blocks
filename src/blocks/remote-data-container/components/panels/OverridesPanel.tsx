import { PanelBody, SelectControl } from '@wordpress/components';
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
	const { overrides: availableOverrides } = blockConfig;

	if ( ! Object.keys( availableOverrides ).length ) {
		return null;
	}

	function findIndex( key: string ): number {
		const override = remoteData.queryInputOverrides?.[ key ];
		if ( ! override ) {
			return -1;
		}

		const overrides = availableOverrides[ key ];
		if ( ! overrides ) {
			return -1;
		}

		return overrides.findIndex(
			o => o.sourceType === override.sourceType && o.source === override.source
		);
	}

	function updateOverrides( inputVar: string, index: number ) {
		const overrides = availableOverrides[ inputVar ]?.[ index ];
		const copyOfQueryInputOverrides = { ...remoteData.queryInputOverrides };

		if ( ! overrides || index === -1 ) {
			delete copyOfQueryInputOverrides?.[ inputVar ];
		} else {
			Object.assign( copyOfQueryInputOverrides, { [ inputVar ]: overrides } );
		}

		updateRemoteData( {
			...remoteData,
			queryInputOverrides: copyOfQueryInputOverrides,
		} );
		sendTracksEvent( 'remotedatablocks_remote_data_container_override', {
			data_source_type: getBlockDataSourceType( remoteData.blockName ),
			override_type: overrides?.sourceType,
			override_target: overrides?.source,
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
			{ Object.entries( availableOverrides ).map( ( [ key, value ] ) => (
				<SelectControl
					key={ key }
					label={ key }
					options={ [
						{ label: 'Choose an override', value: '-1' },
						...value.map( ( override, index ) => ( {
							label: override.display,
							value: index.toString(),
						} ) ),
					] }
					onChange={ index => updateOverrides( key, parseInt( index, 10 ) ) }
					value={ findIndex( key ).toString() }
				/>
			) ) }
		</PanelBody>
	);
}
