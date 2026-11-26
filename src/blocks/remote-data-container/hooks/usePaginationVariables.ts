import { useRef, useState } from '@wordpress/element';

export interface UsePaginationVariables {
	hasNextPage?: boolean;
	page: number;
	paginationQueryInput?: RemoteDataQueryInput;
	perPage?: number;
	resetPage: () => void;
	setPage: ( page: number ) => void;
	setPerPage: ( perPage: number ) => void;
	supportsPagination: boolean;
	supportsPerPage: boolean;
	totalItems?: number;
	totalPages?: number;
}

export interface UsePaginationVariablesInput {
	initialPage?: number;
	initialPerPage?: number;
	inputVariables?: InputVariable[];
	paginationData?: RemoteDataPagination;
}

export function usePaginationVariables( {
	initialPage = 1,
	initialPerPage,
	inputVariables = [],
	paginationData,
}: UsePaginationVariablesInput ): UsePaginationVariables {
	const [ page, setPage ] = useState< number >( initialPage );
	const [ perPage, setPerPage ] = useState< number | null >( initialPerPage ?? null );
	const [ paginationQueryInput, setPaginationQueryInput ] = useState< RemoteDataQueryInput >();
	const cursorPages = useRef( new Map< number, RemoteDataQueryInput >() );

	const nextPageQueryInput = paginationData?.input_variables?.next_page;
	const previousPageQueryInput = paginationData?.input_variables?.previous_page;
	const offsetVariable = paginationData?.input_variable_targets?.offset;
	const pageVariable = paginationData?.input_variable_targets?.page;
	// A cleared search removes response metadata while retaining the user's page size.
	const perPageVariable =
		paginationData?.input_variable_targets?.per_page ??
		( perPage === null
			? undefined
			: inputVariables.find( input => input.type === 'ui:pagination_per_page' )?.slug );

	const calculatedPerPage = perPage ?? paginationData?.per_page ?? 10;
	const hasNextPage = Boolean( nextPageQueryInput );
	const supportsPagination = Boolean( paginationData?.type && paginationData?.type !== 'NONE' );
	const totalItems = paginationData?.total_items;
	const totalPages = totalItems ? Math.ceil( totalItems / calculatedPerPage ) : undefined;

	function getClearedInputs( typePrefix: string ): RemoteDataQueryInput {
		return Object.fromEntries(
			inputVariables
				.filter( input => input.type.startsWith( typePrefix ) )
				.map( input => [ input.slug, undefined ] )
		);
	}

	function resetPage(): void {
		cursorPages.current.clear();
		setPage( 1 );
		setPaginationQueryInput( {
			...getClearedInputs( 'ui:pagination_' ),
			...( pageVariable && { [ pageVariable ]: 1 } ),
			...( offsetVariable && { [ offsetVariable ]: 0 } ),
			...( perPageVariable && { [ perPageVariable ]: calculatedPerPage } ),
		} );
	}

	function setPageForPagination( requestedNewPage: number ): void {
		if ( ! supportsPagination ) {
			return;
		}

		const wantsNextPage = requestedNewPage > page;
		const wantsPreviousPage = requestedNewPage < page && requestedNewPage > 0;
		const newPage = wantsNextPage
			? Math.min( totalPages ?? page + 1, requestedNewPage )
			: Math.max( 1, requestedNewPage );

		if ( ! wantsNextPage && ! wantsPreviousPage ) {
			return;
		}

		switch ( paginationData?.type ) {
			case 'CURSOR':
			case 'CURSOR_SIMPLE': {
				const cursorPage = page + ( wantsNextPage ? 1 : -1 );
				// Forward-only sources can revisit pages using a previously seen cursor.
				const cursorInput = wantsNextPage
					? nextPageQueryInput
					: previousPageQueryInput ??
					  ( cursorPage === 1 ? {} : cursorPages.current.get( cursorPage ) );
				if ( ! cursorInput ) {
					break;
				}
				if ( wantsNextPage ) {
					cursorPages.current.set( cursorPage, cursorInput );
				}
				setPage( cursorPage );
				setPaginationQueryInput( {
					...getClearedInputs( 'ui:pagination_cursor' ),
					...cursorInput,
				} );
				break;
			}

			case 'OFFSET':
				if ( ! offsetVariable ) {
					break;
				}

				setPage( newPage );

				if ( wantsNextPage ) {
					setPaginationQueryInput( {
						...nextPageQueryInput,
						[ offsetVariable ]: calculatedPerPage * ( newPage - 1 ),
					} );
					break;
				}

				setPaginationQueryInput( {
					...previousPageQueryInput,
					[ offsetVariable ]: calculatedPerPage * ( newPage - 1 ),
				} );
				break;

			case 'PAGE':
				if ( ! pageVariable ) {
					break;
				}

				setPage( newPage );

				if ( wantsNextPage ) {
					setPaginationQueryInput( {
						...nextPageQueryInput,
						[ pageVariable ]: newPage,
					} );
					break;
				}

				setPaginationQueryInput( {
					...previousPageQueryInput,
					[ pageVariable ]: newPage,
				} );
				break;
		}
	}

	function setPerPageForPagination( newPerPage: number ): void {
		if ( ! perPageVariable || calculatedPerPage === newPerPage ) {
			return;
		}

		setPerPage( newPerPage );
		setPaginationQueryInput( currentInput => ( {
			...currentInput,
			[ perPageVariable ]: newPerPage,
		} ) );
	}

	return {
		hasNextPage,
		page,
		paginationQueryInput,
		perPage: perPage ?? paginationData?.per_page,
		resetPage,
		setPage: setPageForPagination,
		setPerPage: setPerPageForPagination,
		supportsPagination,
		supportsPerPage: Boolean( perPageVariable ),
		totalItems,
		totalPages,
	};
}
