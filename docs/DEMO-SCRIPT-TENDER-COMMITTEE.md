# Skrip Demo POC SPPT — Jawatankuasa Tender

**Rujukan:** `Skrip POC Petender SPPT - Final.pdf` (PMO TEKUN Nasional)  
**Projek:** Sistem Pengurusan Pembiayaan TEKUN (SPPT)  
**Tempoh cadangan:** 35–40 minit (versi ringkas: 25 minit — lihat Jadual A)  
**Pembentang:** _______________  
**Tarikh demo:** _______________

---

## 1. Matlamat Demo

Demonstrasi **live** keupayaan teknikal SPPT POC mengikut enam modul skrip tender:

| # | Modul (PDF) | Fokus pembuktian |
|---|-------------|------------------|
| 1 | Permohonan & Semakan Kelayakan Awalan | Omnichannel, integrasi, hard rules, eKYC, borang dinamik |
| 2 | Penilaian Risiko & Skor Kredit | AI scoring, formula pembiayaan, edge cases, surat tawaran |
| 3 | Aliran Kerja Kelulusan Berperingkat | Cawangan → Ibu Pejabat, RBAC, aging/escalation |
| 4 | Pengurusan Akaun & Pembayaran Balik | Penjadualan semula, multi-saluran bayaran, Ta'widh |
| 5 | Pemulihan & Kutipan Hutang | Klasifikasi NPL, dunning automatik, notis PDF |
| 6 | Dashboard & Pelaporan | KPI eksekutif, Power BI, eksport |

---

## 2. Persiapan Teknikal (1 hari sebelum / 30 minit sebelum demo)

### 2.1 Jalankan persekitaran

```powershell
cd C:\Tekun\sppt-poc-repo
composer dev:win
```

| Perkhidmatan | URL |
|--------------|-----|
| Portal Pemohon | http://localhost:5180/pemohon |
| Portal Pegawai (Admin) | http://localhost:5180/admin |
| Webfront awam | http://localhost:5180/ |
| API Laravel | http://localhost:8000 |

### 2.2 Seed data (jika perlu)

```powershell
php artisan migrate
php artisan db:seed
```

Seeder penting: `TekunNiagaWorkflowSampleSeeder` (permohonan contoh di pelbagai peringkat workflow), `HuluLangatCawanganUserSeeder`, `IbuPejabatApproverUserSeeder`.

### 2.3 Akaun demo

| Peranan | E-mel | Kata laluan | Guna untuk |
|---------|-------|-------------|------------|
| Pentadbir | `admin@example.com` | `admin12345` | Overview, tetapan, laporan |
| Penyemak Cawangan | `penyemak1.hululangat@tekun.gov.my` | `Tekun123!` | Modul 1 & 3 — Semakan |
| Penyokong Cawangan | `penyokong.hululangat@tekun.gov.my` | `Tekun123!` | Modul 3 — Sokongan |
| Pelulus Ibu Pejabat | `pelulus.ibupejabat@tekun.gov.my` | `Tekun123!` | Modul 3 — Kelulusan |
| Pemohon (contoh) | Daftar baharu semasa demo | — | Modul 1 — self-service |

> **Nota OTP:** Pendaftaran pemohon menggunakan OTP SMS/e-mel sebenar jika Twilio/Mail dikonfigurasi. Untuk demo tanpa SMS, gunakan aliran **Log Masuk Demo** di `/pemohon/log-masuk` (akaun demo: `demo@pemohon.my` / `demo1234` jika tersedia) atau daftar dengan e-mel dan semak log Laravel (`storage/logs/laravel.log`) untuk kod OTP.

### 2.4 Senarai semak hari demo

- [ ] `composer dev:win` berjalan tanpa ralat
- [ ] Buka dua tab: **Pemohon** + **Admin** (Chrome/Edge, zoom 100%)
- [ ] Tutup tab tidak berkaitan; matikan notifikasi OS
- [ ] Sediakan backup: tangkapan skrin modul kritikal + video rakaman (jika ada)
- [ ] Uji sekali aliran penuh Modul 1→3 (15 min dry run)
- [ ] Pastikan `VITE_POWERBI_REPORT_URL` dikonfigurasi jika demo Power BI (atau gunakan URL lalai dalam `client/src/env.ts`)

### 2.5 Legenda status ciri

| Tag | Maksud |
|-----|--------|
| **LIVE** | Berfungsi dalam POC — tunjukkan secara langsung |
| **SIM** | Simulasi / mock integrasi — jelaskan sebagai reka bentuk sedia untuk API sebenar |
| **ROADMAP** | Belum dibina — nyatakan fasa seterusnya |

