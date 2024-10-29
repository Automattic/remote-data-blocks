import { Button, TextControl } from '@wordpress/components';
import { useState } from '@wordpress/element';

import { ModalWithButtonTrigger } from '@/blocks/remote-data-container/components/modals/base-modal';
import { useModalState } from '@/blocks/remote-data-container/hooks/useModalState';
import { __ } from '@/utils/i18n';

interface InputModalProps {
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

	function wrappedOnSelect(): void {
		props.onSelect( inputState );
		close();
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
				<Button variant="primary" onClick={ wrappedOnSelect }>
					{ __( 'Save' ) }
				</Button>
			</form>
		</ModalWithButtonTrigger>
	);
}
