/**
 * Dummy data for Pemantauan Usahawan module (no DB/API connection)
 * Per SPEC Pemantauna usahawan.csv - all phases
 */

// 12.1.2 PMKS - SME Corp scale: Mikro, Kecil, Sederhana
export type PmksKategori = "Mikro" | "Kecil" | "Sederhana";

// 12.1.3 Status perniagaan
export type StatusPerniagaan =
  | "aktif_berkembang"
  | "stabil"
  | "menurun"
  | "tidak_aktif"
  | "tutup"
  | "selesai_bayaran"
  | "kematian"
  | "tidak_berniaga"
  | "mia"
  | "rekod_buruk";

// 12.1.6 Skor prestasi & risiko
export type SkorPrestasi = 0 | 1 | 2 | 3 | 4 | 5;
export type KategoriRisiko = "Biru-Cemerlang" | "Biru-Berjaya" | "Hijau-Potensi" | "Kuning-Memuaskan" | "Merah-Rendah" | "Merah-Lemah";
export type RisikoTambahan = "Risiko Sederhana" | "Risiko Tinggi" | null;

// 12.2.3 Status operasi semasa lawatan
export type StatusOperasi = "Aktif" | "Tidak Aktif" | "Tutup Sementara";

export interface DokumenSokongan {
  id: string;
  jenis: string;
  nama: string;
  tarikh: string;
  catatan?: string;
}

export interface LawatanItem {
  id: string;
  usahawanId: string;
  tarikh: string;
  pegawai: string;
  lokasi: string;
  statusOperasi: StatusOperasi;
  skorSelepas: SkorPrestasi;
  tindakanSusulan: string;
  geoLat?: number;
  geoLng?: number;
  parlimen?: string;
  dun?: string;
}

export interface ProgramLatihan {
  id: string;
  tajuk: string;
  tema: string;
  tarikh: string;
  masa: string;
  lokasi: string;
  platform: "Fizikal" | "Dalam Talian";
  penyampai: string;
  pesertaSasaran: string;
  pesertaHadir: number;
  pesertaJemput: number;
}

export interface KehadiranLatihan {
  id: string;
  usahawanId: string;
  programId: string;
  status: "Hadir" | "Tidak Hadir" | "RSVP";
  sijilUrl?: string;
}

export interface UsahawanItem {
  id: string;
  ic: string;
  nama: string;
  perniagaan: string;
  negeri: string;
  cawangan: string;
  // 12.1.1 Prestasi perniagaan
  jualanBulanan: number;
  keuntunganKasar: number;
  keuntunganBersih: number;
  bilPelanggan: number;
  bilPekerja: number;
  bilCawangan: number;
  keluasanKedai: number; // sq ft
  // 12.1.2 PMKS
  pmks: PmksKategori;
  jualanTahunan: number;
  // 12.1.3 Status
  statusPerniagaan: StatusPerniagaan;
  faktorLuaran?: string;
  // 12.1.6 Skor & risiko
  skorPrestasi: SkorPrestasi;
  kategoriRisiko: KategoriRisiko;
  risikoTambahan: RisikoTambahan;
  // 12.1.7 EWS
  earlyWarning: boolean;
  earlyWarningMsg?: string;
  // Geo
  lat: number;
  lng: number;
  parlimen?: string;
  dun?: string;
  // Lain
  tarikhDaftar: string;
  pensijilan: string[];
  kursusDiikuti: { nama: string; penganjur: string; tarikh: string }[];
  geranDiterima: { sumber: string; jumlah: number }[];
}

export const STATUS_LABELS: Record<StatusPerniagaan, string> = {
  aktif_berkembang: "Aktif & Berkembang",
  stabil: "Stabil",
  menurun: "Menurun",
  tidak_aktif: "Tidak Aktif",
  tutup: "Tutup Operasi",
  selesai_bayaran: "Selesai Bayaran",
  kematian: "Kematian",
  tidak_berniaga: "Tidak Berniaga",
  mia: "Hilang/MIA",
  rekod_buruk: "Rekod Bayaran Tidak Baik",
};

export const SKOR_LABELS: Record<SkorPrestasi, string> = {
  0: "Merah-Lemah",
  1: "Merah-Rendah",
  2: "Kuning-Memuaskan",
  3: "Hijau-Potensi",
  4: "Biru-Berjaya",
  5: "Biru-Cemerlang",
};

export const KATEGORI_COLORS: Record<KategoriRisiko, string> = {
  "Biru-Cemerlang": "bg-blue-600 text-white",
  "Biru-Berjaya": "bg-blue-500 text-white",
  "Hijau-Potensi": "bg-emerald-500 text-white",
  "Kuning-Memuaskan": "bg-amber-500 text-slate-900",
  "Merah-Rendah": "bg-red-500 text-white",
  "Merah-Lemah": "bg-red-700 text-white",
};

