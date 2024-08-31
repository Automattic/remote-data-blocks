import { useReducer, useState } from '@wordpress/element';

import { isNonEmptyObj, constructObjectWithValues } from '@/utils/object';

type ValidationRuleFn = ( v: unknown ) => string | null;

const executeValidationRules = (
	rules: ValidationRuleFn[],
	// eslint-disable-next-line
	value: any
): string | null => {
	let error: string | null = null;
	if ( rules ) {
		rules.some( ( rule ): boolean => {
			const err: string | null = rule( value );
			if ( err ) {
				error = err;
				return true;
			}
			return false;
		} );
	}
	return error;
};

interface ExecuteAllValidationRules {
	errorsObj: { [ x: string ]: string | null };
	hasError: boolean;
}

const executeAllValidationRules = (
	validationRules: { [ x: string ]: ValidationRuleFn[] },
	values: { [ x: string ]: unknown }
): ExecuteAllValidationRules => {
	const errorsMap = new Map< string, string | null >();
	let hasError = false;

	Object.entries( validationRules ).forEach( ( [ ruleId, rule ] ) => {
		if ( Object.prototype.hasOwnProperty.call( values, ruleId ) ) {
			// eslint-disable-next-line security/detect-object-injection
			const error = executeValidationRules( rule, values[ ruleId ] );
			errorsMap.set( ruleId, error );

			if ( ! hasError && error !== null ) {
				hasError = true;
			}
		}
	} );
	return { errorsObj: Object.fromEntries( errorsMap ), hasError };
};

interface UseForm< T > {
	state: T;
	errors: { [ x: string ]: string | null };
	setFormState: ( newState: T ) => void;
	resetFormState: () => void;
	resetErrorState: () => void;
	handleOnChange: ( id: string, value: unknown ) => void;
	handleOnBlur: ( id: string ) => void;
	handleOnSubmit: () => void;
}

export interface ValidationFnResponse {
	errorsObj: { [ x: string ]: string | null };
	hasError: boolean;
}

interface UseFormProps< T > {
	initialValues: T;
	validationRules?: { [ x: string ]: ValidationRuleFn[] };
	submit?: ( state: T, resetForm: () => void ) => void;
	submitValidationFn?: ( state: T ) => ValidationFnResponse;
}

type FormAction< T > =
	| { type: 'setField'; payload: { id: string; value: unknown } }
	| { type: 'setState'; payload: { value: T } };

const reducer = < T, >( state: T, action: FormAction< T > ): T => {
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
	validationRules = {},
	submit,
	submitValidationFn,
}: UseFormProps< T > ): UseForm< T > => {
	const [ state, dispatch ] = useReducer< typeof reducer< T > >( reducer, initialValues );
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

	const setFormState = ( newState: T ): void => {
		dispatch( { type: 'setState', payload: { value: newState } } );
	};

	const handleOnChange = ( id: string, value: unknown ): void => {
		dispatch( { type: 'setField', payload: { id, value } } );
		if ( isNonEmptyObj( validationRules ) && Object.prototype.hasOwnProperty.call( errors, id ) ) {
			setErrors( {
				...errors,
				[ id ]: executeValidationRules(
					validationRules[ id as keyof typeof validationRules ] ?? [],
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
				validationRules[ id as keyof typeof validationRules ] ?? [],
				state
			),
		} );
	};

	const handleOnSubmit = (): void => {
		if ( isNonEmptyObj( errors ) ) {
			const { errorsObj, hasError } = executeAllValidationRules( validationRules, state );
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
			if ( ! finalHasError && submit ) {
				submit( state, resetFormState );
			}
		} else if ( submit ) {
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
	};
};

export default useForm;
