import { Button, PanelBody, TextControl } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

interface QueryInputsPanelProps {
	onUpdateQueryInputs: ( selectorQueryKey: string, inputs: RemoteDataQueryInput[] ) => void;
	remoteData: RemoteData;
	selectors: Selector[];
}

export function QueryInputsPanel( {
	onUpdateQueryInputs,
	remoteData,
	selectors,
}: QueryInputsPanelProps ) {
	const { queryInputs = [], selectorQueryKey = '' } = remoteData;
	const [ localInputs, setLocalInputs ] = useState( queryInputs );
	const inputDefinitions =
		selectors?.find( selector => selector.query_key === selectorQueryKey )?.inputs ?? [];

	return (
		<PanelBody title={ __( 'Query Inputs', 'remote-data-blocks' ) }>
			<form
				onSubmit={ event => {
					event.preventDefault();
					const cleanedInputs = localInputs.map( input => {
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

					onUpdateQueryInputs( selectorQueryKey, cleanedInputs );
				} }
			>
				{ localInputs.map( ( input, index ) =>
					Object.entries( input ).map( ( [ key, value ] ) => {
						const displayValue = Array.isArray( value ) ? value.join( ',' ) : ( value as string );
						const inputDefinition = inputDefinitions.find( definition => definition.slug === key );

						return (
							<TextControl
								key={ `${ index }-${ key }` }
								label={ inputDefinition?.name ?? key }
								value={ displayValue }
								onChange={ newValue => {
									setLocalInputs(
										localInputs.map( ( item, itemIndex ) =>
											itemIndex === index ? { ...item, [ key ]: newValue } : item
										)
									);
								} }
								onBlur={ () => {
									onUpdateQueryInputs( selectorQueryKey, localInputs );
								} }
								__next40pxDefaultSize
								__nextHasNoMarginBottom
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
