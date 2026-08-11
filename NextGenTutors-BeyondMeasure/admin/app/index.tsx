import { createRoot, createElement, useEffect, useMemo, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { AppShell } from './components/AppShell';
import { route } from './router/routes';
import type { BootConfig } from './api/types';

declare global {
	interface Window {
		ngtbmBoot?: BootConfig;
	}
}

const boot = window.ngtbmBoot;
if ( boot?.restRoot ) {
	apiFetch.use( apiFetch.createNonceMiddleware( boot.nonce ) );
	apiFetch.use( apiFetch.createRootURLMiddleware( boot.restRoot.replace( /\/$/, '' ) + '/' ) );
}

function Root() {
	const [ path, setPath ] = useState( () => {
		const hash = window.location.hash.replace( /^#/, '' );
		return hash || '/';
	} );

	useEffect( () => {
		const onHash = () => setPath( window.location.hash.replace( /^#/, '' ) || '/' );
		window.addEventListener( 'hashchange', onHash );
		return () => window.removeEventListener( 'hashchange', onHash );
	}, [] );

	const navigate = ( next: string ) => {
		window.location.hash = next;
		setPath( next );
	};

	const page = useMemo( () => route( path ), [ path ] );

	return createElement( AppShell, { path, navigate, page, boot: boot! } );
}

const el = document.getElementById( 'ngtbm-root' );
if ( el && boot ) {
	createRoot( el ).render( createElement( Root ) );
}
