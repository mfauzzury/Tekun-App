<script setup lang="ts">
import { computed, type Component } from "vue";
import { Check, QrCode, Receipt, Smartphone, Store } from "lucide-vue-next";

import PCard from "@/components/pemohon/ui/PCard.vue";
import type { PpDataAlternatif } from "@/types";
import { formatRinggit } from "@/utils/pemohon-ui";

const props = defineProps<{ data?: PpDataAlternatif | null }>();

function join(...parts: (string | null | undefined | false)[]) {
  return parts.filter(Boolean).join(" · ");
}
const rm = (v?: number | null) => (v != null ? `${formatRinggit(v)}/bulan` : null);
const bulan = (v?: number | null) => (v != null ? `${v} bulan aktif` : null);

const rows = computed(() => {
  const d = props.data;
  if (!d) return [];
  const out: { icon: Component; title: string; detail: string }[] = [];
  if (d.ewallet) {
    out.push({
      icon: Smartphone,
      title: d.ewallet.penyedia ? `e-Wallet (${d.ewallet.penyedia})` : "e-Wallet",
      detail: join(rm(d.ewallet.purataMasukBulanan), d.ewallet.bilanganTransaksiBulanan != null && `${d.ewallet.bilanganTransaksiBulanan} transaksi/bulan`, bulan(d.ewallet.bulanAktif)),
    });
  }
  if (d.duitnowQr) {
    const t = d.duitnowQr.trendPertumbuhanPeratus;
    out.push({
      icon: QrCode,
      title: "DuitNow QR",
      detail: join(rm(d.duitnowQr.purataJualanBulanan), t != null && `trend ${t > 0 ? "+" : ""}${t}%`, bulan(d.duitnowQr.bulanAktif)),
    });
  }
  if (d.bilUtiliti) {
    const { bulanDibayarTepat: tepat, bulanDinilai: dinilai } = d.bilUtiliti;
    out.push({
      icon: Receipt,
      title: "Bil utiliti",
      detail: tepat != null && dinilai ? `${tepat}/${dinilai} bulan dibayar tepat masa` : "Disertakan",
    });
  }
  if (d.platform) {
    out.push({
      icon: Store,
      title: d.platform.nama ? `Platform jualan (${d.platform.nama})` : "Platform jualan",
      detail: join(rm(d.platform.purataJualanBulanan), d.platform.penarafan != null && `penarafan ${d.platform.penarafan}/5`, bulan(d.platform.bulanAktif)),
    });
  }
  return out;
});

const consents = computed(() =>
  [
    props.data?.ccrisTersedia && "Semakan CCRIS dibenarkan",
    props.data?.penyataBankTersedia && "Penyata bank disertakan",
  ].filter((x): x is string => !!x),
);
</script>

<template>
  <PCard title="Data alternatif" subtitle="Maklumat tambahan yang anda kongsi untuk penilaian">
    <div v-if="rows.length || consents.length" class="space-y-3">
      <div v-for="r in rows" :key="r.title" class="flex items-start gap-3">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
          <component :is="r.icon" class="h-[18px] w-[18px]" aria-hidden="true" />
        </span>
        <div class="min-w-0 text-sm">
          <p class="font-medium text-ink-900">{{ r.title }}</p>
          <p class="text-slate-500">{{ r.detail || "Disertakan" }}</p>
        </div>
      </div>
      <div v-if="consents.length" class="flex flex-wrap gap-2 pt-1">
        <span v-for="c in consents" :key="c" class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
          <Check class="h-3 w-3" aria-hidden="true" /> {{ c }}
        </span>
      </div>
    </div>
    <p v-else class="text-sm text-slate-500">Tiada data alternatif dikongsi bagi permohonan ini.</p>
  </PCard>
</template>
