import { Button, TextControl } from '@wordpress/components';
import { useState } from '@wordpress/element';

import { ModalWithButtonTrigger } from '@/blocks/remote-data-container/components/modals/BaseModal';
import { useModalState } from '@/blocks/remote-data-container/hooks/useModalState';
import { sendTracksEvent } from '@/blocks/remote-data-container/utils/tracks';
import { __ } from '@/utils/i18n';
import { getBlockDataSourceType } from '@/utils/localized-block-data';

interface InputModalProps {
	blockName: string;
	headerImage?: string;
	inputs: InputVariable[];
	onSelect: ( data: RemoteDataQueryInput ) => void;
	title: string;
}

export function InputModal( props: InputModalProps ) {
	const initialInputState = props.inputs.reduce(
		( acc, input ) => ( { ...acc, [ input.slug ]: '' } ),
		{}
	);

	const [ inputState, setInputState ] = useState< Record< string, string > >( initialInputState );
	const { close, isOpen, open } = useModalState();

	function onChange( field: string, value: string ): void {
		setInputState( { ...inputState, [ field ]: value } );
	}

	function onSelectItem(): void {
		props.onSelect( inputState );
		close();
		sendTracksEvent( 'remotedatablocks_add_block', {
			action: 'select_item',
			selected_option: 'manual_input',
			data_source_type: getBlockDataSourceType( props.blockName ),
		} );
	}

	return (
		<ModalWithButtonTrigger
			buttonText="Provide manual input"
			buttonVariant="secondary"
			headerImage={ props.headerImage }
			isOpen={ isOpen }
			onClose={ close }
			onOpen={ open }
			title={ props.title }
		>
			<form style={ { marginTop: '1rem' } }>
				{ props.inputs.map( input => (
					<TextControl
						key={ input.slug }
						label={ input.name }
						required={ input.required }
						value={ inputState[ input.slug ] ?? '' }
						onChange={ ( value: string ) => onChange( input.slug, value ) }
						__nextHasNoMarginBottom
						style={ { marginBottom: '8px' } }
					/>
				) ) }
				<Button variant="primary" onClick={ onSelectItem }>
					{ __( 'Save' ) }
				</Button>
			</form>
		</ModalWithButtonTrigger>
	);
}
