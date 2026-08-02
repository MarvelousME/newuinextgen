import { test, expect } from '@playwright/test';
import { testEmail, fillNgForm, submitNgForm, expectFormSubmitted, primaryNgForm } from '../helpers';

test.describe('Blueprint WF-01 Parent Registration', () => {
  test('parent register child form', async ({ page }) => {
    // /register/ shows only the role chooser; forms render behind ?role=.
    await page.goto('/register/?role=parent', { waitUntil: 'domcontentloaded' });
    const form = primaryNgForm(page, 'parent_register');
    await expect(form).toBeVisible();

    await fillNgForm(
      page,
      {
        parent_name: 'E2E Parent',
        email: testEmail('parent-reg'),
        child_name: 'E2E Child',
        grade: 'Grade 10',
      },
      { form }
    );
    const res = await submitNgForm(page, form);
    await expectFormSubmitted(page, 'parent_register', res);
  });
});

test.describe('Blueprint WF-02 Student Registration', () => {
  test('student self-registration form', async ({ page }) => {
    await page.goto('/register/?role=student', { waitUntil: 'domcontentloaded' });
    const form = primaryNgForm(page, 'student_register');
    await expect(form).toBeVisible();

    await fillNgForm(
      page,
      {
        full_name: 'E2E Student',
        email: testEmail('student'),
        grade: 'Grade 11',
      },
      { form }
    );
    const res = await submitNgForm(page, form);
    await expectFormSubmitted(page, 'student_register', res);
  });
});

test.describe('Blueprint WF-19 Support', () => {
  test('contact support form', async ({ page }) => {
    await page.goto('/contact/', { waitUntil: 'domcontentloaded' });
    const form = primaryNgForm(page, 'contact_support');
    await expect(form).toBeVisible({ timeout: 30_000 });

    await fillNgForm(
      page,
      {
        name: 'E2E Support User',
        email: testEmail('support'),
        message: 'This is an automated Playwright support workflow test message.',
      },
      { select: { topic: 'general' }, form }
    );

    const res = await submitNgForm(page, form);
    await expectFormSubmitted(page, 'contact_support', res);
  });

  test('login page renders sign-in form', async ({ page }) => {
    await page.goto('/login/', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('#ngc-loginform, #loginform, form[name="loginform"]')).toBeVisible();
  });
});