---

## 3. Pembukaan (2 minit)

### Naratif

> *"Yang Berbahagia Tuan Pengerusi, ahli jawatankuasa tender yang dihormati,*
>
> *Terima kasih atas peluang membentang penyelesaian SPPT — Sistem Pengurusan Pembiayaan TEKUN Nasional.*
>
> *Demo hari ini mengikut skrip POC rasmi PMO TEKUN, enam modul, secara live. Kami akan tunjukkan:*
> - *Pengalaman pemohon omnichannel (portal self-service + kemasukan pegawai)*
> - *Enjin kelayakan automatik dan sokongan keputusan AI*
> - *Aliran kelulusan berperingkat Cawangan → Ibu Pejabat*
> - *Operasi pasca-kelulusan: akaun, bayaran, kutipan, dan pelaporan eksekutif*
>
> *Ciri yang ditanda simulasi adalah reka bentuk integrasi sedia — bukan kekurangan fungsi teras sistem."*

---

## 4. Modul 1 — Permohonan & Semakan Kelayakan Awalan (~8 minit)

**Keperluan PDF:** Omnichannel, integrasi integriti/kredit, saringan auto-kelayakan, laman utama, pendaftaran OTP, eKYC, borang dinamik, penjejakan status.

### 4.1 Laman Utama & Hebahan (1.1) — **LIVE**

| Tindakan | URL |
|----------|-----|
| Buka portal pemohon | `/pemohon` |
| *(Pilihan)* Webfront awam | `/` |

**Tunjuk:** UI responsif, hebahan skim pembiayaan, pautan Daftar / Log Masuk.

**Naratif:**

> *"Pemohon boleh akses SPPT melalui portal web responsif — sesuai desktop dan mudah alih. Hebahan skim pembiayaan semasa dipaparkan di laman utama."*

---

### 4.2 Pendaftaran Akaun + OTP (1.2) — **LIVE** (OTP **SIM** jika tiada Twilio)

| Tindakan | URL |
|----------|-----|
| Daftar akaun | `/pemohon/daftar` |

**Tunjuk:**
1. Isi nama, MyKad, telefon, e-mel, kata laluan (polisi keselamatan)
2. Pilih saluran OTP (SMS / E-mel)
3. Sahkan OTP → redirect ke semakan kelayakan

**Naratif:**

> *"Pendaftaran baharu termasuk pengesahan TAC/OTP untuk keselamatan identiti. Integrasi SMS/e-mel production menggunakan Twilio / SMTP rasmi TEKUN."*

---

### 4.3 Saringan Auto-Kelayakan / Hard Rules (1.4 + PDF Modul 1) — **LIVE**

| Tindakan | URL |
|----------|-----|
| Semakan kelayakan | `/pemohon/semakan-kelayakan` |

**Senario A — Lulus (tunjuk dulu):**

| Medan | Nilai |
|-------|-------|
| Umur | 30 |
| No. KP | `850101-10-1234` |
| Pendapatan bulanan | RM 3,000 |
| Komitmen sedia ada | RM 500 |

**Tunjuk:** Status **Layak**, senarai peraturan aktif dari API `/api/public/sppt/hard-rules`, skor risiko AI awal.

**Senario B — Auto-reject (tunjuk cepat):**

| Medan | Nilai | Sebab |
|-------|-------|-------|
| Umur | 17 | Bawah had umur |
| ATAU No. KP | `800101-01-0001` | Senarai hitam |
| ATAU Komitmen tinggi | Pendapatan RM 2,000, komitmen RM 1,900 | Nisbah komitmen melebihi had |

**Naratif:**

> *"Enjin Hard Rules Check menapis permohonan secara automatik — umur, senarai hitam, dan had komitmen kewangan. Permohonan yang gagal ditolak tanpa membebankan pegawai."*

---

### 4.4 Pengesahan Identiti eKYC (1.3) — **SIM**

| Tindakan | URL |
|----------|-----|
| eKYC | `/pemohon/ekyc` |

**Tunjuk:** Muat naik / imbas IC (simulasi), liveness detection, keputusan lulus/gagal.

**Naratif:**

> *"Modul eKYC menyokong imbasan dokumen dan padanan wajah. POC menggunakan simulasi; production disambung ke vendor eKYC berlesen (contoh: MyDigital ID)."*

---

