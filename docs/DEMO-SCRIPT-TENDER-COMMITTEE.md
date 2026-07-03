# Demo Script: Sistem Pengurusan Pembiayaan TEKUN (SPPT)

**Untuk:** Jawatankuasa Tender TEKUN Nasional  
**Tempoh Cadangan:** 25–30 minit  
**Tarikh:** _______________  
**Pembentang:** _______________

---

## Persiapan Sebelum Demo

- [ ] Pastikan `npm run dev:cms` berjalan (API: port 4000, Web: port 5180)
- [ ] Buka browser: `http://localhost:5180` (Storefront) dan `http://localhost:5180/admin` (Admin)
- [ ] Portal Pemohon: `http://localhost:5180/portal`
- [ ] Login Admin: `admin@example.com` / `admin12345`
- [ ] Sediakan skrin bersih, tutup tab tidak berkaitan
- [ ] Uji sambungan internet (untuk integrasi masa depan)
- [ ] Sediakan backup: tangkapan skrin / video jika demo gagal

---

## Bahagian 1: Pembukaan & Nilai Usaha (3 minit)

### Naratif Pembentang

> *"Yang Berbahagia Tuan Pengerusi, ahli jawatankuasa tender yang dihormati,*
>
> *Terima kasih atas peluang membentang penyelesaian kami untuk Sistem Pengurusan Pembiayaan TEKUN — SPPT.*
>
> *SPPT direka untuk menyokong keseluruhan kitaran pembiayaan TEKUN — daripada permohonan usahawan sehingga pemantauan bayaran balik dan pelaporan. Kami menawarkan tiga nilai utama:*
>
> 1. **Digitalisasi Bersepadu** — Satu platform untuk pegawai dan pemohon, mengurangkan kerja berulang dan ralat manusia.
> 2. **AI-Assisted Decision Support** — Sokongan keputusan pintar untuk penilaian risiko, pengesahan dokumen, dan pengesanan penipuan — tanpa menggantikan kuasa pegawai.
> 3. **Pematuhan & Audit Trail** — Rekod lengkap, kawalan akses berasaskan peranan, dan kesediaan untuk integrasi JPN, SSM, APK.
>
> *Saya akan tunjukkan aliran pengguna dari perspektif pemohon dan pegawai, diikuti oleh visi AI dan integrasi.*"

---

## Bahagian 2: Portal Pemohon — Pengalaman Usahawan (8 minit)

### 2.1 Laman Utama & Login (2 minit)

**Tindakan:** Buka `/portal` atau `/portal/login`

**Naratif:**

> *"Pemohon mula di Portal Pemohon. Mereka boleh daftar akaun baru atau log masuk. Antara muka direka responsif — boleh digunakan di desktop dan mudah alih."*

**Tunjuk:**
- Halaman login yang bersih
- Pautan Daftar Akaun
- *(Jika ada)* Pemilih bahasa BM/BI

---

### 2.2 Permohonan Baru — Wizard 12 Langkah (4 minit)

**Tindakan:** Klik **Permohonan Baru** → `/portal/permohonan/baru`

**Naratif:**

> *"Permohonan baru mengikut borang BPP TEKUN dalam format wizard langkah demi langkah. Ini mengurangkan rasa tertekan pemohon dan memastikan data lengkap."*

**Tunjuk mengikut langkah:**

| Langkah | Apa yang ditunjuk | Naratif ringkas |
|---------|-------------------|-----------------|
| 1 | Pilih **Individu** atau **Syarikat** | *"Pemohon pilih kategori — borang berubah secara dinamik mengikut pilihan."* |
| 2 | Borang maklumat asas (Nama, MyKad, alamat, dll.) | *"Medan selari dengan BPP-01 — maklumat asas, pasangan, perniagaan."* |
| 3 | Jenis perniagaan (Patuh Syariah / Tidak Patuh / Bercampur) | *"Sokongan penuh untuk pemilihan syariah."* |
| 4 | Produk pembiayaan (SPUMI, Kontrak-i, Tekun Niaga, dll.) | *"Semua skim TEKUN disokong — Qard, TEMAN TEKUN, SPUMI, dan lain-lain."* |
| 5 | Muat naik dokumen (IC, Lesen, Penyata Bank) | *"Drag-and-drop muat naik — sokongan batch. Rancangan: AI-OCR untuk auto-extract data."* |
| 6 | Semakan & Hantar | *"Pemohon semak maklumat sebelum hantar. Rujukan permohonan dijana automatik."* |

