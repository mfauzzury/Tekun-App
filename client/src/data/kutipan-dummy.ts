/**
 * Dummy data for Kutipan module - no DB/API
 */

export const STATUS_KUTIPAN = [
  { value: "dalam-tindakan", label: "Dalam Tindakan Kutipan" },
  { value: "janji-bayar", label: "Janji Bayar" },
  { value: "berjaya", label: "Berjaya" },
  { value: "gagal", label: "Gagal" },
  { value: "sedia-litigasi", label: "Sedia untuk Tindakan Undang-Undang" },
  { value: "berjaya-dihubungi", label: "Berjaya Dihubungi" },
  { value: "gagal-hubungi", label: "Gagal Hubungi" },
  { value: "rujuk-litigasi", label: "Perlu Rujuk Litigasi" },
] as const;

export const MAKLUM_BALAS_PANGGILAN = [
  { value: "bayar-segera", label: "Bayar segera" },
  { value: "janji-bayar", label: "Janji bayar" },
  { value: "rna", label: "Tiada jawapan (RNA)" },
  { value: "nis", label: "Tiada dalam perkhidmatan (NIS)" },
  { value: "vm", label: "Masuk ke peti suara (VM)" },
  { value: "busy", label: "Nombor sibuk (Busy)" },
  { value: "fup", label: "Usahawan minta dihubungi semula (FUP)" },
  { value: "eng", label: "Talian sibuk (ENG)" },
] as const;

export const CAWANGAN = ["Kuala Lumpur", "Kuala Terengganu", "Kota Bharu", "Kuantan", "Johor Bahru"] as const;
export const ZON = ["Zon A", "Zon B", "Zon C", "Zon Utara", "Zon Selatan"] as const;
export const PEGAWAI = ["Ahmad bin Ali", "Siti Nurhaliza", "Mohd Rizal", "Fatimah Ibrahim", "Wan Aziz"] as const;

export interface KutipanItem {
  id: string;
  noPembiayaan: string;
  nama: string;
  jumlahTunggakan: string;
  jumlahDikutip: string;
  tarikhAkhirBayaran: string;
  tarikhLawatan: string;
  cawangan: string;
  pegawaiSeliaan: string;
  zon: string;
  status: string;
  hariLewat: number;
  maklumatPsat?: string;
}

export const KUTIPAN_ITEMS: KutipanItem[] = [
  { id: "K-2024-001", noPembiayaan: "PF-2023-001", nama: "Ahmad bin Abdullah", jumlahTunggakan: "RM 3,200", jumlahDikutip: "RM 2,500", tarikhAkhirBayaran: "28 Feb 2024", tarikhLawatan: "10 Mac 2024", cawangan: "Kuala Lumpur", pegawaiSeliaan: "Ahmad bin Ali", zon: "Zon A", status: "Berjaya", hariLewat: 45, maklumatPsat: "PSAT 15/2024" },
  { id: "K-2024-002", noPembiayaan: "PF-2023-012", nama: "Siti Nurhaliza binti Omar", jumlahTunggakan: "RM 2,100", jumlahDikutip: "RM 1,500", tarikhAkhirBayaran: "15 Feb 2024", tarikhLawatan: "9 Mac 2024", cawangan: "Kuala Terengganu", pegawaiSeliaan: "Siti Nurhaliza", zon: "Zon B", status: "Berjaya", hariLewat: 52, maklumatPsat: "PSAT 12/2024" },
  { id: "K-2024-003", noPembiayaan: "PF-2023-008", nama: "Mohd Rizal bin Hassan", jumlahTunggakan: "RM 4,500", jumlahDikutip: "—", tarikhAkhirBayaran: "10 Jan 2024", tarikhLawatan: "8 Mac 2024", cawangan: "Kota Bharu", pegawaiSeliaan: "Mohd Rizal", zon: "Zon C", status: "Gagal", hariLewat: 89, maklumatPsat: "PSAT 08/2024" },
  { id: "K-2024-004", noPembiayaan: "PF-2023-025", nama: "Fatimah binti Ibrahim", jumlahTunggakan: "RM 1,800", jumlahDikutip: "RM 1,250", tarikhAkhirBayaran: "28 Feb 2024", tarikhLawatan: "7 Mac 2024", cawangan: "Kuantan", pegawaiSeliaan: "Fatimah Ibrahim", zon: "Zon A", status: "Berjaya", hariLewat: 38 },
  { id: "K-2024-005", noPembiayaan: "PF-2023-033", nama: "Wan Azman bin Yusof", jumlahTunggakan: "RM 2,700", jumlahDikutip: "—", tarikhAkhirBayaran: "10 Feb 2024", tarikhLawatan: "10 Mac 2024", cawangan: "Johor Bahru", pegawaiSeliaan: "Wan Aziz", zon: "Zon Selatan", status: "Janji Bayar", hariLewat: 58 },
  { id: "K-2024-006", noPembiayaan: "PF-2023-041", nama: "Norazlina binti Kamal", jumlahTunggakan: "RM 5,200", jumlahDikutip: "—", tarikhAkhirBayaran: "5 Jan 2024", tarikhLawatan: "8 Mac 2024", cawangan: "Kuala Lumpur", pegawaiSeliaan: "Ahmad bin Ali", zon: "Zon A", status: "Sedia untuk Tindakan Undang-Undang", hariLewat: 95 },
  { id: "K-2024-007", noPembiayaan: "PF-2023-019", nama: "Lee Chong Wei", jumlahTunggakan: "RM 1,200", jumlahDikutip: "RM 1,200", tarikhAkhirBayaran: "28 Feb 2024", tarikhLawatan: "9 Mac 2024", cawangan: "Kuala Terengganu", pegawaiSeliaan: "Siti Nurhaliza", zon: "Zon B", status: "Berjaya", hariLewat: 40 },
  { id: "K-2024-008", noPembiayaan: "PF-2023-055", nama: "Rahimah binti Mat", jumlahTunggakan: "RM 3,800", jumlahDikutip: "—", tarikhAkhirBayaran: "10 Feb 2024", tarikhLawatan: "10 Mac 2024", cawangan: "Kota Bharu", pegawaiSeliaan: "Mohd Rizal", zon: "Zon C", status: "Dalam Tindakan Kutipan", hariLewat: 62 },
];

