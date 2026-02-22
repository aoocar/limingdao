const fs = require('fs');
const path = require('path');
const yaml = require('js-yaml');

const bookmarksDir = path.join(__dirname, 'content', 'bookmarks');

// Ensure the bookmarks directory exists
if (!fs.existsSync(bookmarksDir)) {
    console.log('Bookmarks directory not found, creating it...');
    fs.mkdirSync(bookmarksDir, { recursive: true });
}

// Utility to recursively find Markdown files
function getAllMarkdownFiles(dirPath, arrayOfFiles) {
    if (!fs.existsSync(dirPath)) return arrayOfFiles || [];
    const files = fs.readdirSync(dirPath, { withFileTypes: true });
    arrayOfFiles = arrayOfFiles || [];

    files.forEach(function (file) {
        if (file.isDirectory()) {
            arrayOfFiles = getAllMarkdownFiles(path.join(dirPath, file.name), arrayOfFiles);
        } else if (file.name.endsWith('.md')) {
            arrayOfFiles.push(path.join(dirPath, file.name));
        }
    });

    return arrayOfFiles;
}

const files = getAllMarkdownFiles(bookmarksDir);
const data = [];

// Frontmatter regex
const fmRegex = /^---\s*[\n\r]+([\s\S]*?)[\n\r]+---/;

files.forEach(file => {
    const content = fs.readFileSync(file, 'utf8');
    const match = fmRegex.exec(content);
    if (!match) return;

    try {
        const item = yaml.load(match[1]);
        if (!item.title || !item.url) return;

        const taxonomyName = item.taxonomy || '未分类';
        const termName = item.term || null;

        let tx = data.find(t => t.taxonomy === taxonomyName);
        if (!tx) {
            tx = { taxonomy: taxonomyName, icon: item.icon || 'fa-star' };
            data.push(tx);
        } else if (item.icon && tx.icon === 'fa-star') {
            // Update icon if the user specified a custom one
            tx.icon = item.icon;
        }

        const linkObj = {
            title: item.title,
            url: item.url,
            logo: item.logo || '',
            description: item.description || ''
        };
        if (item.qrcode) linkObj.qrcode = item.qrcode;

        if (termName) {
            if (!tx.list) tx.list = [];
            let tm = tx.list.find(t => t.term === termName);
            if (!tm) {
                tm = { term: termName, links: [] };
                tx.list.push(tm);
            }
            tm.links.push(linkObj);
        } else {
            if (!tx.links) tx.links = [];
            tx.links.push(linkObj);
        }
    } catch (e) {
        console.error(`Error parsing YAML in ${file}:`, e);
    }
});

// Since js-yaml doesn't preserve the '---' at the top, but we don't need it because webstack.yml doesn't technically need it.
// Wait, the original had '---' at the top. Let's prepend it just in case.
if (data.length > 0) {
    const outYaml = '---\n\n' + yaml.dump(data, { lineWidth: -1 });
    fs.writeFileSync(path.join(__dirname, 'data', 'webstack.yml'), outYaml, 'utf8');
    console.log(`Generated webstack.yml with ${data.length} taxonomies based on ${files.length} markdown files.`);
} else {
    // If no bookmarks, just write an empty taxonomy or use default to prevent Hugo crash.
    const emptyYaml = '---\n- taxonomy: 未分类\n  icon: fa-star\n  links:\n    - title: Example\n      url: https://example.com\n      description: Please add your first bookmark in Obsidian!\n';
    fs.writeFileSync(path.join(__dirname, 'data', 'webstack.yml'), emptyYaml, 'utf8');
    console.log('No valid bookmarks found. Generated a default webstack.yml template.');
}

// VERY IMPORTANT: Hide the bookmarks directory so Hugo doesn't try to parse them as pages!
// This caused the Vercel build error: "URLs with protocol (http*) not supported"
const hiddenBookmarksDir = path.join(__dirname, 'content', '.bookmarks_archive');
if (fs.existsSync(hiddenBookmarksDir)) {
    fs.rmSync(hiddenBookmarksDir, { recursive: true, force: true });
}
if (fs.existsSync(bookmarksDir)) {
    console.log('Hiding bookmarks directory from Hugo...');
    fs.renameSync(bookmarksDir, hiddenBookmarksDir);
}
