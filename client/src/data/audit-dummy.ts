/**
 * Dummy data for Audit & Kawalan - SPEC 9.x
 * 9.2 Audit Trail, 9.3 Log Sistem, 9.5 Pengesanan Anomali, 9.6 AI Risk
 */

// --- 9.2.2: Audit Trail - ID pengguna, nama, peranan, tarikh, masa, lokasi IP, jenis tindakan, nilai asal/baharu ---
export interface AuditTrailItem {
  id: string;
  userId: string;
  nama: string;
  peranan: string;
  tarikh: string;
  masa: string;
  ip: string;
  jenisTindakan: "log_masuk" | "log_keluar" | "paparan" | "kemaskini" | "kelulusan" | "penghapusan" | "eksport" | "lain";
  modul: string;
  rujukan?: string;
  nilaiAsal?: string;
  nilaiBaharu?: string;
}

export const AUDIT_TRAIL: AuditTrailItem[] = [
  { id: "AUD-001", userId: "admin@tekun.gov.my", nama: "Ahmad Admin", peranan: "Pentadbir Sistem", tarikh: "10 Mac 2024", masa: "14:32", ip: "192.168.1.10", jenisTindakan: "kemaskini", modul: "Permohonan", rujukan: "P-2024-001", nilaiAsal: "Status: Baru", nilaiBaharu: "Status: Dalam Semakan" },
  { id: "AUD-002", userId: "penilai@tekun.gov.my", nama: "Siti Penilai", peranan: "Pegawai Penilaian", tarikh: "10 Mac 2024", masa: "11:15", ip: "192.168.1.25", jenisTindakan: "kelulusan", modul: "Penilaian", rujukan: "P-2024-002", nilaiAsal: "Belum dinilai", nilaiBaharu: "Lulus" },
  { id: "AUD-003", userId: "admin@tekun.gov.my", nama: "Ahmad Admin", peranan: "Pentadbir Sistem", tarikh: "10 Mac 2024", masa: "09:45", ip: "192.168.1.10", jenisTindakan: "log_masuk", modul: "Sistem", rujukan: "-" },
  { id: "AUD-004", userId: "admin@tekun.gov.my", nama: "Ahmad Admin", peranan: "Pentadbir Sistem", tarikh: "9 Mac 2024", masa: "16:45", ip: "192.168.1.10", jenisTindakan: "kemaskini", modul: "Bayaran", rujukan: "B-2024-003", nilaiAsal: "Belum terima", nilaiBaharu: "Terima bayaran RM 500" },
  { id: "AUD-005", userId: "system", nama: "Sistem", peranan: "Sistem", tarikh: "9 Mac 2024", masa: "02:00", ip: "127.0.0.1", jenisTindakan: "lain", modul: "Sistem", rujukan: "Backup", nilaiBaharu: "Backup pangkalan data berjaya" },
  { id: "AUD-006", userId: "pegawai@tekun.gov.my", nama: "Rizal Pegawai", peranan: "Pegawai Disbursement", tarikh: "9 Mac 2024", masa: "14:20", ip: "192.168.1.30", jenisTindakan: "paparan", modul: "Pengeluaran Dana", rujukan: "D-2024-012", nilaiBaharu: "Paparan data sensitif" },
  { id: "AUD-007", userId: "auditor@tekun.gov.my", nama: "Fatimah Auditor", peranan: "Pegawai Audit", tarikh: "8 Mac 2024", masa: "10:30", ip: "192.168.1.50", jenisTindakan: "eksport", modul: "Laporan", rujukan: "LAP-AUDIT-001", nilaiBaharu: "Eksport laporan audit trail" },
  { id: "AUD-008", userId: "unknown", nama: "-", peranan: "-", tarikh: "8 Mac 2024", masa: "23:45", ip: "45.33.xx.xx", jenisTindakan: "log_masuk", modul: "Sistem", rujukan: "Percubaan gagal", nilaiBaharu: "Log masuk gagal - IP luar negara" },
];

// --- 9.3.1: Log Sistem - status sistem, percubaan log masuk, sesi, sumber, API ---
export interface LogSistemItem {
  id: string;
  tarikh: string;
  masa: string;
  jenis: "server" | "login_berjaya" | "login_gagal" | "sesi" | "sumber" | "api" | "backup";
  keterangan: string;
  status: "ok" | "amaran" | "ralat";
  butiran?: string;
}

