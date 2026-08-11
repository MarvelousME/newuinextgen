import apiFetch from '@wordpress/api-fetch';
import type { Envelope } from './types';

export async function getJson<T>( path: string ): Promise<T> {
	const res = ( await apiFetch( { path: path.replace( /^\//, '' ) } ) ) as Envelope<T> | T;
	if ( res && typeof res === 'object' && 'error' in res && ( res as Envelope<T> ).error ) {
		throw new Error( ( res as Envelope<T> ).error!.message );
	}
	if ( res && typeof res === 'object' && 'data' in res ) {
		return ( res as Envelope<T> ).data;
	}
	return res as T;
}

export async function postJson<T>( path: string, data?: unknown ): Promise<T> {
	const res = ( await apiFetch( {
		path: path.replace( /^\//, '' ),
		method: 'POST',
		data,
	} ) ) as Envelope<T>;
	if ( res?.error ) {
		throw new Error( res.error.message );
	}
	return res.data;
}

export async function putJson<T>( path: string, data?: unknown ): Promise<T> {
	const res = ( await apiFetch( {
		path: path.replace( /^\//, '' ),
		method: 'PUT',
		data,
	} ) ) as Envelope<T>;
	if ( res?.error ) {
		throw new Error( res.error.message );
	}
	return res.data;
}
