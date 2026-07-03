/**
 * Dummy data for Bayaran Pembiayaan module (no DB/API)
 */

export const PAYMENT_CHANNELS = [
  { id: "fpx", label: "FPX / TEKUNPay", banks: ["CIMB", "Maybank", "Bank Islam", "RHB", "Public Bank"] },
  { id: "ewallet", label: "e-Wallet", options: ["Touch 'n Go", "ShopeePay", "Boost", "GrabPay", "MAE"] },
  { id: "auto-debit", label: "Auto Debit (e-Mandate)", status: "Aktif" },
  { id: "kaunter", label: "Kaunter (Tunai/Kad Debit)" },
  { id: "eft", label: "EFT" },
  { id: "cek", label: "Cek" },
  { id: "kiriman", label: "Kiriman Wang" },
];

export const BAYARAN_ITEMS = [
  { id: "B-2024-001", nama: "Ahmad bin Abdullah", noAkaun: "A-001", jumlah: 2500, tarikh: "10 Mac 2024", kaedah: "FPX", status: "padan", rujukan: "FPX123456" },
  { id: "B-2024-002", nama: "Siti Nurhaliza binti Omar", noAkaun: "A-002", jumlah: 1500, tarikh: "9 Mac 2024", kaedah: "Tunai", status: "padan", rujukan: "TUN-001" },
  { id: "B-2024-003", nama: "Mohd Rizal bin Hassan", noAkaun: "A-003", jumlah: 3750, tarikh: "8 Mac 2024", kaedah: "Cek", status: "manual", rujukan: "CHQ-789" },
  { id: "B-2024-004", nama: "Fatimah binti Ibrahim", noAkaun: "A-004", jumlah: 1250, tarikh: "7 Mac 2024", kaedah: "FPX", status: "padan", rujukan: "FPX789012" },
  { id: "B-2024-005", nama: "Wong Siew Ling", noAkaun: "A-005", jumlah: 2000, tarikh: "6 Mac 2024", kaedah: "TNG", status: "padan", rujukan: "TNG-456" },
  { id: "B-2024-006", nama: "Raj Kumar", noAkaun: "A-006", jumlah: 5000, tarikh: "5 Mac 2024", kaedah: "Auto Debit", status: "padan", rujukan: "AD-202403" },
];

export const PEMADANAN_RESIT = [
  { id: "R-001", noAkaun: "A-001", ic: "850101011234", jumlah: 2500, tarikh: "10 Mac 2024", status: "auto", kodTransaksi: "FPX123456" },
  { id: "R-002", noAkaun: "A-002", ic: "920505055678", jumlah: 1500, tarikh: "9 Mac 2024", status: "manual", kodTransaksi: "TUN-001" },
  { id: "R-003", noAkaun: "UNMATCHED", ic: "-", jumlah: 3500, tarikh: "8 Mac 2024", status: "unverified", kodTransaksi: "-" },
  { id: "R-004", noAkaun: "A-004", ic: "880303033456", jumlah: 1250, tarikh: "7 Mac 2024", status: "auto", kodTransaksi: "FPX789012" },
];

export const REKON_BANK = [
  { tarikh: "10 Mac 2024", masa: "09:15", jumlah: 2500, noAkaun: "A-001", kodEjen: "E001", noCek: "-", status: "padan" },
  { tarikh: "10 Mac 2024", masa: "11:22", jumlah: 1500, noAkaun: "A-002", kodEjen: "E002", noCek: "-", status: "padan" },
  { tarikh: "10 Mac 2024", masa: "14:05", jumlah: 3500, noAkaun: "-", kodEjen: "-", noCek: "CHQ-789", status: "belum" },
  { tarikh: "9 Mac 2024", masa: "10:30", jumlah: 3750, noAkaun: "A-003", kodEjen: "E003", noCek: "CHQ-456", status: "padan" },
];

export const LEBIHAN_KEKURANGAN = [
  { noAkaun: "A-001", nama: "Ahmad bin Abdullah", jenis: "lebihan", jumlah: 500, ansuranDijangka: 2500, diterima: 3000, status: "offset" },
  { noAkaun: "A-007", nama: "Lee Mei Ling", jenis: "kekurangan", jumlah: 250, ansuranDijangka: 1500, diterima: 1250, status: "tunggakan" },
  { noAkaun: "A-008", nama: "Muthu Raj", jenis: "lebihan", jumlah: 1000, ansuranDijangka: 2000, diterima: 3000, status: "advance" },
];

export const PENYATA_BAYARAN = [
  { tempoh: "Jan–Jun 2025", noAkaun: "A-001", nama: "Ahmad bin Abdullah", tarikhJana: "1 Jul 2025", format: "PDF" },
  { tempoh: "Jan–Mac 2025", noAkaun: "A-002", nama: "Siti Nurhaliza", tarikhJana: "5 Apr 2025", format: "PDF" },
  { tempoh: "2024 (Tahunan)", noAkaun: "A-003", nama: "Mohd Rizal", tarikhJana: "15 Jan 2025", format: "PDF" },
];

export const EARLY_SETTLEMENT_DUMMY = {
  noAkaun: "A-001",
  nama: "Ahmad bin Abdullah",
  bakiPinjaman: 45000,
  ansuranBulanan: 2500,
  tarikhBayaranAkhir: "10 Mac 2025",
  rebatDuaBulan: 5000,
  amaunBersih: 40000,
};

export const AKAUN_SELESAI_BAYAR = [
  { noAkaun: "A-010", nama: "Ali bin Hassan", tarikhSelesai: "1 Mac 2024", statusNOD: "Dalam proses", cawangan: "Kuala Lumpur" },
  { noAkaun: "A-011", nama: "Sara binti Omar", tarikhSelesai: "15 Feb 2024", statusNOD: "Dihantar", cawangan: "Johor Bahru" },
  { noAkaun: "A-012", nama: "Chen Wei Ming", tarikhSelesai: "28 Feb 2024", statusNOD: "Dalam proses", cawangan: "Penang" },
];

export const AI_OCR_RESULTS = [
  { resitId: "R-001", key: "No. Akaun", value: "A-001", confidence: 98 },
  { resitId: "R-001", key: "Jumlah", value: "RM 2,500.00", confidence: 99 },
  { resitId: "R-001", key: "Tarikh", value: "10/03/2024", confidence: 97 },
];

export const AI_ANALYTICS = [
  { label: "Lebihan terdeteksi", count: 12, trend: "naik" },
  { label: "Kekurangan terdeteksi", count: 5, trend: "turun" },
  { label: "Cadangan pelarasan auto", count: 8, trend: "stabil" },
];

export const CHATBOT_SAMPLE_QA = [
  { q: "Berapa baki pinjaman saya?", a: "Baki pinjaman anda: RM 45,000.00 (No. Akaun: A-001)" },
  { q: "Bila tarikh bayaran seterusnya?", a: "Ansuran seterusnya: 10 April 2025, RM 2,500.00" },
  { q: "Cara buat bayaran?", a: "Anda boleh bayar melalui FPX, e-Wallet, Auto Debit atau di kaunter TEKUN." },
];
