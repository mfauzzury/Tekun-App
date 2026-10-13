import { AGAMA_OPTIONS, NEGERI_OPTIONS } from "@/config/sppt-options";
import type { PemohonMyKadScan, PermohonanAttachment, PpDataAlternatif } from "@/types";
import { parseMalaysianIcBirthDate } from "@/utils/malaysianIc";

/** Applicant profile fields used to pre-fill a fresh form (subset of the pemohon store profil). */
type ProfilSeed = { nama: string; noKp: string; alamat: string; telefon: string; email: string; perniagaan: string; noSsm: string };

/** The application form — field set unchanged from the original single-file view (sent as `details`). */
export function createPermohonanForm(profil: ProfilSeed) {
  return {
    // Asas
    kategoriPembiayaan: "",
    jumlahPermohonan: 20000,
    tujuanPembiayaan: "",

    // Pemohon
    nama: profil.nama,
    noIcBaru: profil.noKp,
    noIcLama: "",
    jantina: "",
    agama: "",
    tarikhLahirHari: "",
    tarikhLahirBulan: "",
    tarikhLahirTahun: "",
    umur: "",
    bangsa: "",
    kaum: "",
    tarafPerkahwinan: "",
    bilanganTanggungan: "",
    oku: false,
    diberhentikanPandemik: false,
    asnafBerdaftar: false,
    tarafPendidikan: "",

    // Alamat
    alamat: profil.alamat,
    poskod: "",
    negeri: "",
    noTelefonRumah: "",
    noTelefonBimbit: profil.telefon,
    email: profil.email,
    facebook: "",
    instagram: "",
    statusKediaman: "",

    // Pekerjaan
    statusPekerjaan: "" as "" | "bekerja" | "tidak_bekerja",
    sektorPekerjaan: "",
    jawatan: "",
    statusJawatan: "",
    pendapatan: "",
    namaMajikan: "",
    alamatMajikan: "",
    noTelefonMajikan: "",

    // Pasangan
    namaPasangan: "",
    noIcPasangan: "",
    noPassportPasangan: "",
    pekerjaanPasangan: "",
    alamatMajikanPasangan: "",
    poskodMajikanPasangan: "",
    noTelefonMajikanPasangan: "",
    noTelefonBimbitPasangan: "",
    pendapatanPasangan: "",

    // Perniagaan
    namaPerniagaan: profil.perniagaan,
    noSsm: profil.noSsm,
    tempohBerniaga: "",
    alamatPremis: "",
    poskodPremis: "",
    anggaranPendapatan: "",
    noTelPremis: "",
    noTelBimbitPremis: "",
    statusPremis: "Sewa",
    pemilikanPerniagaan: "Pemilikan Tunggal",
    modalBerbayar: "",
    tarikhDaftar: "",
    tarikhTamatLesen: "",
    keahlianPersatuan: false,
    keahlianPersatuanNyata: "",
    masaBerniagaDari: "08:00",
    masaBerniagaHingga: "18:00",
    pengiktirafan: false,
    pengiktirafanNyata: "",
    nilaiAset: "",
    sumberModal: "",
    kursusAgensi: "",
    kursusLain: "",
    perniagaanTerdahulu: "",
    pembiayaanSediaAda: false,
    institusiPembiayaan: "",
    jumlahPembiayaanSediaAda: "",
    bakiPembiayaan: "",

    // Pembiayaan
    tempohPembiayaan: "",
    kekerapanBayaran: "Bulanan",

    // Sokongan
    sokonganNama: "",
    sokonganNoKp: "",
    sokonganTempohPerkenalan: "",
    sokonganTarikhPerbincangan: "",
    sokonganJumlah: "",
    perakuanJawatan: "",
    perakuanNama: "",
    perakuanAlamat: "",
    perakuanNoTel: "",
    perujuk1Nama: "",
    perujuk1NoKp: "",
    perujuk1Alamat: "",
    perujuk1Hubungan: "",
    perujuk1NoTel: "",
    perujuk2Nama: "",
    perujuk2NoKp: "",
    perujuk2Alamat: "",
    perujuk2Hubungan: "",
    perujuk2NoTel: "",

    // Takaful
    takafulPembiayaan: true,
    takafulKemalangan: false,
    takafulKemalanganPakej: "",
    perkeso: false,
    perkesoPakej: "",

    // Wasiat
    wasiat: false,
    wasiatJumlah: "",

    // Kebenaran
    kebenaranKredit: false,
  };
}

export type PermohonanForm = ReturnType<typeof createPermohonanForm>;
export type FieldErrors = Record<string, string>;
export type AttachmentRow = PermohonanAttachment & { verifying?: boolean; classifying?: boolean };

// ─── IC helpers ──────────────────────────────────────────────────────────────

export function icDigits(ic: string | null | undefined): string {
  return String(ic ?? "").replace(/\D/g, "");
}

export function formatIc(ic: string | null | undefined): string {
  const d = icDigits(ic);
  return d.length === 12 ? `${d.slice(0, 6)}-${d.slice(6, 8)}-${d.slice(8)}` : String(ic ?? "");
}

