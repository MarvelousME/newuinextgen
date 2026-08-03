# NGT Agent Gateway

Separately deployable Agent Gateway for NextGen Tutors.

## Boundary

WordPress / Companion never executes untrusted agent workloads. This service:

- Uses official `@a2a-js/sdk` (v1.0.x) when installed
- Exposes authenticated `/v1/tasks` for first-party agent execution
- Exposes `/v1/mcp/*` with allowlist + SSRF guards
- Requires `NGT_GATEWAY_SHARED_SECRET` (HMAC) unless `NGT_GATEWAY_ALLOW_INSECURE=1` for local tests only

## Run

```bash
cd services/ngt-agent-gateway
npm install
NGT_GATEWAY_SHARED_SECRET=devsecret npm start
```

## Test

```bash
npm test
```

Evidence: 12/12 Node tests including HTTP E2E task + MCP allowlist + SSRF + HMAC.

## WordPress bridge

`NGC_Agent_Gateway_Client` signs requests with `NGT_GATEWAY_SHARED_SECRET` and `NGT_AGENT_GATEWAY_URL`.
