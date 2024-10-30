import { PlaceholderLoop } from '@/blocks/remote-data-container/components/placeholders/PlaceholderLoop';
import { PlaceholderSingle } from '@/blocks/remote-data-container/components/placeholders/PlaceholderSingle';

export interface PlaceholderProps {
	blockConfig: BlockConfig;
	fetchRemoteData: ( input: RemoteDataQueryInput ) => void;
}

export function Placeholder( props: PlaceholderProps ) {
	const { loop } = props.blockConfig;
	const placeholderProps = {
		blockConfig: props.blockConfig,
		onSelect: props.fetchRemoteData,
	};

	if ( loop ) {
		return <PlaceholderLoop { ...placeholderProps } />;
	}

	return <PlaceholderSingle { ...placeholderProps } />;
}
