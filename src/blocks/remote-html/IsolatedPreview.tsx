import { useEffect, useMemo, useRef, useState, renderToString } from '@wordpress/element';

interface IsolatedPreviewProps {
	html: string;
	styles: string[];
	title: string;
}

const MAX_PREVIEW_HEIGHT = 2000;

// Serialized into the frame. Keep this independent of the editor's window and state.
function observeHeight() {
	let viewportWidth = window.innerWidth;
	let viewportHeight = window.innerHeight;
	let lastHeight: number | undefined;

	function resize() {
		const { innerWidth, innerHeight } = window;
		const height = document.body.offsetHeight;
		const heightOnlyResize =
			lastHeight !== undefined && viewportWidth === innerWidth && viewportHeight !== innerHeight;
		// Core measures the frame on viewport resize as well as content changes. Ignore
		// height-only feedback so viewport-relative layouts can keep their authored CSS.
		if ( height !== lastHeight && ! heightOnlyResize ) {
			window.parent.postMessage( { action: 'rdb-preview-resize', height }, '*' );
		}
		viewportWidth = innerWidth;
		viewportHeight = innerHeight;
		lastHeight = height;
	}
	document.addEventListener( 'DOMContentLoaded', () => {
		window.addEventListener( 'resize', resize );
		new ResizeObserver( resize ).observe( document.body );
	} );
}

/** A version-independent sandbox: remote scripts can run without the editor's origin. */
export function IsolatedPreview( { html, styles, title }: IsolatedPreviewProps ) {
	const frame = useRef< HTMLIFrameElement >( null );
	const [ height, setHeight ] = useState( 150 );
	const srcDoc = useMemo(
		() =>
			'<!doctype html>' +
			renderToString(
				<html lang={ document.documentElement.lang }>
					<head>
						<title>{ title }</title>
						<style>{ 'html,body{margin:0;width:100%;}body{position:absolute;}' }</style>
						{ styles.map( ( css, index ) => (
							<style key={ index } dangerouslySetInnerHTML={ { __html: css } } />
						) ) }
						<script dangerouslySetInnerHTML={ { __html: `(${ observeHeight.toString() })();` } } />
					</head>
					<body>
						<div dangerouslySetInnerHTML={ { __html: html } } />
					</body>
				</html>
			),
		[ html, styles, title ]
	);

	useEffect( () => {
		const iframe = frame.current;
		const view = iframe?.ownerDocument.defaultView;
		function resize( event: MessageEvent ) {
			const data = event.data as { action?: unknown; height?: unknown } | null;
			if (
				event.source === iframe?.contentWindow &&
				data?.action === 'rdb-preview-resize' &&
				typeof data.height === 'number' &&
				Number.isFinite( data.height ) &&
				data.height >= 0
			) {
				setHeight( Math.min( Math.ceil( data.height ), MAX_PREVIEW_HEIGHT ) );
			}
		}
		view?.addEventListener( 'message', resize );
		return () => view?.removeEventListener( 'message', resize );
	}, [] );

	return (
		<iframe
			ref={ frame }
			title={ title }
			className="components-sandbox"
			sandbox="allow-scripts allow-presentation"
			srcDoc={ srcDoc }
			tabIndex={ -1 }
			height={ height }
			style={ { width: '100%', border: 0 } }
		/>
	);
}
