/**
 * Dummy data for Pengeluaran Dana module (no DB/API connection)
 * Covers Fasa 1, 2, 3 requirements per SPEC Pengeluaran Dana.csv
 */

export type DisbursementStatus = "Menunggu" | "Dalam Proses" | "Berjaya" | "Gagal" | "Ditolak";
export type DisbursementType = "Penuh" | "Berperingkat";
export type ApprovalLevel = "Cawangan" | "Negeri" | "Ibu Pejabat";
export type FraudRiskLevel = "Rendah" | "Sederhana" | "Tinggi";

export interface PengeluaranItem {
  id: string;
  nama: string;
  jumlah: string;
  jumlahNumeric: number;
  tarikh: string;
  tarikhIso: string;
  status: DisbursementStatus;
  bank?: string;
  noAkaunBank?: string;
  jenisPengeluaran: DisbursementType;
  fasa?: number; // 1, 2, 3 for berperingkat
  peratusFasa?: number;
  noRujukanBank?: string;
  idPembiayaan: string;
  fraudRisk?: FraudRiskLevel;
  fraudAlert?: string;
  bsasVerified?: boolean;
  legalDocsComplete?: boolean;
}

export interface AuditTrailEntry {
  id: string;
  tarikh: string;
  masa: string;
  idPegawai: string;
  namaPegawai: string;
  tindakan: string;
  keputusan?: string;
  noRujukan?: string;
  statusTransaksi?: string;
  butiran?: string;
}

export interface BatchDisbursement {
  id: string;
  tarikh: string;
  status: "Draf" | "Menunggu Kelulusan" | "Diproses" | "Berjaya" | "Sebahagian Gagal";
  jumlahRekod: number;
  jumlahTotal: string;
  diluluskanOleh?: string;
  tarikhLulus?: string;
  items: string[]; // IDs
}

export interface LegalDocument {
  id: string;
  nama: string;
  status: "Belum" | "Dijana" | "Ditandatangani" | "Disahkan";
  tarikhJana?: string;
  ditandatanganiOleh?: string;
}

export interface ExceptionReport {
  id: string;
  idTransaksi: string;
  tarikh: string;
  sebab: string;
  tindakan: string;
  status: "Belum Selesai" | "Dalam Tindakan" | "Selesai";
  pegawaiBertanggungjawab: string;
}

export interface IntegrationStatus {
  sistem: string;
  status: "Aktif" | "Gagal" | "Penyelenggaraan";
  lastSync?: string;
  transaksiHariIni?: number;
}

export const BANK_OPTIONS = ["Maybank", "CIMB", "RHB", "Public Bank", "Bank Islam", "BSN", "Lain-lain"];

export const RBAC_ROLES = [
  { id: "pegawai_cawangan", label: "Pegawai Cawangan", canApprove: false, canViewAudit: true, canExport: true },
  { id: "pegawai_negeri", label: "Pegawai Negeri", canApprove: true, canViewAudit: true, canExport: true },
  { id: "pegawai_ip", label: "Pegawai Ibu Pejabat", canApprove: true, canViewAudit: true, canExport: true },
  { id: "pentadbir", label: "Pentadbir Sistem", canApprove: true, canViewAudit: true, canExport: true },
] as const;

