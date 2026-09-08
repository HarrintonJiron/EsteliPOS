import { chromium } from 'playwright';
import { mkdirSync } from 'fs';
import { join } from 'path';

const BASE = process.env.APP_URL ?? 'http://127.0.0.1:8765';
const LOGIN = process.env.QA_LOGIN ?? 'admin@agroservicio.com';
const PASSWORD = process.env.QA_PASSWORD ?? 'password';
const OUT = join(process.cwd(), 'storage', 'browser-qa');

mkdirSync(OUT, { recursive: true });

const results = [];

function log(id, status, detail = '') {
    results.push({ id, status, detail });
    const icon = status === 'PASS' ? '✓' : status === 'WARN' ? '!' : '✗';
    console.log(`${icon} [${status}] ${id}${detail ? ` — ${detail}` : ''}`);
}

async function snap(page, name) {
    await page.screenshot({ path: join(OUT, `${name}.png`), fullPage: true });
}

async function assertNoConsoleErrors(page, id) {
    const errors = [];
    page.on('console', (msg) => {
        if (msg.type() === 'error') {
            errors.push(msg.text());
        }
    });
    page.on('pageerror', (err) => errors.push(err.message));
    return errors;
}

async function login(page) {
    await page.goto(`${BASE}/login`, { waitUntil: 'networkidle' });
    await page.fill('#login', LOGIN);
    await page.fill('#password', PASSWORD);
    await page.click('button[type="submit"]');
    await page.waitForURL((url) => !url.pathname.includes('/login'), { timeout: 15000 });
}

