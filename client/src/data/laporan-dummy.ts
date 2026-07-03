/**
 * Dummy data for Laporan & Analitik - no DB/API
 * SPEC Laporan n Statistik
 */

// --- Fasa 1 & 2: KPI, Summary ---
export const KPI_SUMMARY = {
  portfolioAktif: 4200000,
  pembiayaanDiluluskan: 156,
  kadarBayaranBalik: 94,
  pertumbuhanYoY: 12,
  kutipanBulanan: 285000,
  sasaranKutipan: 300000,
  kadarNpf: 5.2,
  akaunTunggakan: 23,
  akaunLitigasi: 8,
  akaunReschedule: 12,
};

// --- Fasa 1 & 3: Laporan Tersedia ---
export interface LaporanItem {
  id: string;
  tajuk: string;
  tarikh: string;
  jenis: "Kewangan" | "Prestasi" | "Tunggakan" | "Pengeluaran" | "Kutipan" | "NPF" | "Syariah" | "Pemantauan";
  tempoh: "harian" | "mingguan" | "bulanan" | "suku-tahun" | "tahunan";
  format: "PDF" | "Excel" | "CSV";
}

export const LAPORAN_ITEMS: LaporanItem[] = [
  { id: "LAP-001", tajuk: "Laporan Kewangan Bulanan", tarikh: "Mac 2024", jenis: "Kewangan", tempoh: "bulanan", format: "PDF" },
  { id: "LAP-002", tajuk: "Laporan Prestasi Portfolio", tarikh: "Feb 2024", jenis: "Prestasi", tempoh: "bulanan", format: "Excel" },
  { id: "LAP-003", tajuk: "Analisis Tunggakan", tarikh: "Mac 2024", jenis: "Tunggakan", tempoh: "bulanan", format: "PDF" },
  { id: "LAP-004", tajuk: "Laporan Pengeluaran Dana", tarikh: "Mac 2024", jenis: "Pengeluaran", tempoh: "bulanan", format: "Excel" },
  { id: "LAP-005", tajuk: "Laporan Kutipan Harian", tarikh: "10 Mac 2024", jenis: "Kutipan", tempoh: "harian", format: "CSV" },
  { id: "LAP-006", tajuk: "Laporan NPF Suku Tahun", tarikh: "Q1 2024", jenis: "NPF", tempoh: "suku-tahun", format: "PDF" },
  { id: "LAP-007", tajuk: "Laporan Patuh Syariah", tarikh: "Mac 2024", jenis: "Syariah", tempoh: "bulanan", format: "Excel" },
  { id: "LAP-008", tajuk: "Laporan Pemantauan PP", tarikh: "Mac 2024", jenis: "Pemantauan", tempoh: "bulanan", format: "PDF" },
  { id: "LAP-009", tajuk: "Laporan Resit Kutipan", tarikh: "Mac 2024", jenis: "Kutipan", tempoh: "bulanan", format: "Excel" },
  { id: "LAP-010", tajuk: "Laporan Penyesuaian Bank", tarikh: "Mac 2024", jenis: "Kewangan", tempoh: "bulanan", format: "PDF" },
];

// --- Fasa 2: Chart data ---
export const KUTIPAN_VS_SASARAN = [
  { bulan: "Jan", kutipan: 280, sasaran: 300 },
  { bulan: "Feb", kutipan: 295, sasaran: 300 },
  { bulan: "Mac", kutipan: 285, sasaran: 300 },
  { bulan: "Apr", kutipan: 310, sasaran: 320 },
  { bulan: "Mei", kutipan: 305, sasaran: 320 },
  { bulan: "Jun", kutipan: 298, sasaran: 320 },
];

export const TREND_NPF = [
  { bulan: "Jan", kadar: 5.8 },
  { bulan: "Feb", kadar: 5.5 },
  { bulan: "Mac", kadar: 5.2 },
  { bulan: "Apr", kadar: 5.0 },
  { bulan: "Mei", kadar: 4.9 },
  { bulan: "Jun", kadar: 4.7 },
];

export const AGIHAN_STATUS_AKAUN = [
  { label: "Aktif", value: 142, warna: "#22c55e" },
  { label: "Tunggakan", value: 23, warna: "#eab308" },
  { label: "NPF", value: 8, warna: "#ef4444" },
  { label: "Litigasi", value: 5, warna: "#dc2626" },
  { label: "Selesai", value: 312, warna: "#64748b" },
];

