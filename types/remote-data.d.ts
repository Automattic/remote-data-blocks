interface InnerBlockContext {
	index: number;
}

interface RemoteDataResultFields {
	name: string;
	type: string;
	value: string;
}

interface QueryInputOverride {
	target: string;
	type: 'query_var' | 'url';
}

interface RemoteData {
	blockName: string;
	isCollection: boolean;
	metadata: Record< string, RemoteDataResultFields >;
	queryInput: Record< string, string >;
	queryInputOverrides?: Record< string, QueryInputOverride >;
	resultId: string;
	results: Record< string, string >[];
}

interface ContextBlockAttributes {
	remoteData: RemoteData;
}

interface FieldSelection extends ContextBlockAttributes {
	selectedField: string;
	type: 'field' | 'meta';
}

interface MetaFieldSelection extends FieldSelection {
	selectedField: 'last_updated' | 'total_count';
}

interface ContextBinding {
	source: string;
	args: {
		field: string;
	};
}

interface ContextInnerBlockAttributes {
	alt?: string | RichTextData;
	content?: string | RichTextData;
	index?: number;
	metadata?: {
		bindings?: Record< string, ContextBinding >;
		name?: string;
	};
	url?: string | RichTextData;
}

type RemoteDataQueryInput = Record< string, string >;

interface RemoteDataApiRequest {
	block_name: string;
	query_key: string;
	query_input: RemoteDataQueryInput;
}

interface RemoteDataApiResult {
	output: Record< string, string >;
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
