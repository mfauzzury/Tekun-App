/**
 * Export client dummy TS modules to JSON for PHP SpptSeeder.
 * Run: node scripts/export-sppt-mock-data.mjs
 */
import { readFileSync, writeFileSync, mkdirSync } from "fs";
import { dirname, join } from "path";
import { fileURLToPath } from "url";

const root = join(dirname(fileURLToPath(import.meta.url)), "..");
const outDir = join(root, "database/seeders/data");
mkdirSync(outDir, { recursive: true });

const modules = [
  { file: "client/src/data/pengurusan-akaun-dummy.ts", exports: ["items"] },
  { file: "client/src/data/pengeluaran-dana-dummy.ts", exports: ["items", "auditTrail", "batches", "legalDocsTemplate", "exceptions", "integrationStatuses"] },
  { file: "client/src/data/pengurusan-jaminan-dummy.ts", exports: ["items", "auditLogs", "notifikasi"] },
  { file: "client/src/data/kutipan-dummy.ts", exports: ["KUTIPAN_ITEMS", "SKH_ITEMS", "CALL_CENTER_ITEMS", "PSAT_ITEMS", "SKM_MINGGUAN", "AUDIT_LOG_ITEMS", "KPI_PEGAWAI"] },
  { file: "client/src/data/bayaran-pembiayaan-dummy.ts", exports: ["PAYMENT_CHANNELS", "BAYARAN_ITEMS", "PEMADANAN_RESIT", "REKON_BANK", "LEBIHAN_KEKURANGAN", "PENYATA_BAYARAN", "EARLY_SETTLEMENT_DUMMY", "AKAUN_SELESAI_BAYAR", "AI_OCR_RESULTS", "AI_ANALYTICS", "CHATBOT_SAMPLE_QA"] },
  { file: "client/src/data/litigasi-dummy.ts", exports: ["PANEL_PEGUAM", "AKAUN_NPF", "NOD_ITEMS", "KES_LITIGASI", "EXECUTION_ITEMS", "WSS_ITEMS", "GARNISHEE_ITEMS", "JDS_ITEMS", "KEBANKRAPAN_ITEMS", "WINDING_UP_ITEMS", "LITIGASI_AUDIT", "LAPORAN_KES_AKTIF", "LAPORAN_KEPUTUSAN_BULANAN"] },
  { file: "client/src/data/laporan-dummy.ts", exports: ["KPI_SUMMARY", "LAPORAN_ITEMS", "KUTIPAN_VS_SASARAN", "TREND_NPF", "AGIHAN_STATUS_AKAUN", "PRESTASI_NEGERI", "CROSS_ANALYSIS_PRODUK", "LAPORAN_INDIVIDU", "LAPORAN_RESIT", "LAPORAN_PENYESUAIAN_BANK", "LAPORAN_MONTH_END", "LAPORAN_NPF", "LAPORAN_PATUH_SYARIAH", "LAPORAN_SNC", "LAPORAN_NISBAH_SELIAAN", "AUDIT_LAPORAN", "ARKIB_LAPORAN", "NOTIFIKASI_LAPORAN", "MEDAN_TERSEDIA"] },
  { file: "client/src/data/audit-dummy.ts", exports: ["AUDIT_TRAIL", "LOG_SISTEM", "LOG_PERUBAHAN_DATA", "AMARAN_LIST", "HEATMAP_AKTIVITI", "PENGGUNA_BERISIKO", "ANALITIK_AUDIT", "AI_RISK_ITEMS"] },
  { file: "client/src/data/pemantauan-usahawan-dummy.ts", exports: ["usahawanList", "dokumenByUsahawan", "lawatanList", "programLatihanList", "kehadiranLatihanList", "aiForecastData"] },
];

function stripTypes(source) {
  return source
    .replace(/^\/\*\*[\s\S]*?\*\//gm, "")
    .replace(/^\/\/.*$/gm, "")
    .replace(/export type [\s\S]*?;/g, "")
    .replace(/export interface [\s\S]*?\n}/g, "")
    .replace(/function [a-zA-Z]+\([^)]*\)[^{]*\{[\s\S]*?\n}/g, "")
    .replace(/: Record<[^>]+>/g, "")
    .replace(/const ([a-zA-Z_]+):[^=]+=/g, "const $1 =")
    .replace(/ as const/g, "")
    .replace(/export function[\s\S]*?^}/gm, "")
    .replace(/export /g, "");
}

for (const mod of modules) {
  const filePath = join(root, mod.file);
  let code = stripTypes(readFileSync(filePath, "utf8"));
  const returnKeys = mod.exports.join(", ");
  const fn = new Function(`${code}; return { ${returnKeys} };`);
  const payload = fn();
  const baseName = mod.file.split("/").pop().replace("-dummy.ts", "");
  writeFileSync(join(outDir, `${baseName}.json`), JSON.stringify(payload, null, 2));
  console.log(`Wrote ${baseName}.json`);
}

console.log("Done.");
