/**
 * Shared Playwright fixtures: authenticated admin page + DB evidence helpers.
 */
import { test as base, expect } from '@playwright/test';
import fs from 'node:fs';
import path from 'node:path';
import { wpLogin, wpAdminUser } from '../helpers';
import {
  captureSqlExtract,
  captureCrudPair,
  runSql,
  PROBES,
  type DbEvidenceContext,
} from './db-evidence';

type EvidenceFixtures = {
  adminPage: import('@playwright/test').Page;
  dbEvidence: {
    runSql: typeof runSql;
    capture: typeof captureSqlExtract;
    captureCrud: typeof captureCrudPair;
    probes: typeof PROBES;
    ctx: (partial: Partial<DbEvidenceContext> & Pick<DbEvidenceContext, 'testCase' | 'entity'>) => DbEvidenceContext;
  };
  uiEvidence: {
    shot: (name: string) => Promise<string>;
    dir: string;
  };
};

export const test = base.extend<EvidenceFixtures>({
  adminPage: async ({ page }, use) => {
    await wpLogin(page);
    await use(page);
  },

  dbEvidence: async ({}, use, testInfo) => {
    await use({
      runSql,
      capture: captureSqlExtract,
      captureCrud: captureCrudPair,
      probes: PROBES,
      ctx: (partial) => ({
        user: wpAdminUser,
        tenant: 'default',
        correlationId: testInfo.testId,
        ...partial,
      }),
    });
  },

  uiEvidence: async ({ page }, use, testInfo) => {
    const dir = path.join(
      process.cwd(),
      'reports',
      'evidence',
      'ui',
      testInfo.title.replace(/[^a-zA-Z0-9._-]+/g, '_').slice(0, 80)
    );
    fs.mkdirSync(dir, { recursive: true });
    await use({
      dir,
      shot: async (name: string) => {
        const file = path.join(dir, `${name}.png`);
        await page.screenshot({ path: file, fullPage: true });
        return file;
      },
    });
  },
});

export { expect };
export { captureSqlExtract, captureCrudPair, runSql, PROBES };
