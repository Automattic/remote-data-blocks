import { BlockControls, useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { useEffect, useRef } from '@wordpress/element';

export default function EldenRingMapEdit( { context } ) {
	const blockProps = useBlockProps();
	const innerBlocksProps = useInnerBlocksProps( blockProps );

	const mapRef = useRef();
	const leafletMapRef = useRef();
	const markersLayerGroupRef = useRef();

	const remoteData = context[ 'remote-data-blocks/remoteData' ];

	useEffect( () => {
		if ( ! mapRef.current ) {
			return;
		}

		if ( ! leafletMapRef.current ) {
			const map = global.L.map( mapRef.current ).setView(
				[ remoteData?.results[ 0 ].x, remoteData?.results[ 0 ].y ],
				25
			);
			global.L.tileLayer( 'https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 4 } ).addTo(
				map
			);

			map._handlers.forEach( handler => handler.disable() );

			leafletMapRef.current = map;
			markersLayerGroupRef.current = global.L.layerGroup().addTo( leafletMapRef.current );
		}

		markersLayerGroupRef.current.clearLayers();

		remoteData?.results.forEach( location => {
			global.L.marker( [ location.x, location.y ] ).addTo( markersLayerGroupRef.current );
		} );

		leafletMapRef.current.flyTo( [ remoteData?.results[ 0 ].x, remoteData?.results[ 0 ].y ] );
	}, [ mapRef.current, context ] );

	const inQuery = Boolean( remoteData?.results );

	return (
		<>
			<BlockControls />
			<div { ...innerBlocksProps }>
				{ inQuery ? (
					<div ref={ mapRef } id="map" style={ { height: 400 } }></div>
				) : (
					<p style={ { color: 'red', padding: '20px' } }>
						This block only supports being rendered inside of an Elden Ring Map Query block.
					</p>
				) }
			</div>
		</>
	);
}
