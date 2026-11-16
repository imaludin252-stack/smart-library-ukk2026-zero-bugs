import { spawn } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import os from 'node:os';

const CHROME = 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const BASE = 'http://127.0.0.1:8010';
const PORT = 9351;
const OUT = 'C:/Users/acera/OneDrive/Documents/imal-ukk2026/smart-library-app/dokument/Final/screenshots';
const LOG = 'C:/Users/acera/OneDrive/Documents/imal-ukk2026/smart-library-app/dokument/Final/lampiran/demo-rehearsal.json';

fs.mkdirSync(OUT, { recursive: true });

const profile = path.join(os.tmpdir(), `smartlib-demo-${Date.now()}`);
const chrome = spawn(CHROME, [
  '--headless=new', `--remote-debugging-port=${PORT}`, `--user-data-dir=${profile}`,
  '--no-first-run', '--no-default-browser-check', '--disable-gpu',
  '--force-device-scale-factor=1', '--window-size=1440,900', 'about:blank',
], { stdio: 'ignore' });

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

async function waitForChrome() {
  for (let i = 0; i < 80; i++) {
    try {
      const res = await fetch(`http://127.0.0.1:${PORT}/json/version`);
      if (res.ok) return await res.json();
    } catch { /* belum siap */ }
    await sleep(250);
  }
  throw new Error('Chrome DevTools tidak siap');
}

class CDP {
  constructor(ws) {
    this.ws = ws; this.id = 0; this.pending = new Map(); this.events = [];
    ws.addEventListener('message', (event) => {
      const msg = JSON.parse(event.data);
      if (msg.id && this.pending.has(msg.id)) {
        const { resolve, reject } = this.pending.get(msg.id);
        this.pending.delete(msg.id);
        msg.error ? reject(new Error(JSON.stringify(msg.error))) : resolve(msg.result);
      } else if (msg.method) {
        this.events.push(msg);
      }
    });
  }
  send(method, params = {}, sessionId) {
    const id = ++this.id;
    return new Promise((resolve, reject) => {
      this.pending.set(id, { resolve, reject });
      this.ws.send(JSON.stringify({ id, method, params, ...(sessionId ? { sessionId } : {}) }));
    });
  }
}

