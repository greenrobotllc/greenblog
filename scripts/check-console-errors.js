const puppeteer = require('puppeteer');

const urls = process.argv.slice(2);

if (urls.length === 0) {
  console.error('Usage: node check-console-errors.js <url1> [url2] [url3] ...');
  process.exit(1);
}

(async () => {
  const launchOptions = {
    headless: 'new',
    args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage']
  };
  if (process.env.PUPPETEER_EXECUTABLE_PATH) {
    launchOptions.executablePath = process.env.PUPPETEER_EXECUTABLE_PATH;
  }
  const browser = await puppeteer.launch(launchOptions);

  let totalErrors = 0;

  for (const url of urls) {
    const page = await browser.newPage();
    const errors = [];

    page.on('console', (msg) => {
      if (msg.type() === 'error') {
        errors.push(msg.text());
      }
    });

    page.on('pageerror', (err) => {
      errors.push(err.message);
    });

    page.on('requestfailed', (request) => {
      errors.push(`Request failed: ${request.url()} - ${request.failure().errorText}`);
    });

    try {
      await page.goto(url, { waitUntil: 'networkidle0', timeout: 30000 });
    } catch (err) {
      console.error(`${url} - FAILED TO LOAD: ${err.message}`);
      totalErrors++;
      await page.close();
      continue;
    }

    await page.close();

    if (errors.length > 0) {
      console.error(`${url} - ${errors.length} error(s):`);
      errors.forEach((error, i) => {
        console.error(`  ${i + 1}. ${error}`);
      });
      totalErrors += errors.length;
    } else {
      console.log(`${url} - no errors`);
    }
  }

  await browser.close();

  if (totalErrors > 0) {
    console.error(`\nTotal: ${totalErrors} console error(s) across ${urls.length} URL(s)`);
    process.exit(1);
  }

  console.log(`\nAll ${urls.length} URL(s) passed with no console errors.`);
  process.exit(0);
})();
