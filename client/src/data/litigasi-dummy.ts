/**
 * Dummy data for Litigasi / Undang-Undang module - no DB/API
 * SPEC Undang n Litigasi
 */

// 7.1 Panel Peguam
export interface PanelPeguam {
  id: string;
  namaFirma: string;
  noPendaftaran: string;
  alamat: string;
  negeri: string;
  pegawaiUtama: string;
  telefon: string;
  emel: string;
  status: "aktif" | "tidak-aktif" | "dalam-semakan";
  tarikhLantikan: string;
  kuotaNod: number;
  kesAktif: number;
}

export const PANEL_PEGUAM: PanelPeguam[] = [
  { id: "PP-001", namaFirma: "Messrs Ahmad & Associates", noPendaftaran: "LF-1234", alamat: "No 12, Jalan Tun Razak", negeri: "Kuala Lumpur", pegawaiUtama: "Ahmad bin Abdullah", telefon: "03-12345678", emel: "ahmad@firma.com", status: "aktif", tarikhLantikan: "15 Jan 2023", kuotaNod: 1000, kesAktif: 45 },
  { id: "PP-002", namaFirma: "Messrs Siti & Partners", noPendaftaran: "LF-2345", alamat: "Suite 5, Menara KL", negeri: "Kuala Lumpur", pegawaiUtama: "Siti Nurhaliza", telefon: "03-23456789", emel: "siti@firma.com", status: "aktif", tarikhLantikan: "20 Feb 2023", kuotaNod: 1000, kesAktif: 32 },
  { id: "PP-003", namaFirma: "Messrs Rizal Legal", noPendaftaran: "LF-3456", alamat: "No 8, Jalan Sultan Ismail", negeri: "Kuala Lumpur", pegawaiUtama: "Mohd Rizal", telefon: "03-34567890", emel: "rizal@firma.com", status: "aktif", tarikhLantikan: "10 Mac 2023", kuotaNod: 800, kesAktif: 28 },
  { id: "PP-004", namaFirma: "Messrs Fatimah & Co", noPendaftaran: "LF-4567", alamat: "Lot 15, Wisma TEKUN", negeri: "Kuala Terengganu", pegawaiUtama: "Fatimah Ibrahim", telefon: "09-1234567", emel: "fatimah@firma.com", status: "dalam-semakan", tarikhLantikan: "—", kuotaNod: 0, kesAktif: 0 },
];

// 7.2 Akaun NPF
export interface AkaunNpf {
  id: string;
  noAkaun: string;
  nama: string;
  noIc: string;
  cawangan: string;
  negeri: string;
  jumlahTunggakan: string;
  hariTunggakan: number;
  tarikhAkhirBayaran: string;
  statusNod: "belum" | "dalaman-hantar" | "panel-hantar" | "selesai";
  status: "disahkan" | "menunggu" | "ditolak";
  submittedBy: string;
  tarikhSubmit: string;
}

