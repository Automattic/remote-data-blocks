import { LitElement, html, css } from 'lit';
import { customElement, property } from 'lit/decorators.js';

/* global leaflet */

// Registers the element
@customElement( 'leaflet-map' )
export class LeafletMap extends LitElement {
	// Styles are applied to the shadow root and scoped to this element
	static styles = css`
		div {
			height: 400px;
		}
	`;

	@property()
	accessor coordinates = [];

	firstUpdated() {
		let coordinates = [];
		try {
			coordinates = JSON.parse( this.coordinates ) ?? [];
		} catch ( error ) {}

		this.map = leaflet
			.map( this.renderRoot.querySelector( 'div' ) )
			.setView( [ coordinates[ 0 ].x, coordinates[ 0 ].y ], 25 );
		const layerGroup = leaflet.layerGroup().addTo( this.map );

		leaflet
			.tileLayer( 'https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 4 } )
			.addTo( this.map );

		coordinates
			.filter( location => location.x && location.y )
			.forEach( location => {
				leaflet.marker( [ location.x, location.y ], { title: location.name } ).addTo( layerGroup );
			} );

		this.map.flyTo( [ coordinates[ 0 ].x, coordinates[ 0 ].y ] );
	}

	// Render the component's DOM by returning a Lit template
	render() {
		return html`
			<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
			<div></div>
		`;
	}
}
