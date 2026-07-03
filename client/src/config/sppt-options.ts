/** Senarai negeri dan wilayah persekutuan Malaysia */
export const NEGERI_OPTIONS = [
  "Johor",
  "Kedah",
  "Kelantan",
  "Melaka",
  "Negeri Sembilan",
  "Pahang",
  "Perak",
  "Perlis",
  "Pulau Pinang",
  "Sabah",
  "Sarawak",
  "Selangor",
  "Terengganu",
  "Wilayah Persekutuan Kuala Lumpur",
  "Wilayah Persekutuan Labuan",
  "Wilayah Persekutuan Putrajaya",
];

/** Jenis perniagaan lazim */
export const JENIS_PERNIAGAAN_OPTIONS = [
  "Peruncitan",
  "Pemborongan",
  "Pembuatan",
  "Perkhidmatan",
  "Pertanian",
  "Penternakan",
  "Perikanan",
  "Makanan & Minuman",
  "Fesyen",
  "Teknologi",
  "Lain-lain",
];

/** BPP-BORANG-01: Status perniagaan */
export const STATUS_PERNIAGAAN_OPTIONS = [
  { value: "sedang_berniaga", label: "Sedang Berniaga" },
  { value: "memulakan", label: "Memulakan Perniagaan" },
] as const;

/** BPP-BORANG-01: Sektor perniagaan */
export const SEKTOR_PERNIAGAAN_OPTIONS = [
  "Pertanian",
  "Perusahaan Asas Tani",
  "Peruncitan",
  "Perkhidmatan",
  "Pembuatan",
  "Kontraktor Kecil",
  "Lain-lain",
];

/** BPP-BORANG-01: Jantina */
export const JANTINA_OPTIONS = [
  { value: "L", label: "Lelaki" },
  { value: "P", label: "Wanita" },
] as const;

/** BPP-BORANG-01: Agama */
export const AGAMA_OPTIONS = [
  { value: "islam", label: "Islam" },
  { value: "bukan_islam", label: "Bukan Islam" },
] as const;

/** BPP-BORANG-01: Taraf perkahwinan */
export const TARAF_PERKAHWINAN_OPTIONS = [
  "Bujang",
  "Berkahwin",
  "Duda",
  "Janda",
] as const;

/** BPP-BORANG-01: Taraf pendidikan */
export const TARAF_PENDIDIKAN_OPTIONS = [
  "PHD / Ijazah Sarjana",
  "Ijazah Sarjana Muda",
  "Diploma",
  "STPM",
  "SPM",
  "PMR Setaraf",
  "Sijil Setaraf",
  "Lain-lain",
];

/** BPP-BORANG-01: Status kediaman */
export const STATUS_KEDIAMAN_OPTIONS = [
  "Sendiri",
  "Sewa",
  "Keluarga",
] as const;

/** BPP-BORANG-01: Status premis perniagaan */
export const STATUS_PREMIS_OPTIONS = ["Sendiri", "Sewa", "Keluarga"] as const;

/** BPP-BORANG-01: Pemilikan perniagaan */
export const PEMILIKAN_PERNIAGAAN_OPTIONS = [
  "Individu",
  "Pemilikan Tunggal",
  "Perkongsian",
  "Sendirian Berhad",
] as const;

/** BPP-BORANG-01: Institusi pembiayaan sedia ada */
export const INSTITUSI_PEMBIAYAAN_OPTIONS = [
  "MARA",
  "AIM",
  "Lain-lain Agensi Kerajaan",
] as const;

/** BPP-BORANG-01: Kekerapan bayaran */
export const KEEKERAPAN_BAYARAN_OPTIONS = [
  "Mingguan",
  "Bulanan",
  "Mengikut Tempoh Kontrak Kerja",
  "Inden",
] as const;

/** BPP-BORANG-01: Agensi penganjur kursus */
export const AGENSI_KURSUS_OPTIONS = [
  "INSKEN",
  "SME CORP",
  "CEDAR",
  "Lain-lain",
] as const;

/** BPP-BORANG-01: Pakej Takaful Kemalangan (berdasarkan jumlah pembiayaan) */
export const TAKAFUL_KEMALANGAN_PAKEJ = [
  { value: "pakej1", label: "RM 7.20/tahun (RM 1,000 - RM 30,000)", min: 1000, max: 30000 },
  { value: "pakej2", label: "RM 19.44/tahun (RM 30,001 - RM 50,000)", min: 30001, max: 50000 },
  { value: "pakej3", label: "RM 32.40/tahun (RM 50,001 - RM 100,000)", min: 50001, max: 100000 },
] as const;

/** BPP-BORANG-01: Pakej PERKESO Pekerjaan Sendiri */
export const PERKESO_PAKEJ = [
  { value: "a", label: "RM 157.20/tahun", amount: 157.2 },
  { value: "b", label: "RM 442.80/tahun", amount: 442.8 },
  { value: "c", label: "RM 232.80/tahun", amount: 232.8 },
  { value: "d", label: "RM 592.80/tahun", amount: 592.8 },
] as const;
