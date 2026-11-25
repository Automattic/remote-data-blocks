import { useEffect, useMemo, useRef, useState, renderToString } from '@wordpress/element';

interface IsolatedPreviewProps {
	html: string;
	styles: string[];
	title: string;
}

// Serialized into the frame. Keep this independent of the editor's window and state.
function observeHeight() {
	function resize() {
		window.parent.postMessage(
			{ action: 'rdb-preview-resize', height: document.body.offsetHeight },
			'*'
		);
	}
	document.addEventListener( 'DOMContentLoaded', () => {
		new ResizeObserver( resize ).observe( document.body );
		resize();
	} );
	window.addEventListener( 'load', resize );
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
				setHeight( Math.ceil( data.height ) );
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