export const items: PengeluaranItem[] = [
  {
    id: "D-2024-001",
    idPembiayaan: "P-2024-001",
    nama: "Ahmad bin Abdullah",
    jumlah: "RM 50,000",
    jumlahNumeric: 50000,
    tarikh: "10 Mac 2024",
    tarikhIso: "2024-03-10",
    status: "Menunggu",
    bank: "Maybank",
    noAkaunBank: "1234567890",
    jenisPengeluaran: "Penuh",
    fraudRisk: "Rendah",
    bsasVerified: false,
    legalDocsComplete: true,
  },
  {
    id: "D-2024-002",
    idPembiayaan: "P-2024-002",
    nama: "Siti Nurhaliza binti Omar",
    jumlah: "RM 30,000",
    jumlahNumeric: 30000,
    tarikh: "9 Mac 2024",
    tarikhIso: "2024-03-09",
    status: "Dalam Proses",
    bank: "CIMB",
    noAkaunBank: "9876543210",
    jenisPengeluaran: "Berperingkat",
    fasa: 2,
    peratusFasa: 30,
    fraudRisk: "Sederhana",
    fraudAlert: "Transaksi berulang dalam 24 jam",
    bsasVerified: true,
    legalDocsComplete: true,
  },
  {
    id: "D-2024-003",
    idPembiayaan: "P-2024-003",
    nama: "Mohd Rizal bin Hassan",
    jumlah: "RM 75,000",
    jumlahNumeric: 75000,
    tarikh: "8 Mac 2024",
    tarikhIso: "2024-03-08",
    status: "Berjaya",
    bank: "RHB",
    noAkaunBank: "5555666677",
    noRujukanBank: "TXN-20240308-001",
    jenisPengeluaran: "Penuh",
    fraudRisk: "Rendah",
    bsasVerified: true,
    legalDocsComplete: true,
  },
  {
    id: "D-2024-004",
    idPembiayaan: "P-2024-004",
    nama: "Fatimah binti Ibrahim",
    jumlah: "RM 25,000",
    jumlahNumeric: 25000,
    tarikh: "7 Mac 2024",
    tarikhIso: "2024-03-07",
    status: "Berjaya",
    bank: "Public Bank",
    noAkaunBank: "1111222233",
    noRujukanBank: "TXN-20240307-002",
    jenisPengeluaran: "Berperingkat",
    fasa: 1,
    peratusFasa: 40,
    fraudRisk: "Rendah",
    bsasVerified: true,
    legalDocsComplete: true,
  },
  {
    id: "D-2024-005",
    idPembiayaan: "P-2024-005",
    nama: "Kamarul bin Zainal",
    jumlah: "RM 100,000",
    jumlahNumeric: 100000,
    tarikh: "11 Mac 2024",
    tarikhIso: "2024-03-11",
    status: "Gagal",
    bank: "Bank Islam",
    noAkaunBank: "9999888877",
    jenisPengeluaran: "Penuh",
    fraudRisk: "Tinggi",
    fraudAlert: "Akaun pihak ketiga mencurigakan",
    bsasVerified: false,
    legalDocsComplete: true,
  },
];

export const auditTrail: AuditTrailEntry[] = [
  { id: "A1", tarikh: "10 Mac 2024", masa: "09:15:22", idPegawai: "P001", namaPegawai: "Ahmad bin Kassim", tindakan: "Pengesahan akaun penerima", keputusan: "Lulus", noRujukan: "VAL-20240310-001", statusTransaksi: "Berjaya" },
  { id: "A2", tarikh: "10 Mac 2024", masa: "09:16:45", idPegawai: "P001", namaPegawai: "Ahmad bin Kassim", tindakan: "Dual verification BSAS", keputusan: "Disahkan", noRujukan: "BSAS-20240310-001", statusTransaksi: "Berjaya" },
  { id: "A3", tarikh: "9 Mac 2024", masa: "14:22:10", idPegawai: "P002", namaPegawai: "Siti Aminah binti Rahman", tindakan: "Transaksi EFT diluluskan", keputusan: "Lulus", noRujukan: "EFT-20240309-002", statusTransaksi: "Berjaya" },
  { id: "A4", tarikh: "9 Mac 2024", masa: "14:25:33", idPegawai: "SYS", namaPegawai: "Sistem", tindakan: "Notifikasi automatik dihantar", keputusan: "Berjaya", butiran: "SMS/Email kepada pemohon" },
  { id: "A5", tarikh: "8 Mac 2024", masa: "11:30:00", idPegawai: "P003", namaPegawai: "Mohd Fadzil bin Yusof", tindakan: "Kelulusan Pegawai Negeri", keputusan: "Lulus", butiran: "Jumlah RM 75,000" },
  { id: "A6", tarikh: "11 Mac 2024", masa: "10:05:12", idPegawai: "SYS", namaPegawai: "AI Fraud Detection", tindakan: "Amaran fraud dikesan", keputusan: "Tolak", butiran: "Akaun pihak ketiga mencurigakan - D-2024-005" },
];

