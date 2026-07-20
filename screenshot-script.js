const { chromium, firefox, webkit } = require('playwright');
const path = require('path');
const fs = require('fs');

async function takeScreenshots() {
    // Create screenshots directory if it doesn't exist
    const screenshotsDir = path.join(__dirname, 'screenshots');
    if (!fs.existsSync(screenshotsDir)) {
        fs.mkdirSync(screenshotsDir);
    }

    const browser = await chromium.launch();
    const context = await browser.newContext();
    const page = await context.newPage();

    // Navigate to the showcase page
    await page.goto('file://' + path.join(__dirname, 'showcase.html'));

    // Wait for fonts to load
    await page.waitForTimeout(2000);

    // Take full page screenshot
    await page.screenshot({
        path: path.join(screenshotsDir, 'full-page-desktop.png'),
        fullPage: true
    });

    // Take screenshot at tablet width
    await page.setViewportSize({ width: 768, height: 1024 });
    await page.waitForTimeout(1000);
    await page.screenshot({
        path: path.join(screenshotsDir, 'full-page-tablet.png'),
        fullPage: true
    });

    // Take screenshot at mobile width
    await page.setViewportSize({ width: 375, height: 667 });
    await page.waitForTimeout(1000);
    await page.screenshot({
        path: path.join(screenshotsDir, 'full-page-mobile.png'),
        fullPage: true
    });

    // Take screenshots of specific sections
    const sections = ['hero', 'trust', 'services', 'how', 'impact', 'cta'];
    for (const section of sections) {
        // Desktop
        await page.setViewportSize({ width: 1200, height: 800 });
        await page.waitForTimeout(1000);
        const element = await page.$(`.section:has-text("${section.charAt(0).toUpperCase() + section.slice(1)}")`);
        if (element) {
            await element.screenshot({
                path: path.join(screenshotsDir, `section-${section}-desktop.png`)
            });
        }

        // Mobile
        await page.setViewportSize({ width: 375, height: 667 });
        await page.waitForTimeout(1000);
        if (element) {
            await element.screenshot({
                path: path.join(screenshotsDir, `section-${section}-mobile.png`)
            });
        }
    }

    // Take screenshot of components showcase
    await page.setViewportSize({ width: 1200, height: 800 });
    await page.waitForTimeout(1000);
    const componentsSection = await page.$('.section:has-text("Component Showcase")');
    if (componentsSection) {
        await componentsSection.screenshot({
            path: path.join(screenshotsDir, 'components-showcase.png')
        });
    }

    await browser.close();
    console.log('Screenshots saved to:', screenshotsDir);
}

takeScreenshots().catch(console.error);