export const AKAUN_NPF: AkaunNpf[] = [
  { id: "NPF-001", noAkaun: "PF-2023-041", nama: "Norazlina binti Kamal", noIc: "850315-10-5432", cawangan: "Kuala Lumpur", negeri: "Wilayah Persekutuan", jumlahTunggakan: "RM 5,200", hariTunggakan: 95, tarikhAkhirBayaran: "5 Jan 2024", statusNod: "belum", status: "disahkan", submittedBy: "Ahmad bin Ali", tarikhSubmit: "10 Mac 2024" },
  { id: "NPF-002", noAkaun: "PF-2023-008", nama: "Mohd Rizal bin Hassan", noIc: "780512-08-1234", cawangan: "Kota Bharu", negeri: "Kelantan", jumlahTunggakan: "RM 4,500", hariTunggakan: 89, tarikhAkhirBayaran: "10 Jan 2024", statusNod: "dalaman-hantar", status: "disahkan", submittedBy: "Mohd Rizal", tarikhSubmit: "8 Mac 2024" },
  { id: "NPF-003", noAkaun: "PF-2023-055", nama: "Rahimah binti Mat", noIc: "920618-14-6789", cawangan: "Kota Bharu", negeri: "Kelantan", jumlahTunggakan: "RM 3,800", hariTunggakan: 62, tarikhAkhirBayaran: "10 Feb 2024", statusNod: "belum", status: "menunggu", submittedBy: "Mohd Rizal", tarikhSubmit: "9 Mac 2024" },
  { id: "NPF-004", noAkaun: "PF-2023-033", nama: "Wan Azman bin Yusof", noIc: "881022-06-4321", cawangan: "Johor Bahru", negeri: "Johor", jumlahTunggakan: "RM 2,700", hariTunggakan: 58, tarikhAkhirBayaran: "10 Feb 2024", statusNod: "belum", status: "menunggu", submittedBy: "Wan Aziz", tarikhSubmit: "10 Mac 2024" },
  { id: "NPF-005", noAkaun: "PF-2022-089", nama: "Syarikat XYZ Sdn Bhd", noIc: "C-12345678", cawangan: "Kuala Lumpur", negeri: "Wilayah Persekutuan", jumlahTunggakan: "RM 45,000", hariTunggakan: 120, tarikhAkhirBayaran: "1 Dis 2023", statusNod: "panel-hantar", status: "disahkan", submittedBy: "Ahmad bin Ali", tarikhSubmit: "15 Jan 2024" },
];

// 7.4–7.6 NOD & Saman
export interface NodItem {
  id: string;
  noRujukan: string;
  noAkaun: string;
  nama: string;
  jenis: "dalaman" | "panel";
  tarikhMohon: string;
  tarikhHantar: string | null;
  status: "draf" | "menunggu-bsk" | "dihantar" | "return" | "selesai";
  panelPeguam?: string;
  semakanFizikal?: "belum" | "lulus" | "gagal";
}

export const NOD_ITEMS: NodItem[] = [
  { id: "NOD-001", noRujukan: "NOD-2024-001", noAkaun: "PF-2023-041", nama: "Norazlina binti Kamal", jenis: "dalaman", tarikhMohon: "10 Mac 2024", tarikhHantar: "12 Mac 2024", status: "dihantar", semakanFizikal: "lulus" },
  { id: "NOD-002", noRujukan: "NOD-2024-002", noAkaun: "PF-2023-008", nama: "Mohd Rizal bin Hassan", jenis: "panel", tarikhMohon: "8 Mac 2024", tarikhHantar: "15 Mac 2024", status: "dihantar", panelPeguam: "Messrs Ahmad & Associates", semakanFizikal: "lulus" },
  { id: "NOD-003", noRujukan: "NOD-2024-003", noAkaun: "PF-2022-089", nama: "Syarikat XYZ Sdn Bhd", jenis: "panel", tarikhMohon: "20 Jan 2024", tarikhHantar: null, status: "menunggu-bsk", panelPeguam: "Messrs Siti & Partners" },
  { id: "NOD-004", noRujukan: "NOD-2024-004", noAkaun: "PF-2023-055", nama: "Rahimah binti Mat", jenis: "dalaman", tarikhMohon: "11 Mac 2024", tarikhHantar: null, status: "draf", semakanFizikal: "belum" },
];

// 7.6–7.8 Kes Litigasi
export type StatusKes = "fail-dibuka" | "nod-dihantar" | "saman-dihantar" | "pendengaran" | "perbicaraan" | "penghakiman" | "execution" | "selesai" | "henti";

export interface KesLitigasi {
  id: string;
  noKes: string;
  noKesMahkamah?: string;
  namaMahkamah?: string;
  noAkaun: string;
  nama: string;
  cawangan: string;
  negeri: string;
  produk: string;
  jumlahTuntutan: string;
  tarikhBuka: string;
  tarikhPendengaran?: string;
  tarikhPerbicaraan?: string;
  tarikhPenghakiman?: string;
  status: StatusKes;
  panelPeguam?: string;
  jenisPenghakiman?: "ingkar-kehadiran" | "ingkar-pembelaan" | "terus" | "persetujuan" | "bicara-penuh";
  keputusan?: "menang" | "kalah" | "penyelesaian-luar";
}