### 4.5 Borang Permohonan Digital + Draf (3.5) — **LIVE**

| Tindakan | URL |
|----------|-----|
| Permohonan baharu (pemohon) | `/pemohon/permohonan/baru` |
| ATAU kemasukan pegawai | `/admin/permohonan/baru` (login pegawai) |

**Tunjuk:**
- Borang wizard berbilang langkah (individu/syarikat, produk TEKUN Niaga, dll.)
- Validasi medan wajib
- Simpan draf → kembali kemudian
- Muat naik dokumen (IC, lesen, penyata bank)

**Naratif:**

> *"Borang digital dinamik mengikut jenis pemohon dan produk. Pegawai cawangan juga boleh kemasukan bagi pihak — omnichannel seperti keperluan tender."*

---

### 4.6 Integrasi Integriti / Kredit (PDF Modul 1) — **SIM**

**Naratif (semasa borang / semakan):**

> *"Reka bentuk SPPT sedia untuk panggilan API masa nyata: muflis/e-Syariah, SSM, CCRIS, CTOS. POC memaparkan slot integrasi dan simulasi respons; sambungan production bergantung kebenaran agensi."*

**Tunjuk (jika masa):** `/admin/settings/integrasi` atau halaman Integrasi dalam menu Tetapan.

---

### 4.7 Penyerahan & Penjejakan Status (3.6) — **LIVE**

| Tindakan | URL |
|----------|-----|
| Senarai permohonan pemohon | `/pemohon/permohonan` |
| Timeline status | Klik permohonan → butiran |

**Tunjuk:** Nombor rujukan, status berwarna, garis masa workflow.

**Naratif:**

> *"Selepas hantar, pemohon jejak status secara real-time — Diterima, Dalam Semakan, Dokumen Tambahan, Lulus, Tolak."*

---

## 5. Modul 2 — Penilaian Risiko & Skor Kredit (~7 minit)

**Keperluan PDF:** Enjin pemarkahan, formula fleksibel, edge cases, dashboard penilai, pra-penilaian, kelulusan, surat tawaran.

### 5.1 Dashboard / Inbox Pegawai Penilai (2.1) — **LIVE**

| Tindakan | URL | Login |
|----------|-----|-------|
| Senarai penilaian | `/admin/pembiayaan/penilaian` | `admin@example.com` |
| Penilaian baharu | `/admin/pembiayaan/penilaian/baru` | |

**Tunjuk:** Senarai permohonan menunggu penilaian, filter, paparan berasaskan peranan.

---

### 5.2 AI Credit Scoring (2.3) — **LIVE**

| Tindakan | Lokasi |
|----------|--------|
| Panel AI Credit Scoring | `/admin/pembiayaan/penilaian/baru` → pilih permohonan → **Jana Skor Kredit** |

**Tunjuk:**
- Skor / gred risiko
- Cadangan had pembiayaan
- Kategori (Low / Medium / High / Borderline)

**Naratif:**

> *"AI berfungsi sebagai decision-support — pegawai kekal membuat keputusan muktamad. Skor dijana berdasarkan parameter matrik TEKUN: industri, aliran tunai, komitmen."*

---

### 5.3 Formula Pengiraan & Jadual Pelunasan (2.x Fleksibiliti) — **LIVE**

| Tindakan | Lokasi |
|----------|--------|
| Panel Formula Pembiayaan | Halaman Penilaian Baharu — **PembiayaanFormulaPanel** |

**Tunjuk:**
- Kadar flat vs reducing balance
- Jumlah, tempoh, keuntungan
- Jadual ansuran (amortization) auto-jana

**Naratif:**

> *"Formula boleh dikonfigurasi — flat rate atau baki berkurang — dan jadual pelunasan dijana automatik."*

---

### 5.4 Edge Case / Borderline (2.x Simulasi Kes Sempadan) — **LIVE/SIM**

**Tunjuk:** Permohonan dengan skor borderline → cadangan mitigasi (kurangkan amaun, penjamin tambahan) dalam panel penilaian.

**Naratif:**

> *"Untuk kes kelabu, sistem mencadangkan mitigasi — penstrukturan semula amaun atau keperluan penjamin — tanpa auto-lulus."*

---

### 5.5 Surat Tawaran PDF (2.5) — **LIVE**

| Tindakan | Lokasi |
|----------|--------|
| Muat turun Surat Tawaran | Penilaian Baharu → permohonan berstatus Lulus → **Muat Turun Surat Tawaran** |

**Tunjuk:** PDF `surat-tawaran.blade.php` dijana automatik.

