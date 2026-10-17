import assert from 'node:assert/strict';
import { spawn } from 'node:child_process';
import { createServer } from 'node:http';
import { mkdir, readFile, writeFile } from 'node:fs/promises';
import path from 'node:path';

// Static fixtures exported by isolated feature tests; this server never boots Laravel.
const root = process.cwd();
const frameworkMode = process.argv.includes('--framework');
const typographyBaseline = process.argv.includes('--typography-baseline');
const typographyRefined = process.argv.includes('--typography-refined');
const artifacts = path.join(root, 'storage/framework/testing', frameworkMode ? 'thrive-framework-ui' : 'home-hero-ui');
const html = await readFile(path.join(artifacts, frameworkMode ? 'framework.html' : 'hero.html'), 'utf8');
const variants = frameworkMode ? {} : JSON.parse(await readFile(path.join(artifacts, 'variants.json'), 'utf8'));
await mkdir(path.join(artifacts, 'chrome'), { recursive: true });
const server = createServer(async (request, response) => {
    try {
        const url = new URL(request.url, 'http://localhost');
        if (url.pathname === '/') {
            let body = frameworkMode ? html : html.replace(variants.portrait, url.searchParams.get('variant') === 'error'
                ? 'data:image/png;base64,invalid' : url.searchParams.get('variant') === 'slow'
                    ? '/slow.png' : variants[url.searchParams.get('variant')] ?? variants.portrait);
            if (url.searchParams.has('long')) {
                body = body.replace(/(<p class="front-managed-hero__description">)[\s\S]*?(<\/p>)/,
                    '$1' + 'A longer Admin description must expand both columns naturally. '.repeat(40) + '$2');
            }
            response.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
            response.end(body);
        } else if (url.pathname === '/slow.png') {
            setTimeout(() => {
                response.writeHead(200, { 'Content-Type': 'image/png' });
                response.end(Buffer.from(variants.portrait.split(',')[1], 'base64'));
            }, 800);
        } else if (url.pathname.startsWith('/build/')) {
            const buildRoot = path.join(root, 'public/build');
            const file = path.resolve(buildRoot, '.' + url.pathname.slice('/build'.length));
            assert.ok(file.startsWith(buildRoot + path.sep));
            const mime = { '.css': 'text/css', '.js': 'text/javascript', '.mjs': 'text/javascript', '.woff2': 'font/woff2' };
            response.writeHead(200, { 'Content-Type': mime[path.extname(file)] ?? 'application/octet-stream' });
            response.end(await readFile(file));
        } else {
            response.writeHead(404);
            response.end();
        }
    } catch (error) {
        response.writeHead(500);
        response.end(String(error));
    }
});
await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
const origin = `http://127.0.0.1:${server.address().port}`;
const chrome = spawn(process.env.CHROME_PATH ?? 'C:/Program Files/Google/Chrome/Application/chrome.exe', [
    '--headless=new', '--disable-gpu', '--disable-background-networking', '--no-first-run',
    '--no-default-browser-check', '--remote-debugging-port=0', `--user-data-dir=${path.join(artifacts, 'chrome')}`,
    'about:blank',
], { windowsHide: true, stdio: ['ignore', 'ignore', 'pipe'] });