export const LOG_SISTEM: LogSistemItem[] = [
  { id: "SYS-001", tarikh: "10 Mac 2024", masa: "14:35", jenis: "server", keterangan: "Status server normal", status: "ok", butiran: "CPU 45%, RAM 62%" },
  { id: "SYS-002", tarikh: "10 Mac 2024", masa: "14:30", jenis: "login_gagal", keterangan: "Percubaan log masuk gagal", status: "amaran", butiran: "IP: 45.33.xx.xx, user: admin@tekun.gov.my" },
  { id: "SYS-003", tarikh: "10 Mac 2024", masa: "09:00", jenis: "sesi", keterangan: "Sesi tamat - tidak aktif 30 min", status: "ok", butiran: "User: pegawai@tekun.gov.my" },
  { id: "SYS-004", tarikh: "9 Mac 2024", masa: "23:58", jenis: "api", keterangan: "Integrasi BSAS - berjaya", status: "ok", butiran: "Batch 12 transaksi" },
  { id: "SYS-005", tarikh: "9 Mac 2024", masa: "02:00", jenis: "backup", keterangan: "Backup automatik selesai", status: "ok", butiran: "Saiz: 2.4 GB" },
  { id: "SYS-006", tarikh: "8 Mac 2024", masa: "16:22", jenis: "sumber", keterangan: "Penggunaan RAM tinggi", status: "amaran", butiran: "RAM 89% - threshold 85%" },
];

// --- 9.3.2: Log Perubahan Data Penting ---
export interface LogPerubahanDataItem {
  id: string;
  tarikh: string;
  masa: string;
  modul: string;
  medan: string;
  rujukan: string;
  nilaiAsal: string;
  nilaiBaharu: string;
  pengguna: string;
  ip: string;
}

export const LOG_PERUBAHAN_DATA: LogPerubahanDataItem[] = [
  { id: "CHG-001", tarikh: "10 Mac 2024", masa: "14:32", modul: "Permohonan", medan: "Status", rujukan: "P-2024-001", nilaiAsal: "Baru", nilaiBaharu: "Dalam Semakan", pengguna: "admin@tekun.gov.my", ip: "192.168.1.10" },
  { id: "CHG-002", tarikh: "10 Mac 2024", masa: "11:15", modul: "Penilaian", medan: "Keputusan", rujukan: "P-2024-002", nilaiAsal: "Belum dinilai", nilaiBaharu: "Lulus", pengguna: "penilai@tekun.gov.my", ip: "192.168.1.25" },
  { id: "CHG-003", tarikh: "9 Mac 2024", masa: "16:45", modul: "Akaun", medan: "Jumlah Pembiayaan", rujukan: "B-2024-003", nilaiAsal: "RM 25,000", nilaiBaharu: "RM 25,000 (terima bayaran RM 500)", pengguna: "admin@tekun.gov.my", ip: "192.168.1.10" },
  { id: "CHG-004", tarikh: "9 Mac 2024", masa: "10:20", modul: "Pengeluaran Dana", medan: "Status Disbursement", rujukan: "D-2024-015", nilaiAsal: "Menunggu kelulusan", nilaiBaharu: "Diluluskan", pengguna: "pegawai_ip@tekun.gov.my", ip: "192.168.1.40" },
  { id: "CHG-005", tarikh: "8 Mac 2024", masa: "15:00", modul: "Litigasi", medan: "Status Kes", rujukan: "LIT-2024-008", nilaiAsal: "Dalam tindakan", nilaiBaharu: "Saman difailkan", pengguna: "pegawai_lit@tekun.gov.my", ip: "192.168.1.35" },
];

// --- 9.3.6, 9.5: Amaran - perubahan luar kebiasaan, kemaskini tanpa justifikasi, luar waktu pejabat ---
export interface AmaranItem {
  id: string;
  tarikh: string;
  masa: string;
  tahap: "info" | "amaran" | "kritikal";
  jenis: "luar_waktu" | "ip_luar" | "modul_tidak_skop" | "perubahan_kerap" | "transaksi_anomali" | "kelulusan_luar_norma" | "sistem";
  keterangan: string;
  pengguna?: string;
  ip?: string;
  rujukan?: string;
  tindakan?: string;
  dibaca: boolean;
}

export const AMARAN_LIST: AmaranItem[] = [
  { id: "ALT-001", tarikh: "10 Mac 2024", masa: "23:45", tahap: "kritikal", jenis: "ip_luar", keterangan: "Percubaan log masuk dari IP luar negara (45.33.xx.xx)", ip: "45.33.xx.xx", dibaca: false },
  { id: "ALT-002", tarikh: "9 Mac 2024", masa: "22:30", tahap: "amaran", jenis: "luar_waktu", keterangan: "Akses modul Pengeluaran Dana di luar waktu pejabat", pengguna: "pegawai@tekun.gov.my", ip: "192.168.1.30", dibaca: false },
  { id: "ALT-003", tarikh: "9 Mac 2024", masa: "14:15", tahap: "amaran", jenis: "perubahan_kerap", keterangan: "Pegawai membuat 15 kemaskini dalam 10 minit - modul Akaun", pengguna: "pegawai_caw@tekun.gov.my", dibaca: true },
  { id: "ALT-004", tarikh: "8 Mac 2024", masa: "11:00", tahap: "info", jenis: "sistem", keterangan: "Penggunaan RAM melebihi 85%", dibaca: true },
  { id: "ALT-005", tarikh: "8 Mac 2024", masa: "09:45", tahap: "amaran", jenis: "kelulusan_luar_norma", keterangan: "Kelulusan pembiayaan RM 50,000 dalam masa 5 minit tanpa laluan kerja lengkap", pengguna: "penilai@tekun.gov.my", rujukan: "P-2024-099", dibaca: true },
];

