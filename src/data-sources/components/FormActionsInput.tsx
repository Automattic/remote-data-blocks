import { Button, ButtonGroup } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

interface FormActionsInputProps {
	onSave: () => Promise< void >;
	onCancel: () => void;
	saveDisabled: boolean;
}

export const FormActionsInput = ( { onSave, onCancel, saveDisabled }: FormActionsInputProps ) => {
	return (
		<div className="form-group">
			<ButtonGroup className="form-actions">
				<Button variant="primary" onClick={ () => void onSave() } disabled={ saveDisabled }>
					{ __( 'Save', 'remote-data-blocks' ) }
				</Button>
				<Button variant="secondary" onClick={ onCancel }>
					{ __( 'Cancel', 'remote-data-blocks' ) }
				</Button>
			</ButtonGroup>
		</div>
	);
};
