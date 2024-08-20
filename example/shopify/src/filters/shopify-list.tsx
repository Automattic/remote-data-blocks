import { addFilter } from '@wordpress/hooks';

// Localized script data
declare const SHOPIFY_LIST_PANEL: { assetPath: string };

addFilter(
	'remote-data-blocks.list-header',
	'remote-data-blocks/search-panel',
	(
		DefaultChildComponent,
		searchPanelProps: { blockName: string }
	): React.ComponentType< object > => {
		if ( searchPanelProps.blockName !== 'remote-data-blocks/shopify-product' ) {
			return DefaultChildComponent;
		}

		return () => {
			return (
				<img
					style={ { height: '75px' } }
					src={ `${ SHOPIFY_LIST_PANEL.assetPath }/shopify_logo_black.png` }
					alt="Shopify Logo"
				/>
			);
		};
	}
);

addFilter(
	'remote-data-blocks.list-item',
	'remote-data-blocks/list-panel',
	(
		DefaultChildComponent,
		searchPanelProps: { blockName: string }
	): React.ComponentType< {
		item: {
			image_url: string;
			title: string;
			price: number;
		};
	} > => {
		if ( searchPanelProps.blockName !== 'remote-data-blocks/shopify-product' ) {
			return DefaultChildComponent;
		}

		return ( props: {
			item: {
				image_url: string;
				title: string;
				price: number;
			};
		} ) => {
			return (
				<div>
					<div style={ { display: 'flex', flexDirection: 'row', gap: '8px' } }>
						<img
							style={ { height: '75px', width: '75px', objectFit: 'contain' } }
							src={ props.item.image_url }
							alt="Shopify product"
						/>
						<div>
							<h2>{ props.item.title }</h2>
							<p>{ props.item.price }</p>
						</div>
					</div>
				</div>
			);
		};
	}
);
