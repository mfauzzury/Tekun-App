<script setup lang="ts">
import { computed } from "vue";
import { Pencil } from "lucide-vue-next";

import PBadge from "@/components/pemohon/ui/PBadge.vue";
import { PRODUK_OPTIONS } from "@/data/portal-dummy";
import { JANTINA_OPTIONS } from "@/config/sppt-options";
import { formatRinggit } from "@/utils/pemohon-ui";
import type { PTone } from "@/utils/pemohon-status";
import type { PemohonMyKadScan, PpFaceResult } from "@/types";
import {
  anggaranAnsuranBulanan,
  isMyKadBack,
  isMyKadFront,
  sectionHasValue,
  type AttachmentRow,
  type DataAltForm,
  type PermohonanForm,
} from "./permohonan-form";

const props = defineProps<{
  form: PermohonanForm;
  adaPasangan: boolean;
  attachments: AttachmentRow[];
  scan: PemohonMyKadScan | null;
  faceResult: PpFaceResult | null;
  dataAlt: DataAltForm;
}>();
defineEmits<{ goto: [step: number] }>();

type Row = { label: string; value: string; badge?: { tone: PTone; text: string } };

const dash = (v: unknown) => (v === null || v === undefined || String(v).trim() === "" ? "—" : String(v));

const sections = computed(() => {
  const f = props.form;
  const ansuran = anggaranAnsuranBulanan(Number(f.jumlahPermohonan) || 0, Number(f.tempohPembiayaan) || 0);
  const front = props.attachments.some(isMyKadFront);
  const back = props.attachments.some(isMyKadBack);
  const face = props.faceResult?.keputusan;
  const da = props.dataAlt;
  const sumber = [
    sectionHasValue(da.ewallet) && `e-Dompet${da.ewallet.penyedia ? ` (${da.ewallet.penyedia})` : ""}`,
    sectionHasValue(da.duitnowQr) && "DuitNow QR",
    sectionHasValue(da.bilUtiliti) && "Bil utiliti",
    sectionHasValue(da.platform) && `Platform${da.platform.nama ? ` (${da.platform.nama})` : ""}`,
  ].filter(Boolean) as string[];

  const out: { step: number; title: string; rows: Row[] }[] = [
    {
      step: 0,
      title: "Pembiayaan",
      rows: [
        { label: "Produk", value: dash(PRODUK_OPTIONS.find((p) => p.value === f.kategoriPembiayaan)?.label) },
        { label: "Jumlah", value: formatRinggit(f.jumlahPermohonan) },
        { label: "Tempoh", value: f.tempohPembiayaan ? `${f.tempohPembiayaan} bulan · ${f.kekerapanBayaran}` : "—" },
        { label: "Anggaran ansuran", value: ansuran ? `~${formatRinggit(ansuran)} / bulan` : "—" },
        { label: "Tujuan", value: dash(f.tujuanPembiayaan) },
      ],
    },
    {
      step: 1,
      title: "Identiti & e-KYC",
      rows: [
        {
          label: "MyKad",
          value: front && back ? "Depan & belakang dimuat naik" : front ? "Depan sahaja" : back ? "Belakang sahaja" : "Belum dimuat naik",
          badge: front && back ? { tone: "success", text: "Lengkap" } : { tone: "danger", text: "Diperlukan" },
        },
        {
          label: "Bacaan AI MyKad",
          value: props.scan?.ocr?.ok ? "Berjaya dibaca" : props.scan ? "Tidak dapat dibaca" : "Belum diimbas",
          badge: props.scan?.ocr?.ok ? { tone: "success", text: "OK" } : undefined,
        },
        {
          label: "Pengesahan wajah",
          value: face === "lulus" ? "Lulus" : face === "gagal" ? "Gagal" : face === "semak" ? "Perlu semakan pegawai" : "Belum dibuat — semakan manual",
          badge: face ? { tone: face === "lulus" ? "success" : face === "gagal" ? "danger" : "warning", text: face === "lulus" ? "Disahkan" : face === "gagal" ? "Gagal" : "Semak" } : { tone: "warning", text: "Dilangkau" },
        },
      ],
    },
    {
      step: 2,
      title: "Maklumat diri",
      rows: [
        { label: "Nama", value: dash(f.nama) },
        { label: "No. KP", value: dash(f.noIcBaru) },
        { label: "Jantina / umur", value: `${JANTINA_OPTIONS.find((j) => j.value === f.jantina)?.label ?? "—"}${f.umur ? ` · ${f.umur} tahun` : ""}` },
        { label: "Telefon / emel", value: `${dash(f.noTelefonBimbit)} · ${dash(f.email)}` },
        { label: "Alamat", value: [f.alamat, f.poskod, f.negeri].filter(Boolean).join(", ") || "—" },
        { label: "Pasangan", value: props.adaPasangan ? dash(f.namaPasangan) : "Tiada" },
      ],
    },
    {
      step: 3,
      title: "Perniagaan & pendapatan",
      rows: [
        { label: "Perniagaan", value: `${dash(f.namaPerniagaan)}${f.noSsm ? ` · ${f.noSsm}` : ""}` },
        { label: "Anggaran pendapatan", value: f.anggaranPendapatan ? `${formatRinggit(f.anggaranPendapatan)} / bulan` : "—" },
        {
          label: "Data alternatif",
          value: sumber.length ? sumber.join(", ") : "Tiada",
          badge: sumber.length ? { tone: "info", text: `${sumber.length} sumber` } : undefined,
        },
        { label: "CCRIS / penyata bank", value: `${da.ccrisTersedia ? "Ada CCRIS" : "Tiada CCRIS"} · ${da.penyataBankTersedia ? "Boleh sediakan penyata" : "Tiada penyata"}` },
      ],
    },
    {
      step: 4,
      title: "Sokongan & perlindungan",
      rows: [
        { label: "Penyokong", value: dash(f.sokonganNama) },
        { label: "Perujuk", value: [f.perujuk1Nama, f.perujuk2Nama].filter(Boolean).join(", ") || "—" },
        {
          label: "Perlindungan",
          value: ["Takaful Pembiayaan", f.takafulKemalangan && "Takaful Kemalangan", f.perkeso && "PERKESO", f.wasiat && "Wasiat"].filter(Boolean).join(", "),
        },
      ],
    },
  ];
  return out;
});
</script>