export interface SkhItem {
  id: string;
  noPembiayaan: string;
  nama: string;
  zon: string;
  pegawaiSeliaan: string;
  hariKutipan: string;
  kawasan: string;
  status: string;
}

export const SKH_ITEMS: SkhItem[] = [
  { id: "SKH-001", noPembiayaan: "PF-2023-001", nama: "Ahmad bin Abdullah", zon: "Zon A", pegawaiSeliaan: "Ahmad bin Ali", hariKutipan: "Isnin", kawasan: "Blok A1", status: "Selesai" },
  { id: "SKH-002", noPembiayaan: "PF-2023-012", nama: "Siti Nurhaliza binti Omar", zon: "Zon B", pegawaiSeliaan: "Siti Nurhaliza", hariKutipan: "Selasa", kawasan: "Blok B2", status: "Selesai" },
  { id: "SKH-003", noPembiayaan: "PF-2023-008", nama: "Mohd Rizal bin Hassan", zon: "Zon C", pegawaiSeliaan: "Mohd Rizal", hariKutipan: "Rabu", kawasan: "Blok C1", status: "Belum" },
  { id: "SKH-004", noPembiayaan: "PF-2023-025", nama: "Fatimah binti Ibrahim", zon: "Zon A", pegawaiSeliaan: "Fatimah Ibrahim", hariKutipan: "Isnin", kawasan: "Blok A2", status: "Selesai" },
  { id: "SKH-005", noPembiayaan: "PF-2023-033", nama: "Wan Azman bin Yusof", zon: "Zon Selatan", pegawaiSeliaan: "Wan Aziz", hariKutipan: "Khamis", kawasan: "Blok S1", status: "Dalam Tindakan" },
];

export interface CallCenterItem {
  id: string;
  noPembiayaan: string;
  nama: string;
  noTelefon: string;
  tarikhPanggilan: string;
  pegawai: string;
  maklumBalas: string;
  catatan: string;
}

export const CALL_CENTER_ITEMS: CallCenterItem[] = [
  { id: "CC-001", noPembiayaan: "PF-2023-001", nama: "Ahmad bin Abdullah", noTelefon: "012-3456789", tarikhPanggilan: "10 Mac 2024 10:30", pegawai: "Ahmad bin Ali", maklumBalas: "Bayar segera", catatan: "Peminjam akan bayar hari ini" },
  { id: "CC-002", noPembiayaan: "PF-2023-033", nama: "Wan Azman bin Yusof", noTelefon: "019-8765432", tarikhPanggilan: "10 Mac 2024 11:15", pegawai: "Siti Nurhaliza", maklumBalas: "Janji bayar", catatan: "Janji bayar minggu depan" },
  { id: "CC-003", noPembiayaan: "PF-2023-008", nama: "Mohd Rizal bin Hassan", noTelefon: "013-1112233", tarikhPanggilan: "9 Mac 2024 14:00", pegawai: "Mohd Rizal", maklumBalas: "Tiada jawapan (RNA)", catatan: "3 percubaan, tiada jawapan" },
  { id: "CC-004", noPembiayaan: "PF-2023-025", nama: "Fatimah binti Ibrahim", noTelefon: "017-5556677", tarikhPanggilan: "9 Mac 2024 09:45", pegawai: "Fatimah Ibrahim", maklumBalas: "Bayar segera", catatan: "Sudah bayar melalui FPX" },
  { id: "CC-005", noPembiayaan: "PF-2023-041", nama: "Norazlina binti Kamal", noTelefon: "012-9998877", tarikhPanggilan: "8 Mac 2024 16:20", pegawai: "Wan Aziz", maklumBalas: "Masuk ke peti suara (VM)", catatan: "Perlu susulan" },
];

