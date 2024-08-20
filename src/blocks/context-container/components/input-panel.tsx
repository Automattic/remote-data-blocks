import { Button, Modal, TextControl } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

interface InputPanelProps {
	blockName: string;
	onSelect: ( data: Record< string, string > ) => void;
	panel: {
		inputs: InputVariable[];
		name: string;
		query_key: string;
	};
}

export function InputPanel( props: InputPanelProps ) {
	const { onSelect, panel } = props;
	const initialInputState = panel.inputs.reduce(
		( acc, input ) => ( { ...acc, [ input.slug ]: '' } ),
		{}
	);

	const [ itemSelectorIsOpen, setItemSelectorIsOpen ] = useState< boolean >( false );
	const [ inputState, setInputState ] = useState< Record< string, string > >( initialInputState );

	function onChange( field: string, value: string ): void {
		setInputState( { ...inputState, [ field ]: value } );
	}

	return (
		<>
			<Button
				variant="secondary"
				onClick={ () => {
					setItemSelectorIsOpen( true );
				} }
			>
				{ __( 'Provide manual input', 'remote-data-blocks' ) }
			</Button>

			{ itemSelectorIsOpen && (
				<Modal
					title={ __( panel.name, 'remote-data-blocks' ) }
					size="large"
					onRequestClose={ () => setItemSelectorIsOpen( false ) }
				>
					<form style={ { marginTop: '1rem' } }>
						{ props.panel.inputs.map( input => (
							<TextControl
								key={ input.slug }
								label={ input.name }
								required={ input.required }
								value={ inputState[ input.slug ] ?? '' }
								onChange={ ( value: string ) => onChange( input.slug, value ) }
							/>
						) ) }
						<Button variant="primary" onClick={ () => onSelect( inputState ) }>
							{ __( 'Save', 'remote-data-blocks' ) }
						</Button>
					</form>
				</Modal>
			) }
		</>
	);
}