/** IC → tarikh lahir + umur (unchanged behaviour of the original onNoIcBaruInput). */
export function applyIcBirthDate(form: PermohonanForm) {
  const parsed = parseMalaysianIcBirthDate(form.noIcBaru);
  if (!parsed) return false;
  form.tarikhLahirHari = String(parsed.day);
  form.tarikhLahirBulan = String(parsed.month);
  form.tarikhLahirTahun = String(parsed.year);
  form.umur = String(parsed.age);
  return true;
}

/** Fill the form from an AI MyKad scan. Returns the labels of the fields that were filled. */
export function autofillFromMyKad(form: PermohonanForm, scan: PemohonMyKadScan | null): string[] {
  const data = scan?.ocr?.data;
  const filled: string[] = [];
  if (!data) return filled;

  if (data.nama?.trim()) {
    form.nama = data.nama.trim();
    filled.push("Nama penuh");
  }
  const noIc = scan?.ic?.noIc || data.noIc;
  if (icDigits(noIc).length === 12) {
    form.noIcBaru = formatIc(noIc);
    filled.push("No. Kad Pengenalan");
    if (applyIcBirthDate(form)) filled.push("Tarikh lahir & umur");
  }
  const jantinaRaw = (scan?.ic?.jantina || data.jantina || "").toUpperCase();
  const jantina = jantinaRaw.startsWith("L") ? "L" : jantinaRaw.startsWith("P") || jantinaRaw.startsWith("W") ? "P" : "";
  if (jantina) {
    form.jantina = jantina;
    filled.push("Jantina");
  }
  const agama = AGAMA_OPTIONS.find((a) => a.label.toLowerCase() === (data.agama ?? "").trim().toLowerCase());
  if (agama) {
    form.agama = agama.value;
    filled.push("Agama");
  }
  if (data.alamat?.trim()) {
    const alamat = data.alamat.replace(/\s*\n\s*/g, ", ").trim();
    form.alamat = alamat;
    filled.push("Alamat");
    const poskod = alamat.match(/\b\d{5}\b/)?.[0];
    if (poskod) {
      form.poskod = poskod;
      filled.push("Poskod");
    }
    const upper = alamat.toUpperCase();
    const negeri =
      NEGERI_OPTIONS.find((n) => upper.includes(n.toUpperCase())) ??
      (upper.includes("KUALA LUMPUR")
        ? "Wilayah Persekutuan Kuala Lumpur"
        : upper.includes("LABUAN")
          ? "Wilayah Persekutuan Labuan"
          : upper.includes("PUTRAJAYA")
            ? "Wilayah Persekutuan Putrajaya"
            : upper.includes("PENANG")
              ? "Pulau Pinang"
              : undefined);
    if (negeri) {
      form.negeri = negeri;
      filled.push("Negeri");
    }
  }
  return filled;
}

// ─── MyKad attachments ───────────────────────────────────────────────────────

export function isMyKadFront(a: AttachmentRow) {
  if (a.documentClass === "ic_pemohon_depan" || a.documentClass === "ic_pemohon_combined") return true;
  const v = a.verification;
  return Boolean(v && (v.documentType === "ic_front" || v.documentType === "ic_combined") && v.subject !== "spouse" && !a.documentClass?.startsWith("ic_pasangan"));
}

export function isMyKadBack(a: AttachmentRow) {
  if (a.documentClass === "ic_pemohon_belakang" || a.documentClass === "ic_pemohon_combined") return true;
  const v = a.verification;
  return Boolean(v && (v.documentType === "ic_back" || v.documentType === "ic_combined") && v.subject !== "spouse" && !a.documentClass?.startsWith("ic_pasangan"));
}

// ─── Validation (per step id) ────────────────────────────────────────────────

export function validateStep(id: string, form: PermohonanForm, attachments: AttachmentRow[]): FieldErrors {
  const e: FieldErrors = {};
  if (id === "pembiayaan") {
    if (!form.kategoriPembiayaan) e.kategoriPembiayaan = "Sila pilih kategori pembiayaan.";
    if (!(Number(form.jumlahPermohonan) > 0)) e.jumlahPermohonan = "Masukkan jumlah pembiayaan yang dipohon.";
  }
  if (id === "diri") {
    if (!form.nama.trim()) e.nama = "Nama penuh diperlukan.";
    if (icDigits(form.noIcBaru).length !== 12) e.noIcBaru = "No. Kad Pengenalan mesti 12 digit.";
    if (icDigits(form.noTelefonBimbit).length < 9) e.noTelefonBimbit = "Masukkan no. telefon bimbit yang sah.";
  }
  if (id === "hantar") {
    if (!form.kebenaranKredit) e.kebenaranKredit = "Sila bersetuju dengan Kebenaran Penzahiran Maklumat Kredit.";
    const front = attachments.some(isMyKadFront);
    const back = attachments.some(isMyKadBack);
    if (!front || !back) e.mykad = `Sila muat naik MyKad ${!front && !back ? "depan & belakang" : !front ? "depan" : "belakang"} di langkah Identiti.`;
  }
  return e;
}

