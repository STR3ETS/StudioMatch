// App-icoon en splashscherm in de StudioMatch-huisstijl: het witte beeldmerk (icoon) en het witte logo
// (splash) op Prussian Blue. Leest de maat van elk bestaand plaatje in de native projecten en vervangt
// het door een nieuw plaatje van precies die maat, dus geen lijst met maten om bij te houden.
//   npm run mobile:assets   (na `npx cap add android/ios`, en opnieuw als het logo verandert)
import { readdirSync, statSync } from 'node:fs';
import { join } from 'node:path';
import sharp from 'sharp';

const BLUE = '#101529';
const MARK = 'public/logos/sm-mark-wit.png';
const LOGO = 'public/logos/sm-primary-logo-wit.png';

const files = (dir) => readdirSync(dir).flatMap((name) => {
    const path = join(dir, name);
    return statSync(path).isDirectory() ? files(path) : [path];
});

// Plaatje van width x height: blauwe achtergrond (of transparant), met het beeld gecentreerd op `scale`
// van de kortste zijde. Rond = ronde uitsnede voor ic_launcher_round.
async function render(source, width, height, { scale, background = BLUE, round = false }) {
    const size = Math.round(Math.min(width, height) * scale);
    const art = await sharp(source).resize({ width: size, height: size, fit: 'inside' }).toBuffer();
    let image = sharp({ create: { width, height, channels: 4, background } }).composite([{ input: art, gravity: 'centre' }]);
    if (round) {
        const mask = Buffer.from(`<svg width="${width}" height="${height}"><circle cx="${width / 2}" cy="${height / 2}" r="${Math.min(width, height) / 2}"/></svg>`);
        image = sharp(await image.png().toBuffer()).composite([{ input: mask, blend: 'dest-in' }]);
    }
    return image.png().toBuffer();
}

async function replace(path, options, source = MARK) {
    const { width, height } = await sharp(path).metadata();
    await sharp(await render(source, width, height, options)).toFile(`${path}.tmp`);
    const { renameSync } = await import('node:fs');
    renameSync(`${path}.tmp`, path);
    return `${path} (${width}x${height})`;
}

const done = [];
for (const path of files('android/app/src/main/res')) {
    const name = path.split(/[\\/]/).pop();
    if (name === 'splash.png') done.push(await replace(path, { scale: 0.42 }, LOGO));
    // Adaptief icoon: de voorgrond is transparant en het beeld blijft binnen de veilige zone (66%).
    if (name === 'ic_launcher_foreground.png') done.push(await replace(path, { scale: 0.42, background: { r: 0, g: 0, b: 0, alpha: 0 } }));
    if (name === 'ic_launcher.png') done.push(await replace(path, { scale: 0.6 }));
    if (name === 'ic_launcher_round.png') done.push(await replace(path, { scale: 0.6, round: true }));
}
for (const path of files('ios/App/App/Assets.xcassets')) {
    if (path.includes('AppIcon') && path.endsWith('.png')) done.push(await replace(path, { scale: 0.6 }));
    if (path.includes('Splash') && path.endsWith('.png')) done.push(await replace(path, { scale: 0.3 }, LOGO));
}
console.log(`${done.length} plaatjes bijgewerkt`);
