import { createElement, useEffect, useMemo, useState } from '@wordpress/element';
import {
	ReactFlow,
	Background,
	Controls,
	MiniMap,
	useEdgesState,
	useNodesState,
	type Edge,
	type Node,
} from '@xyflow/react';
import '@xyflow/react/dist/style.css';
import { getJson } from '../api/client';
import { EntityDrawer } from '../components/EntityDrawer';

type Graph = {
	nodes: Array<{ id: string; label: string; status: string; category: string; provides: string[]; depends: string[] }>;
	edges: Array<{ id: string; source: string; target: string; kind: string; capability?: string }>;
};

export function DependencyMapPage() {
	const [ graph, setGraph ] = useState<Graph | null>( null );
	const [ selected, setSelected ] = useState<Graph['nodes'][0] | null>( null );
	const [ nodes, setNodes, onNodesChange ] = useNodesState<Node>( [] );
	const [ edges, setEdges, onEdgesChange ] = useEdgesState<Edge>( [] );

	useEffect( () => {
		getJson<Graph>( 'dependency-graph' ).then( ( g ) => {
			setGraph( g );
			const laid = ( g.nodes || [] ).map( ( n, i ) => ( {
				id: n.id,
				position: { x: ( i % 4 ) * 220, y: Math.floor( i / 4 ) * 140 },
				data: { label: `${ n.label }\n(${ n.status })` },
				style: {
					background: '#1e2732',
					color: '#e8eef5',
					border: '1px solid #2a3544',
					borderRadius: 8,
					padding: 8,
					fontSize: 12,
					width: 180,
				},
			} ) );
			setNodes( laid );
			setEdges(
				( g.edges || [] ).map( ( e ) => ( {
					id: e.id,
					source: e.source,
					target: e.target,
					label: e.capability || e.kind,
					style: { stroke: '#2dd4bf' },
				} ) )
			);
		} );
	}, [ setNodes, setEdges ] );

	const nodeMap = useMemo( () => {
		const m: Record<string, Graph['nodes'][0]> = {};
		( graph?.nodes || [] ).forEach( ( n ) => {
			m[ n.id ] = n;
		} );
		return m;
	}, [ graph ] );

	return createElement(
		'div',
		null,
		createElement( 'h1', { style: { marginTop: 0 } }, 'Dependency Map' ),
		createElement(
			'div',
			{ className: 'ngtbm-graph' },
			createElement( ReactFlow, {
				nodes,
				edges,
				onNodesChange,
				onEdgesChange,
				fitView: true,
				onNodeClick: ( _: unknown, node: Node ) => setSelected( nodeMap[ node.id ] || null ),
				children: [
					createElement( Background, { key: 'bg' } ),
					createElement( Controls, { key: 'controls' } ),
					createElement( MiniMap, { key: 'mini', style: { background: '#171e26' } } ),
				],
			} )
		),
		selected
			? createElement(
					EntityDrawer,
					{ title: selected.label, onClose: () => setSelected( null ) },
					createElement( 'div', null, 'STATUS: ' + selected.status ),
					createElement( 'h4', null, 'Provides' ),
					createElement(
						'ul',
						null,
						( selected.provides || [] ).map( ( p ) => createElement( 'li', { key: p }, p ) )
					),
					createElement( 'h4', null, 'Depends on' ),
					createElement(
						'ul',
						null,
						( selected.depends || [] ).map( ( p ) => createElement( 'li', { key: p }, p ) )
					),
					createElement(
						'div',
						{ style: { display: 'flex', gap: 8, marginTop: 12, flexWrap: 'wrap' } },
						createElement( 'button', { type: 'button', className: 'ngtbm-btn' }, 'Configure' ),
						createElement( 'button', { type: 'button', className: 'ngtbm-btn' }, 'Health' ),
						createElement( 'button', { type: 'button', className: 'ngtbm-btn' }, 'Audit' ),
						createElement( 'button', { type: 'button', className: 'ngtbm-btn danger' }, 'Disable' )
					)
			  )
			: null
	);
}
