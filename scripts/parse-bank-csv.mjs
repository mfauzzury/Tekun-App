import { readFileSync, writeFileSync, copyFileSync } from "fs";
import { join, dirname } from "path";
import { fileURLToPath } from "url";

const root = join(dirname(fileURLToPath(import.meta.url)), "..");
const sourceCsv = process.argv[2] ?? "C:/Tekun/lampiran-list-of-bank.csv";
const csv = readFileSync(sourceCsv, "utf8");
const lines = csv.trim().split(/\r?\n/).slice(1);

const banks = lines
  .map((line) => {
    const quoted = line.match(/^[^,]+,"([^"]+)"/);
    if (quoted) return quoted[1];
    const parts = line.split(",");
    return parts[1]?.replace(/^"|"$/g, "") ?? "";
  })
  .filter(Boolean);

const unique = [...new Set(banks)].sort((a, b) => a.localeCompare(b, "ms"));

const outData = join(root, "client/src/data/list-of-bank.ts");
const outCsv = join(root, "client/src/data/lampiran-list-of-bank.csv");

copyFileSync(sourceCsv, outCsv);

const body = `/** Senarai bank dari lampiran-list-of-bank.csv (kod_bank, nama, swiftcode) */
export const BANK_OPERASI_OPTIONS = ${JSON.stringify(unique, null, 2)} as const;
`;

writeFileSync(outData, body);
console.log(`Wrote ${unique.length} banks to ${outData}`);
