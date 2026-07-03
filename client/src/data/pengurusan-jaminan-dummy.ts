/**
 * Dummy data for Pengurusan Jaminan / Cagaran module (no DB/API)
 * Per SPEC Pengurusan Jaminan.csv - Inden, SST, PO, LO, status, audit trail
 */

export type JaminanStatus = "Aktif" | "Tamat Tempoh" | "Dilepaskan" | "Ditolak" | "Perlu Semakan";

export type JenisCagaran = "Inden" | "SST" | "PO" | "LO" | "Penjamin" | "Gadai Janji" | "Surat Perjanjian";

export type RisikoCagaran = "Rendah" | "Tinggi";

export interface JaminanItem {
  id: string;
  nama: string;
  jenis: JenisCagaran;
  nilai: number;
  status: JaminanStatus;
  risiko: RisikoCagaran;
  noPinjaman: string;
  tarikhMula: string;
  tarikhTamat: string;
  deskripsi?: string;
  dokumen?: { nama: string; jenis: string; tarikh: string }[];
}

export interface AuditLog {
  id: string;
  jaminanId: string;
  action: string;
  user: string;
  timestamp: string;
  detail?: string;
}

export interface NotifikasiJaminan {
  id: string;
  jaminanId: string;
  jenis: "tamat_tempoh" | "perlu_diganti" | "peringatan";
  mesej: string;
  tarikh: string;
  dibaca: boolean;
}

function fmtRm(n: number) {
  return `RM ${n.toLocaleString("en-MY")}`;
}

export const JENIS_CAGARAN_OPTIONS: { value: JenisCagaran; label: string }[] = [
  { value: "Inden", label: "Inden" },
  { value: "SST", label: "SST" },
  { value: "PO", label: "Purchase Order (PO)" },
  { value: "LO", label: "Letter of Offer (LO)" },
  { value: "Penjamin", label: "Penjamin" },
  { value: "Gadai Janji", label: "Gadai Janji" },
  { value: "Surat Perjanjian", label: "Surat Perjanjian" },
];