export const usahawanList: UsahawanItem[] = [
  {
    id: "U-001",
    ic: "850101011234",
    nama: "Ahmad bin Abdullah",
    perniagaan: "Kedai Runcit",
    negeri: "Selangor",
    cawangan: "Shah Alam",
    jualanBulanan: 15000,
    keuntunganKasar: 4500,
    keuntunganBersih: 3200,
    bilPelanggan: 120,
    bilPekerja: 2,
    bilCawangan: 1,
    keluasanKedai: 800,
    pmks: "Mikro",
    jualanTahunan: 180000,
    statusPerniagaan: "aktif_berkembang",
    skorPrestasi: 5,
    kategoriRisiko: "Biru-Cemerlang",
    risikoTambahan: null,
    earlyWarning: false,
    lat: 3.0733,
    lng: 101.5185,
    parlimen: "P.108 Shah Alam",
    dun: "N.40 Kota Anggerik",
    tarikhDaftar: "2022-03-15",
    pensijilan: ["HALAL", "Homebased"],
    kursusDiikuti: [
      { nama: "Pemasaran Digital", penganjur: "TEKUN", tarikh: "2024-01-10" },
      { nama: "Pengurusan Kewangan", penganjur: "SME Corp", tarikh: "2024-02-15" },
    ],
    geranDiterima: [{ sumber: "KUSKOP", jumlah: 5000 }],
  },
  {
    id: "U-002",
    ic: "920315056789",
    nama: "Siti Nurhaliza binti Omar",
    perniagaan: "Katering",
    negeri: "Johor",
    cawangan: "Johor Bahru",
    jualanBulanan: 25000,
    keuntunganKasar: 8000,
    keuntunganBersih: 5500,
    bilPelanggan: 45,
    bilPekerja: 5,
    bilCawangan: 2,
    keluasanKedai: 1200,
    pmks: "Kecil",
    jualanTahunan: 300000,
    statusPerniagaan: "stabil",
    skorPrestasi: 4,
    kategoriRisiko: "Biru-Berjaya",
    risikoTambahan: null,
    earlyWarning: false,
    lat: 1.4927,
    lng: 103.7414,
    parlimen: "P.161 Pulai",
    dun: "N.47 Kempas",
    tarikhDaftar: "2021-08-20",
    pensijilan: ["HALAL", "GMP"],
    kursusDiikuti: [
      { nama: "Katering Profesional", penganjur: "TEKUN", tarikh: "2023-11-05" },
    ],
    geranDiterima: [],
  },
  {
    id: "U-003",
    ic: "780512033456",
    nama: "Mohd Rizal bin Hassan",
    perniagaan: "Kedai Makan",
    negeri: "Kedah",
    cawangan: "Alor Setar",
    jualanBulanan: 8000,
    keuntunganKasar: 2000,
    keuntunganBersih: 1200,
    bilPelanggan: 80,
    bilPekerja: 1,
    bilCawangan: 1,
    keluasanKedai: 400,
    pmks: "Mikro",
    jualanTahunan: 96000,
    statusPerniagaan: "menurun",
    faktorLuaran: "Persaingan pasaran tempatan",
    skorPrestasi: 2,
    kategoriRisiko: "Kuning-Memuaskan",
    risikoTambahan: "Risiko Sederhana",
    earlyWarning: true,
    earlyWarningMsg: "Skor prestasi jatuh di bawah paras had. Cadangan: sesi bimbingan",
    lat: 6.1164,
    lng: 100.3685,
    parlimen: "P.010 Kota Setar",
    dun: "N.14 Alor Merah",
    tarikhDaftar: "2020-05-10",
    pensijilan: [],
    kursusDiikuti: [],
    geranDiterima: [],
  },
  {
    id: "U-004",
    ic: "881122077890",
    nama: "Fatimah binti Ibrahim",
    perniagaan: "Kedai Jahit",
    negeri: "Perak",
    cawangan: "Ipoh",
    jualanBulanan: 6000,
    keuntunganKasar: 1800,
    keuntunganBersih: 1100,
    bilPelanggan: 35,
    bilPekerja: 0,
    bilCawangan: 1,
    keluasanKedai: 300,
    pmks: "Mikro",
    jualanTahunan: 72000,
    statusPerniagaan: "aktif_berkembang",
    skorPrestasi: 3,
    kategoriRisiko: "Hijau-Potensi",
    risikoTambahan: null,
    earlyWarning: false,
    lat: 4.5975,
    lng: 101.0901,
    parlimen: "P.062 Ipoh Barat",
    dun: "N.30 Buntong",
    tarikhDaftar: "2023-01-25",
    pensijilan: ["Homebased"],
    kursusDiikuti: [
      { nama: "Reka Bentuk Fesyen", penganjur: "TEKUN", tarikh: "2024-03-01" },
    ],
    geranDiterima: [{ sumber: "TEKUN", jumlah: 10000 }],
  },
  {
    id: "U-005",
    ic: "750303012345",
    nama: "Kamarul bin Zainal",
    perniagaan: "KZ Trading",
    negeri: "Perak",
    cawangan: "Ipoh",
    jualanBulanan: 4000,
    keuntunganKasar: 800,
    keuntunganBersih: 400,
    bilPelanggan: 25,
    bilPekerja: 1,
    bilCawangan: 1,
    keluasanKedai: 500,
    pmks: "Mikro",
    jualanTahunan: 48000,
    statusPerniagaan: "rekod_buruk",
    faktorLuaran: "Tunggakan bayaran pembiayaan",
    skorPrestasi: 1,
    kategoriRisiko: "Merah-Rendah",
    risikoTambahan: "Risiko Tinggi",
    earlyWarning: true,
    earlyWarningMsg: "NPF berpotensi. Cadangan: pindah ke unit kutipan",
    lat: 4.6100,
    lng: 101.0950,
    parlimen: "P.063 Ipoh Timor",
    dun: "N.31 Tebing Tinggi",
    tarikhDaftar: "2019-09-15",
    pensijilan: [],
    kursusDiikuti: [],
    geranDiterima: [],
  },
];

