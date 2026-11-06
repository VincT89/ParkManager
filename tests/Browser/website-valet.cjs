const { chromium } = require(process.env.PLAYWRIGHT_MODULE);
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const baseURL = process.env.BROWSER_TEST_URL || 'http://127.0.0.1:8765';
assert.equal(new URL(baseURL).hostname, '127.0.0.1');
assert.equal(new URL(baseURL).port, '8765');
const fixture = JSON.parse(fs.readFileSync('storage/framework/testing/management-browser-fixtures.json'));
const output = path.resolve('storage/framework/testing/website-valet-browser');
fs.mkdirSync(output, { recursive: true });

function futureDate(days) {
    const date = new Date();
    date.setUTCDate(date.getUTCDate() + days);
    return date.toISOString().slice(0, 10);
}

(async () => {
    const browser = await chromium.launch({ headless: true, executablePath: process.env.BROWSER_EXECUTABLE });
    const context = await browser.newContext({ baseURL, viewport: { width: 1440, height: 1000 } });
    await context.route('**/*', route => new URL(route.request().url()).origin === baseURL ? route.continue() : route.abort());
    const page = await context.newPage();
    const errors = [];
    const results = [];
    const runId = Date.now();
    const publicName = `Servizio sito ${runId} (test)`;
    const manualName = `Servizio manuale ${runId} (test)`;
    page.on('pageerror', error => errors.push(error.message));

    async function screenshot(name, width) {
        await page.setViewportSize({ width, height: width === 1440 ? 1000 : 844 });
        await page.waitForLoadState('networkidle');
        assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), name);
        await page.screenshot({ path: path.join(output, `${name}-${width}.png`), fullPage: true, animations: 'disabled' });
    }

    async function checkAvailability() {
        const [response] = await Promise.all([
            page.waitForResponse(response => response.url().includes('/booking/check-availability')),
            page.locator('#btn-check').click(),
        ]);
        assert.equal(response.status(), 200);
        const quote = await response.json();
        assert.equal(quote.available, true);
        await page.locator('#customer-details').waitFor({ state: 'visible' });
        return quote.total_price;
    }

    try {
        const initial = await page.goto(`/booking?parking=${fixture.B.public_key}`);
        assert.equal(initial.status(), 200, await page.title());
        assert.equal(await page.getByLabel('Tipo di servizio', { exact: true }).inputValue(), '0');
        for (const width of [360, 1440]) {
            await page.getByLabel('Tipo di servizio', { exact: true }).selectOption('1');
            await screenshot('public-form', width);
            assert.equal(await page.getByLabel('Tipo di servizio', { exact: true }).inputValue(), '1');
            results.push({ width, publicChoice: true, overflow: false });
        }
        await page.locator('#arrival_date').fill(futureDate(90));
        await page.locator('#arrival_time').fill('10:00');
        await page.locator('#departure_date').fill(futureDate(92));
        await page.locator('#departure_time').fill('10:00');
        await page.locator('#is_valet').selectOption('0');
        const standardPrice = await checkAvailability();
        await page.locator('#is_valet').selectOption('1');
        assert.equal(await page.locator('#customer-details').isVisible(), false);
        assert.equal(await checkAvailability(), standardPrice + 10);
        await page.locator('[name=customer_name]').fill(publicName);
        await page.locator('[name=customer_email]').fill('servizio-browser@example.test');
        await page.locator('[name=customer_phone]').fill('0000000000');
        await page.locator('[name=license_plate]').fill('TARGA-TROPPO-LUNGA-PER-TEST');
        await Promise.all([page.waitForNavigation(), page.getByRole('button', { name: 'Conferma Prenotazione', exact: true }).click()]);
        assert.equal(await page.locator('#is_valet').inputValue(), '1');
        await checkAvailability();
        await page.locator('[name=license_plate]').fill('TEST-VALET');
        await Promise.all([
            page.waitForURL('**/payment'),
            page.getByRole('button', { name: 'Conferma Prenotazione', exact: true }).click(),
        ]);
        assert.ok((await page.locator('body').innerText()).includes('Car valet'));
        for (const width of [360, 1440]) await screenshot('public-summary', width);
        await Promise.all([
            page.waitForURL('**/booking/success/**'),
            page.locator('form[action$="/onsite/confirm"] button').click(),
        ]);
        assert.ok((await page.locator('body').innerText()).includes('Car valet'));
        for (const width of [360, 1440]) await screenshot('public-success', width);

        await page.goto('/login');
        await page.locator('[name=email]').fill('socio@example.test');
        await page.locator('[name=password]').fill('Only-browser-fixture-2026');
        await Promise.all([page.waitForURL('**/dashboard'), page.locator('button[type=submit]').click()]);
        await page.goto('/reservations/create');
        assert.equal(await page.getByLabel('Tipo di servizio', { exact: true }).inputValue(), '0');
        const listing = await page.locator('[name=parking_listing_id] option').filter({ hasText: 'Sito B' }).getAttribute('value');
        const product = await page.locator('[name=parking_product_id] option').filter({ hasText: 'Posto B' }).getAttribute('value');
        await page.locator('[name=parking_listing_id]').selectOption(listing);
        await page.locator('[name=parking_product_id]').selectOption(product);
        await page.getByLabel('Tipo di servizio', { exact: true }).selectOption('1');
        await page.locator('[name=customer_name]').fill(manualName);
        await page.locator('[name=starts_at]').fill(`${futureDate(100)}T10:00`);
        await page.locator('[name=ends_at]').fill(`${futureDate(102)}T10:00`);
        await page.locator('[name=price]').fill('30');
        for (const width of [360, 1440]) await screenshot('manual-form', width);
        await Promise.all([
            page.waitForURL(url => url.pathname === '/reservations'),
            page.getByRole('button', { name: 'Crea prenotazione', exact: true }).click(),
        ]);
        await page.goto(`/reservations?only_valet=1&search=${encodeURIComponent(manualName)}`);
        assert.equal(await page.locator('.pm-table tbody tr').count(), 1);
        const editURL = await page.locator('.pm-table a[href$="/edit"]').getAttribute('href');
        await page.goto(editURL);
        assert.equal(await page.getByLabel('Tipo di servizio', { exact: true }).inputValue(), '1');
        await page.getByLabel('Tipo di servizio', { exact: true }).selectOption('0');
        await Promise.all([
            page.waitForURL(url => url.pathname === '/reservations'),
            page.getByRole('button', { name: 'Salva modifiche', exact: true }).click(),
        ]);
        await page.goto(`/reservations?only_valet=1&search=${encodeURIComponent(manualName)}`);
        assert.ok(!(await page.locator('main').innerText()).includes(manualName));
        await page.goto(`/reservations?only_valet=1&search=${encodeURIComponent(publicName)}`);
        assert.equal(await page.locator('.pm-table tbody tr').count(), 1);
        const publicEditURL = await page.locator('.pm-table a[href$="/edit"]').getAttribute('href');
        await page.goto(publicEditURL);
        assert.equal(await page.getByLabel('Tipo di servizio', { exact: true }).inputValue(), '1');
        for (const width of [360, 1440]) await screenshot('edit-form', width);
        assert.deepEqual(errors, []);
        const report = { results, validationPreservesChoice: true, valetSurcharge: 10, publicBooking: true, manualBooking: true, editAndFilter: true, errors };
        fs.writeFileSync(path.join(output, 'report.json'), JSON.stringify(report, null, 2));
        console.log(JSON.stringify(report));
    } finally {
        const logout = page.locator('form[action$="/logout"] button');
        if (await logout.count()) {
            await Promise.all([page.waitForURL('**/login'), logout.click()]).catch(() => {});
        }
        await context.close();
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
