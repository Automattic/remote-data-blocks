import { useEffect, useRef, useState } from '@wordpress/element';

export function Map( { coordinates } ) {
	const [ Leaflet, setLeaflet ] = useState();

	const mapRef = useRef();
	const leafletMapRef = useRef();
	const markersLayerGroupRef = useRef();

	useEffect( () => {
		if ( ! coordinates?.length ) {
			return;
		}
		const interval = setInterval( () => {
			if ( global.L ) {
				clearInterval( interval );
				setLeaflet( global.L );
			}
		}, 100 );
		return () => clearInterval( interval );
	}, [ coordinates ] );

	useEffect( () => {
		if ( ! mapRef.current ) {
			return;
		}

		if ( ! Leaflet ) {
			return;
		}

		if ( ! leafletMapRef.current ) {
			const map = Leaflet.map( mapRef.current ).setView(
				[ coordinates[ 0 ].x, coordinates[ 0 ].y ],
				25
			);
			Leaflet.tileLayer( 'https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 4 } ).addTo(
				map
			);

			map._handlers.forEach( handler => handler.disable() );

			leafletMapRef.current = map;
			markersLayerGroupRef.current = Leaflet.layerGroup().addTo( leafletMapRef.current );
		}

		markersLayerGroupRef.current.clearLayers();

		coordinates.forEach( location => {
			Leaflet.marker( [ location.x, location.y ] ).addTo( markersLayerGroupRef.current );
		} );

		leafletMapRef.current.flyTo( [ coordinates[ 0 ].x, coordinates[ 0 ].y ] );
	}, [ coordinates, Leaflet ] );

	return <div ref={ mapRef } id="map" style={ { height: 400 } }></div>;
}
