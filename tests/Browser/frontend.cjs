const { chromium } = require(process.env.PLAYWRIGHT_MODULE);
const fs = require('node:fs');
const assert = require('node:assert/strict');
const path = require('node:path');
const baseURL = process.env.BROWSER_TEST_URL || 'http://127.0.0.1:8765';
const fixture = JSON.parse(fs.readFileSync('storage/framework/testing/management-browser-fixtures.json'));
const output = path.resolve('storage/framework/testing/frontend-audit-after');
fs.mkdirSync(output, { recursive: true });

async function exerciseCalendar(page, context, fixture, output) {
    await page.route('**/calendar/data?**', route => route.fulfill({ status: 503, json: { message: 'Simulated test failure' } }));
    await page.goto('/calendar');
    await page.locator('#calendar-error').waitFor({ state: 'visible' });
    assert.match(await page.locator('#calendar-error').innerText(), /Non è stato possibile caricare/);
    await page.unroute('**/calendar/data?**');
    const [response] = await Promise.all([
        page.waitForResponse(r => r.url().includes('/calendar/data?') && r.ok()),
        page.getByRole('button', { name: 'Riprova', exact: true }).click(),
    ]);
    const payload = await response.json();
    await page.waitForFunction(() => document.querySelector('#timeline-container').children.length > 0);
    const today = new Date().toLocaleDateString('en-CA', { timeZone: 'Europe/Rome' });
    const probe = 'Cliente <em data-ui-probe="true">test</em>';
    const synthetic = { ...payload, reservations: [{ ...payload.reservations[0], starts_at: today, ends_at: today, starts_at_time: '10:00', ends_at_time: '12:00', product_code: 'ui_test', product_name: 'Categoria "test" <b data-ui-probe="true">nome</b>', customer_name: probe, license_plate: 'TEST-001', flight_reference: '', platform: 'Canale <em data-ui-probe="true">test</em>', spots: 1 }] };
    await page.route('**/calendar/data?**', route => route.fulfill({ status: 200, json: synthetic }));
    await page.reload();
    await page.waitForFunction(() => document.querySelector('#timeline-container').children.length > 0);
    await page.setViewportSize({ width: 360, height: 844 });
    for (const [label, view] of [['Giornaliera', 'daily'], ['Mensile', 'monthly'], ['Settimanale', 'timeline']]) {
        await page.getByRole('button', { name: label, exact: true }).click();
        await page.locator(`#view-${view}`).waitFor({ state: 'visible' });
        if (view === 'daily') assert.ok((await page.locator('#daily-container').innerText()).includes(probe));
        if (view === 'monthly') await page.locator('#monthly-container [data-date]').first().hover();
        assert.equal(await page.locator('[data-ui-probe]').count(), 0);
        assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
        await page.screenshot({ path: path.join(output, `calendar-${view}-360.png`), fullPage: true, animations: 'disabled' });
    }
    await page.unroute('**/calendar/data?**');
}

async function switchParking(page, parking) {
    await page.goto('/dashboard');
    await page.locator('#active-parking').selectOption(String(parking));
    await Promise.all([
        page.waitForResponse(r => r.url().endsWith('/parking/switch') && r.request().method() === 'POST'),
        page.getByRole('button', { name: 'Cambia', exact: true }).click(),
    ]);
    await page.waitForLoadState('networkidle');
    assert.equal(await page.locator('meta[name="parking-context"]').getAttribute('content'), String(parking));
}

