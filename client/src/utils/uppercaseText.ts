/** Field keys that must keep user casing (IDs, numbers, contact, dates). */
export const PERMOHONAN_UPPERCASE_SKIP = new Set([
  "noIcBaru",
  "noIcLama",
  "noIcPasangan",
  "noPassportPasangan",
  "noAkaunBank",
  "noUsahawan",
  "noSsm",
  "bilanganTanggungan",
  "tarikhLahirHari",
  "tarikhLahirTahun",
  "umur",
  "poskod",
  "poskodMajikanPasangan",
  "poskodPremis",
  "noTelefonRumah",
  "noTelefonBimbit",
  "email",
  "noTelefonMajikan",
  "noTelefonMajikanPasangan",
  "noTelefonBimbitPasangan",
  "noTelPremis",
  "noTelBimbitPremis",
  "pengesahanNoTel",
  "perakuanNoTel",
  "perujuk1NoTel",
  "perujuk2NoTel",
  "pendapatan",
  "pendapatanBulan",
  "pendapatanPasangan",
  "pendapatanPasanganBulan",
  "tempohBerniaga",
  "anggaranPendapatan",
  "modalBerbayar",
  "tarikhDaftar",
  "tarikhTamatLesen",
  "nilaiAset",
  "jumlahPembiayaanSediaAda",
  "bakiPembiayaan",
  "jumlahPermohonan",
  "tempohPembiayaan",
  "sokonganNoKp",
  "sokonganTarikhPerbincangan",
  "sokonganJumlah",
  "perujuk1NoKp",
  "perujuk2NoKp",
  "wasiatJumlah",
  "statusPekerjaan",
  "jantina",
  "agama",
  "kaedahPerniagaan",
  "statusPerniagaan",
  "masaBerniagaDari",
  "masaBerniagaHingga",
  "takafulKemalanganPakej",
  "perkesoPakej",
  "sektorPekerjaan",
  "statusJawatan",
  "sektorPerniagaan",
  "kategoriPembiayaan",
  "namaBank",
  "tarafPerkahwinan",
  "tarikhLahirBulan",
  "bangsa",
  "tarafPendidikan",
  "negeri",
  "statusKediaman",
  "statusPremis",
  "pemilikanPerniagaan",
  "kursusAgensi",
  "institusiPembiayaan",
  "kekerapanBayaran",
]);

export function matchSelectOption(value: string, options: readonly string[]): string {
  const trimmed = value.trim();
  if (!trimmed) return "";
  if (options.includes(trimmed)) return trimmed;
  const found = options.find((option) => option.toLowerCase() === trimmed.toLowerCase());
  return found ?? trimmed;
}

export function uppercaseAlphabeticText(value: string): string {
  return value.toUpperCase();
}

export function uppercaseFormFields<T extends Record<string, unknown>>(
  data: T,
  skip: Set<string> = PERMOHONAN_UPPERCASE_SKIP,
): T {
  const result = { ...data };
  for (const [key, value] of Object.entries(result)) {
    if (typeof value === "string" && !skip.has(key)) {
      (result as Record<string, unknown>)[key] = uppercaseAlphabeticText(value);
    }
  }
  return result;
}

export function uppercaseTextInput(event: Event) {
  const target = event.target;
  if (!(target instanceof HTMLInputElement || target instanceof HTMLTextAreaElement)) return;
  if (target.type === "email" || target.readOnly) return;

  const upper = uppercaseAlphabeticText(target.value);
  if (target.value === upper) return;

  const pos = target.selectionStart;
  target.value = upper;
  target.dispatchEvent(new Event("input", { bubbles: true }));
  if (pos !== null) {
    target.setSelectionRange(pos, pos);
  }
}
