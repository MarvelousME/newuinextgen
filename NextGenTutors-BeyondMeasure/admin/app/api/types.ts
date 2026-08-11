export type BootConfig = {
	restRoot: string;
	nonce: string;
	version: string;
	pluginUrl: string;
	userId: number;
	caps: Record<string, boolean>;
	adminUrl: string;
	pageSlug: string;
};

export type Envelope<T> = {
	data: T;
	meta?: { requestId?: string; timestamp?: string; version?: string };
	error?: { code: string; message: string; requestId?: string };
};
