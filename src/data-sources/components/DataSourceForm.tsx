import { __experimentalHeading as Heading } from '@wordpress/components';

type DataSourceFormProps = React.FormHTMLAttributes< HTMLFormElement > & {
	children: React.ReactNode;
	heading: string | React.ReactNode;
};

export const DataSourceForm = ( { children, heading }: DataSourceFormProps ) => {
	return (
		<form className="rdb-settings-page_data-source-form">
			<Heading size={ 24 }>{ heading }</Heading>
			{ children }
		</form>
	);
};
