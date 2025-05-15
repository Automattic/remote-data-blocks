import {
	Button,
	__experimentalToggleGroupControl as ToggleGroupControl,
	IconType,
	Placeholder as PlaceholderComponent,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { cloud } from '@wordpress/icons';

export interface QuerySelectionPlaceholderProps {
	blockConfig: BlockConfig;
	onSelect: ( queryKey: string ) => void;
}

export function QuerySelectionPlaceholder( props: QuerySelectionPlaceholderProps ) {
	const { blockConfig, onSelect } = props;
	const { instructions, settings, selectors } = blockConfig;

	const iconElement: IconType = ( settings.icon as IconType ) ?? cloud;

	return (
		<PlaceholderComponent
			icon={ iconElement }
			label={ settings.title }
			instructions={
				instructions ?? __( 'This block requires selection of one or more items for display.' )
			}
		>
			<ToggleGroupControl
				className="remote-data-blocks-button-group"
				label={ __( '' ) }
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			>
				{ selectors.map( selector => {
					return (
						<Button
							key={ selector.query_key }
							variant="primary"
							onClick={ () => onSelect( selector.query_key ) }
						>
							{ selector.display_name ?? selector.name }
						</Button>
					);
				} ) }
			</ToggleGroupControl>
		</PlaceholderComponent>
	);
}
