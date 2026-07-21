const { test, expect } = require('@playwright/test');
const {
  authHeaders,
  issueMobileToken,
  skipWhenExternal,
} = require('./support/crm-e2e');

test.describe('CRM authenticated navigation E2E', () => {
  skipWhenExternal(test);

  test('authenticated user can open critical module pages', async ({ page, request }) => {
    const fixture = await issueMobileToken(request, 'Playwright Navigation');
    const headers = authHeaders(fixture.token);
    const sessionResponse = await request.post('/api/mobile/web-session', {
      headers,
      data: {
        redirectPath: '/',
        siteId: fixture.siteId,
        embed: false,
      },
    });

    expect(sessionResponse.status()).toBe(200);

    const session = await sessionResponse.json();
    expect(session).toMatchObject({ ok: true });

    await page.goto(session.url);
    await expect(page).toHaveURL(/\/(dashboard\/crm)?$/);
    await expect(page.locator('body')).toContainText(/Tableau de bord/i);

    const pages = [
      ['/locations-materiel', /Location mat[ée]riel/i],
      ['/conges', /Cong[ée]s/i],
    ];

    for (const [route, heading] of pages) {
      await page.goto(route);
      await expect(page.locator('body')).toContainText(heading);
      await expect(page.locator('body')).not.toContainText(/404 - Page non trouv[ée]e/i);
    }
  });
});
