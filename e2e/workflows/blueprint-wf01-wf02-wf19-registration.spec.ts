import { test, expect } from '@playwright/test';
import { testEmail, fillNgForm, submitNgForm, expectFormSubmitted, primaryNgForm } from '../helpers';

test.describe('Blueprint WF-01 Parent Registration', () => {
  test('parent register child form', async ({ page }) => {
    await page.goto('/register/');
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
    await submitNgForm(page, form);
    await expectFormSubmitted(page, 'parent_register');
  });
});

test.describe('Blueprint WF-02 Student Registration', () => {
  test('student self-registration form', async ({ page }) => {
    await page.goto('/register/');
    const forms = page.locator('form.ngc-form, form.bi-ngc-form');
    const count = await forms.count();
    const studentForm = count > 1 ? forms.nth(1) : forms.first();
    await studentForm.scrollIntoViewIfNeeded();

    await studentForm.locator('[name="full_name"]').fill('E2E Student');
    await studentForm.locator('[name="email"]').fill(testEmail('student'));
    await studentForm.locator('[name="grade"]').fill('Grade 11');
    await studentForm.locator('button[type="submit"]').click();
    await expectFormSubmitted(page, 'student_register');
  });
});

test.describe('Blueprint WF-19 Support', () => {
  test('contact support form', async ({ page }) => {
    await page.goto('/contact/');
    const form = primaryNgForm(page, 'contact_support');
    await expect(form).toBeVisible();

    await fillNgForm(
      page,
      {
        name: 'E2E Support User',
        email: testEmail('support'),
        message: 'This is an automated Playwright support workflow test message.',
      },
      { select: { topic: 'general' }, form }
    );

    await submitNgForm(page, form);
    await expectFormSubmitted(page, 'contact_support');
  });

  test('login page renders sign-in form', async ({ page }) => {
    await page.goto('/login/');
    await expect(page.locator('#ngc-loginform, #loginform, form[name="loginform"]')).toBeVisible();
  });
});
