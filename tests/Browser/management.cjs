const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const fs = require('fs');
const path = require('path');
const assert = require('node:assert/strict');
const directory = path.resolve('storage/framework/testing');
const fixture = JSON.parse(fs.readFileSync(path.join(directory, 'management-browser-fixtures.json')));
const output = path.join(directory, 'browser-qa');
fs.mkdirSync(output, { recursive: true });
const baseURL = process.env.BROWSER_TEST_URL || 'http://127.0.0.1:8765';

(async () => {
    const browser = await chromium.launch({ headless: true, executablePath: process.env.BROWSER_EXECUTABLE });
    const errors = [];
    async function pageFor(email, viewport) {
        const context = await browser.newContext({ viewport, baseURL });
        // Browser tests operate only on the disposable local application.
        await context.route('**/*', route => new URL(route.request().url()).origin === baseURL ? route.continue() : route.abort());
        const page = await context.newPage();
        page.on('pageerror', error => errors.push(error.message));
        page.on('dialog', dialog => dialog.accept());
        await page.goto('/login');
        await page.locator('input[name="email"]').fill(email);
        await page.locator('input[name="password"]').fill('Only-browser-fixture-2026');
        await Promise.all([page.waitForURL('**/dashboard'), page.locator('button[type="submit"]').click()]);
        return { context, page };
    }
    try {
        const owner = await pageFor('cliente@example.test', { width: 1440, height: 1000 });
        await owner.page.locator('#active-parking').selectOption(String(fixture.B.parking));
        await Promise.all([owner.page.waitForURL('**/dashboard'), owner.page.getByRole('button', { name: 'Cambia', exact: true }).click()]);
        await owner.page.goto('/reservations');
        assert.match(await owner.page.locator('main').innerText(), /Prenotazione B riservata/);
        assert.doesNotMatch(await owner.page.locator('main').innerText(), /Prenotazione A riservata/);
        await owner.page.screenshot({ path: path.join(output, 'desktop-switch.png'), fullPage: true, animations: 'disabled' });
        await owner.page.goto('/reservations/'+fixture.B.reservation);
        if (await owner.page.getByRole('button', { name: 'Segna come pagato', exact: true }).count()) {
            await owner.page.getByRole('button', { name: 'Segna come pagato', exact: true }).click();
        }
        await owner.page.waitForLoadState('networkidle');
        assert.equal(await owner.page.getByRole('button', { name: 'Segna come pagato', exact: true }).count(), 0);
        await owner.page.goto('/management/users');
        const memberEmail = 'browser-member-'+Date.now()+'@example.test';
        await owner.page.locator('#member-name').fill('Utente browser (test)');
        await owner.page.locator('#member-email').fill(memberEmail);
        await owner.page.locator('#member-password').fill('Only-browser-fixture-2026');
        await owner.page.locator('#member-password-confirmation').fill('Only-browser-fixture-2026');
        await owner.page.getByRole('button', { name: 'Aggiungi utente', exact: true }).click();
        const memberRow = owner.page.getByRole('row').filter({ hasText: memberEmail });
        await memberRow.waitFor({ state: 'visible' });
        await memberRow.locator('select[name="role"]').selectOption('admin');
        await memberRow.getByRole('button', { name: 'Salva ruolo', exact: true }).click();
        await owner.page.waitForLoadState('networkidle');
        assert.equal(await memberRow.locator('select[name="role"]').inputValue(), 'admin');
        await memberRow.getByRole('button', { name: 'Rimuovi accesso', exact: true }).click();
        await owner.page.waitForURL('**/dashboard');
        await owner.page.goto('/management/users');
        assert.equal(await owner.page.getByRole('row').filter({ hasText: memberEmail }).count(), 0);
        await owner.page.screenshot({ path: path.join(output, 'desktop-members.png'), fullPage: true, animations: 'disabled' });
        const partner = await pageFor('socio@example.test', { width: 390, height: 844 });
        assert.equal(await partner.page.locator('#active-parking').count(), 0);
        assert.doesNotMatch(await partner.page.locator('body').innerText(), /Parcheggio A \(test\)/);
        await partner.page.goto('/reservations');
        assert.match(await partner.page.locator('main').innerText(), /Prenotazione B riservata/);
        assert.doesNotMatch(await partner.page.locator('main').innerText(), /Prenotazione A riservata/);
        assert.equal(await partner.page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1), true);
        await partner.page.screenshot({ path: path.join(output, 'mobile-partner.png'), fullPage: true, animations: 'disabled' });
        const forbidden = await partner.page.goto('/reservations/'+fixture.A.reservation);
        assert.equal(forbidden.status(), 404);
        await partner.page.goto('/management/users');
        assert.equal(await partner.page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1), true);
        await partner.page.screenshot({ path: path.join(output, 'mobile-members.png'), fullPage: true, animations: 'disabled' });
        const anonymous = await browser.newContext({ viewport: { width: 390, height: 844 }, baseURL });
        await anonymous.route('**/*', route => new URL(route.request().url()).origin === baseURL ? route.continue() : route.abort());
        const publicPage = await anonymous.newPage();
        await publicPage.goto('/booking?parking='+fixture.B.public_key);
        assert.doesNotMatch(await publicPage.locator('body').innerText(), /Aeroporto di Bari/);
        assert.equal(await publicPage.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1), true);
        await publicPage.screenshot({ path: path.join(output, 'mobile-public-booking.png'), fullPage: true, animations: 'disabled' });
        const arrival = new Date(Date.now()+86400000*2).toISOString().slice(0,10);
        const departure = new Date(Date.now()+86400000*3).toISOString().slice(0,10);
        await publicPage.locator('#arrival_date').fill(arrival);
        await publicPage.locator('#arrival_time').fill('10:00');
        await publicPage.locator('#departure_date').fill(departure);
        await publicPage.locator('#departure_time').fill('12:00');
        await publicPage.locator('#btn-check').click();
        await publicPage.locator('#customer-details').waitFor({ state: 'visible' });
        await publicPage.locator('[name="customer_name"]').fill('Cliente browser (test)');
        await publicPage.locator('[name="customer_email"]').fill('booking-browser@example.test');
        await publicPage.locator('[name="customer_phone"]').fill('0000000000');
        await publicPage.locator('[name="license_plate"]').fill('TEST-001');
        await publicPage.locator('#booking-form button[type="submit"]').click();
        await publicPage.waitForURL('**/payment');
        await publicPage.screenshot({ path: path.join(output, 'mobile-public-payment.png'), fullPage: true, animations: 'disabled' });
        assert.equal(await publicPage.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1), true);
        await publicPage.getByRole('button', { name: 'Conferma prenotazione (paga all’arrivo)', exact: false }).or(publicPage.getByRole('button', { name: "Conferma prenotazione (paga all'arrivo)", exact: false })).click();
        await publicPage.waitForURL('**/booking/success/**');
        assert.match(await publicPage.locator('body').innerText(), /Parcheggio B \(test\)/);
        await publicPage.screenshot({ path: path.join(output, 'mobile-public-success.png'), fullPage: true, animations: 'disabled' });
        assert.equal(await publicPage.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1), true);
        assert.match(await publicPage.getByRole('link', { name: 'Nuova prenotazione' }).getAttribute('href'), new RegExp(fixture.B.public_key));
        assert.deepEqual(errors, []);
        console.log(JSON.stringify({ result: 'passed', checks: ['desktop switch', 'reservation separation', 'onsite payment', 'add member, change role and revoke access', 'mobile partner', 'forbidden direct access', 'mobile layout', 'separate public booking'], screenshots: output }, null, 2));
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
