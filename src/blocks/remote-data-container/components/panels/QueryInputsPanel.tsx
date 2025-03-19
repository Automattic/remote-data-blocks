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
				onSubmit={ event => {
					event.preventDefault();

					const cleanedInputs = queryInputs.map( input => {
						const entries = Object.entries( input ).map( ( [ key, value ] ) => [
							key,
							typeof value === 'string' && value.includes( ',' )
								? value
										.split( ',' )
										.map( item => item.trim() )
										.filter( Boolean )
								: value,
						] );

						return Object.fromEntries( entries ) as RemoteDataQueryInput;
					} );

					onUpdateQueryInputs( cleanedInputs );
				} }
			>
				{ queryInputs.map( ( input, index ) =>
					Object.entries( input ).map( ( [ key, value ] ) => {
						const displayValue = Array.isArray( value ) ? value.join( ',' ) : ( value as string );

						return (
							<TextControl
								key={ `${ index }-${ key }` }
								label={ key }
								value={ displayValue }
								onChange={ newValue => {
									const processedValue = newValue.includes( ',' )
										? newValue.split( ',' ).map( item => item.trim() )
										: newValue;

									onUpdateQueryInputs(
										queryInputs.map( ( item, itemIndex ) =>
											itemIndex === index ? { ...item, [ key ]: processedValue } : item
										)
									);
								} }
							/>
						);
					} )
				) }
				<Button variant="primary" type="submit">
					{ __( 'Update', 'remote-data-blocks' ) }
				</Button>
			</form>
		</PanelBody>
	);
}