export const KES_LITIGASI: KesLitigasi[] = [
  { id: "L-001", noKes: "PK-2024-001", noKesMahkamah: "B-22-1234-2024", namaMahkamah: "Mahkamah Sesyen Kuala Lumpur", noAkaun: "PF-2023-001", nama: "Ahmad bin Abdullah", cawangan: "Kuala Lumpur", negeri: "Wilayah Persekutuan", produk: "Pembiayaan Mikro", jumlahTuntutan: "RM 3,200", tarikhBuka: "15 Jan 2024", tarikhPendengaran: "20 Mac 2024", tarikhPerbicaraan: "5 Apr 2024", status: "perbicaraan", panelPeguam: "Messrs Ahmad & Associates" },
  { id: "L-002", noKes: "PK-2024-002", noKesMahkamah: "B-22-1235-2024", namaMahkamah: "Mahkamah Tinggi Kuala Lumpur", noAkaun: "PF-2022-089", nama: "Syarikat XYZ Sdn Bhd", cawangan: "Kuala Lumpur", negeri: "Wilayah Persekutuan", produk: "Pembiayaan Kontrak-i", jumlahTuntutan: "RM 45,000", tarikhBuka: "20 Feb 2024", tarikhPendengaran: "25 Mac 2024", status: "pendengaran", panelPeguam: "Messrs Siti & Partners" },
  { id: "L-003", noKes: "PK-2024-003", noAkaun: "PF-2023-012", nama: "Mohd Rizal bin Hassan", cawangan: "Kuala Terengganu", negeri: "Terengganu", produk: "Pembiayaan Mikro", jumlahTuntutan: "RM 4,500", tarikhBuka: "5 Mac 2024", tarikhPenghakiman: "10 Mac 2024", status: "selesai", jenisPenghakiman: "ingkar-kehadiran", keputusan: "menang" },
  { id: "L-004", noKes: "PK-2024-004", noAkaun: "PF-2023-025", nama: "Fatimah binti Ibrahim", cawangan: "Kuantan", negeri: "Pahang", produk: "Pembiayaan Mikro", jumlahTuntutan: "RM 1,800", tarikhBuka: "10 Mac 2024", status: "fail-dibuka" },
  { id: "L-005", noKes: "PK-2024-005", noKesMahkamah: "B-22-1236-2024", namaMahkamah: "Mahkamah Sesyen Johor Bahru", noAkaun: "PF-2023-033", nama: "Wan Azman bin Yusof", cawangan: "Johor Bahru", negeri: "Johor", produk: "Pembiayaan Mikro", jumlahTuntutan: "RM 2,700", tarikhBuka: "12 Mac 2024", tarikhPendengaran: "28 Mac 2024", status: "saman-dihantar", panelPeguam: "Messrs Rizal Legal" },
];

// 7.9 Execution
export interface ExecutionItem {
  id: string;
  noKes: string;
  nama: string;
  jumlahDiputuskan: string;
  tarikhKeputusan: string;
  jenisPenghakiman: string;
  status: "draf" | "permohonan" | "notis-hantar" | "penyitaan" | "lelongan" | "selesai";
  tarikhExecution?: string;
}

export const EXECUTION_ITEMS: ExecutionItem[] = [
  { id: "EX-001", noKes: "PK-2024-003", nama: "Mohd Rizal bin Hassan", jumlahDiputuskan: "RM 4,500", tarikhKeputusan: "10 Mac 2024", jenisPenghakiman: "Ingkar Kehadiran", status: "permohonan", tarikhExecution: "15 Mac 2024" },
  { id: "EX-002", noKes: "PK-2023-089", nama: "Abdul Rahman bin Ismail", jumlahDiputuskan: "RM 12,000", tarikhKeputusan: "5 Feb 2024", jenisPenghakiman: "Terus", status: "penyitaan" },
  { id: "EX-003", noKes: "PK-2023-076", nama: "Siti Aminah binti Yusof", jumlahDiputuskan: "RM 8,500", tarikhKeputusan: "20 Jan 2024", jenisPenghakiman: "Persetujuan", status: "selesai", tarikhExecution: "28 Feb 2024" },
];