**Naratif:**

> *"Selepas kelulusan, Surat Tawaran rasmi dijana automatik dalam PDF — sedia untuk cetak atau e-mel."*

---

## 6. Modul 3 — Aliran Kerja Kelulusan Berperingkat (~6 minit)

**Keperluan PDF:** Aliran dinamik Cawangan→HQ, notifikasi/escalation aging, matriks kuasa melulus.

### 6.1 Aliran Digital 3 Peringkat — **LIVE**

Gunakan permohonan seeder (contoh: `PM-2026-WF-SEM-001`) atau hantar permohonan baharu dari Modul 1.

| Peringkat | URL | Akaun | Tindakan demo |
|-----------|-----|-------|---------------|
| **Semakan** | `/admin/permohonan/semakan` | `penyemak1.hululangat@tekun.gov.my` | Buka permohonan → **Sahkan Semakan** + catatan |
| **Sokongan** | `/admin/permohonan/sokongan` | `penyokong.hululangat@tekun.gov.my` | **Sokong Permohonan** |
| **Kelulusan** | `/admin/permohonan/kelulusan` | `pelulus.ibupejabat@tekun.gov.my` | **Luluskan** / **Tolak** / **Kembalikan untuk penjelasan** |

**Tunjuk:**
- Fail digital bergerak peringkat demi peringkat — tiada muat turun manual
- Sejarah workflow / audit trail
- Scope cawangan (penyemak hanya lihat cawangan sendiri)

**Naratif:**

> *"Aliran TEKUN Niaga: Pegawai Cawangan (Semakan) → Pengurus/Penyokong (Sokongan) → Jawatankuasa Ibu Pejabat (Kelulusan). Semua dalam sistem, dengan jejak audit penuh."*

---

### 6.2 Kuasa Melulus / Approval Authority Matrix — **SIM/ROADMAP**

**Naratif:**

> *"Matriks kuasa melulus (contoh: Pengurus Cawangan sehingga RM10,000) boleh dikonfigurasi dalam Tetapan Setup. POC menunjukkan aliran berperingkat; had limit automatik adalah konfigurasi perniagaan dalam fasa production."*

**Tunjuk (jika masa):** `/admin/pembiayaan/tetapan` — parameter perniagaan.

---

### 6.3 Aging & Escalation — **SIM**

**Naratif:**

> *"Sistem direka untuk amaran visual dan eskalasi automatik jika fail melebihi SLA (contoh: 2 hari bekerja di cawangan). Notifikasi e-mel diaktifkan melalui workflow configuration."*

**Tunjuk (jika masa):** `/admin/workflow-configuration`

---

## 7. Modul 4 — Pengurusan Akaun & Pembayaran Balik (~6 minit)

**Keperluan PDF:** Penjadualan semula, multi-saluran bayaran, Ta'widh/penalti Syariah.

### 7.1 Pengurusan Akaun — **LIVE**

| Tindakan | URL |
|----------|-----|
| Senarai akaun | `/admin/pembiayaan/akaun` |

**Tunjuk:** Akaun contoh (cth. `TEK-KL-2024-0001`), baki pokok/keuntungan, status, klasifikasi NPL.

---

### 7.2 Penjadualan Semula — **LIVE**

| Tindakan | URL |
|----------|-----|
| Permohonan penjadualan | `/admin/pembiayaan/penjadualan` |

**Tunjuk:** Cipta permohonan moratorium / lanjutan tempoh → lulus penjadualan → jadual ansuran baharu.

**Naratif:**

> *"Moratorium, pindaan tempoh, atau penyusunan semula jadual — dengan kelulusan berperingkat."*

---

### 7.3 Simulasi Multi-Saluran Bayaran — **LIVE**

| Tindakan | URL |
|----------|-----|
| Terima bayaran | `/admin/pembayaran/terima` |

**Tunjuk:**
1. **Mod Masa Nyata:** FPX / JomPAY / e-Wallet / Auto Debit / Kaunter — rekod bayaran untuk akaun `TEK-KL-2024-0001`
2. **Mod Batch:** Muat naik CSV transaksi (simulasi fail kelompok dari saluran luar)

**Naratif:**

> *"Bayaran dikemaskini ke akaun secara real-time atau melalui batch processing — menyokong JomPAY, FPX, kaunter, dan auto-debit."*

---

### 7.4 Pengiraan Ta'widh / Penalti — **LIVE**

| Tindakan | URL |
|----------|-----|
| Pemantauan tunggakan | `/admin/pembayaran/tunggakan` |

