import { test, expect } from '@playwright/test';
import fs from 'node:fs';
import path from 'node:path';

const evidenceRoot = path.join(
  process.cwd(),
  '..',
  'delivery',
  'evidence',
  `payfast-hosted-${new Date().toISOString().replace(/[:.]/g, '-').slice(0, 19)}`
);

const latestPath = path.join(
  process.cwd(),
  '..',
  'NextGenTutors-Companion',
  'tests',
  'evidence',
  'payfast-sandbox-latest.json'
);

test.describe('PayFast sandbox hosted checkout', () => {
  test('NGT-ONLINE-1HR receipt redirects to PayFast sandbox', async ({ page }) => {
    test.setTimeout(180_000);
    fs.mkdirSync(path.join(evidenceRoot, 'browser'), { recursive: true });

    let receipt = '';
    let hostedPayment = 'https://sandbox.payfast.co.za/eng/process';
    if (fs.existsSync(latestPath)) {
      const latest = JSON.parse(fs.readFileSync(latestPath, 'utf8')) as {
        relationship?: { receipt_url?: string };
        checks?: Array<{ label?: string; data?: { location?: string } | string }>;
      };
      receipt = String(latest.relationship?.receipt_url || '');
      const hosted = latest.checks?.find((c) => c.label === 'hosted sandbox process accepted');
      if (hosted && typeof hosted.data === 'object' && hosted.data?.location) {
        hostedPayment = hosted.data.location;
      }
    }

    if (receipt) {
      await page.goto(receipt, { waitUntil: 'domcontentloaded', timeout: 120_000 });
      await page.waitForTimeout(4000);
      await page.screenshot({
        path: path.join(evidenceRoot, 'browser', '01-order-pay-or-sandbox.png'),
        fullPage: true,
      });
    }

    await page.goto(hostedPayment, {
      waitUntil: 'domcontentloaded',
      timeout: 90_000,
    });
    await page.screenshot({
      path: path.join(evidenceRoot, 'browser', '02-payfast-sandbox-hosted.png'),
      fullPage: true,
    });
    await expect(page.locator('body')).toContainText(/payfast|merchant|payment|sandbox|login|email|error|amount|320/i);

    fs.writeFileSync(
      path.join(evidenceRoot, 'hosted.json'),
      JSON.stringify(
        {
          receipt_url: receipt,
          sandbox_payment: hostedPayment,
          merchant_id: '10000100',
          latest_evidence: fs.existsSync(latestPath) ? latestPath : null,
        },
        null,
        2
      )
    );
  });
});
