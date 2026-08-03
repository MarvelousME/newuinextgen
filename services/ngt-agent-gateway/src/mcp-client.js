/**
 * Controlled MCP client — allowlisted tools only; talks to mock or approved servers.
 */

export function createMcpClient() {
  const allowlist = new Set(['ping', 'business.profile.get', 'health.summary']);

  return {
    async discover(endpoint) {
      // For local mock: invent stable capability set; live servers would use MCP initialize/list.
      return {
        tools: [
          { name: 'ping', description: 'Non-mutating health ping' },
          { name: 'business.profile.get', description: 'Read business profile summary' },
          { name: 'danger.shell', description: 'MUST remain unapproved' },
        ],
        resources: [{ uri: 'ngt://business-profile', name: 'Business profile' }],
        prompts: [{ name: 'draft_social_post' }],
        endpoint,
        discovered_at: new Date().toISOString(),
      };
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
