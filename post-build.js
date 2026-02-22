const fs = require('fs');
const path = require('path');

const bookmarksDir = path.join(__dirname, 'content', 'bookmarks');
const hiddenBookmarksDir = path.join(__dirname, 'content', '.bookmarks_archive');

if (fs.existsSync(hiddenBookmarksDir)) {
    console.log('Restoring bookmarks directory...');
    if (fs.existsSync(bookmarksDir)) {
        fs.rmSync(bookmarksDir, { recursive: true, force: true });
    }
    fs.renameSync(hiddenBookmarksDir, bookmarksDir);
}
