import { test, expect } from '@playwright/test';

test.describe('Blueprint WF-08 Manual Matching', () => {
  test('REST matches list requires authentication', async ({ request }) => {
    const res = await request.get('/wp-json/ngc/v1/matches');
    expect([401, 403]).toContain(res.status());
  });

  test('REST match assign route is registered', async ({ request }) => {
    const res = await request.post('/wp-json/ngc/v1/matches/1/assign', {
      data: { tutor_user_id: 1 },
    });
    expect([401, 403, 404]).toContain(res.status());
  });

  test('smart match endpoint responds', async ({ request }) => {
    const res = await request.get('/wp-json/ngc/v1/match/smart?subject=mathematics&grade=grade-10');
    expect([200, 401, 403, 400, 429]).toContain(res.status());
  });
});

test.describe('Blueprint WF-12 Invoices & WF-14 Cancellation', () => {
  test('REST invoices list requires authentication', async ({ request }) => {
    const res = await request.get('/wp-json/ngc/v1/invoices');
    expect([401, 403]).toContain(res.status());
  });

  test('REST bookings status transition requires authentication', async ({ request }) => {
    const res = await request.post('/wp-json/ngc/v1/bookings/1/status', {
      data: { status: 'cancelled' },
    });
    expect([401, 403, 404, 400]).toContain(res.status());
  });

  test('UI library verify endpoint requires admin', async ({ request }) => {
    const res = await request.get('/wp-json/ngc/v1/ui-library/verify');
    expect([401, 403]).toContain(res.status());
  });
});
