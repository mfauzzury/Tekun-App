<script setup lang="ts">
import { computed, nextTick, ref } from "vue";
import { MessageCircle, Send, X } from "lucide-vue-next";
import { sendPemohonChatMessage, type PemohonChatTurn } from "@/api/pemohonChat";
import { useToast } from "@/composables/useToast";

const toast = useToast();

const open = ref(false);
const sending = ref(false);
const input = ref("");
const scrollEl = ref<HTMLDivElement | null>(null);

const messages = ref<PemohonChatTurn[]>([
  {
    role: "assistant",
    content: "Salam! Saya Pembantu Portal Pemohon SPPT. Ada apa yang boleh saya bantu tentang permohonan pembiayaan anda?",
  },
]);

const canSend = computed(() => input.value.trim().length > 0 && !sending.value);

function toggle() {
  open.value = !open.value;
  if (open.value) {
    scrollToBottom();
  }
}

async function scrollToBottom() {
  await nextTick();
  scrollEl.value?.scrollTo({ top: scrollEl.value.scrollHeight, behavior: "smooth" });
}

async function send() {
  const text = input.value.trim();
  if (!text || sending.value) return;

  const history = [...messages.value];
  messages.value.push({ role: "user", content: text });
  input.value = "";
  sending.value = true;
  scrollToBottom();

  try {
    const res = await sendPemohonChatMessage(text, history);
    messages.value.push({ role: "assistant", content: res.data.reply });
  } catch (e) {
    toast.error("Pembantu AI gagal membalas", e instanceof Error ? e.message : undefined);
  } finally {
    sending.value = false;
    scrollToBottom();
  }
}
</script>

<template>
  <div class="fixed bottom-20 right-4 z-50 md:bottom-6 md:right-6">
    <div
      v-if="open"
      class="mb-3 flex h-[28rem] max-h-[70vh] w-[calc(100vw-2rem)] max-w-sm flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl"
    >
      <div class="flex items-center justify-between bg-gradient-to-r from-blue-800 to-blue-600 px-4 py-3 text-white">
        <div>
          <p class="text-sm font-semibold">Pembantu SPPT</p>
          <p class="text-xs text-blue-100">Bantuan am permohonan pembiayaan</p>
        </div>
        <button
          type="button"
          class="rounded-full p-1 text-blue-100 transition hover:bg-white/10 hover:text-white"
          aria-label="Tutup pembantu"
          @click="open = false"
        >
          <X class="h-5 w-5" />
        </button>
      </div>

      <div ref="scrollEl" class="flex-1 space-y-3 overflow-y-auto bg-slate-50 px-3 py-3">
        <div
          v-for="(turn, idx) in messages"
          :key="idx"
          class="flex"
          :class="turn.role === 'user' ? 'justify-end' : 'justify-start'"
        >
          <p
            class="max-w-[85%] whitespace-pre-wrap rounded-2xl px-3 py-2 text-sm"
            :class="
              turn.role === 'user'
                ? 'rounded-br-sm bg-blue-600 text-white'
                : 'rounded-bl-sm border border-slate-200 bg-white text-slate-700'
            "
          >
            {{ turn.content }}
          </p>
        </div>
        <div v-if="sending" class="flex justify-start">
          <p class="rounded-2xl rounded-bl-sm border border-slate-200 bg-white px-3 py-2 text-sm text-slate-400">
            Menaip...
          </p>
        </div>
      </div>

      <form class="flex items-center gap-2 border-t border-slate-200 bg-white p-2" @submit.prevent="send">
        <input
          v-model="input"
          type="text"
          placeholder="Taip soalan anda..."
          class="min-w-0 flex-1 rounded-full border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
          :disabled="sending"
        />
        <button
          type="submit"
          class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-600 text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-300"
          :disabled="!canSend"
          aria-label="Hantar"
        >
          <Send class="h-4 w-4" />
        </button>
      </form>
    </div>

    <button
      type="button"
      class="flex h-14 w-14 items-center justify-center rounded-full bg-blue-600 text-white shadow-lg transition hover:bg-blue-700"
      :aria-label="open ? 'Tutup pembantu SPPT' : 'Buka pembantu SPPT'"
      @click="toggle"
    >
      <X v-if="open" class="h-6 w-6" />
      <MessageCircle v-else class="h-6 w-6" />
    </button>
  </div>
</template>
