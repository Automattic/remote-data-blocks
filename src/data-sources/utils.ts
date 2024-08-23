export const sanitizeDatasourceSlug = ( slug: string ) => {
	return slug.replace( /[^a-z0-9-]/g, '' ).toLowerCase();
};
