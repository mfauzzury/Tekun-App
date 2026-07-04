/** Dummy data for Portal Pemohon - no API/tables yet */

export const STATUS_OPTIONS = [
  { value: "semua", label: "Semua Status" },
  { value: "diterima", label: "Diterima" },
  { value: "dalam_semakan", label: "Dalam Semakan" },
  { value: "dokumen_tambahan", label: "Dokumen Tambahan Diperlukan" },
  { value: "lulus", label: "Lulus" },
  { value: "tidak_lulus", label: "Tidak Lulus" },
];

export const PERMOHONAN_DUMMY = [
  {
    id: "SPPT-2024-001234",
    produk: "SPUMI",
    jumlah: "RM 50,000",
    tarikh: "5 Mac 2024",
    status: "Dalam Semakan",
    statusKey: "dalam_semakan",
  },
  {
    id: "SPPT-2024-001189",
    produk: "Kontrak-i",
    jumlah: "RM 30,000",
    tarikh: "28 Feb 2024",
    status: "Dokumen Tambahan Diperlukan",
    statusKey: "dokumen_tambahan",
  },
  {
    id: "SPPT-2023-009876",
    produk: "Tekun Niaga",
    jumlah: "RM 75,000",
    tarikh: "15 Dis 2023",
    status: "Lulus",
    statusKey: "lulus",
  },
  {
    id: "SPPT-2023-008521",
    produk: "SPUMI",
    jumlah: "RM 20,000",
    tarikh: "2 Nov 2023",
    status: "Tidak Lulus",
    statusKey: "tidak_lulus",
  },
];

export const PRODUK_OPTIONS = [
  { value: "spumi", label: "SPUMI" },
  { value: "kontrak_i", label: "Kontrak-i" },
  { value: "tekun_niaga", label: "Tekun Niaga" },
  { value: "tawarruq", label: "Tawarruq" },
  { value: "teman_tekun", label: "Teman TEKUN" },
];

export const TEMUDUGA_DUMMY = [
  {
    id: "T-001",
    permohonanId: "SPPT-2024-001234",
    tarikh: "15 Mac 2024",
    masa: "10:00 pagi",
    lokasi: "Pejabat TEKUN Cawangan Selangor",
    jenis: "Fizikal",
    status: "Dijadualkan",
  },
  {
    id: "T-002",
    permohonanId: "SPPT-2024-001189",
    tarikh: "18 Mac 2024",
    masa: "2:30 petang",
    lokasi: "Temuduga Maya (Google Meet)",
    jenis: "Maya",
    status: "Menunggu Pengesahan",
  },
  {
    id: "T-003",
    permohonanId: "SPPT-2023-009876",
    tarikh: "20 Dis 2023",
    masa: "9:00 pagi",
    lokasi: "Pejabat TEKUN Cawangan Selangor",
    jenis: "Fizikal",
    status: "Selesai",
  },
];

export const DOKUMEN_CHECKLIST_DUMMY = [
  { id: "d1", nama: "Kad Pengenalan (depan & belakang)", status: "lengkap", tarikh: "5 Mac 2024" },
  { id: "d2", nama: "Lesen Perniagaan", status: "lengkap", tarikh: "5 Mac 2024" },
  { id: "d3", nama: "Penyata Bank 3 bulan terkini", status: "menunggu_semakan", tarikh: "6 Mac 2024" },
  { id: "d4", nama: "SSM Form 9 / Borang D", status: "diperlukan", tarikh: null },
];

export const PROFIL_USAHAWAN_DUMMY = {
  nama: "Ahmad bin Abdullah",
  noKp: "850101-14-5678",
  email: "ahmad.abdullah@email.com",
  telefon: "012-3456789",
  alamat: "No. 12, Jalan Merdeka, Taman Sri Raya, 43000 Kajang, Selangor",
  perniagaan: "Kedai Runcit Sri Raya",
  noSsm: "SA1234567",
  sektor: "Peruncitan",
  statusSyariah: "Patuh Syariah",
};

export const FAQ_DUMMY = [
  {
    q: "Apakah dokumen yang diperlukan untuk permohonan?",
    a: "Dokumen asas: Kad Pengenalan, Lesen Perniagaan, Penyata Bank 3 bulan, SSM Form 9. Dokumen tambahan bergantung kepada jenis produk.",
  },
  {
    q: "Berapa lama masa semakan permohonan?",
    a: "Biasanya 14-21 hari bekerja selepas dokumen lengkap diterima.",
  },
  {
    q: "Bolehkah saya semak status permohonan secara dalam talian?",
    a: "Ya, log masuk ke Portal SPPT dan pilih 'Permohonan Saya' untuk semakan status masa nyata.",
  },
  {
    q: "Bagaimana untuk menukar tarikh temuduga?",
    a: "Pergi ke halaman Temuduga, pilih permohonan dan klik 'Tukar Tarikh' untuk memilih slot baharu.",
  },
];

/**
 * Auto-eligibility engine simulation (PDF §1.4 "Saringan Auto-Kelayakan").
 * Pure, deterministic, client-side only — no real credit-bureau/blacklist API.
 */