export const items: JaminanItem[] = [
  {
    id: "JAM-2024-001",
    nama: "Ahmad bin Abdullah",
    jenis: "Penjamin",
    nilai: 50000,
    status: "Aktif",
    risiko: "Rendah",
    noPinjaman: "TEK-KL-2024-0001",
    tarikhMula: "2024-01-15",
    tarikhTamat: "2029-01-15",
    dokumen: [{ nama: "Surat Jaminan Ahmad.pdf", jenis: "Surat Perjanjian", tarikh: "2024-01-10" }],
  },
  {
    id: "JAM-2024-002",
    nama: "Siti Nurhaliza binti Omar",
    jenis: "Gadai Janji",
    nilai: 30000,
    status: "Aktif",
    risiko: "Rendah",
    noPinjaman: "TEK-KL-2024-0002",
    tarikhMula: "2024-02-20",
    tarikhTamat: "2027-02-20",
    dokumen: [{ nama: "Gadai Janji Siti.pdf", jenis: "Geran", tarikh: "2024-02-15" }],
  },
  {
    id: "JAM-2024-003",
    nama: "Mohd Rizal bin Hassan",
    jenis: "Inden",
    nilai: 75000,
    status: "Perlu Semakan",
    risiko: "Tinggi",
    noPinjaman: "TEK-SHA-2023-0045",
    tarikhMula: "2023-06-01",
    tarikhTamat: "2025-06-01",
    dokumen: [{ nama: "Inden Rizal.pdf", jenis: "Inden", tarikh: "2023-05-28" }],
  },
  {
    id: "JAM-2024-004",
    nama: "Fatimah binti Ibrahim",
    jenis: "Gadai Janji",
    nilai: 25000,
    status: "Aktif",
    risiko: "Rendah",
    noPinjaman: "TEK-JB-2024-0012",
    tarikhMula: "2024-04-10",
    tarikhTamat: "2029-04-10",
    dokumen: [{ nama: "Resit Cukai Tanah.pdf", jenis: "Resit", tarikh: "2024-04-05" }],
  },
  {
    id: "JAM-2024-005",
    nama: "Kamarul bin Zainal",
    jenis: "SST",
    nilai: 120000,
    status: "Aktif",
    risiko: "Tinggi",
    noPinjaman: "TEK-IPH-2022-0089",
    tarikhMula: "2022-09-15",
    tarikhTamat: "2025-09-15",
    dokumen: [{ nama: "SST Kamarul.pdf", jenis: "SST", tarikh: "2022-09-10" }],
  },
  {
    id: "JAM-2023-006",
    nama: "Abdul Rahman bin Musa",
    jenis: "Surat Perjanjian",
    nilai: 60000,
    status: "Dilepaskan",
    risiko: "Rendah",
    noPinjaman: "TEK-KK-2023-0023",
    tarikhMula: "2023-11-20",
    tarikhTamat: "2026-11-20",
    dokumen: [{ nama: "Perjanjian Rahman.pdf", jenis: "Surat Perjanjian", tarikh: "2023-11-15" }],
  },
  {
    id: "JAM-2024-007",
    nama: "Lina binti Mat Zin",
    jenis: "PO",
    nilai: 35000,
    status: "Aktif",
    risiko: "Rendah",
    noPinjaman: "TEK-KCH-2024-0007",
    tarikhMula: "2024-05-05",
    tarikhTamat: "2029-05-05",
    dokumen: [{ nama: "PO Lina.pdf", jenis: "PO", tarikh: "2024-05-01" }],
  },
  {
    id: "JAM-2024-008",
    nama: "Wong Siew Ling",
    jenis: "LO",
    nilai: 100000,
    status: "Aktif",
    risiko: "Tinggi",
    noPinjaman: "TEK-KL-2023-0098",
    tarikhMula: "2023-08-01",
    tarikhTamat: "2026-08-01",
    dokumen: [{ nama: "LO Wong.pdf", jenis: "LO", tarikh: "2023-07-28" }],
  },
  {
    id: "JAM-2024-009",
    nama: "Nurul Izzati binti Rahman",
    jenis: "Penjamin",
    nilai: 20000,
    status: "Aktif",
    risiko: "Rendah",
    noPinjaman: "TEK-KB-2024-0003",
    tarikhMula: "2024-03-01",
    tarikhTamat: "2029-03-01",
    dokumen: [{ nama: "Jaminan Nurul.pdf", jenis: "Surat Perjanjian", tarikh: "2024-02-25" }],
  },
  {
    id: "JAM-2023-010",
    nama: "Muhammad Hafiz bin Ismail",
    jenis: "Inden",
    nilai: 40000,
    status: "Tamat Tempoh",
    risiko: "Rendah",
    noPinjaman: "TEK-SHA-2024-0015",
    tarikhMula: "2023-06-15",
    tarikhTamat: "2024-06-15",
    dokumen: [{ nama: "Inden Hafiz.pdf", jenis: "Inden", tarikh: "2023-06-10" }],
  },
  {
    id: "JAM-2024-011",
    nama: "Pembekal XYZ Sdn Bhd",
    jenis: "Surat Perjanjian",
    nilai: 85000,
    status: "Aktif",
    risiko: "Tinggi",
    noPinjaman: "TEK-KL-2024-0001",
    tarikhMula: "2024-01-15",
    tarikhTamat: "2027-01-15",
    deskripsi: "Surat jaminan daripada pembekal",
    dokumen: [{ nama: "Surat Jaminan Pembekal.pdf", jenis: "Surat Perjanjian", tarikh: "2024-01-12" }],
  },
  {
    id: "JAM-2024-012",
    nama: "Bank Islam Malaysia",
    jenis: "LO",
    nilai: 150000,
    status: "Perlu Semakan",
    risiko: "Tinggi",
    noPinjaman: "TEK-SHA-2024-0015",
    tarikhMula: "2024-06-15",
    tarikhTamat: "2029-06-15",
    dokumen: [{ nama: "LO Bank Islam.pdf", jenis: "LO", tarikh: "2024-06-10" }],
  },
];

