<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from "vue";
import { ChevronLeft, ChevronRight } from "lucide-vue-next";

import { HEBAHAN_DUMMY } from "@/data/portal-dummy";

const count = HEBAHAN_DUMMY.length;
const activeIndex = ref(0);
const paused = ref(false);
let timer: ReturnType<typeof setInterval> | undefined;

function go(i: number) {
  activeIndex.value = (i + count) % count;
}

onMounted(() => {
  const reduced = window.matchMedia?.("(prefers-reduced-motion: reduce)").matches;
  if (reduced) return;
  timer = setInterval(() => {
    if (!paused.value) go(activeIndex.value + 1);
  }, 5000);
});

onBeforeUnmount(() => {
  if (timer) clearInterval(timer);
});
</script>

<template>
  <section
    class="group relative overflow-hidden rounded-2xl border border-slate-200/80 bg-slate-100 shadow-[0_1px_2px_rgba(16,24,40,0.04),0_4px_16px_-8px_rgba(16,24,40,0.08)]"
    aria-roledescription="karusel"
    aria-label="Hebahan TEKUN"
    @mouseenter="paused = true"
    @mouseleave="paused = false"
    @focusin="paused = true"
    @focusout="paused = false"
  >
    <div class="relative aspect-video w-full">
      <div
        class="flex h-full transition-transform duration-700 ease-out motion-reduce:transition-none"
        :style="{ width: `${count * 100}%`, transform: `translateX(-${(activeIndex * 100) / count}%)` }"
      >
        <div
          v-for="(item, i) in HEBAHAN_DUMMY"
          :key="item.id"
          class="h-full shrink-0"
          :style="{ width: `${100 / count}%` }"
          role="group"
          aria-roledescription="slaid"
          :aria-label="`${i + 1} daripada ${count}: ${item.title}`"
          :aria-hidden="i !== activeIndex"
        >
          <img :src="item.image" :alt="item.title" class="h-full w-full object-cover object-bottom" :loading="i === 0 ? 'eager' : 'lazy'" />
        </div>
      </div>

      <div class="pointer-events-none absolute inset-x-0 bottom-0 h-20 bg-gradient-to-t from-ink-950/60 to-transparent" aria-hidden="true" />
      <p class="absolute bottom-3 left-4 right-24 truncate text-sm font-semibold text-white drop-shadow-sm" aria-live="polite">
        {{ HEBAHAN_DUMMY[activeIndex]?.title }}
      </p>

      <button
        type="button"
        class="absolute left-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full bg-white/85 text-ink-800 opacity-0 shadow-sm transition-opacity hover:bg-white focus-visible:opacity-100 group-hover:opacity-100"
        aria-label="Hebahan sebelumnya"
        @click="go(activeIndex - 1)"
      >
        <ChevronLeft class="h-4 w-4" />
      </button>
      <button
        type="button"
        class="absolute right-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full bg-white/85 text-ink-800 opacity-0 shadow-sm transition-opacity hover:bg-white focus-visible:opacity-100 group-hover:opacity-100"
        aria-label="Hebahan seterusnya"
        @click="go(activeIndex + 1)"
      >
        <ChevronRight class="h-4 w-4" />
      </button>

      <div class="absolute bottom-3.5 right-4 flex items-center gap-1.5">
        <button
          v-for="(item, i) in HEBAHAN_DUMMY"
          :key="item.id"
          type="button"
          class="h-1.5 rounded-full transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white"
          :class="i === activeIndex ? 'w-5 bg-white' : 'w-1.5 bg-white/55 hover:bg-white/80'"
          :aria-label="`Papar hebahan ${i + 1}`"
          :aria-current="i === activeIndex"
          @click="go(i)"
        />
      </div>
    </div>
  </section>
</template>
