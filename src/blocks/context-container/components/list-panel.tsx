import { Button, Flex, MenuGroup, Modal, Spinner } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { MenuSelectOne } from './menu-select-one';
import { useRemoteData } from '../hooks/use-remote-data';

interface ListPanelProps {
	blockName: string;
	onSelect: ( data: Record< string, string > ) => void;
	panel: {
		query_key: string;
	};
}

export function ListPanel( props: ListPanelProps ) {
	const { blockName, onSelect, panel } = props;

	const [ itemSelectorIsOpen, setItemSelectorIsOpen ] = useState< boolean >( false );
	const { data, execute, loading } = useRemoteData( blockName, panel.query_key );

	let results = <p>{ __( 'Select an item', 'remote-data-blocks' ) }</p>;

	if ( loading ) {
		results = <Spinner />;
	} else if ( data?.results.length === 0 ) {
		results = <p>{ __( 'No items found', 'remote-data-blocks' ) }</p>;
	} else if ( data?.results.length ) {
		results = (
			<MenuSelectOne
				blockName={ props.blockName }
				searchResults={ data.results }
				onClick={ ( input: Record< string, string > ) => {
					onSelect( input );
					setItemSelectorIsOpen( false );
				} }
			/>
		);
	}

	return (
		<>
			<Button
				variant="primary"
				onClick={ () => {
					void execute( {} );
					setItemSelectorIsOpen( true );
				} }
			>
				{ __( 'Select an item from a list', 'remote-data-blocks' ) }
			</Button>

			{ itemSelectorIsOpen && (
				<Modal
					title={ __( 'Remote Data', 'remote-data-blocks' ) }
					size="large"
					onRequestClose={ () => setItemSelectorIsOpen( false ) }
				>
					<form style={ { marginTop: '1rem' } }>
						<Flex direction="column" gap={ 2 }>
							<MenuGroup label="Results">{ results }</MenuGroup>
						</Flex>
					</form>
				</Modal>
			) }
		</>
	);
}
