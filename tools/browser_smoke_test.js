// Headless browser smoke test for Malisafi plugin
// Usage:
// 1. Install puppeteer: npm install puppeteer --save-dev
// 2. Run: node tools/browser_smoke_test.js --url "http://localhost/wordpress/your-test-page/"

const puppeteer = require('puppeteer');
const argv = require('minimist')(process.argv.slice(2));

if (!argv.url) {
    console.error('Usage: node tools/browser_smoke_test.js --url "http://localhost/..."');
    process.exit(2);
}

(async () => {
    const browser = await puppeteer.launch({ headless: true });
    const page = await browser.newPage();

    const errors = [];
    const warns = [];
    const logs = [];

    page.on('console', msg => {
        const type = msg.type();
        const text = msg.text();
        if (type === 'error') errors.push(text);
        else if (type === 'warning') warns.push(text);
        else logs.push(text);
    });

    page.on('pageerror', err => {
        errors.push('Page error: ' + err.message);
    });

    console.log('Opening', argv.url);
    try {
        await page.goto(argv.url, { waitUntil: 'networkidle2', timeout: 60000 });
    } catch (e) {
        console.error('Failed to load page:', e.message);
        await browser.close();
        process.exit(3);
    }

    // Wait a short while for async scripts to run
    await page.waitForTimeout(3000);

    console.log('Collected console messages:');
    if (errors.length) {
        console.log('Errors:');
        errors.forEach(e => console.log('  -', e));
    } else {
        console.log('  No errors');
    }
    if (warns.length) {
        console.log('Warnings:');
        warns.forEach(w => console.log('  -', w));
    }

    await browser.close();

    if (errors.length) process.exit(1);
    process.exit(0);
})();