// --- Fasa 2: Peta Malaysia (prestasi negeri) ---
export const PRESTASI_NEGERI = [
  { negeri: "Johor", kod: "JHR", kutipan: 92, warna: "hijau" },
  { negeri: "Kedah", kod: "KDH", kutipan: 88, warna: "hijau" },
  { negeri: "Kelantan", kod: "KTN", kutipan: 75, warna: "kuning" },
  { negeri: "Melaka", kod: "MLK", kutipan: 95, warna: "hijau" },
  { negeri: "N. Sembilan", kod: "NSN", kutipan: 91, warna: "hijau" },
  { negeri: "Pahang", kod: "PHG", kutipan: 82, warna: "kuning" },
  { negeri: "Pulau Pinang", kod: "PNG", kutipan: 94, warna: "hijau" },
  { negeri: "Perak", kod: "PRK", kutipan: 78, warna: "kuning" },
  { negeri: "Perlis", kod: "PLS", kutipan: 96, warna: "hijau" },
  { negeri: "Selangor", kod: "SGR", kutipan: 89, warna: "hijau" },
  { negeri: "Terengganu", kod: "TRG", kutipan: 68, warna: "merah" },
  { negeri: "Sabah", kod: "SBH", kutipan: 72, warna: "kuning" },
  { negeri: "Sarawak", kod: "SWK", kutipan: 85, warna: "kuning" },
  { negeri: "W.P. KL", kod: "KUL", kutipan: 93, warna: "hijau" },
  { negeri: "W.P. Labuan", kod: "LBN", kutipan: 88, warna: "hijau" },
];

// --- Fasa 3: Cross-analysis (Produk x Negeri) ---
export const CROSS_ANALYSIS_PRODUK = [
  { produk: "SPEK", diluluskan: 45, dikeluarkan: 42, negeri: "Selangor" },
  { produk: "TEMAN", diluluskan: 38, dikeluarkan: 35, negeri: "Johor" },
  { produk: "PEMERKASA", diluluskan: 22, dikeluarkan: 20, negeri: "Kedah" },
  { produk: "TEMAN ULTRA", diluluskan: 18, dikeluarkan: 16, negeri: "Pulau Pinang" },
];

// --- Fasa 3: Laporan Individu ---
export const LAPORAN_INDIVIDU = [
  { no: 1, negeri: "Selangor", cawangan: "Puchong", noTel: "012-3456789", nama: "Ahmad bin Ali", alamat: "No 12, Jalan Merdeka", bilPembiayaan: 2, kategori: "A1", amaun: "RM 25,000" },
  { no: 2, negeri: "Johor", cawangan: "Johor Bahru", noTel: "019-8765432", nama: "Siti Nurhaliza", alamat: "Lot 5, Taman Perindustrian", bilPembiayaan: 1, kategori: "A2", amaun: "RM 15,000" },
  { no: 3, negeri: "Kedah", cawangan: "Alor Setar", noTel: "014-1122334", nama: "Mohd Rizal", alamat: "No 8, Jalan Sultanah", bilPembiayaan: 3, kategori: "B1", amaun: "RM 45,000" },
];

// --- Fasa 4: Laporan Resit ---
export const LAPORAN_RESIT = [
  { tarikh: "10 Mac 2024", resit: 45, tunai: 12, eft: 28, cek: 5, jumlah: "RM 125,400" },
  { tarikh: "11 Mac 2024", resit: 38, tunai: 8, eft: 25, cek: 5, jumlah: "RM 98,200" },
  { tarikh: "12 Mac 2024", resit: 52, tunai: 15, eft: 32, cek: 5, jumlah: "RM 142,600" },
];

// --- Fasa 4: Laporan Penyesuaian Bank ---
export const LAPORAN_PENYESUAIAN_BANK = [
  { bank: "Maybank", bakiBawa: "RM 45,200", kutipan: "RM 125,400", bakiHadapan: "RM 170,600", status: "seimbang" },
  { bank: "CIMB", bakiBawa: "RM 12,800", kutipan: "RM 38,200", bakiHadapan: "RM 51,000", status: "seimbang" },
];

// --- Fasa 4: Laporan Month-End ---
export const LAPORAN_MONTH_END = [
  { item: "Cut-off month-end", nilai: "5 hari bulan", catatan: "Pelaporan resit" },
  { item: "Kutipan mengikut tarikh bank-in", nilai: "RM 285,000", catatan: "Mac 2024" },
  { item: "Baki dibawa ke hadapan", nilai: "RM 45,200", catatan: "Baki awal bulan" },
];