export const batches: BatchDisbursement[] = [
  { id: "B-2024-001", tarikh: "10 Mac 2024", status: "Menunggu Kelulusan", jumlahRekod: 5, jumlahTotal: "RM 280,000", items: ["D-2024-001", "D-2024-002", "D-2024-003", "D-2024-004", "D-2024-005"] },
  { id: "B-2024-002", tarikh: "7 Mac 2024", status: "Berjaya", jumlahRekod: 3, jumlahTotal: "RM 150,000", diluluskanOleh: "Mohd Fadzil bin Yusof", tarikhLulus: "7 Mac 2024", items: ["D-2024-003", "D-2024-004"] },
  { id: "B-2024-003", tarikh: "11 Mac 2024", status: "Draf", jumlahRekod: 2, jumlahTotal: "RM 80,000", items: ["D-2024-001", "D-2024-002"] },
];

export const legalDocsTemplate: LegalDocument[] = [
  { id: "L1", nama: "Perjanjian Pembiayaan", status: "Ditandatangani", tarikhJana: "5 Mac 2024", ditandatanganiOleh: "Ahmad bin Abdullah" },
  { id: "L2", nama: "Surat Ikatan/Akuan Sumpah", status: "Ditandatangani", tarikhJana: "5 Mac 2024", ditandatanganiOleh: "Ahmad bin Abdullah" },
  { id: "L3", nama: "Jadual Bayaran Balik", status: "Disahkan", tarikhJana: "6 Mac 2024", ditandatanganiOleh: "Pegawai TEKUN" },
];

export const exceptions: ExceptionReport[] = [
  { id: "E1", idTransaksi: "D-2024-005", tarikh: "11 Mac 2024", sebab: "AI Fraud Detection - Akaun pihak ketiga mencurigakan", tindakan: "Menunggu semakan manual", status: "Belum Selesai", pegawaiBertanggungjawab: "Ahmad bin Kassim" },
  { id: "E2", idTransaksi: "D-2024-002", tarikh: "9 Mac 2024", sebab: "Transaksi ditolak bank - Baki tidak mencukupi", tindakan: "Notifikasi dihantar kepada pemohon", status: "Dalam Tindakan", pegawaiBertanggungjawab: "Siti Aminah" },
];

export const integrationStatuses: IntegrationStatus[] = [
  { sistem: "BSAS (Bursa Suq Al-Sila')", status: "Aktif", lastSync: "11 Mac 2024 10:30", transaksiHariIni: 12 },
  { sistem: "MyGDX", status: "Aktif", lastSync: "11 Mac 2024 10:28", transaksiHariIni: 8 },
  { sistem: "Takaful", status: "Aktif", lastSync: "11 Mac 2024 09:15", transaksiHariIni: 5 },
  { sistem: "EFT/IBG Perbankan", status: "Aktif", lastSync: "11 Mac 2024 10:35", transaksiHariIni: 24 },
  { sistem: "DuitNow", status: "Penyelenggaraan", lastSync: "10 Mac 2024 18:00" },
];

/** Simulate checksum/validation for account number (no real bank integration) */
export function simulateAccountValidation(noAkaun: string, _bank: string): { valid: boolean; message: string } {
  if (!noAkaun || noAkaun.trim().length === 0) return { valid: false, message: "Nombor akaun diperlukan" };
  const digits = noAkaun.replace(/\D/g, "");
  if (digits.length < 10) return { valid: false, message: "Nombor akaun tidak sah (minimum 10 digit). (Simulasi checksum)" };
  if (digits.length > 20) return { valid: false, message: "Format nombor akaun tidak sah" };
  return { valid: true, message: "Pengesahan checksum berjaya. Validasi real-time dengan bank: N/A (Simulasi)" };
}
