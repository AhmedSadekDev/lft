// Isolated tests against captured real-data HTML. The preview server rejects every non-GET request.
const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require(path.resolve('storage/app/phase4b-ui/test-tools/node_modules/playwright'));
const out = __dirname;
(async () => {
 const browser = await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe', headless:true});
 const records=[];
 const pages=['drivers','drivers/create','bookings','bookings/create','services','accounts','private-companies','employees','receipts','profile','agent-photos'];
 for(const phase of ['before','after']) {
  for(const pageName of pages) {
   for(const width of [1440,768,390]) {
    const context=await browser.newContext({viewport:{width,height:1000},deviceScaleFactor:1});
    const page=await context.newPage();const errors=[];const dialogs=[];
    page.on('pageerror',e=>errors.push(e.message));
    page.on('dialog',async d=>{dialogs.push(d.message());await d.dismiss()});
    await page.route('**/*',route=>{const r=route.request();if(!r.url().startsWith('http://127.0.0.1:8765/') || r.method()!=='GET')return route.abort();return route.continue()});
    await page.goto('http://127.0.0.1:8765/dashboard/'+pageName+(phase==='before'?'?phase=before':''),{waitUntil:'networkidle'});
    const metrics=await page.evaluate(()=>({scrollWidth:document.documentElement.scrollWidth,width:innerWidth,heading:document.querySelector('h1')?.textContent.trim(),tables:document.querySelectorAll('table').length,regions:document.querySelectorAll('.lft-table-scroll,.table-responsive').length,formCount:document.querySelectorAll('form').length,active:document.querySelector('#kt_aside_menu [aria-current="page"]')?.textContent.trim(),bodyDirection:getComputedStyle(document.body).direction}));
    const record={phase,page:pageName,width,metrics,errors,dialogs};
    if(phase==='after'&&pageName==='drivers'&&width===390){
      await page.locator('#kt_aside_mobile_toggle').click();
      record.drawerOpen=await page.locator('#kt_aside').evaluate(el=>el.classList.contains('aside-on'));
      await page.screenshot({path:path.join(out,'after-mobile-navigation.png')});
      await page.keyboard.press('Escape');
      record.drawerClosed=await page.locator('#kt_aside').evaluate(el=>!el.classList.contains('aside-on'));
      record.focusReturned=await page.locator('#kt_aside_mobile_toggle').evaluate(el=>el===document.activeElement);
    }
    if(phase==='after'&&pageName==='services'&&width===1440){
      const search=page.locator('.dataTables_filter input');
      if(await search.count()){
        await search.fill('LFT_NO_MATCH_4B');
        record.emptyState=await page.locator('td.dataTables_empty').count()>0;
        await search.fill('');
        const table=page.locator('table').first();
        const first=await table.locator('tbody tr').first().textContent();
        await table.locator('thead th').first().click();
        record.sortChangesFirstRow=first!==await table.locator('tbody tr').first().textContent();
        const del=page.locator('[onclick^="Delete("]').first();
        if(await del.count()){
          await del.click();record.confirmationVisible=await page.locator('.swal2-popup').isVisible();
          await page.locator('.swal2-cancel').click();record.confirmationCancelled=!(await page.locator('.swal2-popup').isVisible());
        }
      }
    }
    if(phase==='after'&&pageName==='drivers/create'&&width===390){
      record.formContract=await page.locator('#kt_content form').first().evaluate(form=>({method:form.method,action:new URL(form.action).pathname,fields:Array.from(form.elements).filter(el=>el.name).map(el=>({name:el.name,type:el.type,required:el.required})),labelled:Array.from(form.querySelectorAll('input:not([type=hidden]),select,textarea')).every(el=>el.labels?.length||el.getAttribute('aria-label'))}));
    }
    if(['drivers','bookings','drivers/create','accounts'].includes(pageName)&&width!==768)await page.screenshot({path:path.join(out,phase+'-'+pageName.replaceAll('/','-')+'-'+width+'.png'),fullPage:false});
    records.push(record);await context.close();
   }
  }
 }
 fs.writeFileSync(path.join(out,'browser-results.json'),JSON.stringify(records,null,2));
 await browser.close();console.log(JSON.stringify({checks:records.length,overflow:records.filter(r=>r.metrics.scrollWidth>r.width+1).map(r=>[r.phase,r.page,r.width,r.metrics.scrollWidth]),errors:records.filter(r=>r.errors.length).map(r=>[r.phase,r.page,r.width,r.errors])},null,2));
})().catch(e=>{console.error(e);process.exitCode=1});
