SPESIFIKASI KEPERLUAN SISTEM (SRS) – VERSI DIPERLUAS

Sistem Pengurusan Pembiayaan TEKUN (SPPT)

Dokumen ini mengandungi process flow terperinci dan screen specification (UI field list) bagi setiap modul.

# Modul Pendaftaran

## Process Flow

1. Pegawai log masuk ke sistem

2. Pegawai memilih menu 'Permohonan Baharu'

3. Maklumat usahawan dimasukkan

4. Dokumen sokongan dimuat naik

5. Sistem melakukan pengesahan data

6. Permohonan dihantar untuk penilaian

## Screen Specification (UI Field List)

| Field | Jenis Input |
| --- | --- |
| Nama Usahawan | Text |
| No Kad Pengenalan | Text |
| Alamat | Textarea |
| No Telefon | Text |
| Jenis Perniagaan | Dropdown |
| Jumlah Pembiayaan Dimohon | Number |
| Dokumen Sokongan | File Upload |

# Modul Penilaian & Kelulusan

## Process Flow

1. Pegawai penilai menerima permohonan

2. Maklumat usahawan disemak

3. Penilaian kredit dilakukan

4. Cadangan jumlah pembiayaan dimasukkan

5. Permohonan dihantar kepada pegawai kelulusan

6. Pegawai kelulusan meluluskan atau menolak

## Screen Specification (UI Field List)

| Field | Jenis Input |
| --- | --- |
| ID Permohonan | Text |
| Nama Usahawan | Text |
| Skor Penilaian | Number |
| Cadangan Pembiayaan | Number |
| Status Kelulusan | Dropdown |
| Catatan Pegawai | Textarea |

# Modul Pengeluaran Dana

## Process Flow

1. Pegawai kewangan menerima permohonan yang diluluskan

2. Sistem menjana rekod pembayaran

3. Maklumat akaun bank usahawan disahkan

4. Transaksi pembayaran diproses

5. Sistem merekodkan transaksi

## Screen Specification (UI Field List)

| Field | Jenis Input |
| --- | --- |
| ID Pembiayaan | Text |
| Nama Usahawan | Text |
| Jumlah Pembiayaan | Number |
| Bank | Dropdown |
| No Akaun Bank | Text |
| Tarikh Pengeluaran | Date |

# Modul Pengurusan Akaun

## Process Flow

1. Sistem mencipta akaun pembiayaan

2. Jadual pembayaran dijana

3. Pegawai boleh mengemaskini maklumat akaun

4. Sistem memantau baki pembiayaan

## Screen Specification (UI Field List)

| Field | Jenis Input |
| --- | --- |
| ID Akaun | Text |
| Nama Usahawan | Text |
| Jumlah Pembiayaan | Number |
| Tempoh Pembiayaan | Number |
| Kadar Keuntungan | Number |
| Baki Semasa | Number |

# Modul Bayaran Pembiayaan

## Process Flow

1. Usahawan membuat pembayaran

2. Sistem menerima rekod transaksi

3. Pegawai mengesahkan pembayaran

4. Sistem mengemaskini baki pembiayaan

## Screen Specification (UI Field List)

| Field | Jenis Input |
| --- | --- |
| ID Pembayaran | Text |
| ID Akaun | Text |
| Jumlah Bayaran | Number |
| Tarikh Bayaran | Date |
| Kaedah Bayaran | Dropdown |

# Modul Pemantauan & Kutipan

## Process Flow

1. Sistem mengenalpasti akaun tertunggak

2. Pegawai pemantauan membuat semakan

3. Tindakan kutipan direkodkan

4. Status akaun dikemaskini

## Screen Specification (UI Field List)

| Field | Jenis Input |
| --- | --- |
| ID Akaun | Text |
| Nama Usahawan | Text |
| Jumlah Tunggakan | Number |
| Tarikh Pemantauan | Date |
| Tindakan | Textarea |

# Modul Undang‑Undang & Litigasi

## Process Flow

1. Kes pembiayaan gagal bayar dikenalpasti

2. Fail kes diwujudkan

3. Pegawai undang-undang mengemaskini status

4. Sistem merekodkan perkembangan kes

## Screen Specification (UI Field List)

| Field | Jenis Input |
| --- | --- |
| ID Kes | Text |
| Nama Usahawan | Text |
| Jumlah Tuntutan | Number |
| Tarikh Fail Kes | Date |
| Status Kes | Dropdown |

# Modul Laporan & Analitik

## Process Flow

1. Pengguna memilih jenis laporan

2. Sistem menjana laporan

3. Data dipaparkan dalam dashboard

4. Pengguna boleh memuat turun laporan

## Screen Specification (UI Field List)

| Field | Jenis Input |
| --- | --- |
| Jenis Laporan | Dropdown |
| Tarikh Dari | Date |
| Tarikh Hingga | Date |
| Cawangan | Dropdown |

# Modul Audit & Kawalan Dalaman

## Process Flow

1. Sistem merekod aktiviti pengguna

2. Pentadbir menyemak audit log

3. Rekod audit disimpan untuk rujukan

## Screen Specification (UI Field List)

| Field | Jenis Input |
| --- | --- |
| ID Log | Text |
| Pengguna | Text |
| Aktiviti | Text |
| Tarikh | Date |
| Alamat IP | Text |

# Modul Integrasi Pihak Ketiga

## Process Flow

1. Sistem menerima data dari sistem luar

2. API memproses data

3. Rekod integrasi disimpan

## Screen Specification (UI Field List)

| Field | Jenis Input |
| --- | --- |
| ID Integrasi | Text |
| Sistem Sumber | Text |
| Jenis Transaksi | Text |
| Tarikh Integrasi | Date |
| Status | Dropdown |

# Modul Pengurusan Jaminan

## Process Flow

1. Maklumat cagaran dimasukkan

2. Sistem menyimpan maklumat jaminan

3. Pegawai boleh mengemaskini maklumat

## Screen Specification (UI Field List)

| Field | Jenis Input |
| --- | --- |
| ID Jaminan | Text |
| Jenis Jaminan | Dropdown |
| Nilai Jaminan | Number |
| Tarikh Daftar | Date |

# Modul Pemantauan Usahawan

## Process Flow

1. Pegawai membuat lawatan pemantauan

2. Maklumat prestasi perniagaan direkodkan

3. Sistem menyimpan laporan lawatan

## Screen Specification (UI Field List)

| Field | Jenis Input |
| --- | --- |
| ID Lawatan | Text |
| Nama Usahawan | Text |
| Tarikh Lawatan | Date |
| Status Perniagaan | Dropdown |
| Catatan Lawatan | Textarea |