async function main() {
  const version = await waitForChrome();
  const ws = new WebSocket(version.webSocketDebuggerUrl);
  await new Promise((resolve, reject) => {
    ws.addEventListener('open', resolve, { once: true });
    ws.addEventListener('error', reject, { once: true });
  });

  const cdp = new CDP(ws);
  const { targetId } = await cdp.send('Target.createTarget', { url: 'about:blank' });
  const { sessionId } = await cdp.send('Target.attachToTarget', { targetId, flatten: true });
  const send = (method, params = {}) => cdp.send(method, params, sessionId);
  await send('Page.enable');
  await send('Network.enable');
  await send('Runtime.enable');
  await send('Emulation.setDeviceMetricsOverride', { width: 1440, height: 900, deviceScaleFactor: 1, mobile: false });

  async function evaluate(expression, awaitPromise = false) {
    const r = await send('Runtime.evaluate', { expression, awaitPromise, returnByValue: true });
    if (r.exceptionDetails) throw new Error(JSON.stringify(r.exceptionDetails));
    return r.result?.value;
  }

  async function navigate(url) {
    await send('Page.navigate', { url });
    for (let i = 0; i < 120; i++) {
      await sleep(150);
      try {
        if (await evaluate('document.readyState') === 'complete') break;
      } catch { /* lanjut */ }
    }
    await sleep(450);
  }

  async function tungguJalur(prefix) {
    for (let i = 0; i < 80; i++) {
      await sleep(200);
      try {
        if ((await evaluate('location.pathname'))?.startsWith(prefix)) return true;
      } catch { /* lanjut */ }
    }
    return false;
  }

  async function screenshot(nama) {
    const { data } = await send('Page.captureScreenshot', { format: 'png', fromSurface: true });
    fs.writeFileSync(path.join(OUT, nama), Buffer.from(data, 'base64'));
    return nama;
  }

  async function login(email) {
    await send('Network.clearBrowserCookies');
    await navigate(`${BASE}/login`);
    await evaluate(`(() => {
      document.querySelector('#email').value = ${JSON.stringify(email)};
      document.querySelector('#password').value = 'password';
      document.querySelector('form').requestSubmit();
      return true;
    })()`);
  }

  const hasil = {};
  const screenshotList = [];

  // 1. Login & siswa
  await send('Network.clearBrowserCookies');
  await navigate(`${BASE}/login`);
  screenshotList.push(await screenshot('demo-01-login.png'));
  await login('rizky@siswa.smkbb.sch.id');
  hasil.loginSiswa = await tungguJalur('/beranda');
  await navigate(`${BASE}/beranda`);
  screenshotList.push(await screenshot('demo-02-siswa-beranda.png'));
  hasil.berandaMemuatNama = (await evaluate('document.body.innerText')).includes('Rizky Pratama');

  await navigate(`${BASE}/katalog?q=Pemrograman`);
  screenshotList.push(await screenshot('demo-03-katalog.png'));
  const hasilCari = await evaluate(`(() => {
    const cards = [...document.querySelectorAll('a[href*="/katalog/"]')];
    return { jumlah: cards.length, judul: document.body.innerText.includes('Pemrograman') };
  })()`);
  hasil.katalogPencarian = hasilCari;

  const tautanDetail = await evaluate(`(() => {
    const a = document.querySelector('a[href*="/katalog/"]');
    return a ? a.getAttribute('href') : null;
  })()`);
  if (tautanDetail) {
    await navigate(tautanDetail.startsWith('http') ? tautanDetail : `${BASE}${tautanDetail}`);
    screenshotList.push(await screenshot('demo-04-detail-buku.png'));
    hasil.detailBuku = (await evaluate('document.body.innerText')).includes('Detail');
  } else {
    hasil.detailBuku = false;
  }

  await navigate(`${BASE}/pinjaman`);
  screenshotList.push(await screenshot('demo-05-pinjaman-saya.png'));
  const teksPinjaman = await evaluate('document.body.innerText');
  hasil.tabDitolakTampil = teksPinjaman.includes('Ditolak / Dibatalkan');
  hasil.kuotaTampil = /kuota peminjaman aktif/i.test(teksPinjaman);

  await navigate(`${BASE}/riwayat`);
  screenshotList.push(await screenshot('demo-06-riwayat-bebas-pustaka.png'));
  hasil.bebasPustakaTerkunci = (await evaluate('document.body.innerText')).includes('Surat Bebas Pustaka Terkunci');

  // 2. Tampilan mobile (bukti responsif saat demo)
  await send('Emulation.setDeviceMetricsOverride', { width: 390, height: 844, deviceScaleFactor: 2, mobile: true });
  await navigate(`${BASE}/pinjaman`);
  screenshotList.push(await screenshot('demo-07-mobile-pinjaman.png'));
  await send('Emulation.setDeviceMetricsOverride', { width: 1440, height: 900, deviceScaleFactor: 1, mobile: false });

  // 3. Petugas
  await login('petugas@smartlibrary.sch.id');
  hasil.loginPetugas = await tungguJalur('/petugas');
  await navigate(`${BASE}/petugas`);
  screenshotList.push(await screenshot('demo-08-petugas-dashboard.png'));

  await navigate(`${BASE}/petugas/sirkulasi?tab=permohonan`);
  screenshotList.push(await screenshot('demo-09-sirkulasi-permohonan.png'));
  const sebelum = await evaluate(`(() => {
    const card = [...document.querySelectorAll('div.card')].find((n) => n.querySelector('form[action*="/verifikasi"]'));
    return card ? card.innerText.split('\\n').slice(0, 4).join(' | ') : null;
  })()`);
  hasil.adaPermohonan = Boolean(sebelum);

  const verifikasi = await evaluate(`(() => {
    const form = document.querySelector('form[action*="/verifikasi"]');
    if (!form) return false;
    form.requestSubmit();
    return true;
  })()`);
  hasil.verifikasiDikirim = verifikasi;
  await sleep(2200);
  screenshotList.push(await screenshot('demo-10-verifikasi-berhasil.png'));
  hasil.flashVerifikasi = (await evaluate('document.body.innerText')).includes('diverifikasi');

  await navigate(`${BASE}/petugas/sirkulasi?tab=aktif`);
  screenshotList.push(await screenshot('demo-11-sirkulasi-aktif.png'));

  await navigate(`${BASE}/petugas/laporan`);
  screenshotList.push(await screenshot('demo-12-laporan.png'));
  const teksLaporan = await evaluate('document.body.innerText');
  hasil.laporanTampil = /laporan/i.test(teksLaporan) && /transaksi/i.test(teksLaporan);

  // 4. Admin
  await login('admin@smartlibrary.sch.id');
  hasil.loginAdmin = await tungguJalur('/admin');
  await navigate(`${BASE}/admin/pengguna`);
  screenshotList.push(await screenshot('demo-13-admin-pengguna.png'));
  hasil.adminPengguna = (await evaluate('document.body.innerText')).includes('Pengguna');

  // 5. Rekap error runtime
  const gagalJaringan = cdp.events
    .filter((e) => e.method === 'Network.loadingFailed')
    .map((e) => e.params.errorText)
    .filter((t) => !String(t).includes('ERR_ABORTED'));
  const errorKonsol = cdp.events
    .filter((e) => e.method === 'Runtime.exceptionThrown')
    .map((e) => e.params.exceptionDetails?.text ?? 'exception');
  hasil.errorKonsol = errorKonsol;
  hasil.gagalJaringan = gagalJaringan;

  const laporan = {
    waktu: new Date().toISOString(),
    baseUrl: BASE,
    viewport: '1440x900 (mobile 390x844)',
    checklist: hasil,
    screenshots: screenshotList,
  };

  fs.writeFileSync(LOG, JSON.stringify(laporan, null, 2));

  console.log('=== DEMO REHEARSAL ===');
  for (const [k, v] of Object.entries(hasil)) {
    console.log(`${k}: ${JSON.stringify(v)}`);
  }
  console.log('Screenshots:', screenshotList.join(', '));
  console.log('LOG:', LOG);

  const wajib = ['loginSiswa', 'berandaMemuatNama', 'detailBuku', 'tabDitolakTampil', 'bebasPustakaTerkunci', 'loginPetugas', 'verifikasiDikirim', 'flashVerifikasi', 'laporanTampil', 'loginAdmin', 'adminPengguna'];
  const gagal = wajib.filter((k) => !hasil[k]);
  if (gagal.length || errorKonsol.length) {
    console.error('CHECKLIST GAGAL:', gagal.join(', ') || '-', 'ERROR:', errorKonsol.join(' | ') || '-');
    process.exitCode = 1;
  } else {
    console.log('SEMUA CHECKLIST DEMO LULUS');
  }

  ws.close();
  chrome.kill();
}

main()
  .then(() => process.exit(process.exitCode ?? 0))
  .catch((err) => { console.error('GAGAL', err); chrome.kill(); process.exit(1); });
