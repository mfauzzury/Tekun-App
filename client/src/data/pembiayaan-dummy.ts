/**
 * Dummy data for the Pemohon "Pembiayaan Saya" (loan management) module.
 * UI-only — mirrors the staff-side camelCase shapes (AkaunItem, BAYARAN_ITEMS,
 * PengeluaranItem, EARLY_SETTLEMENT_DUMMY). No DB/API.
 */

export { PAYMENT_CHANNELS } from "./bayaran-pembiayaan-dummy";

export type PembiayaanStatus = "Aktif" | "Tunggakan" | "Selesai";

export interface PembiayaanAkaun {
  id: string; // no. akaun, e.g. "AKN-2024-00123"
  permohonanId?: string; // link to an approved application, if any
  produk: string;
  jumlahPembiayaan: number;
  bakiPokok: number;
  bakiKeuntungan: number;
  bakiAkhir: number; // total outstanding
  bayaranBulanan: number;
  tunggakan: number;
  tarikhMula: string; // Malay display string
  tarikhMulaIso: string; // ISO for schedule computation
  tarikhTamat: string;
  tempohBulan: number;
  ansuranDibayar: number;
  jumlahAnsuran: number;
  status: PembiayaanStatus;
  statusKey: string;
  tarikhBayaranSeterusnya: string;
}

export interface TransaksiItem {
  id: string;
  tarikh: string;
  jenis: "pengeluaran" | "bayaran";
  keterangan: string;
  jumlah: number;
  kaedah?: string;
  status: string;
  rujukan: string;
}

export interface JadualAnsuran {
  no: number;
  tarikh: string;
  ansuran: number;
  pokok: number;
  keuntungan: number;
  baki: number;
  status: "dibayar" | "akan_datang" | "tertunggak";
}

export const PEMBIAYAAN_DUMMY: PembiayaanAkaun[] = [
  {
    id: "AKN-2024-00123",
    produk: "SPUMI",
    jumlahPembiayaan: 50000,
    bakiPokok: 25000,
    bakiKeuntungan: 2000,
    bakiAkhir: 27000,
    bayaranBulanan: 1500,
    tunggakan: 1500,
    tarikhMula: "15 Jan 2025",
    tarikhMulaIso: "2025-01-15",
    tarikhTamat: "15 Jan 2028",
    tempohBulan: 36,
    ansuranDibayar: 18,
    jumlahAnsuran: 36,
    status: "Tunggakan",
    statusKey: "tunggakan",
    tarikhBayaranSeterusnya: "15 Ogos 2026",
  },
  {
    id: "AKN-2023-00876",
    permohonanId: "SPPT-2023-009876",
    produk: "Tekun Niaga",
    jumlahPembiayaan: 75000,
    bakiPokok: 6250,
    bakiKeuntungan: 500,
    bakiAkhir: 6750,
    bayaranBulanan: 2250,
    tunggakan: 0,
    tarikhMula: "10 Okt 2023",
    tarikhMulaIso: "2023-10-10",
    tarikhTamat: "10 Okt 2026",
    tempohBulan: 36,
    ansuranDibayar: 33,
    jumlahAnsuran: 36,
    status: "Aktif",
    statusKey: "aktif",
    tarikhBayaranSeterusnya: "10 Ogos 2026",
  },
];

