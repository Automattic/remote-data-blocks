import {
	Button,
	__experimentalInputControl as InputControl,
	__experimentalInputControlSuffixWrapper as InputControlSuffixWrapper,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { seen, unseen } from '@wordpress/icons';
import { ComponentPropsWithoutRef } from 'react';

type PasswordInputControlProps = ComponentPropsWithoutRef< typeof InputControl >;

const PasswordInputControl = ( { ...props }: PasswordInputControlProps ) => {
	const [ visible, setVisible ] = useState( false );

	return (
		<InputControl
			{ ...props }
			suffix={
				<InputControlSuffixWrapper>
					<div style={ { display: 'flex' } }>
						<Button
							size="small"
							icon={ visible ? unseen : seen }
							label={
								visible
									? __( 'Hide password', 'remote-data-blocks' )
									: __( 'Show password', 'remote-data-blocks' )
							}
							onClick={ () => setVisible( ! visible ) }
						/>
					</div>
				</InputControlSuffixWrapper>
			}
			type={ visible ? 'text' : 'password' }
		/>
	);
};

export default PasswordInputControl;
