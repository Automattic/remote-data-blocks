import {
	Button,
	Card,
	CardBody,
	CardFooter,
	CardHeader,
	__experimentalHeading as Heading,
	Popover,
} from '@wordpress/components';
import { WPFormat, useAnchor } from '@wordpress/rich-text';

import { FieldShortcodeSelectField } from '@/blocks/remote-data-container/components/field-shortcode/FieldShortcodeSelection';

interface FieldShortcodeSelectFieldPopoverProps {
	contentRef: React.RefObject< HTMLElement >;
	fieldSelection: FieldSelection;
	formatTypeSettings: WPFormat;
	onSelectField: ( data: FieldSelection, fieldValue: string ) => void;
	onClose: () => void;
	resetField: () => void;
}

export function FieldShortcodeSelectFieldPopover( props: FieldShortcodeSelectFieldPopoverProps ) {
	const popoverAnchor = useAnchor( {
		editableContentElement: props.contentRef.current,
		settings: props.formatTypeSettings,
	} );

	return (
		<Popover
			placement="bottom-start"
			anchor={ popoverAnchor }
			className="block-editor-format-toolbar__image-popover"
			noArrow={ false }
			offset={ 8 }
			onClose={ props.onClose }
			// Focus the first element (the field-name combobox) if it's empty when the popover is opened.
			focusOnMount="firstElement"
		>
			<Card style={ { width: '24rem' } }>
				<CardHeader>
					<Heading level={ 4 }>Select a field to bind</Heading>
				</CardHeader>
				<CardBody>
					<FieldShortcodeSelectField
						blockName={ props.fieldSelection.remoteData?.blockName ?? 'Remote Data Block' }
						fieldType={ props.fieldSelection.type ?? 'field' }
						onSelectField={ props.onSelectField }
						queryInput={ props.fieldSelection.remoteData?.queryInput ?? {} }
						selectedField={ props.fieldSelection.selectedField }
					/>
				</CardBody>
				<CardFooter>
					<Button onClick={ props.resetField } isDestructive>
						Reset field
					</Button>
				</CardFooter>
			</Card>
		</Popover>
	);
}
