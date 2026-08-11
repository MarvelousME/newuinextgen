# API Contract — nextgentutors-control/v1

## Envelope

Success: `{ "data": ..., "meta": { "requestId", "timestamp", "version": "1" } }`  
Error: `{ "error": { "code", "message", "requestId" } }`

## Routes

| Method | Path | Cap |
|--------|------|-----|
| GET | /nav | ngt_cp_access |
| GET | /subsystems | ngt_subsystem_read |
| GET | /subsystems/{id} | ngt_subsystem_read |
| POST | /subsystems/{id}/enable | ngt_subsystem_enable |
| POST | /subsystems/{id}/disable | ngt_subsystem_disable |
| GET | /health | ngt_health_read |
| GET | /dependency-graph | ngt_subsystem_read |
| GET | /access-matrix | ngt_access_matrix_read |
| GET | /notifications | ngt_cp_access |
| POST | /notifications/{id}/ack | ngt_notifications_manage |
| GET/PUT | /configuration/{id} | configure / ngt_config_manage |
| GET | /resources/{resource} | ngt_cp_access (+ resource read) |
| GET | /talent/stats | ngt_talent_read |
| GET | /talent/evaluations/{id}/explain | ngt_talent_read |
| GET | /audit | ngt_audit_read |
| GET | /queues | ngt_health_read |
| GET | /capabilities | ngt_subsystem_read |