export interface PsatItem {
  id: string;
  tajuk: string;
  tarikh: string;
  lokasi: string;
  peserta: string;
  status: string;
}

export const PSAT_ITEMS: PsatItem[] = [
  { id: "PSAT-001", tajuk: "Perjumpaan Setempat Ahli TEMAN - Wilayah 1", tarikh: "15 Mac 2024", lokasi: "Dewan Cawangan KL", peserta: "25 usahawan", status: "Selesai" },
  { id: "PSAT-002", tajuk: "Perjumpaan Setempat Ahli TEMAN - Wilayah 2", tarikh: "22 Mac 2024", lokasi: "Dewan Cawangan KT", peserta: "18 usahawan", status: "Scheduled" },
];

export const SKM_MINGGUAN = [
  { minggu: "Minggu 1 (1-7 Mac)", jumlah: 12, berjaya: 9, gagal: 2, janjiBayar: 1 },
  { minggu: "Minggu 2 (8-14 Mac)", jumlah: 15, berjaya: 11, gagal: 2, janjiBayar: 2 },
];

export interface AuditLogItem {
  id: string;
  tarikh: string;
  pegawai: string;
  tindakan: string;
  akaun: string;
  outcome: string;
}

export const AUDIT_LOG_ITEMS: AuditLogItem[] = [
  { id: "AL-001", tarikh: "10 Mac 2024 14:00", pegawai: "Ahmad bin Ali", tindakan: "Lawatan lapangan", akaun: "PF-2023-001", outcome: "Berjaya - RM 2,500" },
  { id: "AL-002", tarikh: "10 Mac 2024 11:15", pegawai: "Siti Nurhaliza", tindakan: "Panggilan Call Center", akaun: "PF-2023-033", outcome: "Janji bayar" },
  { id: "AL-003", tarikh: "9 Mac 2024 16:20", pegawai: "Wan Aziz", tindakan: "Lawatan lapangan", akaun: "PF-2023-041", outcome: "Gagal - tiada di premis" },
  { id: "AL-004", tarikh: "9 Mac 2024 10:30", pegawai: "Ahmad bin Ali", tindakan: "Lawatan lapangan", akaun: "PF-2023-001", outcome: "Berjaya - RM 2,500" },
  { id: "AL-005", tarikh: "8 Mac 2024 14:00", pegawai: "Mohd Rizal", tindakan: "Panggilan Call Center", akaun: "PF-2023-008", outcome: "RNA - Gagal hubungi" },
];

export interface KpiPegawai {
  rank: number;
  nama: string;
  cawangan: string;
  lawatanBerjaya: number;
  peratusKejayaan: number;
  sasaran: number;
  peratusVsSasaran: number;
  akaunDipulihkan: number;
}

export const KPI_PEGAWAI: KpiPegawai[] = [
  { rank: 1, nama: "Ahmad bin Ali", cawangan: "Kuala Lumpur", lawatanBerjaya: 42, peratusKejayaan: 93, sasaran: 40, peratusVsSasaran: 105, akaunDipulihkan: 8 },
  { rank: 2, nama: "Siti Nurhaliza", cawangan: "Kuala Terengganu", lawatanBerjaya: 38, peratusKejayaan: 88, sasaran: 38, peratusVsSasaran: 100, akaunDipulihkan: 6 },
  { rank: 3, nama: "Fatimah Ibrahim", cawangan: "Kuantan", lawatanBerjaya: 35, peratusKejayaan: 85, sasaran: 35, peratusVsSasaran: 100, akaunDipulihkan: 5 },
  { rank: 4, nama: "Wan Aziz", cawangan: "Johor Bahru", lawatanBerjaya: 32, peratusKejayaan: 82, sasaran: 35, peratusVsSasaran: 91, akaunDipulihkan: 4 },
  { rank: 5, nama: "Mohd Rizal", cawangan: "Kota Bharu", lawatanBerjaya: 28, peratusKejayaan: 78, sasaran: 32, peratusVsSasaran: 88, akaunDipulihkan: 3 },
];
