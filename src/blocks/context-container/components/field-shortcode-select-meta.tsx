import { __experimentalHeading as Heading } from '@wordpress/components';

import { FieldSelectionFromMetaFields } from './field-shortcode-select-field';
import { getBlocksConfig } from '../../../utils/localized-block-data';
import { useExistingRemoteData } from '../hooks/use-existing-remote-data';

interface FieldShortcodeSelectMetaProps {
	onSelectField: ( data: FieldSelection, fieldValue: string ) => void;
}

export function FieldShortcodeSelectMeta( props: FieldShortcodeSelectMetaProps ) {
	const blockConfigs = getBlocksConfig();
	const remoteDatas: RemoteData[] = useExistingRemoteData();

	if ( remoteDatas.length === 0 ) {
		return (
			<div className="remote-data-blocks-select-meta">
				<p>No query metadata avaialble.</p>
			</div>
		);
	}

	return (
		<div className="remote-data-blocks-select-meta">
			{ remoteDatas.map( remoteData => (
				<div className="remote-data-blocks-meta-item" key={ remoteData.blockName }>
					<Heading className="remote-data-blocks-item-heading" level="4">
						{ blockConfigs[ remoteData.blockName ]?.settings.title ?? remoteData.blockName }
					</Heading>

					<FieldSelectionFromMetaFields
						onSelectField={ props.onSelectField }
						remoteData={ remoteData }
					/>
				</div>
			) ) }
		</div>
	);
}
