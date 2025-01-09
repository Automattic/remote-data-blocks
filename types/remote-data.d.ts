interface InnerBlockContext {
	index: number;
}

interface RemoteDataResultFields {
	name: string;
	type: string;
	value: string;
}

type RemoteDataResult = Record< string, unknown >;
type RemoteDataQueryInput = Record< string, unknown >;

interface RemoteData {
	blockName: string;
	enabledOverrides?: string[];
	isCollection: boolean;
	metadata: Record< string, RemoteDataResultFields >;
	queryInput: RemoteDataQueryInput;
	resultId: string;
	results: RemoteDataResult[];
}

interface RemoteDataBlockAttributes {
	remoteData?: RemoteData;
}

interface FieldSelection extends RemoteDataBlockAttributes {
	selectedField: string;
	action: 'add_field_shortcode' | 'update_field_shortcode' | 'reset_field_shortcode';
	type: 'field' | 'meta';
	selectionPath: 'select_new_tab' | 'select_existing_tab' | 'select_meta_tab' | 'popover';
}

interface MetaFieldSelection extends FieldSelection {
	selectedField: 'last_updated' | 'total_count';
}

interface RemoteDataBlockBindingArgs {
	block: string;
	field: string;
	label?: string;
}

interface RemoteDataBlockBinding {
	source: string;
	args: RemoteDataBlockBindingArgs;
}

interface RemoteDataInnerBlockAttributes {
	alt?: string | RichTextData;
	className?: string;
	content?: string | RichTextData;
	index?: number;
	metadata?: {
		bindings?: Record< string, RemoteDataBlockBinding >;
		name?: string;
	};
	url?: string | RichTextData;
}

interface RemoteDataApiRequest {
	block_name: string;
	query_key: string;
	query_input: RemoteDataQueryInput;
}

interface RemoteDataApiResult {
	result: Record< string, RemoteDataResultFields >;
}

interface RemoteDataApiResponseBody {
	block_name: string;
	is_collection: boolean;
	metadata: Record< string, RemoteDataResultFields >;
	query_input: RemoteDataQueryInput;
	result_id: string;
	results: RemoteDataApiResult[];
}

interface RemoteDataApiResponse {
	body?: RemoteDataApiResponseBody;
}
