#!/usr/bin/env node
/**
 * Make Wayfinder-generated routes use relative paths instead of absolute paths.
 *
 * This is needed when the app is deployed as a subdirectory (e.g. /ekp/) on a domain
 * that also hosts another application at the root. Absolute URLs like "/login" resolve
 * to the domain root instead of the subdirectory, causing login/register to fail.
 *
 * By stripping the leading "/" we get relative URLs like "login" which the browser
 * correctly resolves against the current page URL — whether that's localhost or /ekp/.
 */

import { readdirSync, readFileSync, writeFileSync, statSync } from 'fs';
import { join, relative } from 'path';
import { fileURLToPath } from 'url';

const __dirname = fileURLToPath(new URL('.', import.meta.url));
const routesDir = join(__dirname, '..', 'resources', 'js', 'routes');

let modifiedCount = 0;

function processFile(filePath) {
    const content = readFileSync(filePath, 'utf-8');
    // Replace url: '/something' → url: 'something' (strip leading slash)
    const newContent = content.replace(/url:\s*'\/([^']*)'/g, (_match, path) => {
        return `url: '${path}'`;
    });
    if (newContent !== content) {
        writeFileSync(filePath, newContent, 'utf-8');
        modifiedCount++;
        console.log(`  ${relative(join(__dirname, '..'), filePath)}`);
    }
}

function walk(dir) {
    for (const entry of readdirSync(dir, { recursive: true })) {
        const fullPath = join(dir, entry);
        if (statSync(fullPath).isDirectory()) continue;
        if (!fullPath.endsWith('.ts')) continue;
        processFile(fullPath);
    }
}

console.log('Making route URLs relative...');
walk(routesDir);
console.log(`Done. Modified ${modifiedCount} definition(s).`);