class Cdp {
    pending = new Map();
    nextId = 0;
    exceptions = [];
    async connect(url) {
        this.socket = new WebSocket(url);
        await new Promise((resolve, reject) => {
            this.socket.addEventListener('open', resolve, { once: true });
            this.socket.addEventListener('error', reject, { once: true });
        });
        this.socket.addEventListener('message', event => {
            const data = JSON.parse(event.data);
            if (data.method === 'Runtime.exceptionThrown') this.exceptions.push(data.params.exceptionDetails);
            const pending = this.pending.get(data.id);
            if (pending) {
                this.pending.delete(data.id);
                if (data.error) pending.reject(new Error(JSON.stringify(data.error)));
                else pending.resolve(data.result);
            }
        });
    }
    send(method, params = {}) {
        return new Promise((resolve, reject) => {
            const id = ++this.nextId;
            this.pending.set(id, { resolve, reject });
            this.socket.send(JSON.stringify({ id, method, params }));
        });
    }
    async evaluate(expression) {
        const result = await this.send('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
        assert.ok(!result.exceptionDetails, JSON.stringify(result.exceptionDetails));
        return result.result.value;
    }
}

let cdp;
try {
    const endpoint = await new Promise((resolve, reject) => {
        let stderr = '';
        const timeout = setTimeout(() => reject(new Error('Chrome startup timed out')), 20000);
        chrome.once('error', reject);
        chrome.stderr.on('data', chunk => {
            stderr += chunk;
            const match = stderr.match(/DevTools listening on (ws:\/\/[^\s]+)/);
            if (match) { clearTimeout(timeout); resolve(match[1]); }
        });
        chrome.once('exit', code => reject(new Error(`Chrome exited ${code}: ${stderr}`)));
    });
    const debugOrigin = endpoint.replace('ws://', 'http://').split('/devtools/')[0];
    const pages = await (await fetch(`${debugOrigin}/json/list`)).json();
    cdp = new Cdp();
    await cdp.connect(pages.find(page => page.type === 'page').webSocketDebuggerUrl);
    await cdp.send('Page.enable');
    await cdp.send('Runtime.enable');
    await cdp.send('Network.enable');
    await cdp.send('Network.setBlockedURLs', { urls: ['*fonts.googleapis.com*', '*fonts.gstatic.com*'] });
    const waitFor = async expression => {
        for (let attempt = 0; attempt < 150; attempt++) {
            if (await cdp.evaluate(expression)) return;
            await new Promise(resolve => setTimeout(resolve, 40));
        }
        throw new Error(`Browser condition timed out: ${expression}`);
    };
    if (frameworkMode) {
        const results = [];
        const beforeTypography = typographyRefined
            ? JSON.parse(await readFile(path.join(artifacts, 'typography-before.json'), 'utf8')) : null;
        let checks = 0;
        const measureFramework = () => cdp.evaluate(`(() => {
            const rect = element => { const r = element.getBoundingClientRect(); return {x:r.x,y:r.y,width:r.width,height:r.height,bottom:r.bottom}; };
            const section = document.querySelector('.front-thrive-framework');
            const grid = section.querySelector('.front-thrive-framework__grid');
            const fontSize = selector => [...section.querySelectorAll(selector)].map(element => parseFloat(getComputedStyle(element).fontSize));
            return {
                width:innerWidth, overflow:document.documentElement.scrollWidth > innerWidth,
                background:getComputedStyle(section).backgroundColor,
                columns:grid ? getComputedStyle(grid).gridTemplateColumns.split(' ').length : 0,
                cards:[...section.querySelectorAll('.front-thrive-framework__card')].map(rect),
                headingFont:getComputedStyle(section.querySelector('h2')).fontFamily,
                cardBackground:grid ? getComputedStyle(grid.firstElementChild).backgroundColor : null,
                container:rect(section.querySelector('.front-thrive-framework__container')),
                header:rect(section.querySelector('.front-thrive-framework__header')),
                descriptions:section.querySelectorAll('.front-thrive-framework__description').length,
                fonts:{eyebrow:fontSize('.front-thrive-framework__eyebrow'), heading:fontSize('h2'),
                    descriptions:fontSize('.front-thrive-framework__description'), letter:fontSize('.front-thrive-framework__letter'),
                    label:fontSize('.front-thrive-framework__stage-label'), titles:fontSize('.front-thrive-framework__title'),
                    stageDescriptions:fontSize('.front-thrive-framework__stage-description')}
            };
        })()`);
        const verifyFramework = (m, count, desktopColumns = 3) => {
            assert.equal(m.overflow, false, `framework overflow at ${m.width}`);
            assert.equal(m.background, 'rgb(242, 237, 220)');
            assert.equal(m.cards.length, count);
            if (count) {
                assert.equal(m.cardBackground, 'rgb(23, 60, 58)');
                assert.equal(m.columns, m.width >= 1200 ? desktopColumns : m.width >= 768 ? 2 : 1);
                for (const card of m.cards) {
                    assert.ok(Math.abs(card.width - m.cards[0].width) < 1, 'equal card widths including incomplete rows');
                    for (const sibling of m.cards.filter(item => Math.abs(item.y - card.y) < 1)) {
                        assert.ok(Math.abs(card.height - sibling.height) < 1, 'equal card height within row');
                    }
                }
            }
            assert.match(m.headingFont, /Georgia/);
            assert.equal(new Set(m.fonts.titles).size, 1, 'consistent stage title sizes');
            assert.equal(new Set(m.fonts.stageDescriptions).size, 1, 'consistent stage description sizes');
            if (typographyRefined && m.width >= 1200) {
                if (m.fonts.descriptions.length === 2) {
                    assert.ok(m.fonts.descriptions[0] >= 17 && m.fonts.descriptions[0] <= 18, 'description one desktop size');
                    assert.ok(m.fonts.descriptions[1] >= 16 && m.fonts.descriptions[1] <= 17, 'description two desktop size');
                }
                for (const [field, min, max] of [['eyebrow',12,13],['heading',48,52],['letter',48,52],['label',12,13],['titles',24,26],['stageDescriptions',15,16]]) {
                    for (const size of m.fonts[field]) assert.ok(size >= min && size <= max, `${field} size ${size} at ${m.width}`);
                }
            }
        };
        const navigateFramework = async width => {
            await cdp.send('Emulation.setDeviceMetricsOverride', {width, height:900, deviceScaleFactor:1, mobile:false});
            await cdp.send('Page.navigate', {url:origin});
            await waitFor(`document.readyState === 'complete' && !!document.querySelector('.front-thrive-framework__grid')`);
            await cdp.evaluate('document.fonts.ready');
            return measureFramework();
        };
        for (const width of [1440, 1200, 1024, 768, 390, 320]) {
            const baseline = await navigateFramework(width);
            verifyFramework(baseline, 6);
            const capture = await cdp.send('Page.captureScreenshot', {format:'png', captureBeyondViewport:true});
            await writeFile(path.join(artifacts, `framework-${width}${typographyBaseline ? '-before' : ''}.png`), Buffer.from(capture.data, 'base64'));
            results.push({scenario:'reference', ...baseline});
            checks++;
            for (const count of [1, 2, 3, 4, 8]) {
                await navigateFramework(width);
                await cdp.evaluate(`(() => {
                    const grid = document.querySelector('.front-thrive-framework__grid');
                    while (grid.children.length > ${count}) grid.lastElementChild.remove();
                    while (grid.children.length < ${count}) grid.appendChild(grid.firstElementChild.cloneNode(true));
                    grid.firstElementChild.querySelector('.front-thrive-framework__stage-description').textContent = 'Longer saved stage descriptions naturally grow the complete grid row. '.repeat(12);
                })()`);
                const m = await measureFramework();
                verifyFramework(m, count);
                results.push({scenario:'dynamic-count-long-description', count, ...m});
                checks++;
            }
            await navigateFramework(width);
            await cdp.evaluate(`document.querySelector('.front-thrive-framework__description').remove()`);
            const oneDescription = await measureFramework();
            assert.equal(oneDescription.descriptions, 1);
            assert.ok(oneDescription.header.height < baseline.header.height);
            await cdp.evaluate(`document.querySelector('.front-thrive-framework__description').remove()`);
            const noDescriptions = await measureFramework();
            assert.equal(noDescriptions.descriptions, 0);
            assert.ok(noDescriptions.header.height < oneDescription.header.height);
            await cdp.evaluate(`document.querySelector('.front-thrive-framework__grid').remove()`);
            const noGrid = await measureFramework();
            assert.equal(noGrid.columns, 0);
            assert.ok(Math.abs(noGrid.container.height - noGrid.header.height) < 1, 'no reserved grid spacing');
            checks++;
        }
        for (const desktopColumns of [3, 4]) {
            for (const width of [1440, 1200, 1199, 1024, 768, 767, 390, 320]) {
                await navigateFramework(width);
                await cdp.evaluate(`document.querySelector('.front-thrive-framework__grid').style.setProperty('--framework-columns', '${desktopColumns}')`);
                const m = await measureFramework();
                verifyFramework(m, 6, desktopColumns);
                if (width >= 1200) {
                    const rowCounts = [];
                    for (const card of m.cards) {
                        const row = rowCounts.find(row => Math.abs(row.y - card.y) < 1);
                        if (row) row.count++; else rowCounts.push({y:card.y, count:1});
                    }
                    assert.deepEqual(rowCounts.map(row => row.count), desktopColumns === 3 ? [3, 3] : [4, 2]);
                    if (beforeTypography) {
                        const before = beforeTypography.results.find(item => item.scenario === 'saved-desktop-columns' && item.width === width && item.desktopColumns === desktopColumns);
                        assert.ok(m.cards[0].width > before.cards[0].width, 'cards wider than before at desktop');
                    }
                    if (desktopColumns === 4) {
                        const threeColumns = results.find(item => item.scenario === 'saved-desktop-columns' && item.width === width && item.desktopColumns === 3);
                        assert.ok(m.cards[0].width < threeColumns.cards[0].width, 'four-column cards narrower than three-column cards');
                    }
                    if (width === 1440) {
                        const capture = await cdp.send('Page.captureScreenshot', {format:'png', captureBeyondViewport:true});
                        await writeFile(path.join(artifacts, `framework-desktop-${desktopColumns}-columns${typographyBaseline ? '-before' : ''}.png`), Buffer.from(capture.data, 'base64'));
                    }
                }
                await cdp.evaluate(`document.querySelector('.front-thrive-framework__stage-description').textContent = 'A long Admin description must not overflow the grid. '.repeat(20)`);
                verifyFramework(await measureFramework(), 6, desktopColumns);
                results.push({scenario:'saved-desktop-columns', desktopColumns, ...m});
                checks++;
            }
        }
        await navigateFramework(320);
        await cdp.evaluate(`document.querySelector('.front-thrive-framework__heading').textContent = 'A'.repeat(240); document.querySelector('.front-thrive-framework__title').textContent = 'B'.repeat(240)`);
        verifyFramework(await measureFramework(), 6);
        checks++;
        assert.deepEqual(cdp.exceptions, []);
        await writeFile(path.join(artifacts, typographyBaseline ? 'typography-before.json' : 'browser-results.json'), JSON.stringify({checks, results}, null, 2));
        console.log(`PASS: ${checks} framework browser scenarios; six viewport screenshots; no JavaScript exceptions.`);
    } else {
    const metrics = () => cdp.evaluate(`(() => {
        const rect = selector => { const element = document.querySelector(selector); if (!element) return null; const r = element.getBoundingClientRect(); return { x:r.x,y:r.y,width:r.width,height:r.height,bottom:r.bottom }; };
        const frame = document.querySelector('[data-home-hero-frame]');
        return { content:rect('.front-managed-hero__content'), media:rect('.front-managed-hero__media'), frame:rect('[data-home-hero-frame]'),
            image:rect('[data-home-hero-image]'), eyebrow:rect('.front-managed-hero__eyebrow'), actions:rect('.front-managed-hero__actions'), fit:frame?.dataset.imageFit, overflow:document.documentElement.scrollWidth > innerWidth,
            background:getComputedStyle(document.querySelector('.front-managed-hero')).backgroundColor,
            skeleton:frame?.classList.contains('is-image-loading'), error:frame?.classList.contains('is-image-error') };
    })()`);
    let checks = 0;
    const results = [];
    const navigate = async (width, variant = 'portrait', extra = '') => {
        await cdp.send('Emulation.setDeviceMetricsOverride', { width, height: 900, deviceScaleFactor: 1, mobile: false });
        await cdp.send('Page.navigate', { url: `${origin}/?variant=${variant}${extra}` });
        await waitFor(`document.readyState === 'complete' && !!document.querySelector('[data-home-hero-frame]') && (document.querySelector('[data-home-hero-frame]').dataset.imageFit || document.querySelector('[data-home-hero-frame]').classList.contains('is-image-error'))`);
        await cdp.evaluate('new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)))');
        return metrics();
    };
    for (const width of [1440, 1024, 768, 390, 320]) {
        const m = await navigate(width);
        assert.equal(m.overflow, false, `horizontal overflow at ${width}`);
        assert.equal(m.background, 'rgb(242, 237, 220)');
        assert.equal(m.fit, 'cover');
        assert.equal(m.skeleton, false);
        if (width >= 768) {
            assert.ok(Math.abs(m.content.y - m.media.y) < 1, `top alignment ${width}`);
            assert.ok(Math.abs(m.content.bottom - m.media.bottom) < 1, `bottom alignment ${width}`);
            assert.ok(Math.abs(m.frame.height - m.content.height) < 1, `frame height ${width}`);
            assert.ok(Math.abs(m.content.width - m.media.width) < 1, `column width ${width}`);
            assert.ok(Math.abs(m.eyebrow.y - m.content.y) < 1, `visible text top ${width}`);
            assert.ok(Math.abs(m.actions.bottom - m.content.bottom) < 1, `button group bottom ${width}`);
        } else {
            assert.ok(m.media.y > m.content.bottom, `stacked order ${width}`);
            assert.ok(Math.abs(m.media.width / m.media.height - 0.8) < 0.01);
        }
        const capture = await cdp.send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: true });
        await writeFile(path.join(artifacts, `hero-${width}.png`), Buffer.from(capture.data, 'base64'));
        results.push({ width, ...m });
        checks++;
    }
    for (const variant of ['landscape', 'opaque-png', 'icon', 'small', 'transparent']) {
        const m = await navigate(1440, variant);
        assert.equal(m.fit, ['icon', 'small', 'transparent'].includes(variant) ? 'contain' : 'cover');
        assert.ok(Math.abs(m.frame.height - m.content.height) < 1);
        assert.equal(m.overflow, false);
        if (variant === 'icon') {
            assert.equal(m.image.width, 48);
            assert.equal(m.image.height, 48);
            assert.ok(Math.abs(m.image.x + 24 - m.frame.x - m.frame.width / 2) < 1);
            assert.ok(Math.abs(m.image.y + 24 - m.frame.y - m.frame.height / 2) < 1);
        }
        results.push({ variant, ...m });
        checks++;
    }
    for (const width of [1440, 1024, 768, 390, 320]) {
        for (const scenario of ['short-short', 'long-short', 'short-long', 'long-long', 'no-description', 'one-button', 'no-buttons', 'no-image']) {
            await navigate(width);
            await cdp.evaluate(`(() => {
                const scenario = ${JSON.stringify(scenario)};
                document.querySelector('.front-managed-hero__heading').textContent = scenario.startsWith('long-')
                    ? 'Understanding your emotions and building lasting emotional resilience. '.repeat(5) : 'Move forward';
                document.querySelector('.front-managed-hero__description').textContent = scenario.endsWith('-long')
                    ? 'Dynamic Admin descriptions expand the shared row naturally without clipping. '.repeat(24) : 'A short saved description.';
                if (scenario === 'no-description') document.querySelector('.front-managed-hero__description').remove();
                if (scenario === 'one-button') document.querySelector('.front-managed-hero__button--secondary').remove();
                if (scenario === 'no-buttons') document.querySelector('.front-managed-hero__actions').remove();
                if (scenario === 'no-image') {
                    document.querySelector('.front-managed-hero__media').remove();
                    document.querySelector('.front-managed-hero__grid').classList.add('front-managed-hero__grid--text-only');
                }
            })()`);
            await cdp.evaluate('new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)))');
            const m = await metrics();
            assert.equal(m.overflow, false, `${scenario} overflow at ${width}`);
            assert.equal(await cdp.evaluate('document.querySelectorAll(".front-managed-hero").length'), 1);
            if (scenario === 'no-image') {
                assert.equal(m.media, null);
                assert.equal(m.frame, null);
            } else if (width >= 768) {
                assert.ok(Math.abs(m.content.y - m.frame.y) < 1, `${scenario} top at ${width}`);
                assert.ok(Math.abs(m.content.bottom - m.frame.bottom) < 1, `${scenario} bottom at ${width}`);
                assert.ok(Math.abs(m.eyebrow.y - m.frame.y) < 1, `${scenario} visible text top at ${width}`);
                if (m.actions) assert.ok(Math.abs(m.actions.bottom - m.frame.bottom) < 1, `${scenario} buttons bottom at ${width}`);
            } else {
                assert.ok(m.media.y > m.content.bottom, `${scenario} mobile stack at ${width}`);
            }
            if (scenario === 'long-long' && width === 1440) {
                const capture = await cdp.send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: true });
                await writeFile(path.join(artifacts, 'hero-long-content.png'), Buffer.from(capture.data, 'base64'));
            }
            results.push({ width, scenario, ...m });
            checks++;
        }
    }
    const long = await navigate(1440, 'portrait', '&long=1');
    assert.ok(long.content.height > results[0].content.height);
    assert.ok(Math.abs(long.content.height - long.frame.height) < 1);
    assert.equal(long.overflow, false);
    checks++;
    const mobileIcon = await navigate(320, 'icon');
    assert.equal(mobileIcon.image.width, 48);
    assert.equal(mobileIcon.image.height, 48);
    assert.ok(Math.abs(mobileIcon.image.x + 24 - mobileIcon.frame.x - mobileIcon.frame.width / 2) < 1);
    assert.ok(Math.abs(mobileIcon.image.y + 24 - mobileIcon.frame.y - mobileIcon.frame.height / 2) < 1);
    assert.equal(mobileIcon.overflow, false);
    assert.notEqual(await cdp.evaluate('getComputedStyle(document.querySelector(".front-managed-hero__button i"), "::before").content'), 'none');
    await cdp.send('Emulation.setFocusEmulationEnabled', { enabled: true });
    await cdp.evaluate('document.body.tabIndex = -1; document.body.focus()');
    await cdp.send('Input.dispatchKeyEvent', { type: 'keyDown', key: 'Tab', code: 'Tab', windowsVirtualKeyCode: 9 });
    await cdp.send('Input.dispatchKeyEvent', { type: 'keyUp', key: 'Tab', code: 'Tab', windowsVirtualKeyCode: 9 });
    assert.ok(await cdp.evaluate('document.querySelector(".front-managed-hero__button").matches(":focus-visible")'));
    checks++;
    const mobileLong = await navigate(320, 'portrait', '&long=1');
    assert.equal(mobileLong.overflow, false);
    assert.ok(mobileLong.media.y > mobileLong.content.bottom);
    checks++;
    const failed = await navigate(390, 'error');
    assert.ok(failed.error);
    assert.ok(await cdp.evaluate('document.querySelector("[data-home-hero-image]").hidden'));
    assert.equal(failed.overflow, false);
    checks++;
    await cdp.send('Emulation.setEmulatedMedia', { features: [{ name: 'prefers-reduced-motion', value: 'reduce' }] });
    await cdp.send('Page.navigate', { url: `${origin}/?variant=slow` });
    await waitFor('document.querySelector("[data-home-hero-frame]")?.classList.contains("is-image-loading")');
    const loading = await metrics();
    assert.ok(loading.skeleton);
    assert.equal(await cdp.evaluate('getComputedStyle(document.querySelector("[data-home-hero-frame]"), "::before").animationName'), 'none');
    await waitFor('document.querySelector("[data-home-hero-frame]")?.dataset.imageFit === "cover"');
    const loaded = await metrics();
    assert.ok(Math.abs(loading.frame.height - loaded.frame.height) < 1);
    assert.ok(Math.abs(loading.frame.width - loaded.frame.width) < 1);
    checks++;
    assert.deepEqual(cdp.exceptions, [], 'JavaScript runtime exceptions');
    await writeFile(path.join(artifacts, 'browser-results.json'), JSON.stringify({ checks, results }, null, 2));
    console.log(`PASS: ${checks} browser scenarios; five viewport screenshots; no JavaScript exceptions.`);
    }
} finally {
    cdp?.socket?.close();
    chrome.kill();
    server.closeAllConnections();
    server.close();
}