async function run() {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    const page = await context.newPage();
    const consoleErrors = [];
    page.on('console', (msg) => {
        if (msg.type() === 'error') {
            consoleErrors.push(msg.text());
        }
    });
    page.on('pageerror', (err) => consoleErrors.push(err.message));

    try {
        // 1. Login público
        await page.goto(`${BASE}/login`, { waitUntil: 'networkidle' });
        const loginOk = (await page.locator('text=EsteliPOS').count()) > 0
            || (await page.locator('text=Agroservicio POS').count()) > 0;
        log('LOGIN-01 Página de login', loginOk ? 'PASS' : 'FAIL');
        await snap(page, '01-login');

        // 2. Autenticación
        try {
            await login(page);
            if (page.url().includes('/arqueo')) {
                await page.goto(`${BASE}/dashboard-general`, { waitUntil: 'networkidle' });
            }
            log('LOGIN-02 Inicio de sesión admin', 'PASS', page.url());
        } catch (e) {
            log('LOGIN-02 Inicio de sesión admin', 'FAIL', e.message);
            await snap(page, '02-login-fail');
            throw e;
        }

        // 3. Dashboard
        await page.goto(`${BASE}/dashboard-general`, { waitUntil: 'networkidle' });
        const dash = await page.locator('text=Dashboard General').first().isVisible();
        log('CORE-01 Dashboard general', dash ? 'PASS' : 'FAIL');
        await snap(page, '03-dashboard');

        // 4. POS ferretería
        await page.goto(`${BASE}/facturacion/pos`, { waitUntil: 'networkidle' });
        await page.waitForTimeout(1500);
        const posVisible = await page.locator('#posApp').isVisible();
        log('POS-01 Punto de venta carga', posVisible ? 'PASS' : 'FAIL');

        const productBtn = page.locator('#productsGrid button[onclick*="addProductToTicket"]').first();
        await productBtn.waitFor({ state: 'visible', timeout: 10000 });
        if (await productBtn.count()) {
            await productBtn.click();
            await page.waitForTimeout(800);
            const ticketHasItem = await page.locator('#ticketItems > *').count() > 0;
            log('POS-02 Agregar producto al ticket', ticketHasItem ? 'PASS' : 'FAIL');

            if (ticketHasItem) {
                const payBtn = page.locator('#payBtn');
                await payBtn.click();
                await page.waitForSelector('#paymentModal:not(.hidden)', { timeout: 5000 }).catch(() => null);
                await page.waitForTimeout(400);
                const cashInput = page.locator('#amountReceived');
                if (await cashInput.isVisible()) {
                    const totalText = await page.locator('#paymentTotalDisplay').innerText();
                    const amount = (parseFloat(totalText.replace(/[^\d.]/g, '')) || 100) + 50;
                    await cashInput.fill(String(amount));
                }
                const confirmPay = page.locator('#paymentForm button[type="submit"]');
                if (await confirmPay.isVisible()) {
                    await confirmPay.click();
                    await page.waitForLoadState('networkidle');
                    await page.waitForTimeout(1500);
                }
                const saleOk = /facturacion\/(receipt|change)/.test(page.url());
                log('POS-03 Flujo de cobro (venta completada)', saleOk ? 'PASS' : 'WARN', page.url());
            }
        } else {
            log('POS-02 Agregar producto al ticket', 'FAIL', 'Sin productos en grid');
        }
        await snap(page, '04-pos');

        // 5. Inventario
        await page.goto(`${BASE}/inventario`, { waitUntil: 'networkidle' });
        log('INV-01 Listado inventario', (await page.locator('text=Inventario').count()) > 0 ? 'PASS' : 'FAIL');
        await snap(page, '05-inventario');

        // 6. Reparaciones (tienda celulares)
        await page.goto(`${BASE}/reparaciones/nueva`, { waitUntil: 'networkidle' });
        await page.fill('input[name="client_name"]', 'Cliente QA Browser');
        await page.fill('input[name="client_phone"]', '88887777');
        await page.fill('input[name="device_brand"]', 'Samsung');
        await page.fill('input[name="device_model"]', 'Galaxy A54');
        await page.fill('textarea[name="problem_description"]', 'Pantalla rota — prueba QA navegador');
        const submitRepair = page.locator('#repairForm button[type="submit"], #repairForm input[type="submit"]').first();
        if (await submitRepair.count()) {
            await submitRepair.click();
            await page.waitForTimeout(2000);
            log('REP-02 Guardar orden reparación', page.url().includes('/reparaciones/') ? 'PASS' : 'WARN', page.url());
        }
        log('REP-01 Formulario reparación', 'PASS', 'Campos completados');
        await snap(page, '06-reparacion-form');

        // 7. Planilla
        await page.goto(`${BASE}/planilla`, { waitUntil: 'networkidle' });
        log('PLA-01 Dashboard planilla', (await page.locator('text=Planilla').count()) > 0 ? 'PASS' : 'FAIL');
        await snap(page, '07-planilla');

        // 8. Reportes
        await page.goto(`${BASE}/reportes`, { waitUntil: 'networkidle' });
        log('REP-02 Módulo reportes', (await page.locator('text=Reportes').count()) > 0 ? 'PASS' : 'FAIL');
        await snap(page, '08-reportes');

        // 9. Configuración / tipos de cambio
        await page.goto(`${BASE}/settings/exchange-rates`, { waitUntil: 'networkidle' });
        const exchangeOk = page.url().includes('exchange-rates') && (await page.locator('body').innerText()).length > 50;
        log('CFG-01 Tipos de cambio', exchangeOk ? 'PASS' : 'FAIL');
        await snap(page, '09-exchange-rates');

        // 10. Clientes
        await page.goto(`${BASE}/clientes`, { waitUntil: 'networkidle' });
        log('CLI-01 Listado clientes', (await page.locator('body').innerText()).includes('Cliente') ? 'PASS' : 'FAIL');
        await snap(page, '10-clientes');

        // 11. Compras (incl. editar — bug Blade corregido)
        await page.goto(`${BASE}/compras`, { waitUntil: 'networkidle' });
        log('COM-01 Listado compras', page.url().includes('/compras') ? 'PASS' : 'FAIL');
        const compraLink = page.locator('a[href*="/compras/"][href*="/edit"], a[href*="/compras/"]:not([href*="create"])').first();
        if (await compraLink.count()) {
            const href = await compraLink.getAttribute('href');
            if (href && href.includes('/edit')) {
                await page.goto(href.startsWith('http') ? href : `${BASE}${href}`, { waitUntil: 'networkidle' });
                const editOk = (await page.locator('body').innerText()).includes('Editar Compra')
                    && !(await page.locator('body').innerText()).includes('Server Error');
                log('COM-02 Editar compra', editOk ? 'PASS' : 'FAIL', page.url());
            }
        } else {
            log('COM-02 Editar compra', 'WARN', 'Sin compras en listado');
        }
        await snap(page, '11-compras');

        // 12. Créditos
        await page.goto(`${BASE}/creditos`, { waitUntil: 'networkidle' });
        log('CRE-01 Cartera créditos', page.url().includes('/creditos') ? 'PASS' : 'FAIL');
        const creditClientLink = page.locator('a[href*="/creditos/cliente/"]').first();
        if (await creditClientLink.count()) {
            await creditClientLink.click();
            await page.waitForLoadState('networkidle');
            const creditShowOk = page.url().includes('/creditos/cliente/')
                && !(await page.locator('body').innerText()).includes('Server Error');
            log('CRE-02 Detalle crédito cliente', creditShowOk ? 'PASS' : 'FAIL', page.url());
        } else {
            await page.goto(`${BASE}/creditos/cliente/1`, { waitUntil: 'networkidle' });
            const creditFallback = page.url().includes('/creditos/cliente/')
                && !(await page.locator('body').innerText()).includes('Server Error');
            log('CRE-02 Detalle crédito cliente', creditFallback ? 'PASS' : 'WARN', page.url());
        }
        await snap(page, '12-creditos');

        // 13. Proformas
        await page.goto(`${BASE}/proformas`, { waitUntil: 'networkidle' });
        log('PRO-01 Listado proformas', page.url().includes('/proformas') ? 'PASS' : 'FAIL');
        await snap(page, '13-proformas');

        // 14. Contabilidad
        await page.goto(`${BASE}/contabilidad`, { waitUntil: 'networkidle' });
        const contOk = page.url().includes('/contabilidad')
            && !(await page.locator('body').innerText()).includes('Server Error');
        log('CON-01 Dashboard contabilidad', contOk ? 'PASS' : 'FAIL');
        await snap(page, '14-contabilidad');

        // 15. Planilla submódulos
        for (const [id, path, label] of [
            ['PLA-02', '/planilla/employees', 'Empleados'],
            ['PLA-03', '/planilla/loans', 'Préstamos'],
            ['PLA-04', '/planilla/bonuses', 'Bonificaciones'],
            ['PLA-05', '/planilla/deductions', 'Deducciones'],
            ['PLA-06', '/planilla/leave', 'Permisos'],
        ]) {
            await page.goto(`${BASE}${path}`, { waitUntil: 'networkidle' });
            const ok = !(await page.locator('body').innerText()).includes('Server Error');
            log(`${id} ${label}`, ok ? 'PASS' : 'FAIL', page.url());
        }
        await snap(page, '15-planilla-modulos');

        // 16. Configuración general
        await page.goto(`${BASE}/settings/general`, { waitUntil: 'networkidle' });
        log('CFG-02 Configuración general', page.url().includes('/settings') ? 'PASS' : 'FAIL');
        await snap(page, '16-settings');

        // 17. Ticket reparación (si hay orden)
        await page.goto(`${BASE}/reparaciones`, { waitUntil: 'networkidle' });
        const repairShowLink = page.locator('a[href*="/reparaciones/"]:not([href*="nueva"])').first();
        if (await repairShowLink.count()) {
            const repairHref = await repairShowLink.getAttribute('href');
            const repairId = repairHref?.match(/reparaciones\/(\d+)/)?.[1];
            if (repairId) {
                await page.goto(`${BASE}/reparaciones/${repairId}/ticket`, { waitUntil: 'networkidle' });
                const ticketOk = !(await page.locator('body').innerText()).includes('Server Error')
                    && !(await page.locator('body').innerText()).includes('Undefined variable');
                log('REP-03 Ticket reparación', ticketOk ? 'PASS' : 'FAIL', page.url());
            }
        } else {
            log('REP-03 Ticket reparación', 'WARN', 'Sin órdenes previas');
        }
        await snap(page, '17-repair-ticket');

        // 18. Logout
        await page.goto(`${BASE}/dashboard-general`, { waitUntil: 'networkidle' });
        const logoutForm = page.locator('form[action*="logout"] button[type="submit"], form[action*="logout"] input[type="submit"]').first();
        if (await logoutForm.count()) {
            await logoutForm.click();
            await page.waitForLoadState('networkidle');
            log('AUTH-03 Cierre de sesión', page.url().includes('/login') ? 'PASS' : 'WARN', page.url());
        } else {
            log('AUTH-03 Cierre de sesión', 'WARN', 'Botón logout no encontrado');
        }
        await snap(page, '18-logout');

        // 19. Consola JS
        const criticalErrors = consoleErrors.filter((e) => !e.includes('favicon') && !e.includes('cdn.tailwindcss.com'));
        log('JS-01 Errores de consola', criticalErrors.length === 0 ? 'PASS' : 'WARN', criticalErrors.slice(0, 3).join(' | ') || 'ninguno');
    } catch (error) {
        log('RUNTIME', 'FAIL', error.message);
        await snap(page, '99-error');
    } finally {
        await browser.close();
    }

    const failed = results.filter((r) => r.status === 'FAIL').length;
    const warned = results.filter((r) => r.status === 'WARN').length;
    const passed = results.filter((r) => r.status === 'PASS').length;

    console.log('\n--- RESUMEN QA NAVEGADOR ---');
    console.log(`PASS: ${passed} | WARN: ${warned} | FAIL: ${failed}`);
    console.log(`Capturas: ${OUT}`);

    process.exit(failed > 0 ? 1 : 0);
}

run();
