import { useEffect, useRef } from '@wordpress/element';

export function Map( { coordinates } ) {
	const mapRef = useRef();
	const leafletMapRef = useRef();
	const markersLayerGroupRef = useRef();

	useEffect( () => {
		if ( ! mapRef.current ) {
			return;
		}

		if ( ! leafletMapRef.current ) {
			const map = global.L.map( mapRef.current ).setView(
				[ coordinates[ 0 ].x, coordinates[ 0 ].y ],
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

		coordinates.forEach( location => {
			global.L.marker( [ location.x, location.y ] ).addTo( markersLayerGroupRef.current );
		} );

		leafletMapRef.current.flyTo( [ coordinates[ 0 ].x, coordinates[ 0 ].y ] );
	}, [ coordinates ] );

	return <div ref={ mapRef } id="map" style={ { height: 400 } }></div>;
}
