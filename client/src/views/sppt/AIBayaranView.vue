<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { ref, onMounted } from "vue";
import { Scan, BarChart3, MessageCircle, Send } from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import { fetchSpptDataset } from "@/api/sppt";
import { AI_OCR_RESULTS, AI_ANALYTICS, CHATBOT_SAMPLE_QA } from "@/data/bayaran-pembiayaan-dummy";

const { t, tp } = useI18n();

const activeTab = ref<"ocr" | "analitik" | "chatbot">("ocr");
const chatInput = ref("");
const chatMessages = ref<{ role: "user" | "bot"; text: string }[]>([]);

const ocrResults = ref<(typeof AI_OCR_RESULTS)[number][]>([]);
const analytics = ref<(typeof AI_ANALYTICS)[number][]>([]);
const sampleQA = ref<(typeof CHATBOT_SAMPLE_QA)[number][]>([]);

onMounted(async () => {
  const [ocrRes, analyticsRes, qaRes] = await Promise.all([
    fetchSpptDataset("pembayaran", "ai_ocr_results"),
    fetchSpptDataset("pembayaran", "ai_analytics"),
    fetchSpptDataset("pembayaran", "chatbot_sample_qa"),
  ]);
  ocrResults.value = ocrRes.data as typeof ocrResults.value;
  analytics.value = analyticsRes.data as typeof analytics.value;
  sampleQA.value = qaRes.data as typeof sampleQA.value;
});

function uploadOCR() {
  alert("Muat naik resit untuk AI OCR (dummy). Tiada sambungan API.");
}

function sendChat() {
  if (!chatInput.value.trim()) return;
  chatMessages.value.push({ role: "user", text: chatInput.value });
  const q = chatInput.value;
  chatInput.value = "";
  const match = sampleQA.value.find((s) => s.q.toLowerCase().includes(q.toLowerCase()) || q.toLowerCase().includes("baki") || q.toLowerCase().includes("bayaran"));
  const reply = match ? match.a : "Maaf, saya tidak pasti. Sila hubungi pegawai TEKUN untuk bantuan lanjut.";
  setTimeout(() => chatMessages.value.push({ role: "bot", text: reply }), 500);
}
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <SpptPageHeader
        title="AI – Pengurusan Bayaran Balik"
        :breadcrumb="[{ label: 'Pembayaran', to: '/admin/pembayaran' }, { label: 'AI Bayaran' }]"
      />

      <div class="flex flex-wrap gap-2">
        <button
          v-for="t in [
            { id: 'ocr' as const, label: 'AI OCR Resit', icon: Scan },
            { id: 'analitik' as const, label: 'Analitik', icon: BarChart3 },
            { id: 'chatbot' as const, label: 'Chatbot', icon: MessageCircle },
          ]"
          :key="t.id"
          type="button"
          class="flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm font-medium transition-colors"
          :class="activeTab === t.id ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
          @click="activeTab = t.id"
        >
          <component :is="t.icon" class="h-4 w-4" />
          {{ t.label }}
        </button>
      </div>

      <!-- AI OCR -->
      <article v-if="activeTab === 'ocr'" class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-4 py-3">
          <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-900">
            <Scan class="h-4 w-4" />
            Pengesahan Resit dengan AI OCR
          </h2>
          <p class="mt-1 text-xs text-slate-500">Pemadanan pintar menggunakan logik AI untuk ekstrak data dari imej resit.</p>
        </div>
        <div class="p-4">
          <button
            class="flex items-center gap-2 rounded-lg border-2 border-dashed border-slate-300 px-6 py-4 text-sm text-slate-600 transition-colors hover:border-slate-400 hover:bg-slate-50"
            @click="uploadOCR"
          >
            <Scan class="h-5 w-5" />
            Muat naik imej resit / slip bank / cek
          </button>
          <div class="mt-4 overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-slate-100 text-left">
                  <th class="px-4 py-2 text-xs font-semibold uppercase text-slate-500">Resit</th>
                  <th class="px-4 py-2 text-xs font-semibold uppercase text-slate-500">Medan</th>
                  <th class="px-4 py-2 text-xs font-semibold uppercase text-slate-500">Nilai</th>
                  <th class="px-4 py-2 text-xs font-semibold uppercase text-slate-500">Confidence</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="(r, i) in ocrResults" :key="i">
                  <td class="px-4 py-2 font-mono text-slate-600">{{ r.resitId }}</td>
                  <td class="px-4 py-2 text-slate-600">{{ r.key }}</td>
                  <td class="px-4 py-2 font-medium text-slate-900">{{ r.value }}</td>
                  <td class="px-4 py-2">
                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-700">{{ r.confidence }}%</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </article>

      <!-- Analitik -->
      <article v-else-if="activeTab === 'analitik'" class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-4 py-3">
          <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-900">
            <BarChart3 class="h-4 w-4" />
            Analitik Lebihan/Kekurangan & Cadangan Pelarasan
          </h2>
          <p class="mt-1 text-xs text-slate-500">Pengesanan automatik dan cadangan pelarasan.</p>
        </div>
        <div class="grid gap-4 p-4 md:grid-cols-3">
          <div
            v-for="a in analytics"
            :key="a.label"
            class="rounded-lg border border-slate-200 p-4"
          >
            <p class="text-sm font-medium text-slate-900">{{ a.label }}</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ a.count }}</p>
            <p class="text-xs text-slate-500">Trend: {{ a.trend }}</p>
          </div>
        </div>
      </article>

      <!-- Chatbot -->
      <article v-else-if="activeTab === 'chatbot'" class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-4 py-3">
          <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-900">
            <MessageCircle class="h-4 w-4" />
            Chatbot – Semakan Baki, Penyata & Panduan Pembayaran
          </h2>
          <p class="mt-1 text-xs text-slate-500">Contoh soalan: Berapa baki pinjaman saya? Bila tarikh bayaran seterusnya? Cara buat bayaran?</p>
        </div>
        <div class="flex flex-col p-4" style="min-height: 320px">
          <div class="flex-1 space-y-3 overflow-y-auto">
            <div
              v-for="(m, i) in chatMessages"
              :key="i"
              class="flex"
              :class="m.role === 'user' ? 'justify-end' : 'justify-start'"
            >
              <div
                class="max-w-[80%] rounded-lg px-3 py-2 text-sm"
                :class="m.role === 'user' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-800'"
              >
                {{ m.text }}
              </div>
            </div>
            <div v-if="chatMessages.length === 0" class="rounded-lg bg-slate-50 p-4 text-center text-sm text-slate-500">
              <p class="font-medium">Cuba tanya:</p>
              <ul class="mt-2 space-y-1 text-left">
                <li v-for="s in sampleQA" :key="s.q">• {{ s.q }}</li>
              </ul>
            </div>
          </div>
          <div class="mt-4 flex gap-2">
            <input
              v-model="chatInput"
              type="text"
              placeholder="Tanya tentang baki, penyata, bayaran..."
              class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
              @keydown.enter="sendChat"
            />
            <button
              class="flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800"
              @click="sendChat"
            >
              <Send class="h-4 w-4" />
              Hantar
            </button>
          </div>
        </div>
      </article>
    </div>
  </AdminLayout>
</template>
