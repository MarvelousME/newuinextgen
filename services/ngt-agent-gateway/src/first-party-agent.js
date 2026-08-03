/** First-party trusted diagnostics agent (no external network). */

export function createFirstPartyExecutor() {
  return {
    async execute(task) {
      const text = String(task.message || '').trim();
      if (!text) {
        return { ok: false, error: 'empty_message' };
      }
      if (/ethnicity|gender|age_min|race\b/i.test(text)) {
        return {
          ok: false,
          error: 'protected_trait_rejected',
          summary: 'Recruitment prompts must not include protected traits.',
        };
      }
      return {
        ok: true,
        summary: `echo:${text.slice(0, 200)}`,
        artifacts: [
          {
            kind: 'text',
            name: 'echo.txt',
            text: text.slice(0, 4000),
          },
        ],
      };
    },
  };
}
