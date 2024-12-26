import { Icon } from '@wordpress/components';
import { chevronRightSmall } from '@wordpress/icons';

import { DataSourceConfig } from '@/data-sources/types';
import './DataSourceList.scss';

interface DataSourceMetaTagsProps {
	source: DataSourceConfig;
}

interface DataSourceMetaTag {
	key: string;
	primaryValue: string;
	secondaryValue?: string;
}

const DataSourceDescriptor = ( props: DataSourceMetaTagsProps ) => {
	let tag: DataSourceMetaTag | undefined;

	switch ( props.source.service ) {
		case 'airtable':
			tag = {
				key: 'base',
				primaryValue: props.source.service_config.base?.name,
				secondaryValue: props.source.service_config.tables?.[ 0 ]?.name,
			};
			break;
		case 'shopify':
			tag = { key: 'store', primaryValue: props.source.service_config.store_name };
			break;
		case 'google-sheets':
			tag = {
				key: 'spreadsheet',
				primaryValue: props.source.service_config.spreadsheet.name ?? 'Google Sheet',
				secondaryValue: props.source.service_config.sheet.name,
			};
			break;
	}

	if ( ! tag ) {
		return null;
	}

	return (
		<span key={ tag.key } className="data-source-meta">
			{ tag.primaryValue }
			{ tag.secondaryValue && (
				<>
					<Icon icon={ chevronRightSmall } style={ { fill: '#949494', verticalAlign: 'middle' } } />
					{ tag.secondaryValue }
				</>
			) }
		</span>
	);
};

const DataSourceMetaTags = ( props: DataSourceMetaTagsProps ) => {
	return (
		<>
			{ ! props.source.uuid && <span className="data-source-badge">Code</span> }
			<DataSourceDescriptor source={ props.source } />
		</>
	);
};

export default DataSourceMetaTags;
