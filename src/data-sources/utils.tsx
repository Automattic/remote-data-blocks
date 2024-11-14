import CheckIcon from '@/settings/icons/CheckIcon';
import ErrorIcon from '@/settings/icons/ErrorIcon';

/**
 * Tranforms a string into a valid data source slug.
 *
 * @param input The string to slugify.
 */
export const slugify = ( input: string ) => {
	return input
		.toString() // Ensure input is a string
		.toLowerCase() // Convert to lowercase
		.trim() // Trim leading and trailing spaces
		.replace( /\s+/g, '-' ) // Replace spaces with hyphens
		.replace( /[^a-z0-9-]/g, '' ) // Remove invalid characters
		.replace( /--+/g, '-' ) // Replace multiple hyphens with a single hyphen
		.replace( /^-+|-+$/g, '' ); // Trim leading and trailing hyphens
};

export function getConnectionMessage(
	status: 'success' | 'error' | null,
	message: string
): JSX.Element {
	const StatusIcon = () => {
		if ( status === 'success' ) {
			return <CheckIcon />;
		}

		if ( status === 'error' ) {
			return <ErrorIcon />;
		}

		return null;
	};

	return (
		<div className={ status ? `status-message is-${ status }` : '' }>
			{ status && (
				<span className="status-icon">
					<StatusIcon />
				</span>
			) }
			{ message }
		</div>
	);
}
