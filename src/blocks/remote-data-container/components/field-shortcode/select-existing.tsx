import { __experimentalHeading as Heading } from '@wordpress/components';

import { FieldSelectionFromAvailableBindings } from '@/blocks/remote-data-container/components/field-shortcode/select-field';
import { useExistingRemoteData } from '@/blocks/remote-data-container/hooks/use-existing-remote-data';
import { getBlocksConfig } from '@/utils/localized-block-data';

interface FieldShortcodeSelectExistingProps {
	onSelectField: ( data: FieldSelection, fieldValue: string ) => void;
}

export function FieldShortcodeSelectExisting( props: FieldShortcodeSelectExistingProps ) {
	const blockConfigs = getBlocksConfig();
	const remoteDatas: RemoteData[] = useExistingRemoteData().filter(
		remoteData => ! remoteData.isCollection
	);

	if ( remoteDatas.length === 0 ) {
		return (
			<div className="remote-data-blocks-select-existing">
				<p>No existing items.</p>
			</div>
		);
	}

	return (
		<div className="remote-data-blocks-select-existing">
			{ remoteDatas.map( remoteData => (
				<div className="remote-data-blocks-existing-item" key={ remoteData.blockName }>
					<Heading className="remote-data-blocks-item-heading" level="4">
						{ blockConfigs[ remoteData.blockName ]?.settings.title ?? remoteData.blockName }
					</Heading>

					<FieldSelectionFromAvailableBindings
						onSelectField={ props.onSelectField }
						remoteData={ remoteData }
					/>
				</div>
			) ) }
		</div>
	);
}
