import { Button, ButtonGroup } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

interface DataSourceFormActionsProps {
	onSave: () => Promise< void >;
	onCancel: () => void;
	IsSaveDisabled: boolean;
}

export const DataSourceFormActions = ( {
	onSave,
	onCancel,
	IsSaveDisabled,
}: DataSourceFormActionsProps ) => {
	return (
		<div className="form-group">
			<ButtonGroup className="form-actions">
				<Button variant="primary" onClick={ () => void onSave() } disabled={ IsSaveDisabled }>
					{ __( 'Save', 'remote-data-blocks' ) }
				</Button>
				<Button variant="secondary" onClick={ onCancel }>
					{ __( 'Cancel', 'remote-data-blocks' ) }
				</Button>
			</ButtonGroup>
		</div>
	);
};