export const ELIGIBILITY_RULES = {
  minAge: 18,
  maxAge: 60,
  blacklistedIcs: ["800101-01-0001", "900202-02-0002"],
  maxExistingCommitmentRatio: 0.7,
} as const;

export interface EligibilityInput {
  umur: number;
  noKp: string;
  pendapatanBulanan: number;
  jumlahKomitmenSediaAda: number;
}

export interface EligibilityResult {
  eligible: boolean;
  reasons: string[];
}

export function evaluateEligibility(input: EligibilityInput): EligibilityResult {
  const reasons: string[] = [];

  if (input.umur < ELIGIBILITY_RULES.minAge || input.umur > ELIGIBILITY_RULES.maxAge) {
    reasons.push(`Umur mesti di antara ${ELIGIBILITY_RULES.minAge} hingga ${ELIGIBILITY_RULES.maxAge} tahun.`);
  }

  if ((ELIGIBILITY_RULES.blacklistedIcs as readonly string[]).includes(input.noKp.trim())) {
    reasons.push("No. Kad Pengenalan disenaraikan dalam senarai hitam (blacklist).");
  }

  const ratio = input.pendapatanBulanan > 0 ? input.jumlahKomitmenSediaAda / input.pendapatanBulanan : 1;
  if (ratio > ELIGIBILITY_RULES.maxExistingCommitmentRatio) {
    reasons.push("Komitmen kewangan sedia ada melebihi had maksimum berbanding pendapatan.");
  }

  return { eligible: reasons.length === 0, reasons };
}

/** OTP/TAC simulation (PDF §1.2) — no real SMS/email gateway. */
export const OTP_EXPIRY_SECONDS = 300;

export function generateOtp(): string {
  return String(Math.floor(100000 + Math.random() * 900000));
}

/** eKYC simulation (PDF §1.3) — no real vendor/liveness detection. */
export interface EkycSimulationInput {
  idFrontUploaded: boolean;
  idBackUploaded: boolean;
  selfieCaptured: boolean;
}

export interface EkycSimulationResult {
  status: "lulus" | "gagal";
  confidence: number;
  reasons: string[];
}

export function simulateEkyc(input: EkycSimulationInput): EkycSimulationResult {
  if (input.idFrontUploaded && input.idBackUploaded && input.selfieCaptured) {
    return { status: "lulus", confidence: 0.94, reasons: ["Padanan wajah berjaya", "Dokumen sah dan jelas"] };
  }

  const reasons: string[] = [];
  if (!input.idFrontUploaded) reasons.push("Imej hadapan kad pengenalan tidak dimuat naik.");
  if (!input.idBackUploaded) reasons.push("Imej belakang kad pengenalan tidak dimuat naik.");
  if (!input.selfieCaptured) reasons.push("Pengesahan wajah (liveness) tidak lengkap.");

  return { status: "gagal", confidence: 0.2, reasons };
}

/** Status timeline (PDF §3.6 "application timeline tracker"), keyed by STATUS_OPTIONS' statusKey. */
export interface StatusMilestone {
  label: string;
  done: boolean;
}

export const STATUS_TIMELINE: Record<string, StatusMilestone[]> = {
  diterima: [
    { label: "Permohonan Diterima", done: true },
    { label: "Semakan Dokumen", done: false },
    { label: "Penilaian Kredit", done: false },
    { label: "Keputusan", done: false },
  ],
  dalam_semakan: [
    { label: "Permohonan Diterima", done: true },
    { label: "Semakan Dokumen", done: true },
    { label: "Penilaian Kredit", done: false },
    { label: "Keputusan", done: false },
  ],
  dokumen_tambahan: [
    { label: "Permohonan Diterima", done: true },
    { label: "Semakan Dokumen", done: true },
    { label: "Dokumen Tambahan Diperlukan", done: false },
    { label: "Penilaian Kredit", done: false },
    { label: "Keputusan", done: false },
  ],
  lulus: [
    { label: "Permohonan Diterima", done: true },
    { label: "Semakan Dokumen", done: true },
    { label: "Penilaian Kredit", done: true },
    { label: "Keputusan: Lulus", done: true },
  ],
  tidak_lulus: [
    { label: "Permohonan Diterima", done: true },
    { label: "Semakan Dokumen", done: true },
    { label: "Penilaian Kredit", done: true },
    { label: "Keputusan: Tidak Lulus", done: true },
  ],
};

/**
 * Trimmed copy of the step taxonomy from views/sppt/PermohonanBaruView.vue (staff-side form),
 * dropping staff-only fields marked "(diisi TEKUN)" there (e.g. PERKESO sektor/kelas).
 */
export const PERMOHONAN_BARU_STEPS = [
  { id: "asas", label: "Asas" },
  { id: "pemohon", label: "Pemohon" },
  { id: "alamat", label: "Alamat" },
  { id: "pekerjaan", label: "Pekerjaan" },
  { id: "perniagaan", label: "Perniagaan" },
  { id: "pembiayaan", label: "Pembiayaan" },
  { id: "dokumen", label: "Dokumen" },
] as const;
