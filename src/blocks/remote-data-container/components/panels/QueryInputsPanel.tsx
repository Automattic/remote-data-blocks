import { Button, PanelBody, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

interface QueryInputsPanelProps {
	queryInputs: RemoteDataQueryInput[];
	onUpdateQueryInputs: ( inputs: RemoteDataQueryInput[] ) => void;
}

export function QueryInputsPanel( { queryInputs, onUpdateQueryInputs }: QueryInputsPanelProps ) {
	return (
		<PanelBody title={ __( 'Query Inputs', 'remote-data-blocks' ) }>
			<form
				onSubmit={ ( event: React.FormEvent ) => {
					event.preventDefault();
					onUpdateQueryInputs( queryInputs );
				} }
			>
				{ queryInputs.map( ( input, index ) =>
					Object.entries( input ).map( ( [ key, value ] ) => (
						<TextControl
							key={ `${ index }-${ key }` }
							label={ key }
							value={ value as string }
							onChange={ newValue => {
								const newInputs = queryInputs.map( ( item, i ) =>
									i === index ? { ...item, [ key ]: newValue } : item
								);
								onUpdateQueryInputs( newInputs );
							} }
						/>
					) )
				) }
				<Button variant="primary" type="submit">
					{ __( 'Update', 'remote-data-blocks' ) }
				</Button>
			</form>
		</PanelBody>
	);
}