// ─── Anggaran ansuran (flat 4% setahun — anggaran sahaja) ────────────────────

export const KADAR_ANGGARAN = 0.04;

export function anggaranAnsuranBulanan(jumlah: number, bulan: number): number | null {
  if (!(jumlah > 0) || !(bulan > 0)) return null;
  const jumlahBayaran = jumlah * (1 + KADAR_ANGGARAN * (bulan / 12));
  return jumlahBayaran / bulan;
}

// ─── Data alternatif ─────────────────────────────────────────────────────────

type Sec<K extends keyof PpDataAlternatif> = NonNullable<PpDataAlternatif[K]>;
export type DataAltForm = {
  ewallet: Sec<"ewallet">;
  duitnowQr: Sec<"duitnowQr">;
  bilUtiliti: Sec<"bilUtiliti">;
  platform: Sec<"platform">;
  ccrisTersedia: boolean;
  penyataBankTersedia: boolean;
};
export type DataAltSectionKey = "ewallet" | "duitnowQr" | "bilUtiliti" | "platform";

export function createDataAlt(): DataAltForm {
  return {
    ewallet: { penyedia: null, purataMasukBulanan: null, bilanganTransaksiBulanan: null, bulanAktif: null },
    duitnowQr: { purataJualanBulanan: null, bilanganTransaksiBulanan: null, bulanAktif: null, trendPertumbuhanPeratus: null },
    bilUtiliti: { bulanDibayarTepat: null, bulanDinilai: null },
    platform: { nama: null, purataJualanBulanan: null, penarafan: null, bulanAktif: null },
    ccrisTersedia: false,
    penyataBankTersedia: false,
  };
}

const INTEGER_FIELDS = new Set(["bilanganTransaksiBulanan", "bulanAktif", "bulanDibayarTepat", "bulanDinilai"]);

function isBlank(v: unknown) {
  return v === null || v === undefined || (typeof v === "string" && v.trim() === "");
}

export function sectionHasValue(section: Record<string, unknown> | null | undefined): boolean {
  return Boolean(section && Object.values(section).some((v) => !isBlank(v)));
}

/** Copy non-empty values from `src` into the matching section of `target` (in place). */
export function mergeSection(target: Record<string, unknown>, src: Record<string, unknown> | null | undefined) {
  if (!src) return;
  for (const key of Object.keys(target)) {
    if (!isBlank(src[key])) target[key] = src[key];
  }
}

export function mergeDataAlt(target: DataAltForm, src: PpDataAlternatif | null | undefined) {
  if (!src) return;
  (["ewallet", "duitnowQr", "bilUtiliti", "platform"] as DataAltSectionKey[]).forEach((k) =>
    mergeSection(target[k] as Record<string, unknown>, src[k] as Record<string, unknown> | null),
  );
  target.ccrisTersedia = Boolean(src.ccrisTersedia);
  target.penyataBankTersedia = Boolean(src.penyataBankTersedia);
}

/** API payload: only sections with a value; numbers cleaned (integers rounded, blanks → null). */
export function dataAltPayload(d: DataAltForm): PpDataAlternatif {
  const out: PpDataAlternatif = { ccrisTersedia: d.ccrisTersedia, penyataBankTersedia: d.penyataBankTersedia };
  (["ewallet", "duitnowQr", "bilUtiliti", "platform"] as DataAltSectionKey[]).forEach((k) => {
    const section = d[k] as Record<string, unknown>;
    if (!sectionHasValue(section)) return;
    const clean: Record<string, unknown> = {};
    for (const [field, raw] of Object.entries(section)) {
      if (isBlank(raw)) clean[field] = null;
      else if (typeof raw === "string" && !["penyedia", "nama"].includes(field)) clean[field] = Number(raw);
      else clean[field] = raw;
      if (typeof clean[field] === "number") {
        const n = clean[field] as number;
        clean[field] = !Number.isFinite(n) ? null : INTEGER_FIELDS.has(field) ? Math.max(0, Math.round(n)) : n;
      }
    }
    (out as Record<string, unknown>)[k] = clean;
  });
  return out;
}

export function hasAnyDataAlt(d: DataAltForm) {
  return (["ewallet", "duitnowQr", "bilUtiliti", "platform"] as DataAltSectionKey[]).some((k) => sectionHasValue(d[k] as Record<string, unknown>));
}

/** First human-readable message from an apiRequest error (prefers 422 field details). */
export function errorMessage(e: unknown, fallback?: string): string | undefined {
  if (!(e instanceof Error)) return fallback;
  const details = (e as Error & { details?: Record<string, string[] | string> | null }).details;
  const first = details ? Object.values(details)[0] : undefined;
  const msg = Array.isArray(first) ? first[0] : first;
  return msg || (e.message && e.message !== "Request failed" && e.message !== "Validation failed" ? e.message : fallback);
}
