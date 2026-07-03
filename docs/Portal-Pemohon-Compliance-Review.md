# Portal Pemohon SPPT – Semakan Pematuhan Spesifikasi Tender

**URL Portal:** http://localhost:5180/portal  
**Rujukan:** Spec Modul Pendaftaran dan Permohonan.csv, Spec penilaian n kelulusan.csv  
**Tarikh Semakan:** Mac 2025

---

## Ringkasan Eksekutif

| Status | Bilangan |
|--------|----------|
| ✅ **Memenuhi** (UI/fungsi asas wujud) | 12 |
| ⚠️ **Separa** (UI ada, backend/integrasi belum) | 18 |
| ❌ **Belum** (tiada implementasi) | 45+ |

Portal pemohon pada masa ini menyediakan **struktur UI dan aliran asas** yang selari dengan spesifikasi, tetapi kebanyakan keperluan **backend, integrasi, AI, dan keselamatan** belum dilaksanakan.

---

## 1. MODUL PENDAFTARAN & PERMOHONAN

### 1.0 Akses Portal SPPT (Pendaftaran ID & Login)

| ID | Keperluan | Status | Catatan |
|----|-----------|--------|---------|
| 1.0.1 | Daftar akaun (Nama, MyKad, Email, No. Telefon) | ⚠️ Separa | Halaman `/portal/daftar` wujud sebagai placeholder; borang penuh belum |
| 1.0.2 | OTP melalui SMS/Email untuk pengesahan akaun | ❌ Belum | Tiada integrasi SMS/Email |
| 1.0.3 | Log masuk dengan ID & kata laluan | ⚠️ Separa | Halaman login wujud; logik simulasi sahaja, tiada API |
| 1.0.4 | 2FA (OTP / authenticator) | ❌ Belum | Tiada 2FA |
| 1.0.5 | Fraud Detection | ❌ Belum | Tiada |
| 1.0.6 | Audit trail pendaftaran & login | ❌ Belum | Tiada |
| 1.0.7 | Integrasi JPN semasa kemasukan MyKad | ❌ Belum | Tiada |
| 1.0.8 | eKYC standard | ❌ Belum | Tiada |
| 1.0.9 | Integrasi APK (EXPERIAN, CTOS, CCRIS) | ❌ Belum | Tiada |
| 1.0.10 | Pendaftaran syarikat multi-pemilik | ⚠️ Separa | Borang Permohonan Baru ada pilihan Individu/Syarikat |
| 1.0.11 | Primary Account Holder | ❌ Belum | Tiada |
| 1.0.12 | RBAC untuk pengarah tambahan | ❌ Belum | Tiada |
| 1.0.13 | Pengesahan JPN/MyGDX, notifikasi transaksi | ❌ Belum | Tiada |

### 1.1 AI Chatbot / Virtual Assistant

| ID | Keperluan | Status | Catatan |
|----|-----------|--------|---------|
| 1.1.1–1.1.13 | Chatbot akses web/mobile, NLP BM/BI, Onboarding, integrasi modul, AI Recommendation, audit trail, handover ke pegawai | ⚠️ Separa | Halaman `/portal/bantuan` ada UI chat & FAQ; tiada AI/NLP sebenar |

### 1.2 Pendaftaran Maklumat Pemohon (Individu/Syarikat)

| ID | Keperluan | Status | Catatan |
|----|-----------|--------|---------|
| 1.2.1 | Borang elektronik (Nama, MyKad, Alamat, dll), responsive, auto-save, validation | ⚠️ Separa | Permohonan Baru ada borang lengkap BPP; tiada auto-save, tiada API |
| 1.2.2 | Pilihan Individu / Syarikat | ✅ Memenuhi | Ada dalam wizard |
| 1.2.3 | Borang dinamik mengikut kategori | ⚠️ Separa | Medan berubah untuk Syarikat; boleh diperkukuh |
| 1.2.4 | Integrasi MyGDX, JPN, SSM, APK | ❌ Belum | Tiada |
| 1.2.5 | AI eKYC, liveness detection | ❌ Belum | Tiada |
| 1.2.6 | Duplicate record check | ❌ Belum | Tiada |
| 1.2.7 | Auto-fill dari pangkalan data TEKUN | ❌ Belum | Tiada |
| 1.2.8 | MSSQL, AES-256, TLS 1.3 | ❌ Belum | Tiada pangkalan data |
| 1.2.9 | Audit trail | ❌ Belum | Tiada |
| 1.2.10 | Dwibahasa BM/BI | ⚠️ Separa | UI BM; pemilih bahasa di login ada tetapi tiada i18n penuh |
| 1.2.11 | Auto-notification + Application Reference ID | ❌ Belum | Tiada |

