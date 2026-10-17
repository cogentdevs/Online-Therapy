import assert from 'node:assert/strict';
import {spawn} from 'node:child_process';
import {createServer} from 'node:http';
import {readFile,writeFile,mkdir} from 'node:fs/promises';
import path from 'node:path';
// Only static HTML rendered by verified in-memory tests is served. Laravel never runs here.
const root=process.cwd();
const artifacts=path.join(root,'storage/framework/testing/responsive-overflow');
const phase=process.argv.includes('--before')?'before':'after';
await mkdir(path.join(artifacts,'chrome'),{recursive:true});
const server=createServer(async(req,res)=>{
 try {
 const url=new URL(req.url,'http://localhost');
 if(['/home','/register','/admin-hero','/admin-table'].includes(url.pathname)){
 res.setHeader('Content-Type','text/html; charset=utf-8');res.end((await readFile(path.join(artifacts,url.pathname.slice(1)+'.html'),'utf8')).replace(/http:\/\/localhost(?::[0-9]+)?/g,origin));return;}
 if(url.pathname.startsWith('/images/')){
 res.setHeader('Content-Type','image/svg+xml');res.end('<svg xmlns="http://www.w3.org/2000/svg" width="1800" height="600"><rect width="1800" height="600" fill="#173C3A"/><text x="30" y="300" fill="#F2EDDC" font-size="120">Thrive fixture image</text></svg>');return;}
 if(url.pathname.startsWith('/build/')){
 const base=path.join(root,'public/build');const file=path.resolve(base,'.'+url.pathname.slice(6));assert.ok(file.startsWith(base+path.sep));
 res.setHeader('Content-Type',file.endsWith('.css')?'text/css':file.endsWith('.js')?'text/javascript':'application/octet-stream');res.end(await readFile(file));return;}
 res.writeHead(404);res.end();
 }catch{res.writeHead(404);res.end();}
});
await new Promise(resolve=>server.listen(0,'127.0.0.1',resolve));
const origin='http://127.0.0.1:'+server.address().port;
const chrome = spawn(process.env.CHROME_PATH ?? 'C:/Program Files/Google/Chrome/Application/chrome.exe', [
    '--headless=new', '--disable-gpu', '--disable-background-networking', '--no-first-run',
    '--no-default-browser-check', '--remote-debugging-port=0', `--user-data-dir=${path.join(artifacts, 'chrome')}`,
    'about:blank',
], { windowsHide: true, stdio: ['ignore', 'ignore', 'pipe'] });