**Penekanan:**

> *"Struktur borang mematuhi BPP-01 TEKUN. Untuk fasa seterusnya, kami merancang auto-fill dari MyGDX, JPN, SSM, dan pengesahan APK (EXPERIAN, CTOS, CCRIS) semasa kemasukan data."*

---

### 2.3 Permohonan Saya & Profil Usahawan (2 minit)

**Tindakan:** Klik **Permohonan Saya** → `/portal/permohonan`

**Naratif:**

> *"Selepas hantar, pemohon boleh semak semua permohonan di satu tempat. Status dipaparkan dengan jelas: Diterima, Dalam Semakan, Dokumen Tambahan Diperlukan, Lulus, Tidak Lulus."*

**Tunjuk:**
- Senarai permohonan dengan status berwarna
- Filter mengikut status
- Nombor rujukan permohonan

**Tindakan:** Klik **Profil Usahawan** → `/portal/profil`

**Naratif:**

> *"Profil usahawan digital menyimpan maklumat lengkap, checklist dokumen, dan status syariah. Ini memudahkan pemohon tahu apa yang sudah lengkap dan apa yang masih perlu."*

---

## Bahagian 3: Portal Admin — Pengurusan Pegawai (7 minit)

### 3.1 Login Admin & Dashboard (2 minit)

**Tindakan:** Buka `/admin/login` → Log masuk `admin@example.com` / `admin12345`

**Naratif:**

> *"Pegawai TEKUN mengakses portal pentadbiran. Akses dikawal mengikut peranan — Pentadbir, Pegawai Operasi, Penilai, Kewangan, Pemantauan, Undang-undang."*

**Tunjuk:**
- Dashboard admin
- Menu utama (Posts, Pages, Categories, Media, Settings)
- *(Jika ada)* Modul pembiayaan / permohonan

---

### 3.2 Modul Pembiayaan — Aliran Kerja (3 minit)

**Naratif:**

> *"SPPT menyokong sepuluh modul utama mengikut SRS:*
>
> - *Pendaftaran & Permohonan*
> - *Penilaian & Kelulusan*
> - *Pengeluaran Dana*
> - *Pengurusan Akaun*
> - *Bayaran Pembiayaan*
> - *Pemantauan & Kutipan*
> - *Undang-Undang & Litigasi*
> - *Laporan & Analitik*
> - *Audit & Kawalan Dalaman*
> - *Integrasi Pihak Ketiga*
> - *Pengurusan Jaminan*
> - *Pemantauan Usahawan*
>
> *Setiap modul mengikut aliran: Input → Validation → Approval → Update → Notification. Ini memastikan konsistensi dan audit trail."*

**Tunjuk:** *(Sesuaikan dengan apa yang sedia ada dalam UI)*
- Senarai permohonan untuk penilaian
- Status workflow
- Butang kelulusan / penolakan

---

### 3.3 Laporan & Analitik (2 minit)

**Naratif:**

> *"Modul Laporan menyokong:*
> - *Laporan Statistik Pembiayaan*
> - *Laporan Prestasi Bayaran Balik*
> - *Laporan Tunggakan*
> - *Laporan Prestasi Pegawai*
> - *Laporan Pemantauan Usahawan*
>
> *Data boleh dieksport untuk mesyuarat pengurusan dan pematuhan."*

---

## Bahagian 4: AI & Inovasi — Sokongan Keputusan (5 minit)

### Naratif (Slide / Paparan)

> *"SPPT dirancang dengan lima ciri AI sebagai sokongan keputusan — bukan pengganti pegawai:*
>
> | Ciri AI | Fungsi | Manfaat |
> |---------|--------|---------|
> | **1. AI Loan Risk Scoring** | Skor risiko automatik berdasarkan data sejarah | Penilaian lebih pantas, konsisten |
> | **2. AI Document OCR + Validation** | Ekstrak data dari IC, penyata bank, lesen | Kurangkan kerja manual, kurang ralat |
> | **3. AI Fraud Detection** | Pengesanan identiti duplikat, akaun berulang, anomali | Lindungi dana, pematuhan audit |
> | **4. AI Repayment Prediction** | Ramalan bayaran lewat / default | Campur tangan awal, kurangkan NPL |
> | **5. AI Chatbot** | Bantuan BM/BI untuk pemohon | Kurangkan beban pusat panggilan |
>
> *Semua AI berfungsi sebagai decision-support — pegawai tetap membuat keputusan muktamad."*

