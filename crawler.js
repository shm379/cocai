import { launch } from 'puppeteer';
import axios from 'axios';
import { createWriteStream, existsSync, mkdirSync } from 'fs';
import { basename, join, extname, dirname } from 'path';
import { fileURLToPath } from 'url';
import { Map } from './models/Map.js'; // مدل نقشه

// شبیه‌سازی __dirname
const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

// URL پایه
const baseUrl = 'https://www.clasher.us';
const imageFolder = join(__dirname, 'maps');

// بررسی و ایجاد پوشه ذخیره تصاویر
if (!existsSync(imageFolder)) {
    mkdirSync(imageFolder, { recursive: true });
}

// تابع دانلود و ذخیره تصویر
async function downloadAndSaveImage(imageUrl, townHall) {
    try {
        const imageName = basename(imageUrl).split('?')[0]; // حذف پارامترهای URL در صورت وجود
        const townHallFolder = join(imageFolder, `TH${townHall}`); // نام پوشه به صورت 'TH17'

        // بررسی و ایجاد پوشه مربوط به townHall
        if (!existsSync(townHallFolder)) {
            mkdirSync(townHallFolder, { recursive: true });
        }

        const imagePath = join(townHallFolder, imageName);

        // بررسی وجود فایل قبلی برای جلوگیری از دانلود مجدد
        if (existsSync(imagePath)) {
            console.log(`Image already exists: ${imagePath}`);
            return imagePath;
        }

        const writer = createWriteStream(imagePath);
        const response = await axios({
            url: imageUrl,
            method: 'GET',
            responseType: 'stream',
        });

        response.data.pipe(writer);

        return new Promise((resolve, reject) => {
            writer.on('finish', () => resolve(imagePath));
            writer.on('error', reject);
        });
    } catch (error) {
        console.error(`Failed to download image: ${imageUrl}`, error);
        return null;
    }
}


// تابع استخراج جزئیات نقشه
async function crawlMapDetails(browser, mapUrl, title) {
    const page = await browser.newPage();
    try {
        // تنظیم user-agent واقعی
        await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36');

        console.log(`Navigating to map page: ${mapUrl}`);
        await page.goto(mapUrl, { waitUntil: 'networkidle0', timeout: 120000 }); // افزایش timeout به 120 ثانیه

        // استخراج townHall از عنوان صفحه
        let townHall = null;
        try {
            townHall = await page.$eval('div.app-title h1', (el) => {
                const text = el.textContent;
                const match = text.match(/TH\s*(\d+)/i);
                return match ? parseInt(match[1], 10) : null; // استخراج عدد به صورت عددی
            });
            if (townHall !== null) {
                console.log(`Extracted TownHall: ${townHall}`);
            } else {
                console.warn(`Could not extract TownHall from page: ${mapUrl}`);
            }
        } catch (e) {
            console.warn(`Could not extract TownHall from page: ${mapUrl}`);
        }

        // استخراج URL تصویر از صفحه نقشه
        let imageUrl = '';
        try {
            imageUrl = await page.$eval('img.map-pic', (img) => img.src);
            console.log(`Image URL: ${imageUrl}`);
        } catch (e) {
            console.warn(`Could not extract image URL from page: ${mapUrl}`);
            return;
        }

        // بررسی اگر تصویر هنوز یک داده‌ی Base64 است
        if (imageUrl.startsWith('data:image/')) {
            console.warn(`Received a placeholder image on ${mapUrl}`);
            return;
        }

        // ساختن آدرس coc-copy-base
        const cocCopyBaseUrl = mapUrl.replace('/design/', '/coc-copy-base/');
        console.log(`Navigating to coc-copy-base URL: ${cocCopyBaseUrl}`);
        await page.goto(cocCopyBaseUrl, { waitUntil: 'networkidle0', timeout: 120000 }); // افزایش timeout به 120 ثانیه

        // منتظر ریدایرکت به لینک نهایی (حداکثر ۳۰ ثانیه)
        try {
            await page.waitForFunction(() => {
                return window.location.href.startsWith('https://link.clashofclans.com/en/?action=OpenLayout&id=');
            }, { timeout: 30000 }); // انتظار حداکثر ۳۰ ثانیه
        } catch {
            console.warn(`Redirect did not occur as expected on ${cocCopyBaseUrl}`);
            return;
        }

        // استخراج لینک نهایی پس از ریدایرکت
        let finalLayoutLink = page.url();
        console.log(`Final Layout Link after redirect: ${finalLayoutLink}`);

        // بررسی اینکه لینک نهایی با الگوی مورد نظر شروع می‌شود
        if (!finalLayoutLink.startsWith('https://link.clashofclans.com/en/?action=OpenLayout&id=')) {
            console.warn(`Final layout link does not match expected pattern: ${finalLayoutLink}`);
            return;
        }

        // استخراج ID از لینک نهایی (اختیاری، در اینجا برای نمایش فقط)
        const idMatch = finalLayoutLink.match(/id=([A-Za-z0-9]+)/);
        const extractedId = idMatch ? idMatch[1] : null;

        if (finalLayoutLink && extractedId && townHall !== null) {
            console.log(`Extracted ID: ${extractedId}`);

            // دانلود و ذخیره تصویر
            const savedImagePath = await downloadAndSaveImage(imageUrl, townHall);

            if (savedImagePath) {
                // ذخیره اطلاعات در دیتابیس
                try {
                    await Map.create({
                        title: title, // استفاده از عنوان استخراج‌شده
                        image_path: savedImagePath,
                        map_link: finalLayoutLink,
                        town_hall: townHall, // مقدار عددی townHall
                    });
                    console.log(`Saved Map: ${title}, Link: ${finalLayoutLink}`);
                } catch (dbError) {
                    if (dbError.name === 'SequelizeUniqueConstraintError') {
                        console.warn(`Map with title "${title}" already exists (caught by DB). Skipping...`);
                    } else {
                        console.error(`Error inserting map into database: ${dbError}`);
                    }
                }
            }
        } else {
            console.warn(`Could not extract necessary information from map URL: ${mapUrl}`);
        }
    } catch (error) {
        console.error(`Error crawling map details: ${mapUrl}`, error);
    } finally {
        await page.close();
    }
}