// --- Fasa 4: Laporan NPF ---
export const LAPORAN_NPF = [
  { kategori: "1-30 hari", bil: 8, amaun: "RM 45,000", peratus: 2.1 },
  { kategori: "31-60 hari", bil: 6, amaun: "RM 32,000", peratus: 1.5 },
  { kategori: "61-90 hari", bil: 5, amaun: "RM 28,000", peratus: 1.3 },
  { kategori: "90+ hari (NPF)", bil: 8, amaun: "RM 65,000", peratus: 3.1 },
];

// --- Fasa 4: Laporan Syariah ---
export const LAPORAN_PATUH_SYARIAH = [
  { produk: "SPEK", akaun: 245, disalurkan: "RM 4.2M", kutipan: "RM 2.8M", baki: "RM 1.4M" },
  { produk: "TEMAN", akaun: 189, disalurkan: "RM 2.1M", kutipan: "RM 1.5M", baki: "RM 600K" },
];

export const LAPORAN_SNC = [
  { produk: "TEMAN ULTRA", akaun: 3, disalurkan: "RM 45,000", kutipan: "RM 12,000", baki: "RM 33,000" },
];

// --- Fasa 4: Laporan Pemantauan ---
export const LAPORAN_NISBAH_SELIAAN = [
  { pegawai: "Ahmad bin Ali", negeri: "Selangor", cawangan: "Puchong", penerima: 85, prestasi: "95%", status: "baik" },
  { pegawai: "Siti Nurhaliza", negeri: "Johor", cawangan: "JB", penerima: 72, prestasi: "88%", status: "baik" },
  { pegawai: "Mohd Rizal", negeri: "Kedah", cawangan: "Alor Setar", penerima: 68, prestasi: "82%", status: "sederhana" },
];

// --- Fasa 5: Audit trail ---
export interface AuditLaporan {
  id: string;
  tajuk: string;
  pengguna: string;
  tarikh: string;
  masa: string;
  format: string;
}

export const AUDIT_LAPORAN: AuditLaporan[] = [
  { id: "AUD-001", tajuk: "Laporan Kewangan Bulanan", pengguna: "admin@tekun.gov.my", tarikh: "10 Mac 2024", masa: "09:15", format: "PDF" },
  { id: "AUD-002", tajuk: "Laporan Prestasi Portfolio", pengguna: "pegawai1@tekun.gov.my", tarikh: "9 Mac 2024", masa: "14:30", format: "Excel" },
  { id: "AUD-003", tajuk: "Analisis Tunggakan", pengguna: "admin@tekun.gov.my", tarikh: "8 Mac 2024", masa: "11:00", format: "PDF" },
];

// --- Fasa 5: Arkib laporan ---
export const ARKIB_LAPORAN = [
  { id: "ARK-001", tajuk: "Laporan Tahunan 2023", tarikh: "31 Dis 2023", saiz: "2.4 MB", jenis: "Tahunan" },
  { id: "ARK-002", tajuk: "Laporan Q4 2023", tarikh: "31 Dis 2023", saiz: "1.1 MB", jenis: "Suku Tahun" },
  { id: "ARK-003", tajuk: "Laporan Bulanan Nov 2023", tarikh: "30 Nov 2023", saiz: "856 KB", jenis: "Bulanan" },
];

// --- Fasa 5: Notifikasi ---
export const NOTIFIKASI_LAPORAN = [
  { id: "N1", mesej: "Sasaran kutipan Mac belum dicapai (95%)", tarikh: "10 Mac 2024", tahap: "amaran", dibaca: false },
  { id: "N2", mesej: "NPF Cawangan Terengganu melebihi ambang (6.2%)", tarikh: "9 Mac 2024", tahap: "kritikal", dibaca: true },
  { id: "N3", mesej: "3 akaun tertunggak melepasi 90 hari", tarikh: "8 Mac 2024", tahap: "info", dibaca: true },
];

// --- Fasa 5: Customize - medan tersedia ---
export const MEDAN_TERSEDIA = [
  "Negeri", "Cawangan", "Nama Usahawan", "No. Telefon", "Alamat", "Bil. Pembiayaan",
  "Kategori Bayaran", "Amaun Pembiayaan", "Tarikh Permohonan", "Tarikh Kelulusan", "Produk",
];

// --- RBAC roles ---
export type RoleLaporan = "hq" | "pengurus-negeri" | "pegawai-kutipan";

export const ROLE_LABELS: Record<RoleLaporan, string> = {
  hq: "Ibu Pejabat (HQ)",
  "pengurus-negeri": "Pengurus Negeri",
  "pegawai-kutipan": "Pegawai Kutipan",
};