// 7.10 WSS
export interface WssItem {
  id: string;
  noKes: string;
  nama: string;
  jenisAset: string;
  lokasi: string;
  nilaiSemasa: string;
  tarikhPenyitaan: string;
  status: "penyitaan" | "lelongan-dijadual" | "lelongan-selesai" | "hasil-dikredit";
}

export const WSS_ITEMS: WssItem[] = [
  { id: "WSS-001", noKes: "PK-2023-089", nama: "Abdul Rahman bin Ismail", jenisAset: "Kenderaan (Kereta)", lokasi: "Shah Alam, Selangor", nilaiSemasa: "RM 25,000", tarikhPenyitaan: "15 Mac 2024", status: "penyitaan" },
  { id: "WSS-002", noKes: "PK-2023-055", nama: "Lee Ming Sdn Bhd", jenisAset: "Mesin & Peralatan", lokasi: "Klang, Selangor", nilaiSemasa: "RM 45,000", tarikhPenyitaan: "10 Feb 2024", status: "lelongan-dijadual" },
  { id: "WSS-003", noKes: "PK-2022-120", nama: "Hassan bin Omar", jenisAset: "Tanah & Bangunan", lokasi: "Kota Bharu, Kelantan", nilaiSemasa: "RM 180,000", tarikhPenyitaan: "5 Jan 2024", status: "lelongan-selesai" },
];

// 7.11 Garnishee
export interface GarnisheeItem {
  id: string;
  noKes: string;
  nama: string;
  institusi: string;
  noAkaun: string;
  jumlah: string;
  status: "permohonan" | "diluluskan" | "dikredit" | "ditolak";
  tarikh: string;
}

export const GARNISHEE_ITEMS: GarnisheeItem[] = [
  { id: "GAR-001", noKes: "PK-2024-001", nama: "Ahmad bin Abdullah", institusi: "Bank Islam Malaysia", noAkaun: "***1234", jumlah: "RM 3,200", status: "permohonan", tarikh: "18 Mac 2024" },
  { id: "GAR-002", noKes: "PK-2023-098", nama: "Noraini binti Ahmad", institusi: "Maybank", noAkaun: "***5678", jumlah: "RM 5,500", status: "dikredit", tarikh: "10 Mac 2024" },
];

// 7.12 JDS
export interface JdsItem {
  id: string;
  noKes: string;
  nama: string;
  tarikhJds: string;
  status: "dijadual" | "hadir" | "tidak-hadir" | "tangguh" | "selesai";
  keputusan?: string;
}

export const JDS_ITEMS: JdsItem[] = [
  { id: "JDS-001", noKes: "PK-2024-002", nama: "Syarikat XYZ Sdn Bhd", tarikhJds: "28 Mac 2024", status: "dijadual" },
  { id: "JDS-002", noKes: "PK-2023-087", nama: "Kamarul bin Zainal", tarikhJds: "15 Mac 2024", status: "tidak-hadir", keputusan: "Warrant ditahan" },
];

// 7.13 Kebankrapan
export interface KebankrapanItem {
  id: string;
  noKes: string;
  nama: string;
  noKesInsolvensi: string;
  tarikhBankrap: string;
  tarikhPod: string;
  status: "dalam-proses" | "pod-dihantar" | "block-payment" | "selesai";
}

