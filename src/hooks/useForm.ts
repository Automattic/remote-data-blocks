import { useReducer, useState } from '@wordpress/element';

import { isNonEmptyObj, constructObjectWithValues } from '@/utils/object';

export type ValidationRuleFn< T > = ( v: Partial< T > ) => string | null;

export type ValidationRules< T > = {
	[ P in keyof Omit< T, '__version' | 'display_name' > ]: ValidationRuleFn< T >;
};

const executeValidationRules = < T >( rule: ValidationRuleFn< T >, value: T ): string | null => {
	const error = rule( value );
	if ( error ) {
		return error;
	}
	return null;
};

interface ExecuteAllValidationRules {
	errorsObj: { [ x: string ]: string | null };
	hasError: boolean;
}

const executeAllValidationRules = < T >(
	validationRules: ValidationRules< T >,
	values: T
): ExecuteAllValidationRules => {
	const errorsMap = new Map< string, string | null >();
	let hasError = false;

	Object.entries( validationRules ).forEach( ( [ ruleId, rule ] ) => {
		if ( Object.prototype.hasOwnProperty.call( values, ruleId ) ) {
			// eslint-disable-next-line security/detect-object-injection
			const error = executeValidationRules< T >( rule as ValidationRuleFn< T >, values );
			errorsMap.set( ruleId, error );

			if ( ! hasError && error !== null ) {
				hasError = true;
			}
		}
	} );
	return { errorsObj: Object.fromEntries( errorsMap ), hasError };
};

interface UseForm< T > {
	state: Partial< T >;
	errors: { [ x: string ]: string | null };
	setFormState: ( newState: Partial< T > ) => void;
	resetFormState: () => void;
	resetErrorState: () => void;
	handleOnChange: ( id: string, value: unknown ) => void;
	handleOnBlur: ( id: string ) => void;
	handleOnSubmit: () => void;
	validState: T | null;
}

export interface ValidationFnResponse {
	errorsObj: { [ x: string ]: string | null };
	hasError: boolean;
}

interface UseFormProps< T > {
	initialValues: Partial< T >;
	validationRules?: ValidationRules< Partial< T > >;
	submit?: ( state: T, resetForm: () => void ) => void;
	submitValidationFn?: ( state: Partial< T > ) => ValidationFnResponse;
}

type FormAction< T > =
	| { type: 'setField'; payload: { id: string; value: unknown } }
	| { type: 'setState'; payload: { value: T } };

const reducer = < T >( state: T, action: FormAction< T > ): T => {
	switch ( action.type ) {
		case 'setField':
			return { ...state, [ action.payload.id ]: action.payload.value };
		case 'setState':
			return { ...state, ...action.payload.value };
		default:
			throw new Error();
	}
};

export const useForm = < T extends Record< string, unknown > >( {
	initialValues,
	validationRules = {} as ValidationRules< Partial< T > >,
	submit,
	submitValidationFn,
}: UseFormProps< T > ): UseForm< T > => {
	const [ state, dispatch ] = useReducer< typeof reducer< Partial< T > > >(
		reducer,
		initialValues
	);
	const [ touched, setTouched ] = useState(
		constructObjectWithValues< boolean >( validationRules, false )
	);
	const [ errors, setErrors ] = useState(
		constructObjectWithValues< string | null >( validationRules, null )
	);

	const resetErrorState = (): void => {
		setErrors( constructObjectWithValues< string | null >( validationRules, null ) );
	};

	const resetFormState = (): void => {
		dispatch( { type: 'setState', payload: { value: initialValues } } );
		resetErrorState();
	};

	const setFormState = ( newState: Partial< T > ): void => {
		dispatch( { type: 'setState', payload: { value: newState } } );
	};

	const handleOnChange = ( id: string, value: unknown ): void => {
		dispatch( { type: 'setField', payload: { id, value } } );
		if ( isNonEmptyObj( validationRules ) && Object.prototype.hasOwnProperty.call( errors, id ) ) {
			setErrors( {
				...errors,
				[ id ]: executeValidationRules(
					validationRules[ id as keyof typeof validationRules ] ?? ( () => null ),
					{ ...state, [ id ]: value }
				),
			} );
		}
	};

	const handleOnBlur = ( id: string ): void => {
		setTouched( { ...touched, [ id ]: true } );
		setErrors( {
			...errors,
			[ id ]: executeValidationRules(
				validationRules[ id as keyof typeof validationRules ] ?? ( () => null ),
				state
			),
		} );
	};

	const validation = executeAllValidationRules( validationRules, state );
	const validateState = ( _partialState: Partial< T > ): _partialState is T => {
		return ! validation.hasError;
	};

	const handleOnSubmit = (): void => {
		if ( isNonEmptyObj( errors ) ) {
			const { errorsObj, hasError } = validation;
			let finalErrorsObj: { [ x: string ]: string | null };
			let finalHasError: boolean;
			if ( submitValidationFn && typeof submitValidationFn === 'function' ) {
				const { errorsObj: submitValidationFnErrorsObj, hasError: submitValidationFnHasError } =
					submitValidationFn( state );
				finalErrorsObj = {
					...errorsObj,
					...submitValidationFnErrorsObj,
				};
				finalHasError = hasError || submitValidationFnHasError;
			} else {
				finalErrorsObj = { ...errorsObj };
				finalHasError = hasError;
			}
			setErrors( finalErrorsObj );
			if ( ! finalHasError && submit && validateState( state ) ) {
				submit( state, resetFormState );
			}
		} else if ( submit && validateState( state ) ) {
			submit( state, resetFormState );
		}
	};

	return {
		state,
		errors,
		setFormState,
		resetErrorState,
		resetFormState,
		handleOnChange,
		handleOnBlur,
		handleOnSubmit,
		validState: validateState( state ) ? state : null,
	};
};

export default useForm;
