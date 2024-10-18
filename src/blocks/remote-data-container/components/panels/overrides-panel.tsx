import { PanelBody, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

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

		const overrides = availableOverrides[ key ]?.overrides;
		if ( ! overrides ) {
			return -1;
		}

		return overrides.findIndex( o => o.type === override.type && o.target === override.target );
	}

	function updateOverrides( inputVar: string, index: number ) {
		const overrides = availableOverrides[ inputVar ]?.overrides[ index ];

		if ( ! overrides ) {
			return;
		}

		updateRemoteData( {
			...remoteData,
			queryInputOverrides: {
				...( remoteData.queryInputOverrides ?? {} ),
				[ inputVar ]: overrides,
			},
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
					label={ value.name }
					options={ [
						{ label: 'Choose an override', value: '' },
						...value.overrides.map( ( override, index ) => ( {
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
