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
	category: string;
	description: string;
	loop: boolean;
	name: string;
	overrides: Record< string, InputVariableOverrides >;
	panels: {
		inputs: InputVariable[];
		name: string;
		query_key: string;
		type: string;
	}[];
	title: string;
}

interface BlocksConfig {
	[ blockName: string ]: BlockConfig;
}

interface LocalizedData {
	config: BlocksConfig;
	rest_url: string;
}
