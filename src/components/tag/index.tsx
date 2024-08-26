import { __experimentalText as Text } from '@wordpress/components';
import React from 'react';
import './style.scss';

interface TagProps {
	label?: string;
	value: string;
	id: string;
}

const styles = {
	label: {
		backgroundColor: '#17a2b8',
		padding: '2px',
		fontSize: '10px',
	},
	value: {
		backgroundColor: '#e6e6fa',
		padding: '2px',
		fontSize: '10px',
	},
	container: {
		border: '1px solid #17a2b8',
		borderRadius: '8px',
		display: 'inline-flex',
		color: '#fff',
		overflow: 'hidden',
	},
};

export const Tag = ( { label, value, id }: TagProps ) => (
	<div id={ id } style={ styles.container }>
		{ label && (
			<Text style={ styles.label } optimizeReadabilityFor={ styles.label.backgroundColor }>
				{ label }
			</Text>
		) }
		<Text style={ styles.value } optimizeReadabilityFor={ styles.value.backgroundColor }>
			{ value }
		</Text>
	</div>
);
