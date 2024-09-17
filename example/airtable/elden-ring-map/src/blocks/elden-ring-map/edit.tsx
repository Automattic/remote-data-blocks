import { BlockControls, useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { useCallback, useEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import type Leaflet from 'leaflet';

type Props = {
	context: {
		'remote-data-blocks/remoteData': {
			results: Leaflet.Point[];
		};
	};
};

export function Edit( { context }: Props ) {
	const [ nodeReady, setNodeReady ] = useState< boolean >( false );
	const blockProps = useBlockProps();
	const innerBlocksProps = useInnerBlocksProps( blockProps );

	const mapRef = useRef< HTMLDivElement >();
	const leafletMapRef = useRef< Leaflet.Map >();
	const markersLayerGroupRef = useRef< Leaflet.LayerGroup >();

	const remoteData = context[ 'remote-data-blocks/remoteData' ];
	const setMapRef = useCallback( ( node: HTMLDivElement ) => {
		console.log( 'setMapRef', node );
		if ( ! node ) {
			setNodeReady( false );
			return;
		}
		setNodeReady( true );
		mapRef.current = node;
	}, [] );

	useEffect( () => {
		if ( ! ( nodeReady && mapRef.current ) ) {
			return;
		}

		if ( ! remoteData?.results?.length ) {
			return;
		}

		const Leaflet = global.L;

		if ( ! Leaflet ) {
			return;
		}

		if (
			'undefined' === typeof remoteData?.results[ 0 ]?.x ||
			'undefined' === typeof remoteData.results[ 0 ].y
		) {
			return;
		}

		//		if ( ! leafletMapRef.current ) {
		const map = global.L.map( mapRef.current ).setView(
			[ remoteData?.results[ 0 ].x, remoteData?.results[ 0 ].y ],
			25
		);
		global.L.tileLayer( 'https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
			maxZoom: 4,
		} ).addTo( map );

		const layerGroup = Leaflet.layerGroup();
		if ( 'function' === typeof layerGroup?.getEvents ) {
			const handlers = layerGroup.getEvents();
			Object.values( handlers ).forEach( handler => {
				if ( handler instanceof Leaflet.Handler ) {
					console.log( 'disabling handler' );
					handler.disable();
				}
			} );
		}

		leafletMapRef.current = map;
		markersLayerGroupRef.current = Leaflet.layerGroup().addTo( leafletMapRef.current );
		//		}

		if ( markersLayerGroupRef.current ) {
			markersLayerGroupRef.current.clearLayers();

			remoteData.results.forEach( location => {
				if ( markersLayerGroupRef.current ) {
					global.L.marker( [ location.x, location.y ] ).addTo( markersLayerGroupRef.current );
				}
			} );

			leafletMapRef.current?.flyTo( [ remoteData.results[ 0 ].x, remoteData.results[ 0 ].y ] );
		}
	}, [ nodeReady, remoteData ] );

	const inQuery = Boolean( remoteData?.results );

	return (
		<div { ...innerBlocksProps }>
			<BlockControls> </BlockControls>
			{ inQuery ? (
				<div ref={ setMapRef } id="map" style={ { height: 400 } }></div>
			) : (
				<p style={ { color: 'red', padding: '20px' } }>
					{
						( __(
							'This block only supports being rendered inside of an Elden Ring Map Query block.'
						),
						'remote-data-blocks' )
					}
				</p>
			) }
		</div>
	);
}