**Tunjuk:** Tab klasifikasi + konfigurasi Ta'widh → **Kira Semula Ta'widh** → paparan penalti mengikut parameter Syariah.

**Naratif:**

> *"Caj lewat bayar (Ta'widh) dikira automatik mengikut parameter Lembaga Penasihat Syariah TEKUN."*

---

## 8. Modul 5 — Pemulihan & Kutipan Hutang (~4 minit)

**Keperluan PDF:** Klasifikasi NPL, dunning automatik, notis peringatan.

### 8.1 Klasifikasi Akaun NPL — **LIVE**

| Tindakan | URL |
|----------|-----|
| Pemantauan tunggakan | `/admin/pembayaran/tunggakan` → tab **Klasifikasi** |

**Tunjuk:**
- Lancar / Perhatian Khusus / Tidak Lancar (NPL)
- Aging analysis (hari tunggakan)
- **Kira Semula Klasifikasi NPL**

---

### 8.2 Dunning Automatik & Notis — **LIVE**

| Tindakan | URL |
|----------|-----|
| Tab Notis Kutipan | `/admin/pembayaran/tunggakan` → tab **Notis** |
| Proses dunning | Butang **Proses Dunning** |
| PDF notis | Muat turun Notis Peringatan 1/2/3 |

**Naratif:**

> *"Sistem menjana, menjadual, dan menghantar SMS amaran serta Surat Notis Peringatan secara automatik mengikut garis masa tunggakan."*

---

## 9. Modul 6 — Dashboard & Pelaporan Eksekutif (~3 minit)

**Keperluan PDF:** Dashboard KPI real-time, laporan ad-hoc, eksport.

### 9.1 Dashboard Utama — **LIVE**

| Tindakan | URL |
|----------|-----|
| Main dashboard | `/admin` |

**Tunjuk:** Ringkasan metrik SPPT (permohonan, akaun, kutipan — mengikut data seed).

---

### 9.2 Power BI / Analitik — **LIVE** (embed) / **SIM** (data production)

| Tindakan | URL |
|----------|-----|
| Dashboard Power BI | `/admin/laporan/power-bi` |
| Laporan & Analitik | `/admin/laporan` |

**Tunjuk:** Embed Power BI interaktif; penapis cawangan/negeri (jika dikonfigurasi dalam report).

**Naratif:**

> *"Pengurusan tertinggi mendapat insight KPI — agihan dana, kadar kutipan, taburan NPL — dengan penapis mengikut cawangan, daerah, negeri. Laporan ad-hoc boleh dieksport Excel/PDF."*

---

### 9.3 Audit Trail — **LIVE**

| Tindakan | URL |
|----------|-----|
| Audit trail | `/admin/platform/observability/audit-trail` |

**Naratif:** Pematuhan audit dalaman — setiap tindakan direkod.

---

## 10. AI & Inovasi (ringkas, 2 minit — boleh digabung Modul 2)

Rujuk `docs/SPPT_AI_Features.md`:

| # | Ciri | Status POC |
|---|------|------------|
| 1 | AI Loan Risk Scoring | **LIVE** — portal pemohon + penilaian |
| 2 | AI Document OCR + Validation | **SIM** — classify/verify endpoints |
| 3 | AI Fraud Detection | **ROADMAP** |
| 4 | AI Repayment Prediction | **SIM** — `/admin/pembayaran/ai` |
| 5 | AI Chatbot Pemohon | **LIVE** — `/pemohon/bantuan` |

**Naratif:**

> *"Semua AI adalah decision-support — pegawai kekal membuat keputusan muktamad mengikut prosedur TEKUN."*

---

## 11. Penutup & Sesi Soal Jawab (3 minit)

### Naratif

> *"Kesimpulannya, SPPT POC membuktikan:*
>
> 1. *Kemasukan data omnichannel dengan saringan automatik*
> 2. *Penilaian risiko berpandukan AI dan formula fleksibel*
> 3. *Kelulusan berperingkat tanpa bottleneck dokumen fizikal*
> 4. *Operasi akaun, bayaran, Ta'widh, dan kutipan bersepadu*
> 5. *Pelaporan eksekutif dan audit trail*
>
> *Kami bersedia menjawab soalan teknikal dan komersial."*

---

## Jadual A — Versi Ringkas (25 minit)