<template>
  <div class="space-y-3">
    <section v-for="s in sections" :key="s.step" class="rounded-2xl border border-slate-200 bg-white">
      <header class="flex items-center justify-between gap-3 px-4 pt-3.5">
        <h3 class="text-sm font-semibold text-ink-900">{{ s.title }}</h3>
        <button
          type="button"
          class="inline-flex h-8 items-center gap-1 rounded-lg px-2.5 text-xs font-semibold text-brand-700 hover:bg-brand-50 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand-100"
          :aria-label="`Ubah ${s.title}`"
          @click="$emit('goto', s.step)"
        >
          <Pencil class="h-3.5 w-3.5" /> Ubah
        </button>
      </header>
      <dl class="divide-y divide-slate-100 px-4 pb-2">
        <div v-for="r in s.rows" :key="r.label" class="grid grid-cols-[110px_minmax(0,1fr)] gap-3 py-2 text-sm sm:grid-cols-[170px_minmax(0,1fr)]">
          <dt class="text-slate-500">{{ r.label }}</dt>
          <dd class="flex flex-wrap items-center gap-2 break-words font-medium text-ink-900">
            {{ r.value }}
            <PBadge v-if="r.badge" :tone="r.badge.tone">{{ r.badge.text }}</PBadge>
          </dd>
        </div>
      </dl>
    </section>
  </div>
</template>
