type RemoteDataBinding = Pick< RemoteDataResultFields, 'name' | 'type' >;
type AvailableBindings = Record< string, RemoteDataBinding >;

interface InputVariableOverrides {
	name: string;
	overrides: QueryInputOverride[];
	type: string;
}

interface InputVariable {
	name: string;
	required: boolean;
	slug: string;
	type: string;
}

interface BlockConfig {
	availableBindings: AvailableBindings;
	dataSourceType: string;
	loop: boolean;
	name: string;
	overrides: Record< string, InputVariableOverrides >;
	patterns: {
		default: string;
		inner_blocks?: string;
	};
	selectors: {
		image_url?: string;
		inputs: InputVariable[];
		name: string;
		query_key: string;
		type: string;
	}[];
	settings: {
		category: string;
		description?: string;
		title: string;
	};
}

interface BlocksConfig {
	[ blockName: string ]: BlockConfig;
}

interface LocalizedBlockData {
	config: BlocksConfig;
	rest_url: string;
	tracks_global_properties?: TracksGlobalProperties;
}
