import { Component } from '@wordpress/element';

import { PlaceholderError } from './placeholders/PlaceholderError';

type ComponentRenderResult = ReturnType< Component[ 'render' ] >;

interface EditErrorBoundaryProps {
	blockTitle: string;
	children: ComponentRenderResult;
}

interface EditErrorBoundaryState {
	error: Error | null;
}

export class EditErrorBoundary extends Component< EditErrorBoundaryProps, EditErrorBoundaryState > {
	public state: EditErrorBoundaryState = { error: null };

	public static getDerivedStateFromError( error: Error ): EditErrorBoundaryState {
		return { error };
	}

	public render(): ComponentRenderResult {
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
