/**
 * Controlled MCP client — allowlisted tools only; discover is live JSON-RPC.
 */

export function createMcpClient(opts = {}) {
  const allowlist = new Set(['ping', 'business.profile.get', 'health.summary']);
  const fetchFn = opts.fetchImpl || globalThis.fetch.bind(globalThis);

  async function rpc(endpoint, method, params) {
    const res = await fetchFn(endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify({ jsonrpc: '2.0', id: 1, method, params: params || {} }),
    });
    if (!res.ok) {
      throw new Error('http_' + res.status);
    }
    const body = await res.json();
    if (body && body.error) {
      throw new Error(body.error.message || 'rpc_error');
    }
    return body.result || {};
  }

  return {
    async discover(endpoint) {
      try {
        const init = await rpc(endpoint, 'initialize', {
          protocolVersion: '2024-11-05',
          capabilities: {},
          clientInfo: { name: 'ngt-agent-gateway', version: '1' },
        });
        let tools = [];
        try {
          const listed = await rpc(endpoint, 'tools/list', {});
          tools = Array.isArray(listed.tools)
            ? listed.tools.map((t) => ({
                name: String(t.name || ''),
                description: String(t.description || ''),
              }))
            : [];
        } catch {
          tools = [];
        }
        return {
          tools,
          resources: [],
          prompts: [],
          endpoint,
          protocol: init.protocolVersion || null,
          serverInfo: init.serverInfo || null,
          discovered_at: new Date().toISOString(),
          mode: 'live',
        };
      } catch (err) {
        return {
          tools: [],
          resources: [],
          prompts: [],
          endpoint,
          error: err instanceof Error ? err.message : 'discover_failed',
          discovered_at: new Date().toISOString(),
          mode: 'failed',
        };
      }
    },

    async executeAllowlisted(endpoint, tool, args) {
      const name = String(tool || '');
      if (!allowlist.has(name)) {
        return { ok: false, error: 'tool_not_allowlisted', tool: name };
      }
      if (name === 'ping') {
        return {
          ok: true,
          tool: name,
          result: { pong: true, endpoint, at: new Date().toISOString() },
          mode: 'controlled',
        };
      }
      if (name === 'business.profile.get') {
        return {
          ok: true,
          tool: name,
          result: {
            company: 'Next Gen Tutors',
            region: 'South Africa',
            note: 'Redacted profile stub for gateway verification',
          },
        };
      }
      if (name === 'health.summary') {
        return { ok: true, tool: name, result: { status: 'ok', args } };
      }
      return { ok: false, error: 'unhandled_tool' };
    },
  };
}
