import { Component } from '@wordpress/element';

import { PlaceholderError } from './placeholders/PlaceholderError';

interface EditErrorBoundaryProps {
	blockTitle: string;
	children: React.ReactNode;
}

interface EditErrorBoundaryState {
	error: Error | null;
}

export class EditErrorBoundary extends Component< EditErrorBoundaryProps, EditErrorBoundaryState > {
	public state: EditErrorBoundaryState = { error: null };

	public static getDerivedStateFromError( error: Error ): EditErrorBoundaryState {
		return { error };
	}

	public render(): React.ReactNode {
		if ( this.state.error ) {
			return (
				<PlaceholderError
					blockTitle={ this.props.blockTitle }
					error={ this.state.error }
					onRetry={ () => this.setState( { error: null } ) }
				/>
			);
		}

		return this.props.children;
	}
}
