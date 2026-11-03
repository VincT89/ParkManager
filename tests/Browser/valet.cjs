const { chromium } = require(process.env.PLAYWRIGHT_MODULE);
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const baseURL = process.env.BROWSER_TEST_URL || 'http://127.0.0.1:8765';
const fixture = JSON.parse(fs.readFileSync('storage/framework/testing/management-browser-fixtures.json'));
const output = path.resolve('storage/framework/testing/valet-browser');
fs.mkdirSync(output, { recursive: true });

(async () => {
    const browser = await chromium.launch({ headless: true, executablePath: process.env.BROWSER_EXECUTABLE });
    const context = await browser.newContext({ baseURL, viewport: { width: 1440, height: 1000 } });
    await context.route('**/*', route => new URL(route.request().url()).origin === baseURL ? route.continue() : route.abort());
    const page = await context.newPage();
    const errors = [];
    const results = [];
    page.on('pageerror', error => errors.push(error.message));
    try {
        await page.goto('/login');
        await page.locator('[name=email]').fill('socio@example.test');
        await page.locator('[name=password]').fill('Only-browser-fixture-2026');
        await Promise.all([page.waitForURL('**/dashboard'), page.locator('button[type=submit]').click()]);
        assert.equal(await page.locator('meta[name="parking-context"]').getAttribute('content'), String(fixture.B.parking));
        assert.equal(await page.locator('#active-parking').count(), 0);

        for (const width of [360, 768, 1440]) {
            await page.setViewportSize({ width, height: width === 1440 ? 1000 : 844 });
            const response = await page.goto('/reservations?sort_by=starts_at_asc');
            assert.equal(response.status(), 200);
            const checkbox = page.getByRole('checkbox', { name: 'Solo valet', exact: true });
            await Promise.all([
                page.waitForURL(url => url.searchParams.get('only_valet') === '1'),
                checkbox.check(),
            ]);
            await page.waitForLoadState('networkidle');
            const rows = await page.locator('.pm-table tbody tr').allTextContents();
            assert.equal(rows.length, 20);
            assert.ok(rows.every(row => row.includes('Valet B ') && !row.includes('Valet A ')));
            assert.ok(await checkbox.isChecked());
            assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
            const label = await page.locator('label[for="only-valet"]').boundingBox();
            assert.ok(label.height >= 44 && label.x >= 0 && label.x + label.width <= width);
            await page.screenshot({ path: path.join(output, `valet-${width}.png`), animations: 'disabled' });

            await Promise.all([
                page.waitForURL(url => url.searchParams.get('page') === '2'),
                page.locator('.pm-pagination a').filter({ hasText: /^2$/ }).click(),
            ]);
            assert.equal(new URL(page.url()).searchParams.get('only_valet'), '1');
            assert.equal(await page.locator('.pm-table tbody tr').count(), 5);
            assert.ok(await checkbox.isChecked());

            await page.locator('[name=search]').fill('Valet B 01');
            await Promise.all([
                page.waitForURL(url => url.searchParams.get('search') === 'Valet B 01'),
                page.locator('[name=search]').press('Enter'),
            ]);
            assert.equal(await page.locator('.pm-table tbody tr').count(), 1);
            const exportURL = await page.getByRole('link', { name: 'Export Excel', exact: true }).getAttribute('href');
            assert.equal(new URL(exportURL).searchParams.get('only_valet'), '1');

            await page.goto('/reservations?only_valet=1&sort_by=starts_at_asc');
            await Promise.all([
                page.waitForURL(url => !url.searchParams.has('only_valet')),
                checkbox.uncheck(),
            ]);
            assert.ok((await page.locator('.pm-table tbody').innerText()).includes('Prenotazione B riservata'));
            results.push({ width, filter: true, pagination: true, combinedSearch: true, reset: true, overflow: false });
        }

        await page.goto('/reservations?only_valet=1');
        const [download] = await Promise.all([
            page.waitForEvent('download'),
            page.getByRole('link', { name: 'Export Excel', exact: true }).click(),
        ]);
        assert.equal(await download.failure(), null);
        assert.match(download.suggestedFilename(), /^prenotazioni_.*\.xlsx$/);
        assert.deepEqual(errors, []);
        fs.writeFileSync(path.join(output, 'report.json'), JSON.stringify({ results, exportDownload: true, errors }, null, 2));
        console.log(JSON.stringify({ results, exportDownload: true, errors }));
    } finally {
        await page.setViewportSize({ width: 1440, height: 1000 });
        const logout = page.locator('form[action$="/logout"] button');
        if (await logout.count()) {
            await Promise.all([page.waitForURL('**/login'), logout.click()]).catch(() => {});
        }
        await context.close();
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
