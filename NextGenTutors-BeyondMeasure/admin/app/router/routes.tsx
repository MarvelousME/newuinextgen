import { createElement } from '@wordpress/element';
import { CommandCenterPage } from '../pages/CommandCenterPage';
import { TalentPage } from '../pages/TalentPage';
import { AccessMatrixPage } from '../pages/AccessMatrixPage';
import { DependencyMapPage } from '../pages/DependencyMapPage';
import { SubsystemsPage } from '../pages/SubsystemsPage';
import { PlaceholderPage } from '../pages/PlaceholderPage';
import { AuditPage } from '../pages/AuditPage';
import { HealthPage } from '../pages/HealthPage';
import { QueuesPage } from '../pages/QueuesPage';
import { SettingsPage } from '../pages/SettingsPage';
import { ConfigPage } from '../pages/ConfigPage';
import type { ReactNode } from 'react';

export function route( path: string ): ReactNode {
	const p = path.split( '?' )[ 0 ] || '/';
	switch ( p ) {
		case '/':
			return createElement( CommandCenterPage );
		case '/tutors/talent':
			return createElement( TalentPage );
		case '/security/access-matrix':
		case '/security/roles':
		case '/security/capabilities':
			return createElement( AccessMatrixPage );
		case '/ecosystem/dependency-map':
		case '/governance/architecture':
			return createElement( DependencyMapPage );
		case '/ecosystem/subsystems':
		case '/ecosystem/capabilities':
		case '/ecosystem/connections':
		case '/ecosystem/compliance':
			return createElement( SubsystemsPage );
		case '/governance/audit':
			return createElement( AuditPage );
		case '/operations/health':
			return createElement( HealthPage );
		case '/operations/queues':
		case '/operations/dlq':
		case '/automation/queues':
			return createElement( QueuesPage );
		case '/settings':
			return createElement( SettingsPage );
		case '/tutors/talent/config':
			return createElement( ConfigPage, { subsystemId: 'bridge-talent-intelligence' } );
		default:
			return createElement( PlaceholderPage, { path: p } );
	}
}
