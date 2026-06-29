/**
 * The types provided by @types/wordpress__block-editor are out of date.
 */

declare module '@wordpress/block-editor' {
	type BlockAttributes = import('@wordpress/blocks').BlockAttributes;
	type BlockInstance< T extends Record< string, unknown > = Record< string, unknown > > =
		import('@wordpress/blocks').BlockInstance< T >;
	type ReactElement = import('react').ReactElement;
	type ComponentType< P = any > = import('react').ComponentType< P >;

	const store: import('@wordpress/data').StoreDescriptor;

	const BlockControls: ComponentType< any >;
	const InspectorControls: ComponentType< any >;
	const InnerBlocks: ComponentType< any > & {
		Content: ComponentType< any >;
		DefaultBlockAppender: ComponentType< any >;
	};

	type EditorStyle = Record< string, unknown >;

	function BlockContextProvider( props: { children: ReactElement; value: object } ): JSX.Element;
	function transformStyles( styles: EditorStyle[], wrapperSelector?: string ): string[];
	const useBlockProps: ( ( props?: object ) => Record< string, unknown > ) & {
		save: ( props?: object ) => Record< string, unknown >;
	};
	function useInnerBlocksProps( props?: object, options?: object ): Record< string, unknown >;

	function useBlockEditContext(): {
		clientId: string;
		[ key: string ]: unknown;
	};

	function __experimentalUseBlockPreview( props: {
		blocks: BlockInstance[];
		props: object;
	} ): object;

	function __experimentalBlockPatternsList( props: {
		blockPatterns: BlockPattern[];
		onClickPattern: ( pattern: BlockPattern, blocks: BlockInstance[] ) => void;
		shownPatterns: BlockPattern[];
	} ): JSX.Element;

	// Incomplete type for our use case.
	interface BlockPattern {
		blocks: BlockInstance< RemoteDataInnerBlockAttributes >[];
		blockTypes?: string[];
		id?: number;
		name: string;
		source: string;
		syncStatus: string;
		title: string;
	}

	interface BlockEditorStoreActions {
		replaceInnerBlocks: ( clientId: string, blocks: BlockInstance[] ) => Promise< void >;
		selectBlock: ( clientId: string, initialPosition?: number? ) => Promise< void >;
	}

	interface BlockEditorStoreSelectors {
		__experimentalGetAllowedPatterns: ( clientId: string ) => BlockPattern[];
		getBlocks: < T extends BlockAttributes >( clientId: string ) => BlockInstance< T >[];
		getBlocksByClientId: < T extends BlockAttributes >( clientId: string ) => BlockInstance< T >[];
		getBlocksByName: ( name: string ) => string[];
		getPatternsByBlockTypes: ( name: string | string[], clientId?: string ) => BlockPattern[];
		getSettings: () => EditorSettings;
		hasMultiSelection: () => boolean;
	}
}