**Tunjuk:** Halaman Bantuan `/portal/bantuan` — UI Chatbot & FAQ

> *"Ini adalah asas untuk AI Chatbot — sokongan NLP BM/BI dan handover ke pegawai akan ditambah dalam fasa integrasi."*

---

## Bahagian 5: Keselamatan, Pematuhan & Teknikal (4 minit)

### Naratif

> *"Dari segi teknikal dan pematuhan:*
>
> - **Keselamatan:** Role-based access control (RBAC), audit trail, enkripsi data
> - **Prestasi:** Masa tindak balas kurang 10 saat (sasaran SRS)
> - **Ketersediaan:** Sokongan operasi harian, sandaran berkala
> - **Integrasi:** Reka bentuk sedia untuk JPN, MyGDX, SSM, APK (EXPERIAN, CTOS, CCRIS)
> - **Stack:** Vue 3, Express, Prisma, SQLite (dev) — boleh skala ke MSSQL/PostgreSQL
>
> *Kami faham keperluan TEKUN untuk pematuhan penuh dan kesediaan audit."*

---

## Bahagian 6: Penutup & Soalan (3 minit)

### Naratif

> *"Kesimpulannya, SPPT menawarkan:*
>
> 1. **Pengalaman pemohon yang baik** — wizard jelas, status transparen, sokongan chatbot
> 2. **Kecekapan pegawai** — workflow bersepadu, AI sokongan keputusan, laporan automatik
> 3. **Pematuhan & kawalan** — audit trail, RBAC, integrasi pihak ketiga
> 4. **Jalan evolusi jelas** — fasa asas, integrasi, AI lanjutan
>
> *Kami bersedia menjawab sebarang soalan dan menyediakan butiran teknikal atau komersial mengikut keperluan jawatankuasa."*

---

## Senarai Semak Selepas Demo

- [ ] Jawab soalan dengan yakin dan jujur
- [ ] Jika ada yang belum dibina: nyatakan sebagai "rancangan fasa seterusnya" dan tunjukkan roadmap
- [ ] Catat soalan yang memerlukan susulan bertulis
- [ ] Ucap terima kasih dan tinggalkan bahan sokongan (jika ada)

---

## Cadangan Jawapan untuk Soalan Lazim

| Soalan | Cadangan Jawapan |
|--------|------------------|
| *"Adakah integrasi JPN/SSM/APK sudah siap?"* | "Reka bentuk dan spesifikasi sedia. Integrasi bergantung kepada API dan kebenaran pihak berkenaan. Kami merancang dalam Fasa 2." |
| *"Berapa lama masa untuk deploy penuh?"* | "Bergantung skop fasa. Fasa 1 (asas) boleh dalam X bulan; integrasi dan AI dalam fasa seterusnya." |
| *"Adakah AI menggantikan pegawai?"* | "Tidak. AI berfungsi sebagai decision-support. Pegawai tetap membuat keputusan muktamad mengikut prosedur TEKUN." |
| *"Bagaimana dengan keselamatan data?"* | "Enkripsi, RBAC, audit trail, dan pematuhan standard industri. Boleh dibincang terperinci dalam dokumen teknikal." |
| *"Sokongan mobile?"* | "Portal responsif. Aplikasi mudah alih (React Native/Expo) dalam stack — boleh dikembangkan untuk pemohon." |

---

## Nota untuk Pembentang

- **Bercakap dengan yakin** — tunjukkan pemahaman mendalam tentang proses TEKUN
- **Jangan over-promise** — nyatakan dengan jelas apa yang siap vs rancangan
- **Highlight alignment** — sentiasa kaitkan dengan BPP, SRS, dan keperluan tender
- **Gunakan istilah TEKUN** — Pembiayaan, Qard, TEMAN TEKUN, SPUMI, BPP, dll.
- **Bersedia untuk demo live** — pastikan persekitaran stabil sebelum hari demo

---

*Dokumen ini disediakan sebagai panduan. Sesuaikan mengikut masa yang diperuntukkan dan keutamaan jawatankuasa.*