export const auditLogs: AuditLog[] = [
  { id: "A1", jaminanId: "JAM-2024-003", action: "Status dikemaskini", user: "Pegawai A", timestamp: "2025-03-10 09:15", detail: "Perlu Semakan → Semakan" },
  { id: "A2", jaminanId: "JAM-2024-003", action: "Dokumen dimuat naik", user: "Pegawai A", timestamp: "2025-03-09 14:30", detail: "Inden Rizal.pdf" },
  { id: "A3", jaminanId: "JAM-2023-010", action: "Status automatik", user: "Sistem", timestamp: "2025-06-01 00:00", detail: "Aktif → Tamat Tempoh" },
  { id: "A4", jaminanId: "JAM-2023-006", action: "Dilepaskan", user: "Pegawai B", timestamp: "2025-03-05 11:00", detail: "Akaun selesai bayar" },
  { id: "A5", jaminanId: "JAM-2024-003", action: "Status dikemaskini", user: "Pegawai A", timestamp: "2025-03-08 10:00", detail: "Sah → Perlu Semakan" },
];

export const notifikasi: NotifikasiJaminan[] = [
  { id: "N1", jaminanId: "JAM-2024-003", jenis: "peringatan", mesej: "Jaminan Inden (JAM-2024-003) tamat tempoh dalam 90 hari", tarikh: "2025-03-10", dibaca: false },
  { id: "N2", jaminanId: "JAM-2024-005", jenis: "peringatan", mesej: "Jaminan SST (JAM-2024-005) tamat tempoh dalam 180 hari", tarikh: "2025-03-09", dibaca: false },
  { id: "N3", jaminanId: "JAM-2023-010", jenis: "tamat_tempoh", mesej: "Jaminan Inden (JAM-2023-010) telah tamat tempoh. Tindakan diperlukan.", tarikh: "2025-03-08", dibaca: true },
  { id: "N4", jaminanId: "JAM-2024-012", jenis: "perlu_diganti", mesej: "Jaminan LO (JAM-2024-012) perlu diganti - status Perlu Semakan", tarikh: "2025-03-07", dibaca: false },
];

export const PINJAMAN_OPTIONS = [
  { value: "TEK-KL-2024-0001", label: "TEK-KL-2024-0001 - Ahmad bin Abdullah" },
  { value: "TEK-KL-2024-0002", label: "TEK-KL-2024-0002 - Siti Nurhaliza binti Omar" },
  { value: "TEK-SHA-2023-0045", label: "TEK-SHA-2023-0045 - Mohd Rizal bin Hassan" },
  { value: "TEK-JB-2024-0012", label: "TEK-JB-2024-0012 - Fatimah binti Ibrahim" },
  { value: "TEK-IPH-2022-0089", label: "TEK-IPH-2022-0089 - Kamarul bin Zainal" },
  { value: "TEK-KK-2023-0023", label: "TEK-KK-2023-0023 - Abdul Rahman bin Musa" },
  { value: "TEK-KCH-2024-0007", label: "TEK-KCH-2024-0007 - Lina binti Mat Zin" },
  { value: "TEK-KL-2023-0098", label: "TEK-KL-2023-0098 - Wong Siew Ling" },
  { value: "TEK-KB-2024-0003", label: "TEK-KB-2024-0003 - Nurul Izzati binti Rahman" },
  { value: "TEK-SHA-2024-0015", label: "TEK-SHA-2024-0015 - Muhammad Hafiz bin Ismail" },
];

export function getSummaryFromItems(list: JaminanItem[]) {
  const aktif = list.filter((i) => i.status === "Aktif").length;
  const totalNilai = list.filter((i) => i.status === "Aktif").reduce((s, i) => s + i.nilai, 0);
  const sah = list.filter((i) => i.status === "Aktif" || i.status === "Dilepaskan").length;
  const perluSemakan = list.filter((i) => i.status === "Perlu Semakan").length;

  return [
    { label: "Jumlah Jaminan Aktif", value: aktif },
    { label: "Nilai Jaminan", value: fmtRm(totalNilai) },
    { label: "Jaminan Sah", value: sah },
    { label: "Perlu Semakan", value: perluSemakan },
  ];
}

export function getAuditLogsForJaminan(jaminanId: string): AuditLog[] {
  return auditLogs.filter((a) => a.jaminanId === jaminanId);
}

export function getUnreadNotifikasi(): NotifikasiJaminan[] {
  return notifikasi.filter((n) => !n.dibaca);
}
