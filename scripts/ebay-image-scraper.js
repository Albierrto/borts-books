// ebay-image-scraper.js
const puppeteer = require('puppeteer');
const fs = require('fs');
const csv = require('csv-parse/sync');
const { stringify } = require('csv-stringify/sync');

async function getEbayImages(itemId) {
    const url = `https://www.ebay.com/itm/${itemId}`;
    const browser = await puppeteer.launch({ headless: "new" });
    const page = await browser.newPage();
    await page.goto(url, { waitUntil: 'networkidle2', timeout: 30000 });

    // Wait for image gallery to load
    await page.waitForSelector('img', { timeout: 10000 }).catch(() => {});

    // Try to get all main images
    const images = await page.evaluate(() => {
        // eBay uses different selectors, so try a few
        const imgSet = new Set();
        // Main image
        document.querySelectorAll('img').forEach(img => {
            if (img.src && img.src.includes('ebayimg.com')) {
                imgSet.add(img.src);
            }
        });
        return Array.from(imgSet);
    });

    await browser.close();
    return images;
}

async function processCSV(inputFile, outputFile) {
    try {
        // Read and parse the input CSV
        const fileContent = fs.readFileSync(inputFile, 'utf-8');
        const records = csv.parse(fileContent, {
            columns: true,
            skip_empty_lines: true
        });

        // Check if "Item number" column exists
        if (!records[0] || !('Item number' in records[0])) {
            throw new Error('Item number column not found in CSV');
        }

        // Process each record
        const results = [];
        for (const record of records) {
            const itemId = record['Item number'];
            console.log(`Processing item ${itemId}...`);
            
            try {
                const images = await getEbayImages(itemId);
                results.push({
                    'Item number': itemId,
                    'Images': images.join('|')
                });
            } catch (err) {
                console.error(`Error processing item ${itemId}:`, err);
                results.push({
                    'Item number': itemId,
                    'Images': 'Error: ' + err.message
                });
            }
        }

        // Write results to output CSV
        const output = stringify(results, {
            header: true,
            columns: ['Item number', 'Images']
        });
        fs.writeFileSync(outputFile, output);
        console.log(`Results written to ${outputFile}`);

    } catch (err) {
        console.error('Error processing CSV:', err);
        process.exit(1);
    }
}

// Command line usage: node ebay-image-scraper.js input.csv output.csv
if (require.main === module) {
    const inputFile = process.argv[2];
    const outputFile = process.argv[3];
    
    if (!inputFile || !outputFile) {
        console.error('Usage: node ebay-image-scraper.js input.csv output.csv');
        process.exit(1);
    }

    processCSV(inputFile, outputFile).catch(err => {
        console.error('Error:', err);
        process.exit(1);
    });
}

module.exports = { getEbayImages, processCSV };