export const dokumenByUsahawan: Record<string, DokumenSokongan[]> = {
  "U-001": [
    { id: "D1", jenis: "Penyata Jualan", nama: "Penyata_Jan2024.pdf", tarikh: "2024-01-31" },
    { id: "D2", jenis: "Gambar Premis", nama: "premis_001.jpg", tarikh: "2024-02-15" },
    { id: "D3", jenis: "Sijil HALAL", nama: "halal_cert.pdf", tarikh: "2023-06-01" },
  ],
  "U-002": [
    { id: "D4", jenis: "Penyata Bank", nama: "bank_statement.pdf", tarikh: "2024-02-28" },
    { id: "D5", jenis: "Sijil GMP", nama: "gmp_cert.pdf", tarikh: "2023-08-15" },
  ],
  "U-003": [
    { id: "D6", jenis: "Surat Pernyataan", nama: "pernyataan_usahawan.pdf", tarikh: "2024-01-10" },
  ],
  "U-004": [],
  "U-005": [
    { id: "D7", jenis: "Invois Jualan", nama: "invois_sample.pdf", tarikh: "2023-12-20" },
  ],
};

export const lawatanList: LawatanItem[] = [
  {
    id: "L-001",
    usahawanId: "U-001",
    tarikh: "2024-03-05",
    pegawai: "En. Zulkifli Ahmad",
    lokasi: "Kedai Runcit Ahmad, Shah Alam",
    statusOperasi: "Aktif",
    skorSelepas: 5,
    tindakanSusulan: "Tiada. Prestasi cemerlang.",
    geoLat: 3.0733,
    geoLng: 101.5185,
    parlimen: "P.108 Shah Alam",
    dun: "N.40 Kota Anggerik",
  },
  {
    id: "L-002",
    usahawanId: "U-002",
    tarikh: "2024-03-08",
    pegawai: "Pn. Noraini Hassan",
    lokasi: "Siti Enterprise, Johor Bahru",
    statusOperasi: "Aktif",
    skorSelepas: 4,
    tindakanSusulan: "Cadangan: sertai program pemasaran digital",
    geoLat: 1.4927,
    geoLng: 103.7414,
  },
  {
    id: "L-003",
    usahawanId: "U-003",
    tarikh: "2024-03-01",
    pegawai: "En. Zulkifli Ahmad",
    lokasi: "Kedai Makan Rizal, Alor Setar",
    statusOperasi: "Aktif",
    skorSelepas: 2,
    tindakanSusulan: "Sesi bimbingan dijadualkan 15 Mac 2024. Penjadualan semula bayaran dicadangkan.",
    geoLat: 6.1164,
    geoLng: 100.3685,
  },
  {
    id: "L-004",
    usahawanId: "U-004",
    tarikh: "2024-02-20",
    pegawai: "Pn. Noraini Hassan",
    lokasi: "Kedai Jahit Fatimah, Ipoh",
    statusOperasi: "Aktif",
    skorSelepas: 3,
    tindakanSusulan: "Tiada. Pemantauan rutin 6 bulan.",
    geoLat: 4.5975,
    geoLng: 101.0901,
  },
  {
    id: "L-005",
    usahawanId: "U-005",
    tarikh: "2024-02-10",
    pegawai: "En. Zulkifli Ahmad",
    lokasi: "KZ Trading, Ipoh",
    statusOperasi: "Tidak Aktif",
    skorSelepas: 1,
    tindakanSusulan: "Pindah kes ke unit kutipan. Litigasi jika tiada respons.",
    geoLat: 4.6100,
    geoLng: 101.0950,
  },
];

