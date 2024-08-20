interface LocalizedSettingsData {
	branch: string;
	hash: string;
	version: string;
}

type NullableKeys< T, K extends keyof T > = Omit< T, K > & {
	[ P in K ]: T[ P ] | null;
};
