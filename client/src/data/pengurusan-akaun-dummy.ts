/**
 * Dummy data for Pengurusan Akaun module (no DB/API connection)
 * Per SPEC Pengurusan Akaun.csv - tracking baki, status, carian dinamik
 */

export type AkaunStatus =
  | "Aktif"
  | "Selesai"
  | "Tunggakan"
  | "NPF"
  | "Dibekukan"
  | "Batal"
  | "Write Off"
  | "Meninggal Dunia"
  | "Reschedule";

export type RisikoLevel = "Normal" | "Amaran" | "Kritikal";

export interface AkaunItem {
  id: string;
  ic: string;
  nama: string;
  namaSyarikat?: string;
  ssm?: string;
  pukonsa?: string;
  cawangan: string;
  negeri: string;
  produk: string;
  tarikhMula: string;
  tarikhTamat: string;
  jumlahPembiayaan: number;
  bakiPokok: number;
  bakiKeuntungan: number;
  bakiSimpanan: number;
  penalti: number;
  tunggakan: number;
  bakiAkhir: number;
  bayaranBulanan: number;
  status: AkaunStatus;
  risiko: RisikoLevel;
  noBsas?: string;
  snc?: boolean;
}

export const CAWANGAN_OPTIONS = [
  "Kuala Lumpur",
  "Shah Alam",
  "Johor Bahru",
  "Ipoh",
  "Kota Bharu",
  "Kota Kinabalu",
  "Kuching",
];

export const PRODUK_OPTIONS = [
  "Pembiayaan Mikro",
  "Pembiayaan Kontrak-i",
  "Pembiayaan Usahawan",
  "Pembiayaan Khas",
];

function fmtRm(n: number) {
  return `RM ${n.toLocaleString("en-MY")}`;
}