export const KEBANKRAPAN_ITEMS: KebankrapanItem[] = [
  { id: "BKR-001", noKes: "PK-2022-089", nama: "Syarikat ABC Sdn Bhd", noKesInsolvensi: "B-28-1234-2023", tarikhBankrap: "15 Nov 2023", tarikhPod: "20 Nov 2023", status: "block-payment" },
  { id: "BKR-002", noKes: "PK-2023-045", nama: "Mohd Fadzil bin Ibrahim", noKesInsolvensi: "B-28-2345-2024", tarikhBankrap: "10 Mac 2024", tarikhPod: "—", status: "dalam-proses" },
];

// 7.14 Winding-Up
export interface WindingUpItem {
  id: string;
  noKes: string;
  namaSyarikat: string;
  noKesWindingUp: string;
  tarikhPetisyen: string;
  status: "petisyen" | "perintah" | "block-payment" | "liquidator";
}

export const WINDING_UP_ITEMS: WindingUpItem[] = [
  { id: "WU-001", noKes: "PK-2022-089", namaSyarikat: "Syarikat XYZ Sdn Bhd", noKesWindingUp: "W-28-5678-2024", tarikhPetisyen: "5 Mac 2024", status: "petisyen" },
];

// 7.15 Audit
export interface LitigasiAuditItem {
  id: string;
  tarikh: string;
  masa: string;
  pegawai: string;
  tindakan: string;
  kes: string;
  firmaGuaman?: string;
  dokumen?: string;
}

export const LITIGASI_AUDIT: LitigasiAuditItem[] = [
  { id: "AUD-001", tarikh: "18 Mac 2024", masa: "10:32", pegawai: "Ahmad bin Ali (BSK)", tindakan: "Pengesahan permohonan saman", kes: "PK-2024-005", firmaGuaman: "Messrs Rizal Legal" },
  { id: "AUD-002", tarikh: "18 Mac 2024", masa: "09:15", pegawai: "Siti Nurhaliza (JUU)", tindakan: "Kemaskini status penghakiman", kes: "PK-2024-002", dokumen: "Surat keputusan mahkamah.pdf" },
  { id: "AUD-003", tarikh: "17 Mac 2024", masa: "14:20", pegawai: "Mohd Rizal (PC)", tindakan: "Upload salinan NOD", kes: "NOD-2024-002", firmaGuaman: "Messrs Ahmad & Associates" },
  { id: "AUD-004", tarikh: "17 Mac 2024", masa: "11:00", pegawai: "Fatimah Ibrahim (BSK)", tindakan: "Kelulusan akaun NPF", kes: "NPF-003" },
  { id: "AUD-005", tarikh: "16 Mac 2024", masa: "16:45", pegawai: "Wan Aziz (Admin)", tindakan: "Kemaskini direktori panel peguam", kes: "—", firmaGuaman: "Messrs Fatimah & Co" },
];

// Laporan
export const LAPORAN_KES_AKTIF = [
  { mahkamah: "Mahkamah Sesyen KL", kes: 12, nilai: "RM 45,200" },
  { mahkamah: "Mahkamah Tinggi KL", kes: 5, nilai: "RM 120,500" },
  { mahkamah: "Mahkamah Sesyen JB", kes: 8, nilai: "RM 32,100" },
  { mahkamah: "Mahkamah Sesyen KB", kes: 6, nilai: "RM 28,400" },
];

export const LAPORAN_KEPUTUSAN_BULANAN = [
  { bulan: "Jan 2024", menang: 3, kalah: 0, selesai: 2 },
  { bulan: "Feb 2024", menang: 5, kalah: 1, selesai: 4 },
  { bulan: "Mac 2024", menang: 2, kalah: 0, selesai: 3 },
];

export const NEGERI_OPTIONS = ["Semua", "Wilayah Persekutuan", "Selangor", "Johor", "Kelantan", "Terengganu", "Pahang"];
export const CAWANGAN_OPTIONS = ["Semua", "Kuala Lumpur", "Kota Bharu", "Kuantan", "Johor Bahru", "Kuala Terengganu"];
export const PRODUK_OPTIONS = ["Semua", "Pembiayaan Mikro", "Pembiayaan Kontrak-i", "Pembiayaan Khas"];
