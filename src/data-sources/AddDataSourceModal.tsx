import {
	Button,
	ButtonGroup,
	Modal,
	Panel,
	PanelBody,
	PanelRow,
	SelectControl,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { DataSourceType } from './types';

interface AddDataSourceModalProps {
	onSubmit: ( service: DataSourceType ) => void;
}

const AddDataSourceModal = ( { onSubmit }: AddDataSourceModalProps ) => {
	const [ isOpen, setOpen ] = useState( false );
	const [ selectedService, setSelectedService ] = useState< DataSourceType | undefined >();

	const openModal = () => setOpen( true );
	const closeModal = () => setOpen( false );
	const resetModal = () => {
		closeModal();
		setSelectedService( undefined );
	};

	const onAddClick = () => {
		if ( ! selectedService ) {
			return;
		}
		onSubmit( selectedService );
		resetModal();
	};

	return (
		<>
			<Button variant="primary" onClick={ openModal }>
				{ __( 'Add Data Source', 'remote-data-blocks' ) }
			</Button>
			{ isOpen && (
				<Modal
					title={ __( 'Add a Data Source' ) }
					onRequestClose={ resetModal }
					className="add-data-source-modal"
				>
					<Panel>
						<PanelBody>
							<PanelRow>
								<SelectControl
									label="Service"
									options={ [
										{ label: __( 'Choose a Service' ), value: '' },
										{ label: 'Airtable', value: 'airtable' },
										{ label: 'Shopify', value: 'shopify' },
									] }
									onChange={ value => setSelectedService( value as DataSourceType ) }
								/>
							</PanelRow>
							<PanelRow>
								<ButtonGroup>
									<Button variant="secondary" onClick={ resetModal }>
										{ __( 'Cancel', 'remote-data-blocks' ) }
									</Button>
									<Button variant="primary" onClick={ onAddClick } disabled={ ! selectedService }>
										{ __( 'Add', 'remote-data-blocks' ) }
									</Button>
								</ButtonGroup>
							</PanelRow>
						</PanelBody>
					</Panel>
				</Modal>
			) }
		</>
	);
};

export default AddDataSourceModal;
