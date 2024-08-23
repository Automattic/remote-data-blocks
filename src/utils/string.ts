export const toTitleCase = ( str: string ): string => {
	return str.replace( /\w\S*/g, txt => {
		return txt.charAt( 0 ).toUpperCase() + txt.substring( 1 ).toLowerCase();
	} );
};
