import assert from 'node:assert/strict';
import {spawn} from 'node:child_process';
import {createServer} from 'node:http';
import {readFile,writeFile,mkdir} from 'node:fs/promises';
import path from 'node:path';
// Uses rendered fixtures exported by isolated tests; never boots the application.
const root=process.cwd();
const artifacts=path.join(root,'storage/framework/testing/work-together-ui');
const html=await readFile(path.join(artifacts,'work.html'),'utf8');
await mkdir(path.join(artifacts,'chrome'),{recursive:true});
const server=createServer(async(req,res)=>{
 try {
 const url=new URL(req.url,'http://localhost');
 if(url.pathname==='/'){res.setHeader('Content-Type','text/html; charset=utf-8');res.end(html);return;}
 if(url.pathname.startsWith('/build/')){
 const base=path.join(root,'public/build');const file=path.resolve(base,'.'+url.pathname.slice(6));
 assert.ok(file.startsWith(base+path.sep));
 res.setHeader('Content-Type',file.endsWith('.css')?'text/css':'application/octet-stream');res.end(await readFile(file));return;}
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
    const results=[];
    for(const width of [1440,1200,1024,768,390,320]){
      for(const columns of [3,4]){
        await cdp.send('Emulation.setDeviceMetricsOverride',{width,height:1100,deviceScaleFactor:1,mobile:false});
        await cdp.send('Page.navigate',{url:origin});
        await waitFor('document.readyState === "complete" && !!document.querySelector(".front-work-together__grid")');
        await cdp.evaluate('document.fonts.ready');
        await cdp.evaluate(`(() => {
          const section=document.querySelector('.front-work-together');
          const before=getComputedStyle(section,'::before');
          if(before.content!=='none') throw new Error('Divider must be absent without Framework');
          const framework=document.createElement('section');
          framework.className='front-thrive-framework';
          section.before(framework);
          const divider=getComputedStyle(section,'::before');
          if(divider.content==='none'||parseFloat(divider.height)!==1||Math.abs(parseFloat(divider.width)-section.getBoundingClientRect().width)>1) {
            throw new Error('Expected responsive divider between Framework and Work Together');
          }
        })()`);
        await cdp.evaluate('document.querySelector(".front-work-together__grid").style.setProperty("--work-columns",'+columns+')');
        const m=await cdp.evaluate(`(()=>{
          const grid=document.querySelector('.front-work-together__grid');
          const cards=[...grid.children];const r=e=>{const a=e.getBoundingClientRect();return {x:a.x,y:a.y,width:a.width,height:a.height,bottom:a.bottom,right:a.right}};
          const full=cards.find(e=>e.classList.contains('front-work-together__card--full'));
          return {width:innerWidth,columns:getComputedStyle(grid).gridTemplateColumns.split(' ').length,
          overflow:document.documentElement.scrollWidth>innerWidth,grid:r(grid),cards:cards.map(r),full:r(full),
          fullColumns:getComputedStyle(full).gridTemplateColumns.split(' ').length,
          overflowing:cards.some(e=>e.scrollWidth>e.clientWidth+1||e.scrollHeight>e.clientHeight+1)};
        })()`);
        assert.equal(m.columns,width>=1200?columns:width>=768?2:1);
        assert.equal(m.cards.length,7);assert.equal(m.overflow,false);assert.equal(m.overflowing,false);
        assert.ok(Math.abs(m.full.width-m.grid.width)<1);
        if(width<768)assert.equal(m.fullColumns,1);
        for(const card of m.cards.slice(0,-1)){
          for(const other of m.cards.slice(0,-1).filter(e=>Math.abs(e.y-card.y)<1))assert.ok(Math.abs(card.height-other.height)<1);
        }
        if(columns===3 && [1440,390].includes(width)){
          const shot=await cdp.send('Page.captureScreenshot',{format:'png',captureBeyondViewport:true});
          await writeFile(path.join(artifacts,'work-'+width+'.png'),Buffer.from(shot.data,'base64'));
        }
        results.push({columns,...m});
      }
    }
    assert.deepEqual(cdp.exceptions,[]);
    await writeFile(path.join(artifacts,'browser-results.json'),JSON.stringify(results,null,2));
    console.log('PASS: 12 viewport/layout scenarios, equal row heights, full-width span, mobile stacking, no overflow or runtime exceptions.');
} finally {
 cdp?.socket?.close();chrome.kill();server.closeAllConnections();server.close();
}
