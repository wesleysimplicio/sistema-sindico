// @ts-check
const { test, expect } = require('@playwright/test');

const ADMIN_EMAIL = process.env.ADMIN_EMAIL || 'admin@sindico.local';
const ADMIN_PASSWORD = process.env.ADMIN_PASSWORD || 'senha123';

test.describe('Admin happy path', () => {
  test('login -> dashboard -> create notice', async ({ page }) => {
    await page.goto('/login');
    await expect(page.getByRole('heading', { name: /entrar|login/i })).toBeVisible();

    await page.locator('input[name="email"]').fill(ADMIN_EMAIL);
    await page.locator('input[name="password"]').fill(ADMIN_PASSWORD);
    await page.getByRole('button', { name: /entrar|login|acessar/i }).click();

    await page.waitForURL(/\/(dashboard|admin|home|painel)?$/i, { timeout: 10_000 });
    await expect(page.locator('body')).toContainText(/dashboard|painel|s[ií]ndico/i);

    await page.goto('/avisos');
    const newBtn = page.getByRole('link', { name: /novo|criar|adicionar/i }).or(
      page.getByRole('button', { name: /novo|criar|adicionar/i })
    );
    await newBtn.first().click();

    const title = `E2E ${Date.now()}`;
    await page.locator('input[name="title"], #title').first().fill(title);
    await page.locator('textarea[name="body"], #body').first().fill('Aviso criado via Playwright E2E.');
    await page.getByRole('button', { name: /salvar|publicar|criar/i }).first().click();

    await page.waitForURL(/\/avisos/i, { timeout: 10_000 });
    await expect(page.locator('body')).toContainText(title);
  });

  test('login negative — wrong password shows error', async ({ page }) => {
    await page.goto('/login');
    await page.locator('input[name="email"]').fill(ADMIN_EMAIL);
    await page.locator('input[name="password"]').fill('wrong-password');
    await page.getByRole('button', { name: /entrar|login|acessar/i }).click();
    await expect(page.locator('body')).toContainText(/inv[aá]lid|invalid|erro|incorret/i);
  });

  test('protected route redirects when anonymous', async ({ page }) => {
    await page.goto('/dashboard');
    await page.waitForURL(/\/login/i, { timeout: 5_000 });
    expect(page.url()).toMatch(/\/login/i);
  });
});