### 1.3 Pemilihan Jenis Perniagaan

| ID | Keperluan | Status | Catatan |
|----|-----------|--------|---------|
| 1.3.1 | Kategori: Patuh Syariah, Tidak Patuh, Bercampur, Lain-lain | ⚠️ Separa | Ada dalam borang Permohonan Baru |
| 1.3.2–1.3.7 | Senarai dinamik, dokumen sokongan, audit trail, integrasi | ❌ Belum | Tiada |

### 1.4 Pemilihan Jenis Produk Pembiayaan

| ID | Keperluan | Status | Catatan |
|----|-----------|--------|---------|
| 1.4.1 | Pilih produk (SPUMI, Kontrak-i, Tekun Niaga) | ✅ Memenuhi | Ada dropdown |
| 1.4.2 | AI Recommendation Engine | ❌ Belum | Tiada |
| 1.4.3 | Paparan perbandingan produk | ❌ Belum | Tiada |
| 1.4.4–1.4.9 | Filter, status syariah, konfigurasi AI, audit trail, mobile-first | ❌ Belum | Tiada |

### 1.5 Profil Usahawan Digital

| ID | Keperluan | Status | Catatan |
|----|-----------|--------|---------|
| 1.5.1 | Profil dijana selepas pendaftaran | ⚠️ Separa | Halaman `/portal/profil` ada; data dummy |
| 1.5.2 | Maklumat lengkap, produk yang dipohon, status kelulusan | ⚠️ Separa | UI ada; data dummy |
| 1.5.3 | Status pelbagai peringkat (diterima, dalam semakan, lulus, dll) | ✅ Memenuhi | Status dipaparkan dalam Permohonan Saya & Profil |
| 1.5.4 | Document checklist | ✅ Memenuhi | Ada dalam Profil Usahawan |
| 1.5.5 | Notifikasi automatik | ❌ Belum | Tiada |
| 1.5.6 | Audit trail | ❌ Belum | Tiada |

### 1.6 Semakan Kelayakan Awal

| ID | Keperluan | Status | Catatan |
|----|-----------|--------|---------|
| 1.6.1–1.6.6 | Integrasi APK/SSM, AI Risk Scoring, dashboard, rule-based validation | ❌ Belum | Proses ini untuk pegawai; tiada dalam portal pemohon |

### 1.7 Muat Naik Dokumen Sokongan

| ID | Keperluan | Status | Catatan |
|----|-----------|--------|---------|
| 1.7.1 | Muat naik dokumen (IC, Lesen, Penyata Bank) | ⚠️ Separa | UI drag-and-drop ada dalam Permohonan Baru; tiada simpanan |
| 1.7.2 | AI-OCR | ❌ Belum | Tiada |
| 1.7.3 | Drag-and-drop, batch upload | ✅ Memenuhi | Ada |
| 1.7.4–1.7.10 | Validation, AI Document Classifier, watermark, RBAC, version control, audit trail, notifikasi | ❌ Belum | Tiada |

### 1.8 Penjadualan Temuduga

| ID | Keperluan | Status | Catatan |
|----|-----------|--------|---------|
| 1.8.1 | Pemohon pilih slot (self-service) | ⚠️ Separa | Halaman `/portal/temuduga` ada; tiada integrasi kalendar |
| 1.8.2 | Modul penjadualan, notifikasi SMS/Email | ❌ Belum | Tiada |
| 1.8.3–1.8.9 | Kalendar, integrasi Google Calendar, auto-rescheduling, mod fizikal/maya, audit trail | ❌ Belum | Tiada |

### 1.9 Notifikasi & Audit Trail

