type RemoteDataBinding = Pick< RemoteDataResultFields, 'name' | 'type' >;
type AvailableBindings = Record< string, RemoteDataBinding >;

/**
 * This corresponds directly to the input schema defined for a data source.
 */
interface InputVariable {
	/** The display friendly name of the variable */
	name: string;
	/** Whether the variable is required */
	required: boolean;
	/** The slug of the variable */
	slug: string;
	/** The type of the variable */
	type: string;
}

/**
 * This corresponds directly to the overrides defined for a data source.
 */
interface InputVariableOverride {
	/** The display friendly name of the variable */
	display_name?: string;
	/** The help text of the variable */
	help_text?: string;
	/** The slug of the variable */
	name: string;
}

/**
 * This corresponds directly to the block config.
 */
interface BlockConfig {
	/** The available bindings for the block */
	availableBindings: AvailableBindings;
	/** The available overrides for the block */
	availableOverrides: InputVariableOverride[];
	/** The data source type of the block */
	dataSourceType: string;
	/** Whether the block is a loop */
	loop: boolean;
	/** The name of the block */
	name: string;
	/** The patterns of the block */
	patterns: {
		/** The default pattern of the block */
		default: string;
		/** The inner blocks of the block */
		inner_blocks?: string;
	};
	/** The selectors of the block */
	selectors: {
		/** The image URL of the block */
		image_url?: string;
		/** The inputs of the block */
		inputs: InputVariable[];
		/** The name of the block */
		name: string;
		/** The query key of the block */
		query_key: string;
		/** The type of the block */
		type: string;
	}[];
	/** The settings of the block */
	settings: {
		/** The category of the block */
		category: string;
		/** The description of the block */
		description?: string;
		/** The title of the block */
		title: string;
	};
}

/**
 * This corresponds directly to the blocks config.
 */
interface BlocksConfig {
	[ blockName: string ]: BlockConfig;
}

/**
 * This corresponds directly to the localized block data.
 */
interface LocalizedBlockData {
	/** The blocks config */
	config: BlocksConfig;
	/** The REST URL */
	rest_url: string;
	/** The tracks global properties */
	tracks_global_properties?: TracksGlobalProperties;
}