export const items: AkaunItem[] = [
  {
    id: "TEK-KL-2024-0001",
    ic: "850101011234",
    nama: "Ahmad bin Abdullah",
    cawangan: "Kuala Lumpur",
    negeri: "Wilayah Persekutuan",
    produk: "Pembiayaan Mikro",
    tarikhMula: "2024-01-15",
    tarikhTamat: "2029-01-15",
    jumlahPembiayaan: 50000,
    bakiPokok: 42500,
    bakiKeuntungan: 5200,
    bakiSimpanan: 1500,
    penalti: 0,
    tunggakan: 0,
    bakiAkhir: 49200,
    bayaranBulanan: 2500,
    status: "Aktif",
    risiko: "Normal",
    noBsas: "BSAS-2024-001",
  },
  {
    id: "TEK-KL-2024-0002",
    ic: "920315056789",
    nama: "Siti Nurhaliza binti Omar",
    namaSyarikat: "Siti Enterprise",
    ssm: "1234567-V",
    cawangan: "Kuala Lumpur",
    negeri: "Wilayah Persekutuan",
    produk: "Pembiayaan Usahawan",
    tarikhMula: "2024-02-20",
    tarikhTamat: "2027-02-20",
    jumlahPembiayaan: 80000,
    bakiPokok: 28000,
    bakiKeuntungan: 4200,
    bakiSimpanan: 800,
    penalti: 0,
    tunggakan: 0,
    bakiAkhir: 33000,
    bayaranBulanan: 1500,
    status: "Aktif",
    risiko: "Normal",
    noBsas: "BSAS-2024-002",
  },
  {
    id: "TEK-SHA-2023-0045",
    ic: "780512033456",
    nama: "Mohd Rizal bin Hassan",
    cawangan: "Shah Alam",
    negeri: "Selangor",
    produk: "Pembiayaan Mikro",
    tarikhMula: "2023-06-01",
    tarikhTamat: "2028-06-01",
    jumlahPembiayaan: 30000,
    bakiPokok: 0,
    bakiKeuntungan: 0,
    bakiSimpanan: 0,
    penalti: 0,
    tunggakan: 0,
    bakiAkhir: 0,
    bayaranBulanan: 0,
    status: "Selesai",
    risiko: "Normal",
  },
  {
    id: "TEK-JB-2024-0012",
    ic: "881122077890",
    nama: "Fatimah binti Ibrahim",
    cawangan: "Johor Bahru",
    negeri: "Johor",
    produk: "Pembiayaan Mikro",
    tarikhMula: "2024-04-10",
    tarikhTamat: "2029-04-10",
    jumlahPembiayaan: 25000,
    bakiPokok: 18750,
    bakiKeuntungan: 2100,
    bakiSimpanan: 500,
    penalti: 150,
    tunggakan: 2500,
    bakiAkhir: 24000,
    bayaranBulanan: 1250,
    status: "Tunggakan",
    risiko: "Amaran",
  },
  {
    id: "TEK-IPH-2022-0089",
    ic: "750303012345",
    nama: "Kamarul bin Zainal",
    namaSyarikat: "KZ Trading",
    ssm: "9876543-T",
    pukonsa: "PUK-2022-089",
    cawangan: "Ipoh",
    negeri: "Perak",
    produk: "Pembiayaan Kontrak-i",
    tarikhMula: "2022-09-15",
    tarikhTamat: "2025-09-15",
    jumlahPembiayaan: 120000,
    bakiPokok: 45000,
    bakiKeuntungan: 8500,
    bakiSimpanan: 2200,
    penalti: 500,
    tunggakan: 12000,
    bakiAkhir: 66200,
    bayaranBulanan: 3500,
    status: "NPF",
    risiko: "Kritikal",
    noBsas: "BSAS-2022-089",
  },
  {
    id: "TEK-KB-2024-0003",
    ic: "930618088765",
    nama: "Nurul Izzati binti Rahman",
    cawangan: "Kota Bharu",
    negeri: "Kelantan",
    produk: "Pembiayaan Mikro",
    tarikhMula: "2024-03-01",
    tarikhTamat: "2029-03-01",
    jumlahPembiayaan: 20000,
    bakiPokok: 19500,
    bakiKeuntungan: 1800,
    bakiSimpanan: 400,
    penalti: 0,
    tunggakan: 0,
    bakiAkhir: 21700,
    bayaranBulanan: 800,
    status: "Aktif",
    risiko: "Normal",
  },
  {
    id: "TEK-KK-2023-0023",
    ic: "860707044321",
    nama: "Abdul Rahman bin Musa",
    cawangan: "Kota Kinabalu",
    negeri: "Sabah",
    produk: "Pembiayaan Usahawan",
    tarikhMula: "2023-11-20",
    tarikhTamat: "2026-11-20",
    jumlahPembiayaan: 60000,
    bakiPokok: 0,
    bakiKeuntungan: 0,
    bakiSimpanan: 0,
    penalti: 0,
    tunggakan: 0,
    bakiAkhir: 0,
    bayaranBulanan: 0,
    status: "Selesai",
    risiko: "Normal",
  },
  {
    id: "TEK-KCH-2024-0007",
    ic: "910201099876",
    nama: "Lina binti Mat Zin",
    cawangan: "Kuching",
    negeri: "Sarawak",
    produk: "Pembiayaan Mikro",
    tarikhMula: "2024-05-05",
    tarikhTamat: "2029-05-05",
    jumlahPembiayaan: 35000,
    bakiPokok: 32000,
    bakiKeuntungan: 3100,
    bakiSimpanan: 700,
    penalti: 0,
    tunggakan: 500,
    bakiAkhir: 36300,
    bayaranBulanan: 1400,
    status: "Tunggakan",
    risiko: "Amaran",
  },
  {
    id: "TEK-KL-2023-0098",
    ic: "770808055432",
    nama: "Wong Siew Ling",
    namaSyarikat: "WSL Enterprise",
    ssm: "5555555-K",
    cawangan: "Kuala Lumpur",
    negeri: "Wilayah Persekutuan",
    produk: "Pembiayaan Khas",
    tarikhMula: "2023-08-01",
    tarikhTamat: "2026-08-01",
    jumlahPembiayaan: 100000,
    bakiPokok: 75000,
    bakiKeuntungan: 0,
    bakiSimpanan: 0,
    penalti: 0,
    tunggakan: 0,
    bakiAkhir: 75000,
    bayaranBulanan: 0,
    status: "Dibekukan",
    risiko: "Amaran",
  },
  {
    id: "TEK-SHA-2024-0015",
    ic: "940912011223",
    nama: "Muhammad Hafiz bin Ismail",
    cawangan: "Shah Alam",
    negeri: "Selangor",
    produk: "Pembiayaan Mikro",
    tarikhMula: "2024-06-15",
    tarikhTamat: "2029-06-15",
    jumlahPembiayaan: 40000,
    bakiPokok: 38000,
    bakiKeuntungan: 4100,
    bakiSimpanan: 900,
    penalti: 0,
    tunggakan: 0,
    bakiAkhir: 43000,
    bayaranBulanan: 1800,
    status: "Aktif",
    risiko: "Normal",
  },
];

export const shortcutFunctions = [
  { label: "Semakan Status Permohonan", to: "/admin/permohonan", icon: "FileSearch" },
  { label: "Semakan Baki Pembiayaan", to: "#", icon: "Wallet" },
  { label: "Penukaran No. Telefon", to: "#", icon: "Phone" },
  { label: "Laporan Tunggakan", to: "/admin/pembayaran/tunggakan", icon: "AlertTriangle" },
];

export function getSummaryFromItems(list: AkaunItem[]) {
  const aktif = list.filter((i) => i.status === "Aktif").length;
  const totalBakiTertunggak = list.reduce((s, i) => s + i.tunggakan + i.penalti, 0);
  const bayaranTerkini = list.filter((i) => i.status === "Aktif" && i.tunggakan === 0).length;
  const akaunBaru = list.filter((i) => {
    const m = new Date(i.tarikhMula).getMonth();
    const y = new Date(i.tarikhMula).getFullYear();
    const now = new Date();
    return m === now.getMonth() && y === now.getFullYear();
  }).length;

  return [
    { label: "Jumlah Akaun Aktif", value: aktif },
    { label: "Baki Tertunggak", value: fmtRm(totalBakiTertunggak) },
    { label: "Bayaran Terkini", value: bayaranTerkini },
    { label: "Akaun Baru Bulan Ini", value: akaunBaru },
  ];
}