export const programLatihanList: ProgramLatihan[] = [
  {
    id: "P-001",
    tajuk: "Pemasaran Digital & TikTok",
    tema: "Pemasaran Digital",
    tarikh: "2024-03-15",
    masa: "09:00 - 17:00",
    lokasi: "Dewan TEKUN Shah Alam",
    platform: "Fizikal",
    penyampai: "En. Amiruddin",
    pesertaSasaran: "Industri makanan",
    pesertaHadir: 28,
    pesertaJemput: 35,
  },
  {
    id: "P-002",
    tajuk: "Pengurusan Kewangan Mikro",
    tema: "Kewangan",
    tarikh: "2024-03-20",
    masa: "14:00 - 17:00",
    lokasi: "Zoom Meeting",
    platform: "Dalam Talian",
    penyampai: "Pn. Salmah",
    pesertaSasaran: "Penerima biaya NPF",
    pesertaHadir: 15,
    pesertaJemput: 20,
  },
  {
    id: "P-003",
    tajuk: "Pengurusan Operasi Kedai",
    tema: "Pengurusan Operasi",
    tarikh: "2024-03-25",
    masa: "09:00 - 13:00",
    lokasi: "Hotel Seri Malaysia, Ipoh",
    platform: "Fizikal",
    penyampai: "En. Kamarul",
    pesertaSasaran: "Peserta baharu",
    pesertaHadir: 22,
    pesertaJemput: 25,
  },
];

export const kehadiranLatihanList: KehadiranLatihan[] = [
  { id: "K1", usahawanId: "U-001", programId: "P-001", status: "Hadir", sijilUrl: "/sijil/U001-P001.pdf" },
  { id: "K2", usahawanId: "U-002", programId: "P-001", status: "Hadir", sijilUrl: "/sijil/U002-P001.pdf" },
  { id: "K3", usahawanId: "U-003", programId: "P-001", status: "Tidak Hadir" },
  { id: "K4", usahawanId: "U-004", programId: "P-001", status: "Hadir", sijilUrl: "/sijil/U004-P001.pdf" },
  { id: "K5", usahawanId: "U-001", programId: "P-002", status: "RSVP" },
  { id: "K6", usahawanId: "U-003", programId: "P-002", status: "RSVP" },
];

// 12.4 AI - dummy forecasts & anomalies
export interface AIForecastItem {
  usahawanId: string;
  bulan: string;
  jualanRamalan: number;
  jualanAktual?: number;
  anomaly: boolean;
  anomalyReason?: string;
}

export const aiForecastData: AIForecastItem[] = [
  { usahawanId: "U-001", bulan: "2024-04", jualanRamalan: 16500, anomaly: false },
  { usahawanId: "U-001", bulan: "2024-05", jualanRamalan: 17200, anomaly: false },
  { usahawanId: "U-002", bulan: "2024-04", jualanRamalan: 26000, anomaly: false },
  { usahawanId: "U-003", bulan: "2024-04", jualanRamalan: 6500, jualanAktual: 4200, anomaly: true, anomalyReason: "Jualan turun 35% dari ramalan" },
  { usahawanId: "U-005", bulan: "2024-04", jualanRamalan: 3500, anomaly: true, anomalyReason: "Risiko NPF tinggi - ramalan menurun" },
];

export const earlyWarningList = usahawanList.filter((u) => u.earlyWarning);

export function getSummary() {
  const aktif = usahawanList.filter((u) => u.statusPerniagaan === "aktif_berkembang" || u.statusPerniagaan === "stabil").length;
  const lawatanBulanIni = lawatanList.filter((l) => {
    const d = new Date(l.tarikh);
    const n = new Date();
    return d.getMonth() === n.getMonth() && d.getFullYear() === n.getFullYear();
  }).length;
  const prestasiBaik = usahawanList.filter((u) => u.skorPrestasi >= 3).length;
  const perluPerhatian = usahawanList.filter((u) => u.earlyWarning || u.skorPrestasi < 3).length;

  return [
    { label: "Usahawan Aktif", value: aktif },
    { label: "Lawatan Bulan Ini", value: lawatanBulanIni },
    { label: "Prestasi Baik", value: prestasiBaik },
    { label: "Perlu Perhatian", value: perluPerhatian },
  ];
}
