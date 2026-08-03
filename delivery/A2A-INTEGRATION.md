# A2A-INTEGRATION

## Boundary

```text
WordPress / Companion / Mission Control
              |
      signed internal API (future) + admin pin UI
              |
       Agent Gateway (separate service — official a2a-js)
        /          \
 MCP client       A2A client
```

## WordPress responsibilities (implemented)

| Capability | Class | Notes |
| --- | --- | --- |
| Pin Agent Cards | `NGC_A2a_Gateway::pin_agent` | SSRF-checked URL; approved flag |
| Durable tasks | `NGC_A2a_Gateway::create_task` | Persisted; does **not** auto-execute |
| Admin UI | `NGC_Agentic_Admin::render_a2a` | Pin + inspect |

## Not in this delivery (honest)

- Running official `a2a-js` inside WordPress PHP.
- Streaming, push notification auth, full artifact scanning pipeline.
- Live cross-agent task execution against external peers.

**A2A agents verified: 0/0 pinned in runtime evidence (code path ready).**