async function exerciseMovement(page, context, fixture) {
    await page.goto(`/reservations/${fixture.B.reservation}/edit`);
    const date = (await page.locator('[name="starts_at"]').inputValue()).split('T')[0];
    const dayURL = `/calendar/day?type=entries&date=${date}`;
    await page.goto(dayURL);
    const checkbox = page.locator(`#check_${fixture.B.reservation}`);
    const before = await checkbox.isChecked();
    const movementURL = `**/reservations/${fixture.B.reservation}/toggle-movement`;
    await page.route(movementURL, route => route.fulfill({ status: 500, json: { message: 'Simulated test failure' } }));
    await checkbox.click();
    await page.locator('#pm-request-error').waitFor({ state: 'visible' });
    assert.equal(await checkbox.isChecked(), before);
    assert.equal(await checkbox.isEnabled(), true);
    await page.unroute(movementURL);

    await Promise.all([page.waitForResponse(r => r.url().includes('/toggle-movement') && r.ok()), checkbox.click()]);
    await page.reload();
    assert.equal(await checkbox.isChecked(), !before);
    await Promise.all([page.waitForResponse(r => r.url().includes('/toggle-movement') && r.ok()), checkbox.click()]);
    await page.reload();
    assert.equal(await checkbox.isChecked(), before);

    const otherTab = await context.newPage();
    try {
        await switchParking(otherTab, fixture.A.parking);
        await checkbox.click();
        await page.locator('#pm-request-error').waitFor({ state: 'visible' });
        assert.match(await page.locator('#pm-request-error').innerText(), /un’altra scheda/);
        assert.equal(await checkbox.isChecked(), before);
    } finally {
        await switchParking(otherTab, fixture.B.parking);
        await otherTab.close();
    }
}

