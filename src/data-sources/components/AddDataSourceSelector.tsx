import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { SUPPORTED_SERVICES_LABELS } from '@/data-sources/constants';
import { DataSourceType } from '@/data-sources/types';

import './AddDataSourceSelector.scss';

interface AddDataSourceSelectorProps {
    onSelect: (dataSource: DataSourceType) => void;
}

export default function AddDataSourceSelector({ onSelect }: AddDataSourceSelectorProps) {
    // Simple text-based selector to avoid TypeScript issues with the icons
    return (
        <div className="add-data-source-selector">
            <Button
                key="airtable"
                className="add-data-source-selector__button"
                onClick={() => onSelect('airtable')}
            >
                {SUPPORTED_SERVICES_LABELS.airtable}
            </Button>
            
            <Button
                key="google-sheets"
                className="add-data-source-selector__button"
                onClick={() => onSelect('google-sheets')}
            >
                {SUPPORTED_SERVICES_LABELS['google-sheets']}
            </Button>
            
            <Button
                key="shopify"
                className="add-data-source-selector__button"
                onClick={() => onSelect('shopify')}
            >
                {SUPPORTED_SERVICES_LABELS.shopify}
            </Button>
            
            <Button
                key="salesforce-d2c"
                className="add-data-source-selector__button"
                onClick={() => onSelect('salesforce-d2c')}
            >
                {SUPPORTED_SERVICES_LABELS['salesforce-d2c']}
            </Button>
            
            <Button
                key="generic-http"
                className="add-data-source-selector__button"
                onClick={() => onSelect('generic-http')}
            >
                {SUPPORTED_SERVICES_LABELS['generic-http']}
            </Button>
        </div>
    );
}