// کراول صفحه اصلی و استخراج اطلاعات نقشه‌ها
async function crawlMaps() {
    const browser = await launch({
        headless: false, // حالت غیر headless برای مشاهده فرآیند
        defaultViewport: null,
        args: ['--start-maximized'],
    });
    const page = await browser.newPage();

    // تنظیم user-agent واقعی
    await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36');

    try {
        let nextPage = `${baseUrl}/clash-of-clans/layouts/home-village`;
        let hasNextPage = true;

        while (hasNextPage) {
            console.log(`Navigating to page: ${nextPage}`);
            await page.goto(nextPage, { waitUntil: 'networkidle0', timeout: 120000 }); // افزایش timeout به 120 ثانیه
            await page.waitForSelector('div.col-auto.post-media', { timeout: 120000 });

            // استخراج لینک‌های نقشه‌ها
            const mapLinks = await page.$$eval('div.col-auto.post-media a', (links) =>
                links.map((link) => link.href).filter((href) => href.includes('/design/'))
            );

            console.log(`Found ${mapLinks.length} maps on ${nextPage}`);

            for (const mapUrl of mapLinks) {
                if (mapUrl) {
                    // استخراج عنوان از URL
                    const titleMatch = mapUrl.match(/\/([^/]+)$/);
                    const title = titleMatch ? titleMatch[1].replace(/-/g, ' ') : 'Unknown';
                    console.log(`Processing map with title: ${title}`);

                    // بررسی وجود نقشه در دیتابیس بر اساس عنوان
                    const existingMap = await Map.findOne({ where: { title: title } });
                    if (existingMap) {
                        console.log(`Map with title "${title}" already exists. Skipping...`);
                        continue; // ادامه به نقشه بعدی
                    }

                    // اگر نقشه در دیتابیس وجود ندارد، پردازش آن
                    await crawlMapDetails(browser, mapUrl, title);
                } else {
                    console.warn('Map URL is undefined');
                }
            }

            // پیدا کردن لینک صفحه بعد
            const nextButton = await page.$('ul.nav li.page-item a.page-link[href*="page="]:not(.disabled)');

            if (nextButton) {
                // استخراج href از دکمه صفحه بعد
                const href = await page.evaluate((btn) => btn.getAttribute('href'), nextButton);
                // ساختن URL کامل
                nextPage = new URL(href, baseUrl).href;
                console.log(`Navigating to next page: ${nextPage}`);
            } else {
                console.log('No more pages to navigate.');
                hasNextPage = false;
            }
        }
    } catch (error) {
        console.error('Error crawling maps:', error);
    } finally {
        await browser.close();
    }
}

// اجرای کراولر
crawlMaps().catch(console.error);
