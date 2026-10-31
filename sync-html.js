import fs from 'fs';
import path from 'path';

const manifestPath = path.resolve('public/build/manifest.json');
if (fs.existsSync(manifestPath)) {
    const manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf8'));
    const cssFile = manifest['resources/js/app.jsx']?.css?.[0] || manifest['resources/css/app.css']?.file;
    const jsFile = manifest['resources/js/app.jsx']?.file;

    let html = fs.readFileSync(path.resolve('public/index.html'), 'utf8');

    if (cssFile) {
        html = html.replace(/href="\/build\/assets\/app-[^"]+\.css"/, `href="/build/${cssFile}"`);
    }
    if (jsFile) {
        html = html.replace(/src="\/build\/assets\/app-[^"]+\.js"/, `src="/build/${jsFile}"`);
    }

    fs.writeFileSync(path.resolve('public/index.html'), html);
    console.log('✅ Synchronized public/index.html with Vite manifest!');
}