| ID | Keperluan | Status | Catatan |
|----|-----------|--------|---------|
| 1.9.1–1.9.10 | Notifikasi automatik, multi-channel, template, audit trail, anomaly detection, export, tamper-proof | ❌ Belum | Tiada |

---

## 2. MODUL PENILAIAN & KELULUSAN (Pemohon)

*Nota: Modul Penilaian & Kelulusan kebanyakannya untuk pegawai TEKUN. Portal pemohon terlibat dari segi:*

| Rujukan | Keperluan | Status | Catatan |
|---------|-----------|--------|---------|
| 2.4.2 | Status keputusan di portal pemohon dengan no. rujukan & tarikh | ⚠️ Separa | Permohonan Saya & Profil papar status; data dummy |
| 2.5.3 | Pemohon terima surat tawaran melalui portal + email | ❌ Belum | Tiada |
| 2.5.7 | Workflow penerimaan: Terima / Tolak / Mohon pindaan | ❌ Belum | Tiada |

---

## 3. Perbandingan: Portal Semasa vs Spesifikasi

### Yang Memenuhi / Separa Memenuhi

| Fungsi | Status |
|--------|--------|
| Laman Utama dengan ringkasan permohonan | ✅ |
| Permohonan Baru (wizard 12 langkah, borang BPP) | ✅ |
| Permohonan Saya (senarai, filter, status) | ✅ |
| Profil Usahawan (maklumat, checklist dokumen) | ✅ |
| Temuduga (senarai, filter) | ⚠️ UI sahaja |
| Bantuan (chat UI, FAQ) | ⚠️ UI sahaja |
| Login / Daftar (halaman) | ⚠️ UI sahaja |
| Pilihan Individu/Syarikat | ✅ |
| Muat naik dokumen (drag-and-drop) | ✅ |
| Status permohonan (diterima, dalam semakan, lulus, dll) | ✅ |

### Yang Belum

| Kategori | Contoh |
|----------|--------|
| **Autentikasi** | OTP, 2FA, Fraud Detection |
| **Integrasi** | JPN, MyGDX, SSM, APK (EXPERIAN, CTOS, CCRIS) |
| **AI** | eKYC, Chatbot NLP, AI Recommendation, OCR, Document Classifier |
| **Backend** | Pangkalan data, API, audit trail |
| **Notifikasi** | SMS, Email, WhatsApp, Push |
| **Keselamatan** | Enkripsi, RBAC, tamper-proof logging |

---

## 4. Cadangan Keutamaan

### Fasa 1 (Asas)
1. **Pendaftaran & Login** – Borang daftar lengkap, API auth, simpan pengguna
2. **Permohonan** – Simpan permohonan ke DB, Application Reference ID
3. **Notifikasi** – Email asas untuk pendaftaran & status permohonan

### Fasa 2 (Integrasi)
4. **Integrasi** – JPN/MyGDX, SSM, APK (jika boleh)
5. **OTP & 2FA** – Pengesahan akaun & log masuk
6. **Temuduga** – Kalendar, pilih slot, notifikasi

### Fasa 3 (AI & Lanjutan)
7. **AI Chatbot** – NLP atau integrasi dengan perkhidmatan AI
8. **eKYC** – Jika ada perkhidmatan pihak ketiga
9. **Audit trail** – Log semua aktiviti

---

## 5. Kesimpulan

Portal pemohon pada `/portal` **memenuhi struktur dan aliran UI** yang dikehendaki dalam spesifikasi tender untuk Modul Pendaftaran & Permohonan. Halaman utama, permohonan, profil, temuduga, dan bantuan sudah wujud dengan layout dan dummy data.

Namun, **kebanyakan keperluan mandatori** yang melibatkan integrasi, AI, keselamatan, dan audit trail **belum dilaksanakan**. Untuk pematuhan penuh tender, perlu:

- Backend API & pangkalan data
- Integrasi dengan JPN, SSM, APK
- AI Chatbot, eKYC, OCR
- OTP, 2FA, notifikasi SMS/Email
- Audit trail

**Status semasa:** Portal sesuai untuk **prototaip UI/UX** dan **uji aliran pengguna**. Untuk **production** dan **pematuhan tender penuh**, perlu dilengkapkan keperluan backend dan integrasi di atas.
