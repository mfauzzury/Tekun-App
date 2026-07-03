# Portal Pemohon SPPT – Cadangan Menu

**URL:** `http://localhost:5180/portal`  
**Pemilik:** Pemohon / Usahawan (bukan staff TEKUN)  
**Fungsi utama:** Hantar permohonan pembiayaan dan semak status permohonan

---

## Ringkasan Menu

| # | Menu | Path | Rujukan Spec | Penerangan |
|---|------|------|--------------|------------|
| 1 | Laman Utama | `/portal` | 1.5.1, 1.5.2 | Dashboard pemohon – ringkasan permohonan, status terkini, tindakan seterusnya |
| 2 | Permohonan Baru | `/portal/permohonan/baru` | 1.2, 1.3, 1.4, 1.7 | Wizard permohonan: daftar maklumat → pilih produk → muat naik dokumen → hantar |
| 3 | Permohonan Saya | `/portal/permohonan` | 1.5.3, 1.5.5 | Senarai permohonan dengan status (diterima, dalam semakan, lulus, tidak lulus, dll.) |
| 4 | Profil Usahawan | `/portal/profil` | 1.5.1–1.5.6 | Profil digital, checklist dokumen, status syariah, maklumat peribadi & perniagaan |
| 5 | Temuduga | `/portal/temuduga` | 1.8.1, 1.8.2 | Jadual temuduga – pilih slot, semak jadual, tukar tarikh (self-service) |
| 6 | Bantuan | `/portal/bantuan` | 1.1.1–1.1.13 | AI Chatbot / Virtual Assistant – bantuan pendaftaran, dokumen, status permohonan |

---

## Butiran Setiap Menu

### 1. Laman Utama (`/portal`)
- Ringkasan status permohonan terkini
- Tindakan seterusnya (contoh: lengkapkan dokumen, pilih slot temuduga)
- Notifikasi penting
- Pautan pantas ke Permohonan Baru dan Permohonan Saya

### 2. Permohonan Baru (`/portal/permohonan/baru`)
Aliran wizard mengikut spec:
1. **Pilih kategori** – Individu / Syarikat (1.2.2, 1.2.3)
2. **Maklumat pemohon** – Nama, MyKad, alamat, maklumat syarikat (1.2.1)
3. **Jenis perniagaan** – Patuh Syariah / Tidak Patuh / Bercampur (1.3.1)
4. **Produk pembiayaan** – SPUMI, Kontrak-i, Tekun Niaga, dll. (1.4.1)
5. **Muat naik dokumen** – IC, Lesen Perniagaan, Penyata Bank, dll. (1.7.1)
6. **Semakan & hantar** – Semak maklumat, hantar permohonan

### 3. Permohonan Saya (`/portal/permohonan`)
- Senarai semua permohonan dengan status
- Status: Diterima, Dalam Semakan, Dokumen Tambahan Diperlukan, Lulus, Tidak Lulus
- Nombor rujukan permohonan
- Klik untuk lihat butiran / semak status

### 4. Profil Usahawan (`/portal/profil`)
- Maklumat peribadi & perniagaan
- Checklist dokumen (dimuat naik / menunggu semakan / diperlukan)
- Status syariah
- Senarai produk yang dipohon & status kelulusan
- Kemaskini maklumat (jika dibenarkan)

### 5. Temuduga (`/portal/temuduga`)
- Pilih slot temuduga (self-service)
- Lihat jadual temuduga akan datang
- Tukar tarikh / masa (jika dibenarkan)
- Mod fizikal / maya (online)

### 6. Bantuan (`/portal/bantuan`)
- AI Chatbot / Virtual Assistant
- NLP BM & BI
- Bantuan pendaftaran, muat naik dokumen, pilihan produk, semakan status
- Handover ke pegawai jika perlu

---

## Akses & Autentikasi (di luar menu utama)

| Laman | Path | Rujukan Spec |
|-------|------|--------------|
| Daftar Akaun | `/portal/daftar` | 1.0.1 – Nama, MyKad, Email, No. Telefon |
| Log Masuk | `/portal/login` | 1.0.3 – ID & kata laluan |
| Pengesahan OTP | (selepas daftar / log masuk) | 1.0.2, 1.0.4 – 2FA |

---

## Notifikasi (bukan item menu)

- Notifikasi dalam header (ikon loceng)
- SMS / Email untuk transaksi penting (1.9.1–1.9.4)
- Halaman pusat notifikasi: `/portal/notifikasi` (pilihan)

---

## Struktur URL Ringkas

```
/portal                    → Laman Utama
/portal/login              → Log Masuk
/portal/daftar             → Daftar Akaun
/portal/permohonan         → Permohonan Saya (senarai)
/portal/permohonan/baru    → Permohonan Baru (wizard)
/portal/permohonan/:id     → Butiran permohonan
/portal/profil             → Profil Usahawan
/portal/temuduga           → Temuduga
/portal/bantuan            → Bantuan / Chatbot
/portal/notifikasi         → (Opsional) Pusat notifikasi
```

---

## Perbezaan dengan Portal Staff

| Aspek | Portal Staff (`/admin`) | Portal Pemohon (`/portal`) |
|-------|-------------------------|----------------------------|
| Pengguna | Pegawai TEKUN | Pemohon / Usahawan |
| Fungsi | Urus permohonan, penilaian, kelulusan, pembayaran, laporan | Hantar permohonan, semak status, jadual temuduga |
| Autentikasi | Admin login | Daftar + OTP + 2FA |
| Menu | Pengurusan Pembiayaan, Pentadbiran, dll. | Permohonan, Profil, Temuduga, Bantuan |