export const TRANSAKSI_DUMMY: Record<string, TransaksiItem[]> = {
  "AKN-2024-00123": [
    { id: "TRX-A1-020", tarikh: "16 Jun 2026", jenis: "bayaran", keterangan: "Bayaran ansuran bulanan", jumlah: 1500, kaedah: "FPX", status: "Berjaya", rujukan: "FPX7714203" },
    { id: "TRX-A1-019", tarikh: "15 Mei 2026", jenis: "bayaran", keterangan: "Bayaran ansuran bulanan", jumlah: 1500, kaedah: "Auto Debit", status: "Berjaya", rujukan: "AD-202605" },
    { id: "TRX-A1-001", tarikh: "15 Jan 2025", jenis: "pengeluaran", keterangan: "Pengeluaran dana pembiayaan", jumlah: 50000, status: "Selesai", rujukan: "PD-2025-0123" },
  ],
  "AKN-2023-00876": [
    { id: "TRX-A2-035", tarikh: "10 Jul 2026", jenis: "bayaran", keterangan: "Bayaran ansuran bulanan", jumlah: 2250, kaedah: "e-Wallet (TnG)", status: "Berjaya", rujukan: "TNG-556012" },
    { id: "TRX-A2-034", tarikh: "10 Jun 2026", jenis: "bayaran", keterangan: "Bayaran ansuran bulanan", jumlah: 2250, kaedah: "FPX", status: "Berjaya", rujukan: "FPX6620981" },
    { id: "TRX-A2-001", tarikh: "10 Okt 2023", jenis: "pengeluaran", keterangan: "Pengeluaran dana pembiayaan", jumlah: 75000, status: "Selesai", rujukan: "PD-2023-0876" },
  ],
};

export const RESTRUCTURE_TEMPOH_OPTIONS = [12, 24, 36, 48, 60] as const;

/** Format a number as Malaysian Ringgit, e.g. 27000 -> "RM 27,000.00". */
export function formatRM(value: number): string {
  return `RM ${value.toLocaleString("ms-MY", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function addMonths(iso: string, months: number): Date {
  const d = new Date(`${iso}T00:00:00`);
  d.setMonth(d.getMonth() + months);
  return d;
}

function formatTarikh(d: Date): string {
  return d.toLocaleDateString("ms-MY", { day: "numeric", month: "short", year: "numeric" });
}

/** Build the full installment schedule (jadual ansuran) for an account. */
export function generateJadualAnsuran(akaun: PembiayaanAkaun): JadualAnsuran[] {
  const totalRepay = akaun.bayaranBulanan * akaun.jumlahAnsuran;
  const pokokPerAnsuran = Math.round(akaun.jumlahPembiayaan / akaun.jumlahAnsuran);
  const keuntunganPerAnsuran = akaun.bayaranBulanan - pokokPerAnsuran;

  const rows: JadualAnsuran[] = [];
  for (let n = 1; n <= akaun.jumlahAnsuran; n += 1) {
    const baki = Math.max(0, totalRepay - n * akaun.bayaranBulanan);
    let status: JadualAnsuran["status"] = "akan_datang";
    if (n <= akaun.ansuranDibayar) {
      status = "dibayar";
    } else if (n === akaun.ansuranDibayar + 1 && akaun.tunggakan > 0) {
      status = "tertunggak";
    }
    rows.push({
      no: n,
      tarikh: formatTarikh(addMonths(akaun.tarikhMulaIso, n)),
      ansuran: akaun.bayaranBulanan,
      pokok: pokokPerAnsuran,
      keuntungan: keuntunganPerAnsuran,
      baki,
      status,
    });
  }
  return rows;
}

export interface SettlementQuote {
  bakiPinjaman: number;
  ansuranBulanan: number;
  rebatDuaBulan: number;
  amaunBersih: number;
}

/** Early-settlement quote — same rebate formula as the staff-side module. */
export function computeSettlement(akaun: PembiayaanAkaun): SettlementQuote {
  const rebatDuaBulan = akaun.bayaranBulanan * 2;
  return {
    bakiPinjaman: akaun.bakiAkhir,
    ansuranBulanan: akaun.bayaranBulanan,
    rebatDuaBulan,
    amaunBersih: Math.max(0, akaun.bakiAkhir - rebatDuaBulan),
  };
}

export interface RestructureQuote {
  tempohBaharu: number;
  bayaranSemasa: number;
  bayaranBaharu: number;
  jumlahBaharu: number;
}

/** Recompute monthly installment for a new tenure (demo formula). */
export function computeRestructure(akaun: PembiayaanAkaun, tempohBaharu: number): RestructureQuote {
  const bayaranBaharu = Math.ceil(akaun.bakiAkhir / tempohBaharu);
  return {
    tempohBaharu,
    bayaranSemasa: akaun.bayaranBulanan,
    bayaranBaharu,
    jumlahBaharu: bayaranBaharu * tempohBaharu,
  };
}
