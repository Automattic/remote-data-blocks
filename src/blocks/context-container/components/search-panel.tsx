import { Button, Flex, MenuGroup, Modal, SearchControl, Spinner } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

import { MenuSelectOne } from './menu-select-one';
import { useRemoteData } from '../hooks/use-remote-data';

interface SearchPanelProps {
	blockName: string;
	onSelect: ( data: RemoteDataQueryInput ) => void;
	panel: {
		query_key: string;
	};
}

export function SearchPanel( props: SearchPanelProps ) {
	const { blockName, onSelect, panel } = props;

	const [ itemSelectorIsOpen, setItemSelectorIsOpen ] = useState< boolean >( false );
	const [ searchTerms, setSearchTerms ] = useState< string >( '' );
	const { data, execute, loading } = useRemoteData( blockName, panel.query_key );

	function captureEnterKey( event: React.KeyboardEvent< HTMLInputElement > ) {
		if ( event.code !== 'Enter' ) {
			return;
		}

		event.preventDefault();

		void execute( { search_terms: searchTerms } );
	}

	useEffect( () => {
		if ( itemSelectorIsOpen ) {
			void execute( { search_terms: searchTerms } );
		}
	}, [ itemSelectorIsOpen, searchTerms ] );

	let results = <p>{ __( 'Search to select an item', 'remote-data-blocks' ) }</p>;

	if ( loading ) {
		results = <Spinner />;
	} else if ( data?.results.length === 0 ) {
		results = <p>{ __( 'No items found for this search', 'remote-data-blocks' ) }</p>;
	} else if ( data?.results.length ) {
		results = (
			<MenuSelectOne
				blockName={ blockName }
				searchResults={ data.results }
				onClick={ ( input: RemoteDataQueryInput ) => {
					onSelect( input );
					setItemSelectorIsOpen( false );
				} }
			/>
		);
	}

	const HeaderComponent = applyFilters(
		'remote-data-blocks.list-header',
		() => <h1>Search</h1>,
		props
	) as React.ComponentType< SearchPanelProps >;

	return (
		<>
			<Button variant="primary" onClick={ () => setItemSelectorIsOpen( true ) }>
				{ __( 'Search for an item', 'remote-data-blocks' ) }
			</Button>

			{ itemSelectorIsOpen && (
				<Modal
					contentLabel={ __( 'Remote Data', 'remote-data-blocks' ) }
					__experimentalHideHeader
					size="large"
					onRequestClose={ () => setItemSelectorIsOpen( false ) }
					style={ { display: 'flex', height: '100%' } }
				>
					<form style={ { marginTop: '1rem', height: '100%' } }>
						<Flex direction="column" gap={ 2 } style={ { height: '100%' } }>
							<div style={ { flexShrink: 0 } }>
								<header>
									<HeaderComponent { ...props } />
								</header>
								<SearchControl
									value={ searchTerms }
									label="Search"
									hideLabelFromVision={ false }
									onKeyDown={ captureEnterKey }
									onChange={ newValue => setSearchTerms( newValue ) }
								/>
							</div>

							<MenuGroup>{ results }</MenuGroup>
						</Flex>
					</form>
				</Modal>
			) }
		</>
	);
}