class Cdp {
    pending = new Map();
    nextId = 0;
    exceptions = [];
    async connect(url) {
        this.socket = new WebSocket(url);
        await new Promise((resolve, reject) => {
            this.socket.addEventListener('open', resolve, { once: true });
            this.socket.addEventListener('error', reject, { once: true });
        });
        this.socket.addEventListener('message', event => {
            const data = JSON.parse(event.data);
            if (data.method === 'Runtime.exceptionThrown') this.exceptions.push(data.params.exceptionDetails);
            const pending = this.pending.get(data.id);
            if (pending) {
                this.pending.delete(data.id);
                if (data.error) pending.reject(new Error(JSON.stringify(data.error)));
                else pending.resolve(data.result);
            }
        });
    }
    send(method, params = {}) {
        return new Promise((resolve, reject) => {
            const id = ++this.nextId;
            this.pending.set(id, { resolve, reject });
            this.socket.send(JSON.stringify({ id, method, params }));
        });
    }
    async evaluate(expression) {
        const result = await this.send('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
        assert.ok(!result.exceptionDetails, JSON.stringify(result.exceptionDetails));
        return result.result.value;
    }
}

let cdp;
try {
    const endpoint = await new Promise((resolve, reject) => {
        let stderr = '';
        const timeout = setTimeout(() => reject(new Error('Chrome startup timed out')), 20000);
        chrome.once('error', reject);
        chrome.stderr.on('data', chunk => {
            stderr += chunk;
            const match = stderr.match(/DevTools listening on (ws:\/\/[^\s]+)/);
            if (match) { clearTimeout(timeout); resolve(match[1]); }
        });
        chrome.once('exit', code => reject(new Error(`Chrome exited ${code}: ${stderr}`)));
    });
    const debugOrigin = endpoint.replace('ws://', 'http://').split('/devtools/')[0];
    const pages = await (await fetch(`${debugOrigin}/json/list`)).json();
    cdp = new Cdp();
    await cdp.connect(pages.find(page => page.type === 'page').webSocketDebuggerUrl);
    await cdp.send('Page.enable');
    await cdp.send('Runtime.enable');
    await cdp.send('Network.enable');
    await cdp.send('Network.setBlockedURLs', { urls: ['*fonts.googleapis.com*', '*fonts.gstatic.com*'] });
    const waitFor = async expression => {
        for (let attempt = 0; attempt < 150; attempt++) {
            if (await cdp.evaluate(expression)) return;
            await new Promise(resolve => setTimeout(resolve, 40));
        }
        throw new Error(`Browser condition timed out: ${expression}`);
    };
    await cdp.send('Network.setBlockedURLs',{urls:['*google.com*','*gstatic.com*','*googleapis.com*','*localhost/*']});
    const results=[];
    const componentChecks=[];
    for(const page of ['home','register','admin-hero','admin-table']){
      await cdp.send('Emulation.setDeviceMetricsOverride',{width:1440,height:1000,deviceScaleFactor:1,mobile:false});
      await cdp.send('Page.navigate',{url:origin+'/'+page});
      await waitFor('document.readyState === "complete"');
      await cdp.evaluate('document.fonts.ready');
      await new Promise(r=>setTimeout(r,300));
      await waitFor('[...document.images].filter(i=>i.src.includes("overflow-")).every(i=>i.complete && i.naturalWidth>0)');
      if(page==='admin-hero')await cdp.evaluate(`(()=>{
        const source=document.querySelector('[data-current-hero-image]');
        const shared=source.cloneNode();shared.removeAttribute('style');shared.removeAttribute('data-current-hero-image');shared.className='admin-current-image';shared.dataset.overflowShared='';source.after(shared);
      })()`);
      for(const width of [1440,390,320,768,1440,375,1024,1280]){
        await cdp.send('Emulation.setDeviceMetricsOverride',{width,height:1000,deviceScaleFactor:1,mobile:false});
        await new Promise(r=>setTimeout(r,150));
        const m=await cdp.evaluate(`(()=>{
          const root=document.documentElement;
          const rect=e=>{const r=e.getBoundingClientRect();const p=e.parentElement.getBoundingClientRect();return {width:r.width,left:r.left,right:r.right,height:r.height,parentWidth:p.width,parentClient:e.parentElement.clientWidth};};
          const probe=s=>[...document.querySelectorAll(s)].map(e=>({selector:s,...rect(e),scroll:e.scrollWidth,client:e.clientWidth}));
          return {viewport:innerWidth,client:root.clientWidth,scroll:root.scrollWidth,bodyScroll:document.body.scrollWidth,
          suspects:[...probe('.front-logo__image--footer'),...probe('[data-current-hero-image]'),...probe('[data-overflow-shared]'),...probe('.front-brand-row__inner'),...probe('.front-register-captcha')],
          outside:[...document.querySelectorAll('body *')].filter(e=>{const r=e.getBoundingClientRect();return r.width>0&&getComputedStyle(e).visibility!=='hidden'&&(r.right>root.clientWidth+1||r.left< -1)}).slice(0,14).map(e=>({tag:e.tagName,class:e.className,...rect(e)})),
          tables:[...document.querySelectorAll('.table-responsive')].map(e=>({scroll:e.scrollWidth,client:e.clientWidth,overflow:getComputedStyle(e).overflowX})),
          frameworkColumns:document.querySelector('.front-thrive-framework__grid')?getComputedStyle(document.querySelector('.front-thrive-framework__grid')).gridTemplateColumns.split(' ').length:null,
          workColumns:document.querySelector('.front-work-together__grid')?getComputedStyle(document.querySelector('.front-work-together__grid')).gridTemplateColumns.split(' ').length:null};
        })()`);
        if(phase==='after'){
          assert.ok(m.scroll<=Math.max(m.client,page.startsWith('admin')?320:0)+1,page+' document overflow at '+width+': '+JSON.stringify(m));
          assert.ok(m.bodyScroll<=Math.max(m.client,page.startsWith('admin')?320:0)+1,page+' body overflow at '+width+': '+JSON.stringify(m));
          for(const image of m.suspects.filter(e=>e.selector.includes('image')||e.selector.includes('shared')))assert.ok(image.width<=image.parentClient+1,'Image wider than parent: '+JSON.stringify(image));
          if(page==='home'){assert.equal(m.frameworkColumns,width>=1200?3:width>=768?2:1);assert.equal(m.workColumns,width>=1200?3:width>=768?2:1);}
          for(const table of m.tables)assert.equal(table.overflow,'auto');
        }
        if(phase==='after' && page==='home'){
          const variants=await cdp.evaluate(`(()=>{
            const values=[];
            for(const [selector,property] of [['.front-thrive-framework__grid','--framework-columns'],['.front-work-together__grid','--work-columns']]){
              const grid=document.querySelector(selector);grid.style.setProperty(property,4);
              values.push(getComputedStyle(grid).gridTemplateColumns.split(' ').length);grid.style.setProperty(property,3);
            }
            return values;
          })()`);
          assert.deepEqual(variants,[width>=1200?4:width>=768?2:1,width>=1200?4:width>=768?2:1]);
          if(width===390){
            const nav=await cdp.evaluate(`(async()=>{
              const toggle=document.querySelector('.navbar-toggler');if(!toggle)return false;
              const target=document.querySelector(toggle.getAttribute('data-bs-target'));toggle.click();
              await new Promise(r=>setTimeout(r,400));const opened=target.classList.contains('show');toggle.click();await new Promise(r=>setTimeout(r,400));return opened&&!target.classList.contains('show');
            })()`);
            assert.equal(nav,true,'Mobile navigation should open and close');componentChecks.push('mobile navigation');
            const slider=await cdp.evaluate(`(async()=>{
              const carousel=document.querySelector('#frontHeroCarousel');if(!carousel)return false;
              const active=carousel.querySelector('.carousel-item.active');carousel.querySelector('.carousel-control-next').click();
              await new Promise(r=>setTimeout(r,800));return active!==carousel.querySelector('.carousel-item.active');
            })()`);
            assert.equal(slider,true,'Existing Slider should advance');componentChecks.push('Slider next control');
          }
        }
        if(phase==='after' && page==='admin-table' && width===320){
          const internal=await cdp.evaluate(`(()=>{
            const wrapper=document.querySelector('.table-responsive');const table=wrapper.querySelector('table');const old=table.getAttribute('style');
            table.style.minWidth='1200px';const result={scroll:wrapper.scrollWidth,client:wrapper.clientWidth,document:document.documentElement.scrollWidth};
            if(old===null)table.removeAttribute('style');else table.setAttribute('style',old);return result;
          })()`);
          assert.ok(internal.scroll>internal.client);assert.ok(internal.document<=Math.max(m.client,320)+1);componentChecks.push('Admin internal table scrolling');
        }
        results.push({page,...m});
        if([1440,320].includes(width)){
          const shot=await cdp.send('Page.captureScreenshot',{format:'png',captureBeyondViewport:true});
          await writeFile(path.join(artifacts,phase+'-'+page+'-'+width+'.png'),Buffer.from(shot.data,'base64'));
        }
      }
    }
    await writeFile(path.join(artifacts,phase+'-measurements.json'),JSON.stringify(results,null,2));
    console.log(JSON.stringify({phase,scenarios:results.length,componentChecks,overflows:results.filter(m=>m.scroll>m.client+1||m.bodyScroll>m.client+1).map(m=>({page:m.page,width:m.viewport,client:m.client,scroll:m.scroll,body:m.bodyScroll})),imageRisks:results.flatMap(m=>m.suspects.filter(s=>s.width>s.parentClient+1&&s.selector!==' .front-brand-row__inner').map(s=>({page:m.page,viewport:m.viewport,...s})))},null,2));
} finally {
 cdp?.socket?.close();chrome.kill();server.closeAllConnections();server.close();
}
