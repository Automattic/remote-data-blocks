import { Card, CardBody, MenuItem } from '@wordpress/components';
import { applyFilters } from '@wordpress/hooks';

const filterHookName = 'remote-data-blocks.list-item';

interface MenuSelectOneProps {
	searchResults: Record< string, string >[];
	onClick: ( input: Record< string, string > ) => void;
	blockName: string;
}

export function MenuSelectOne( props: MenuSelectOneProps ) {
	const { searchResults, onClick } = props;

	function onMenuItemClick( item: Record< string, string > ) {
		onClick( item );
	}

	const DefaultChildComponent = ( { item }: { item: Record< string, string > } ) => (
		<Card>
			<CardBody>
				{ Object.entries( item ).map( ( [ key, value ], index ) => (
					<div key={ index } className="remote-data-blocks-search-field">
						<strong>{ key }</strong>: { value }
					</div>
				) ) }
			</CardBody>
		</Card>
	);
	const ChildComponent = applyFilters(
		filterHookName,
		DefaultChildComponent,
		props
	) as React.ComponentType< { item: Record< string, string > } >;

	const choices = searchResults.map( ( item, itemIndex ) => {
		return (
			<MenuItem
				key={ itemIndex }
				onClick={ () => onMenuItemClick( item ) }
				role="menuitemradio"
				className="remote-data-blocks-menu-item"
			>
				<div className="remote-data-blocks-search-result">
					<ChildComponent item={ item } />
				</div>
			</MenuItem>
		);
	} );

	return choices;
}
