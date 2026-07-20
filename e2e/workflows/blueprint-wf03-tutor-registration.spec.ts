import { test, expect } from '@playwright/test';
import { testEmail, fillNgForm, submitNgForm, expectFormSubmitted, primaryNgForm } from '../helpers';

test.describe('Blueprint WF-03 Tutor Registration', () => {
  test('become-a-tutor form — fill all fields and submit', async ({ page }) => {
    await page.goto('/become-a-tutor/');
    const form = primaryNgForm(page, 'become_tutor');
    await expect(form).toBeVisible();

    await fillNgForm(
      page,
      {
        full_name: 'E2E Tutor Applicant',
        email: testEmail('tutor'),
        phone: '0821234567',
        subjects: 'Mathematics, Physical Science',
        experience: 'Five years teaching CAPS and IEB learners with excellent results.',
        province: 'Gauteng',
      },
      { form }
    );

    await submitNgForm(page, form);
    await expectFormSubmitted(page, 'become_tutor');
  });
});