| Masa | Modul | Tindakan utama |
|------|-------|----------------|
| 0–2 | Pembukaan | Slaid + konteks |
| 2–8 | 1 | Portal → hard rules (lulus + tolak) → eKYC → hantar permohonan |
| 8–14 | 2+3 | Penilaian + AI score + formula → workflow 3 peringkat (3 login pantas) |
| 14–19 | 4 | Akaun → bayaran FPX → Ta'widh |
| 19–23 | 5+6 | NPL + dunning + Power BI |
| 23–25 | Penutup | Ringkasan + Q&A |

---

## Jadual B — Pemetaan Skrip PDF → Bukti Demo

| Keperluan PDF | Bukti dalam POC | Status |
|---------------|-----------------|--------|
| Kemasukan omnichannel (ejen + portal) | `/admin/permohonan/baru` + `/pemohon/permohonan/baru` | LIVE |
| Integrasi SSM/CCRIS/CTOS/muflis | Halaman Integrasi + simulasi semasa semakan | SIM |
| Auto-reject hard rules | `/pemohon/semakan-kelayakan` + API hard-rules | LIVE |
| Laman utama responsif | `/pemohon`, `/` | LIVE |
| Pendaftaran + OTP | `/pemohon/daftar` | LIVE |
| eKYC | `/pemohon/ekyc` | SIM |
| Borang dinamik + draf | Wizard permohonan | LIVE |
| Timeline status | Butiran permohonan | LIVE |
| Credit scoring engine | Penilaian Baharu + AI panel | LIVE |
| Formula flat/reducing + jadual | PembiayaanFormulaPanel | LIVE |
| Edge case borderline | Cadangan mitigasi dalam penilaian | LIVE |
| Workflow Cawangan→HQ | Semakan→Sokongan→Kelulusan | LIVE |
| Approval authority matrix | Tetapan Setup | SIM |
| Aging escalation | Workflow configuration | SIM |
| Surat Tawaran PDF | Muat turun dari penilaian | LIVE |
| Penjadualan semula | `/admin/pembiayaan/penjadualan` | LIVE |
| Multi-saluran + batch bayaran | `/admin/pembayaran/terima` | LIVE |
| Ta'widh Syariah | `/admin/pembayaran/tunggakan` | LIVE |
| Klasifikasi NPL | Tab klasifikasi tunggakan | LIVE |
| Dunning + Notis 1/2/3 | Proses dunning + PDF | LIVE |
| Dashboard KPI | `/admin` + Power BI | LIVE |
| Laporan ad-hoc + eksport | `/admin/laporan` | LIVE/SIM |

---

## Soalan Lazim — Cadangan Jawapan

| Soalan | Jawapan |
|--------|---------|
| *Integrasi JPN/SSM/APK sudah siap?* | Reka bentuk dan endpoint simulasi sedia. Production bergantung API rasmi dan MOU agensi — Fasa 2. |
| *AI ganti pegawai?* | Tidak. AI decision-support sahaja; pegawai kekal authority. |
| *198 cawangan — skala?* | Architecture Laravel + Vue SPA; RBAC per cawangan sudah ditunjuk (Hulu Langat vs Ibu Pejabat). |
| *Mobile app?* | Portal responsif web-first; app native dalam roadmap. |
| *Keselamatan data?* | RBAC, Sanctum session, audit trail, enkripsi — boleh elaborat dalam doc teknikal. |
| *Masa deploy penuh?* | Bergantung skop kontrak; modular rollout mengikut modul tender. |

---

## Pelan Kontingensi

| Masalah | Tindakan |
|---------|----------|
| API down | Tunjuk tangkapan skrin / video rakaman |
| OTP gagal | Guna log masuk demo pemohon atau skip ke aliran pegawai |
| Power BI tidak load | Tunjuk `/admin/laporan` statik + jelaskan embed |
| Workflow kosong | Pastikan `php artisan db:seed` — rujuk `PM-2026-WF-SEM-001` |
| Demo terlalu lama | Ikut Jadual A (25 min) — langkau eKYC & integrasi |

---

## Nota Pembentang

- **Jujur tentang SIM/ROADMAP** — jawatankuasa hargai ketelusan
- **Gunakan istilah TEKUN** — Tekun Niaga, Qard, Ta'widh, BPP, NPL
- **Dry run wajib** — uji 3 login workflow berturut-turut
- **Highlight alignment** — setiap modul rujuk skrip PDF PMO

---

*Dokumen ini selaras dengan `Skrip POC Petender SPPT - Final.pdf` dan keadaan codebase SPPT POC semasa. Kemas kini selepas perubahan major.*
