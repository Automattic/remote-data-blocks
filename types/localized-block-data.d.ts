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
	loop: boolean;
	name: string;
	overrides: Record< string, InputVariableOverrides >;
	panels: {
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
}
