import CheckIcon from '@/settings/icons/CheckIcon';
import ErrorIcon from '@/settings/icons/ErrorIcon';

export const sanitizeDataSourceSlug = ( slug: string ) => {
	return slug.replace( /[^a-z0-9-]/gi, '' ).toLowerCase();
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
