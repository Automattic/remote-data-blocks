import { act, renderHook } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

import { useModalState } from '@/blocks/remote-data-container/hooks/use-modal-state';

describe( 'useModalState', () => {
	it( 'should initialize with isOpen as false', () => {
		const { result } = renderHook( () => useModalState() );
		expect( result.current.isOpen ).toBe( false );
	} );

	it( 'should open the modal', () => {
		const { result } = renderHook( () => useModalState() );
		act( () => {
			result.current.open();
		} );
		expect( result.current.isOpen ).toBe( true );
	} );

	it( 'should close the modal', () => {
		const { result } = renderHook( () => useModalState() );
		act( () => {
			result.current.open();
			result.current.close();
		} );
		expect( result.current.isOpen ).toBe( false );
	} );

	it( 'should call onOpen when opening', () => {
		const onOpen = vi.fn();
		const onClose = vi.fn();
		const { result } = renderHook( () => useModalState( onOpen, onClose ) );
		act( () => {
			result.current.open();
		} );
		expect( onOpen ).toHaveBeenCalledTimes( 1 );
		expect( onClose ).not.toHaveBeenCalled();
	} );

	it( 'should call onClose when closing', () => {
		const onOpen = vi.fn();
		const onClose = vi.fn();
		const { result } = renderHook( () => useModalState( onOpen, onClose ) );
		act( () => {
			result.current.open();
			result.current.close();
		} );
		expect( onOpen ).toHaveBeenCalledTimes( 1 );
		expect( onClose ).toHaveBeenCalledTimes( 1 );
	} );
} );
