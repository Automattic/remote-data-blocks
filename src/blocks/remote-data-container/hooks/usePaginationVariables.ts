import { useState } from '@wordpress/element';

export interface UsePaginationVariables {
	hasNextPage?: boolean;
	page: number;
	paginationQueryInput?: RemoteDataQueryInput;
	perPage?: number;
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
	paginationData?: RemoteDataPagination;
}

export function usePaginationVariables( {
	initialPage = 1,
	initialPerPage,
	paginationData,
}: UsePaginationVariablesInput ): UsePaginationVariables {
	const [ page, setPage ] = useState< number >( initialPage );
	const [ perPage, setPerPage ] = useState< number | null >( initialPerPage ?? null );
	const [ paginationQueryInput, setPaginationQueryInput ] = useState< RemoteDataQueryInput >();

	const nextPageQueryInput = paginationData?.input_variables?.next_page;
	const previousPageQueryInput = paginationData?.input_variables?.previous_page;
	const offsetVariable = paginationData?.input_variable_targets?.offset;
	const pageVariable = paginationData?.input_variable_targets?.page;
	const perPageVariable = paginationData?.input_variable_targets?.per_page;

	const calculatedPerPage = perPage ?? paginationData?.per_page ?? 10;
	const hasNextPage = Boolean( nextPageQueryInput );
	const supportsPagination = Boolean( paginationData?.type && paginationData?.type !== 'NONE' );
	const totalItems = paginationData?.total_items;
	const totalPages = totalItems ? Math.ceil( totalItems / calculatedPerPage ) : undefined;

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
			case 'CURSOR_SIMPLE':
				// With cursor pagination, we can only go one page at a time.
				if ( wantsNextPage ) {
					setPage( page + 1 );
					setPaginationQueryInput( nextPageQueryInput );
					break;
				}

				setPage( page - 1 );
				setPaginationQueryInput( previousPageQueryInput );
				break;

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
		setPaginationQueryInput( {
			...paginationQueryInput,
			[ perPageVariable ]: newPerPage,
		} );
	}

	return {
		hasNextPage,
		page,
		paginationQueryInput,
		perPage: perPage ?? paginationData?.per_page,
		setPage: setPageForPagination,
		setPerPage: setPerPageForPagination,
		supportsPagination,
		supportsPerPage: Boolean( perPageVariable ),
		totalItems,
		totalPages,
	};
}
