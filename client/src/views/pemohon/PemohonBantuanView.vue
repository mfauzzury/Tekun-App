<script setup lang="ts">
import { computed, ref } from "vue";
import { ChevronDown, Clock, Mail, MapPin, Phone, Search, SearchX } from "lucide-vue-next";
import PemohonLayout from "@/layouts/PemohonLayout.vue";
import PEmptyState from "@/components/pemohon/ui/PEmptyState.vue";
import PPageHeader from "@/components/pemohon/ui/PPageHeader.vue";
import { FAQ_DUMMY, HUBUNGI_TEKUN } from "@/data/portal-dummy";
import { pInputClass } from "@/utils/pemohon-ui";

const carian = ref("");
const faqTapis = computed(() => {
  const q = carian.value.trim().toLowerCase();
  if (!q) return FAQ_DUMMY;
  return FAQ_DUMMY.filter((item) => item.q.toLowerCase().includes(q) || item.a.toLowerCase().includes(q));
});
</script>

<template>
  <PemohonLayout>
    <div class="space-y-6">
      <PPageHeader eyebrow="Pusat bantuan" title="Bantuan & Soalan Lazim" subtitle="Jawapan kepada soalan yang kerap ditanya mengenai permohonan pembiayaan SPPT." />

      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- FAQ -->
        <section class="space-y-4 lg:col-span-2" aria-labelledby="faq-heading">
          <h2 id="faq-heading" class="sr-only">Soalan lazim</h2>
          <div class="relative">
            <label for="carian-faq" class="sr-only">Cari soalan</label>
            <Search class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" aria-hidden="true" />
            <input
              id="carian-faq"
              v-model="carian"
              type="search"
              placeholder="Cari soalan, cth: dokumen, tempoh, bayaran..."
              :class="[pInputClass, 'h-12 pl-11!']"
            />
          </div>

          <div v-if="faqTapis.length" class="space-y-2.5">
            <details
              v-for="item in faqTapis"
              :key="item.q"
              class="group rounded-2xl border border-slate-200/80 bg-white shadow-[0_1px_2px_rgba(16,24,40,0.04)] transition-shadow open:shadow-[0_1px_2px_rgba(16,24,40,0.04),0_8px_24px_-12px_rgba(16,24,40,0.12)]"
            >
              <summary
                class="flex cursor-pointer list-none items-center justify-between gap-4 rounded-2xl px-5 py-4 text-[15px] font-medium text-ink-900 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand-100 [&::-webkit-details-marker]:hidden"
              >
                {{ item.q }}
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition-transform group-open:rotate-180 group-open:bg-brand-50 group-open:text-brand-600">
                  <ChevronDown class="h-4 w-4" />
                </span>
              </summary>
              <p class="px-5 pb-5 text-sm leading-relaxed text-slate-600">{{ item.a }}</p>
            </details>
          </div>
          <div v-else class="rounded-2xl border border-slate-200/80 bg-white">
            <PEmptyState :icon="SearchX" title="Tiada padanan" :description="`Tiada soalan sepadan dengan “${carian}”. Cuba kata kunci lain atau hubungi kami.`" />
          </div>
        </section>

        <!-- Contact -->
        <aside class="space-y-3" aria-labelledby="hubungi-heading">
          <h2 id="hubungi-heading" class="text-sm font-semibold text-ink-900">Hubungi kami</h2>

          <a
            :href="`tel:${HUBUNGI_TEKUN.telefon.replace(/[^0-9+]/g, '')}`"
            class="group flex items-center gap-3.5 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-[0_1px_2px_rgba(16,24,40,0.04)] transition-all hover:border-slate-300 hover:shadow-md focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand-100"
          >
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><Phone class="h-5 w-5" /></span>
            <span class="min-w-0">
              <span class="block text-xs font-medium text-slate-500">Talian bantuan</span>
              <span class="block text-[15px] font-semibold text-ink-900 group-hover:text-brand-700">{{ HUBUNGI_TEKUN.telefon }}</span>
            </span>
          </a>

          <a
            :href="`mailto:${HUBUNGI_TEKUN.emel}`"
            class="group flex items-center gap-3.5 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-[0_1px_2px_rgba(16,24,40,0.04)] transition-all hover:border-slate-300 hover:shadow-md focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand-100"
          >
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600"><Mail class="h-5 w-5" /></span>
            <span class="min-w-0">
              <span class="block text-xs font-medium text-slate-500">Emel</span>
              <span class="block truncate text-[15px] font-semibold text-ink-900 group-hover:text-brand-700">{{ HUBUNGI_TEKUN.emel }}</span>
            </span>
          </a>

          <div class="flex items-start gap-3.5 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-[0_1px_2px_rgba(16,24,40,0.04)]">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"><MapPin class="h-5 w-5" /></span>
            <span class="min-w-0">
              <span class="block text-xs font-medium text-slate-500">Ibu pejabat</span>
              <span class="mt-0.5 block text-sm leading-relaxed text-ink-800">{{ HUBUNGI_TEKUN.alamat }}</span>
            </span>
          </div>

          <div class="flex items-start gap-3.5 rounded-2xl border border-slate-200/70 bg-slate-50 p-4">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-slate-500 ring-1 ring-slate-200"><Clock class="h-5 w-5" /></span>
            <span class="min-w-0">
              <span class="block text-xs font-medium text-slate-500">Waktu operasi</span>
              <span v-for="waktu in HUBUNGI_TEKUN.waktuOperasi" :key="waktu" class="mt-0.5 block text-sm text-ink-800">{{ waktu }}</span>
            </span>
          </div>
        </aside>
      </div>
    </div>
  </PemohonLayout>
</template>
