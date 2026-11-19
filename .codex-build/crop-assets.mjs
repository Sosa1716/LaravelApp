import sharp from 'file:///C:/Users/vegag/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/sharp/dist/index.mjs';
const src='C:/Users/vegag/AppData/Local/Temp/codex-clipboard-874ec86f-3f3c-4a19-bafb-84d7ec6dd8e9.png';
const out='C:/Users/vegag/LaravelApp/.codex-build';
await sharp(src).extract({left:6,top:0,width:381,height:174}).png().toFile(`${out}/logo-crop.png`);
await sharp(src).extract({left:91,top:155,width:215,height:242}).png().toFile(`${out}/mascot-crop.png`);
await sharp(src).extract({left:5,top:258,width:94,height:212}).png().toFile(`${out}/waiter-left.png`);
await sharp(src).extract({left:286,top:258,width:100,height:212}).png().toFile(`${out}/waiter-right.png`);
