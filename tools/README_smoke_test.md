Browser smoke test

Quick instructions to run the headless smoke test which opens a page and captures console errors.

1. Install dev deps (run in plugin root):

```bash
npm init -y
npm install puppeteer minimist --save-dev
```

2. Run the test against a local page (adjust URL):

```bash
node tools/browser_smoke_test.js --url "http://localhost/wordpress/your-test-page/"
```

Exit codes:
- 0 : no console errors
- 1 : console errors found
- 2 : usage error (missing --url)
- 3 : failed to load page

Notes:
- This runs headless Chromium. Ensure your local WP site is reachable from the machine running the test.
- The script reports console warnings and errors; it does not attempt to fix them.
