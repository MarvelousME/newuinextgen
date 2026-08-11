import { createElement, useEffect, useState } from '@wordpress/element';
import { getJson, putJson } from '../api/client';

type Props = { subsystemId: string };

export function ConfigPage( { subsystemId }: Props ) {
	const [ values, setValues ] = useState<Record<string, any>>( {} );
	const [ schema, setSchema ] = useState<any>( null );
	const [ msg, setMsg ] = useState( '' );

	useEffect( () => {
		getJson<{ values: Record<string, any>; schema: any }>( `configuration/${ subsystemId }` ).then( ( d ) => {
			setValues( d.values || {} );
			setSchema( d.schema );
		} );
	}, [ subsystemId ] );

	const save = async () => {
		try {
			await putJson( `configuration/${ subsystemId }`, { values } );
			setMsg( 'Saved & validated.' );
		} catch ( e ) {
			setMsg( ( e as Error ).message );
		}
	};

	return createElement(
		'div',
		null,
		createElement( 'h1', { style: { marginTop: 0 } }, 'Configuration — ' + subsystemId ),
		( schema?.sections || [] ).map( ( section: any ) =>
			createElement(
				'section',
				{ key: section.id, className: 'ngtbm-tile', style: { marginBottom: 12 } },
				createElement( 'h3', null, section.title ),
				( section.fields || [] ).map( ( field: any ) =>
					createElement(
						'label',
						{ key: field.key, style: { display: 'flex', justifyContent: 'space-between', marginBottom: 8 } },
						field.label,
						createElement( 'input', {
							type: field.type === 'boolean' ? 'checkbox' : 'text',
							checked: field.type === 'boolean' ? Boolean( values?.[ section.id ]?.[ field.key ] ) : undefined,
							value: field.type === 'boolean' ? undefined : String( values?.[ section.id ]?.[ field.key ] ?? '' ),
							onChange: ( e: any ) => {
								const next = { ...values, [ section.id ]: { ...( values[ section.id ] || {} ) } };
								next[ section.id ][ field.key ] =
									field.type === 'boolean' ? e.target.checked : field.type === 'percent' || field.type === 'number' ? Number( e.target.value ) : e.target.value;
								setValues( next );
							},
						} )
					)
				)
			)
		),
		createElement(
			'div',
			{ style: { display: 'flex', gap: 8 } },
			createElement( 'button', { type: 'button', className: 'ngtbm-btn' }, 'Reset' ),
			createElement( 'button', { type: 'button', className: 'ngtbm-btn primary', onClick: save }, 'Save & Validate' )
		),
		msg ? createElement( 'p', null, msg ) : null,
		createElement(
			'div',
			{ className: 'ngtbm-danger-zone' },
			createElement( 'h3', null, 'Danger Zone' ),
			createElement( 'p', null, 'Disable subsystem or reset configuration. These actions are capability-gated and audited.' ),
			createElement( 'button', { type: 'button', className: 'ngtbm-btn danger' }, 'Disable subsystem' ),
			' ',
			createElement( 'button', { type: 'button', className: 'ngtbm-btn danger' }, 'Reset configuration' )
		)
	);
}
