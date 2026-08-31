/** Static architecture model — drives Control Center composition. */
export const NAV_ITEMS = [
  { id: 'overview', label: 'Overview' },
  { id: 'topology', label: 'Topology' },
  { id: 'subsystems', label: 'Subsystems' },
  { id: 'applications', label: 'Applications' },
  { id: 'infrastructure', label: 'Infrastructure' },
  { id: 'ai-agents', label: 'AI & Agents' },
  { id: 'workflows', label: 'Workflows' },
  { id: 'data', label: 'Data' },
  { id: 'messaging', label: 'Messaging' },
  { id: 'integrations', label: 'Integrations' },
  { id: 'networking', label: 'Networking' },
  { id: 'security', label: 'Security' },
  { id: 'observability', label: 'Observability' },
  { id: 'deployments', label: 'Deployments' },
  { id: 'backups', label: 'Backups' },
  { id: 'automation', label: 'Automation' },
  { id: 'audit', label: 'Audit' },
  { id: 'reports', label: 'Reports' },
  { id: 'incidents', label: 'Incidents' },
  { id: 'settings', label: 'Settings' },
];

export const ACTION_FLOW = [
  { id: 'discover', label: 'Discover', accent: 'cyan' },
  { id: 'observe', label: 'Observe', accent: 'cyan' },
  { id: 'configure', label: 'Configure', accent: 'purple' },
  { id: 'operate', label: 'Operate', accent: 'green' },
  { id: 'automate', label: 'Automate', accent: 'purple' },
  { id: 'diagnose', label: 'Diagnose', accent: 'orange' },
  { id: 'recover', label: 'Recover', accent: 'green' },
  { id: 'audit', label: 'Audit', accent: 'cyan' },
  { id: 'report', label: 'Report', accent: 'cyan' },
];

export const DESIGN_SYSTEM_CARDS = [
  'Design Tokens',
  'Components',
  'Forms',
  'Data Viz',
  'Motion',
  '3D / R3F',
  'Status / Alerts',
  'Command Palette',
];

export const CONTROL_PLANE_CARDS = [
  { title: 'Subsystem Registry', desc: 'Single Source of Truth' },
  { title: 'Capability Engine', desc: 'Contract Enforcement' },
  { title: 'Operation Engine', desc: 'Lifecycle Orchestration' },
  { title: 'Configuration', desc: 'Tenant Blueprints' },
  { title: 'Event Bus', desc: 'Async Messaging' },
  { title: 'Audit', desc: 'Immutable Trail' },
  { title: 'Permission', desc: 'IAM L0–L7' },
  { title: 'Tenant Service', desc: 'Isolation Boundary' },
];

export const PROVIDER_INTERFACES = [
  'IHealthProvider',
  'IConfigurationProvider',
  'ILifecycleProvider',
  'ILogProvider',
  'IMetricsProvider',
  'IBackupProvider',
  'IRestoreProvider',
  'IDeploymentProvider',
  'ISecretProvider',
  'IOperationProvider',
  'IEventProvider',
];

export const SUBSYSTEMS = [
  { name: 'Ollama', caps: ['Models', 'Runtime', 'Health', 'Logs', 'Metrics'] },
  { name: 'Open WebUI', caps: ['Chat UI', 'Sessions', 'Health', 'Logs'] },
  { name: 'PostgreSQL', caps: ['Relational', 'Backups', 'Health', 'Metrics'] },
  { name: 'Redis', caps: ['Cache', 'Pub/Sub', 'Health', 'Metrics'] },
  { name: 'RabbitMQ', caps: ['Queues', 'Events', 'Health', 'Logs'] },
  { name: 'Qdrant', caps: ['Vectors', 'Search', 'Health', 'Metrics'] },
  { name: 'Agents', caps: ['MCP', 'Policy', 'Health', 'Audit'] },
  { name: 'Workflows', caps: ['n8n', 'Triggers', 'Health', 'Logs'] },
  { name: 'WordPress', caps: ['Content', 'Woo', 'Health', 'Plugins'] },
  { name: 'Odoo', caps: ['CRM', 'Sales', 'Health', 'DB/Tenant'] },
  { name: 'Any Provider', caps: ['Contract', 'Adapter', 'Health', 'Metrics'] },
];

export const INFRA_TILES = [
  'Docker',
  'Kubernetes',
  'Networks',
  'Storage',
  'VMs',
  'Edge',
  'External Services',
];

export const DATA_STATE_TILES = [
  'Relational DBs',
  'NoSQL DBs',
  'Vector DBs',
  'Cache',
  'Object Storage',
  'Message Brokers',
  'File Storage',
  'Search / Index',
];

export const SECURITY_ITEMS = [
  'Authentication',
  'SSO / OAuth2',
  'Authorization',
  'RBAC / ABAC',
  'Policy Engine',
  'Secrets',
  'Tenant Isolation',
  'Audit',
  'Compliance',
  'Destructive-op controls',
];

export const EVENT_TYPES = [
  { type: 'health.changed', status: 'green' },
  { type: 'deployment.started', status: 'blue' },
  { type: 'deployment.completed', status: 'green' },
  { type: 'deployment.failed', status: 'red' },
  { type: 'configuration.changed', status: 'purple' },
  { type: 'backup.completed', status: 'green' },
  { type: 'operation.started', status: 'blue' },
  { type: 'operation.failed', status: 'red' },
  { type: 'security.alert', status: 'orange' },
  { type: 'workflow.failed', status: 'red' },
  { type: 'agent.completed', status: 'green' },
];

export const LIFECYCLE_STAGES = [
  { id: 'queued', label: 'QUEUED', tone: 'amber' },
  { id: 'running', label: 'RUNNING', tone: 'blue' },
  { id: 'retrying', label: 'RETRYING', tone: 'orange' },
  { id: 'succeeded', label: 'SUCCEEDED', tone: 'green' },
  { id: 'failed', label: 'FAILED', tone: 'red' },
  { id: 'rolling-back', label: 'ROLLING BACK', tone: 'orange' },
  { id: 'rolled-back', label: 'ROLLED BACK', tone: 'amber' },
  { id: 'cancelled', label: 'CANCELLED', tone: 'red-muted' },
];

export const LEGEND_ITEMS = [
  { label: 'Control / API flow', style: 'cyan-solid' },
  { label: 'Layer transition', style: 'purple-solid' },
  { label: 'Event / operational', style: 'green-dash' },
  { label: 'Dependency', style: 'gray-solid' },
];
