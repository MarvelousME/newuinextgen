# AI architecture

Companion BYOK models/agents (`includes/ai/`, REST `/ai/*`) stay in Companion. Optional plugin NextGenTutors-AI-Integration is a signed outbox to agents-api (`ngtai/v1`) and must not hold domain tables. Do not put API keys in packages.