(async () => {
    const browser = await chromium.launch({ headless: true, executablePath: process.env.BROWSER_EXECUTABLE });
    const context = await browser.newContext({ baseURL, viewport: { width: 1440, height: 1000 } });
    await context.route('**/*', route => new URL(route.request().url()).origin === baseURL ? route.continue() : route.abort());
    const page = await context.newPage();
    const errors = [];
    page.on('pageerror', error => errors.push({ url: page.url(), error: error.message }));
    const results = [];
    try {
        await page.goto('/login');
        await page.locator('[name=email]').fill('cliente@example.test');
        await page.locator('[name=password]').fill('Only-browser-fixture-2026');
        await Promise.all([page.waitForURL('**/dashboard'), page.locator('button[type=submit]').click()]);
        await page.locator('#active-parking').selectOption(String(fixture.B.parking));
        await Promise.all([page.waitForURL('**/dashboard'), page.getByRole('button', { name: 'Cambia', exact: true }).click()]);
        await page.goto('/admin/parkings');
        const parkingEdit = await page.getByRole('link', { name: 'Configura parcheggio', exact: true }).first().getAttribute('href');
        await page.goto('/platforms');
        const platformEdit = await page.getByRole('link', { name: 'Configura', exact: true }).first().getAttribute('href');
        const pages = [
            ['dashboard', '/dashboard'], ['reservations', '/reservations'], ['reservation-new', '/reservations/create'],
            ['reservation-edit', `/reservations/${fixture.B.reservation}/edit`], ['reservation', `/reservations/${fixture.B.reservation}`],
            ['calendar', '/calendar'], ['calendar-day', '/calendar/day'], ['analytics', '/analytics'],
            ['blocks', '/availability-blocks'], ['block-new', '/availability-blocks/create'], ['alerts', '/alerts'],
            ['platforms', '/platforms'], ['platform-new', '/platforms/create'], ['platform-edit', platformEdit],
            ['parkings', '/admin/parkings'], ['parking-new', '/admin/parkings/create'], ['parking-edit', parkingEdit],
            ['users', '/management/users'], ['activity', '/management/activity'], ['account', '/admin/account'], ['sync-logs', '/sync-logs']
        ];
        const selectedPages = process.env.FRONTEND_AUDIT_PAGES?.split(',');
        const widths = selectedPages ? [360, 768, 1440, 1920] : [360, 768, 1440];
        for (const width of widths) {
            await page.setViewportSize({ width, height: width === 1440 ? 1000 : 844 });
            for (const [name, url] of pages) {
                if (selectedPages && !selectedPages.includes(name)) continue;
                const response = await page.goto(url);
                await page.waitForLoadState('networkidle');
                if (width > 1024) assert.equal(await page.locator('.pm-sidebar-close').isVisible(), false);
                if (name === 'users') {
                    const geometry = await page.locator('main').evaluate(main => {
                        const cards = [...main.querySelectorAll(':scope > .pm-card')].map(el => el.getBoundingClientRect());
                        const controlsFit = [...main.querySelectorAll('.pm-members-table tbody td')].every(cell => {
                            const bounds = cell.getBoundingClientRect();
                            return [...cell.querySelectorAll('select, button')].every(control => {
                                const r = control.getBoundingClientRect();
                                return r.left >= bounds.left && r.right <= bounds.right && r.width >= 90;
                            });
                        });
                        return { widths: cards.map(r => r.width), controlsFit };
                    });
                    assert.equal(geometry.widths[0], geometry.widths[1], 'User panels should have equal widths');
                    assert.equal(geometry.controlsFit, true, 'Role and access controls should fit their columns');
                }
                const result = await page.evaluate(() => {
                    const width = innerWidth;
                    const topbar = document.querySelector('.pm-topbar');
                    const overflow = [...document.querySelectorAll('.pm-content *')].filter(el => {
                        const r = el.getBoundingClientRect();
                        if (!r.width || r.right <= width + 1) return false;
                        for (let p = el.parentElement; p && !p.matches('.pm-content'); p = p.parentElement) {
                            if (['auto', 'scroll', 'hidden', 'clip'].includes(getComputedStyle(p).overflowX)) return false;
                        }
                        return true;
                    }).slice(0, 8).map(el => ({ tag: el.tagName, class: el.className, text: el.textContent.trim().slice(0, 65), width: Math.round(el.getBoundingClientRect().width) }));
                    return { contentWidth: document.documentElement.scrollWidth, topbarHeight: Math.round(topbar?.getBoundingClientRect().height || 0), overflow };
                });
                results.push({ width, name, status: response.status(), ...result });
                if ([360, 1440, 1920].includes(width) && ['calendar', 'analytics', 'reservation', 'reservation-new', 'reservation-edit', 'parking-new', 'platforms', 'users'].includes(name)) {
                    await page.screenshot({ path: path.join(output, `${name}-${width}.png`), fullPage: true, animations: 'disabled' });
                }
            }
        }
        console.log(`Completed ${results.length} page and viewport checks.`);
        await page.goto('/dashboard');
        await page.locator('.pm-sidebar-toggle').click();
        await page.setViewportSize({ width: 390, height: 844 });
        await page.locator('.pm-mobile-menu-btn').click();
        await page.waitForFunction(() => getComputedStyle(document.querySelector('.pm-sidebar-link-label')).opacity === '1');
        const resizedMenu = await page.locator('#pm-sidebar').evaluate(el => ({ width: el.getBoundingClientRect().width, labelOpacity: getComputedStyle(el.querySelector('.pm-sidebar-link-label')).opacity }));
        await page.screenshot({ path: path.join(output, 'menu-after-resize.png'), animations: 'disabled' });
        await page.keyboard.press('Escape');
        const menuClosesOnEscape = !(await page.locator('#pm-sidebar').getAttribute('class')).includes('mobile-open');
        await page.setViewportSize({ width: 1440, height: 1000 });
        await page.goto('/platforms');
        await page.getByRole('button', { name: 'Importa un periodo', exact: true }).click();
        const dialogFocusInside = await page.locator('#historical-sync-modal').evaluate(el => el.contains(document.activeElement));
        await page.keyboard.press('Escape');
        const dialogClosesOnEscape = !await page.locator('#historical-sync-modal').isVisible();
        assert.equal(dialogClosesOnEscape, true);
        assert.equal(dialogFocusInside, true);
        assert.equal(await page.getByRole('button', { name: 'Importa un periodo', exact: true }).evaluate(el => el === document.activeElement), true);
        assert.equal(menuClosesOnEscape, true);
        assert.equal(resizedMenu.width, 260);
        assert.equal(resizedMenu.labelOpacity, '1');
        await exerciseCalendar(page, context, fixture, output);
        await exerciseMovement(page, context, fixture);
        const report = { results, errors, resizedMenu, menuClosesOnEscape, dialogFocusInside, dialogClosesOnEscape };
        fs.writeFileSync(path.join(output, selectedPages ? 'report-final.json' : 'report.json'), JSON.stringify(report, null, 2));
        assert.deepEqual(errors, []);
        assert.deepEqual(results.filter(r => r.contentWidth > r.width + 1 || r.status !== 200), []);
        console.log(JSON.stringify({ result: 'passed', interactions: ['sidebar resize and keyboard', 'modal focus and Escape', 'calendar retry, views and escaped text', 'failed and saved movements', 'switch in another tab'], failures: results.filter(r => r.contentWidth > r.width + 1 || r.status !== 200), errors, resizedMenu, menuClosesOnEscape, dialogFocusInside, dialogClosesOnEscape, checked: results.length, output }, null, 2));
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