// --- 9.5.5: Dashboard analitik - heatmap masa aktif, pengguna berisiko, statistik amaran ---
export interface HeatmapCell {
  hari: string;
  jam: number;
  nilai: number;
}

export const HEATMAP_AKTIVITI: HeatmapCell[] = (() => {
  const hari = ["Isnin", "Selasa", "Rabu", "Khamis", "Jumaat"];
  const cells: HeatmapCell[] = [];
  for (let d = 0; d < 5; d++) {
    for (let h = 8; h <= 18; h++) {
      const base = h >= 9 && h <= 12 ? 8 : h >= 14 && h <= 17 ? 6 : 2;
      cells.push({ hari: hari[d], jam: h, nilai: base + Math.floor(Math.random() * 4) });
    }
  }
  return cells;
})();

export interface PenggunaBerisiko {
  id: string;
  pengguna: string;
  nama: string;
  skorRisiko: number;
  bilAmaran: number;
  bilAktivitiLuarWaktu: number;
  bilPerubahanData: number;
  status: "rendah" | "sederhana" | "tinggi";
}

export const PENGGUNA_BERISIKO: PenggunaBerisiko[] = [
  { id: "U1", pengguna: "pegawai_caw@tekun.gov.my", nama: "Rizal Cawangan", skorRisiko: 72, bilAmaran: 3, bilAktivitiLuarWaktu: 2, bilPerubahanData: 45, status: "tinggi" },
  { id: "U2", pengguna: "penilai@tekun.gov.my", nama: "Siti Penilai", skorRisiko: 58, bilAmaran: 1, bilAktivitiLuarWaktu: 0, bilPerubahanData: 28, status: "sederhana" },
  { id: "U3", pengguna: "pegawai_ip@tekun.gov.my", nama: "Ahmad IP", skorRisiko: 35, bilAmaran: 0, bilAktivitiLuarWaktu: 0, bilPerubahanData: 15, status: "rendah" },
];

// --- 9.2.6: Laporan Analitik Audit Trail - pengguna perubahan tidak konsisten, kelulusan luar norma ---
export interface AnalitikAuditItem {
  id: string;
  metrik: string;
  nilai: string | number;
  trend: "naik" | "turun" | "stabil";
  perbandingan?: string;
}

export const ANALITIK_AUDIT: AnalitikAuditItem[] = [
  { id: "A1", metrik: "Pengguna dengan perubahan tidak konsisten", nilai: 2, trend: "turun", perbandingan: "Bulan lepas: 4" },
  { id: "A2", metrik: "Kelulusan luar norma (cepat)", nilai: 1, trend: "stabil", perbandingan: "Bulan lepas: 1" },
  { id: "A3", metrik: "Percubaan akses tidak dibenarkan", nilai: 3, trend: "naik", perbandingan: "Bulan lepas: 1" },
  { id: "A4", metrik: "Log masuk luar waktu pejabat", nilai: 5, trend: "turun", perbandingan: "Bulan lepas: 8" },
];

// --- 9.6: AI Risk & Anomaly Detection ---
export interface AIRiskItem {
  id: string;
  rujukan: string;
  jenis: "npf_risk" | "anomali_transaksi" | "anomali_pengguna" | "early_warning";
  skorRisiko: number;
  keterangan: string;
  tarikh: string;
  cadangan?: string;
}

export const AI_RISK_ITEMS: AIRiskItem[] = [
  { id: "AI-001", rujukan: "P-2024-088", jenis: "npf_risk", skorRisiko: 78, keterangan: "AI mengesan pola pembayaran tidak normal - 3 bayaran lewat berturut-turut", tarikh: "10 Mac 2024", cadangan: "Susulan pemantauan disyorkan" },
  { id: "AI-002", rujukan: "B-2024-045", jenis: "anomali_transaksi", skorRisiko: 65, keterangan: "Pengeluaran dana melebihi had biasa untuk produk", tarikh: "9 Mac 2024", cadangan: "Semak kelulusan dan justifikasi" },
  { id: "AI-003", rujukan: "pegawai_caw@tekun.gov.my", jenis: "anomali_pengguna", skorRisiko: 72, keterangan: "Perubahan data yang mencurigakan - 15 kemaskini dalam 10 minit", tarikh: "9 Mac 2024", cadangan: "Notifikasi kepada penyelia" },
  { id: "AI-004", rujukan: "P-2024-102", jenis: "early_warning", skorRisiko: 55, keterangan: "Early warning: Prestasi perniagaan merosot berdasarkan maklumat terkini", tarikh: "8 Mac 2024", cadangan: "Lawatan pemantauan disyorkan" },
];

// --- Jenis tindakan labels ---
export const JENIS_TINDAKAN_LABELS: Record<AuditTrailItem["jenisTindakan"], string> = {
  log_masuk: "Log Masuk",
  log_keluar: "Log Keluar",
  paparan: "Paparan Data",
  kemaskini: "Kemaskini",
  kelulusan: "Kelulusan",
  penghapusan: "Penghapusan",
  eksport: "Eksport",
  lain: "Lain-lain",
};
