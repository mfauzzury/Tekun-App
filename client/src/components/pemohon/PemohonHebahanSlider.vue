<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from "vue";
import { HEBAHAN_DUMMY } from "@/data/portal-dummy";

const activeIndex = ref(0);
let timer: ReturnType<typeof setInterval> | undefined;

onMounted(() => {
  timer = setInterval(() => {
    activeIndex.value = (activeIndex.value + 1) % HEBAHAN_DUMMY.length;
  }, 4500);
});

onBeforeUnmount(() => {
  if (timer) clearInterval(timer);
});
</script>

<template>
  <div class="relative aspect-video w-full overflow-hidden rounded-2xl bg-slate-800 shadow-sm">
    <div
      class="flex h-full transition-transform duration-700 ease-in-out"
      :style="{ width: `${HEBAHAN_DUMMY.length * 100}%`, transform: `translateX(-${(activeIndex * 100) / HEBAHAN_DUMMY.length}%)` }"
    >
      <div v-for="item in HEBAHAN_DUMMY" :key="item.id" class="h-full shrink-0" :style="{ width: `${100 / HEBAHAN_DUMMY.length}%` }">
        <img :src="item.image" :alt="item.title" class="h-full w-full object-cover object-bottom" />
      </div>
    </div>

    <div class="absolute right-3 top-3 flex gap-1.5 rounded-full bg-black/25 px-2 py-1.5 backdrop-blur-sm">
      <span
        v-for="(item, i) in HEBAHAN_DUMMY"
        :key="item.id"
        class="h-1.5 rounded-full transition-all"
        :class="i === activeIndex ? 'w-5 bg-white' : 'w-1.5 bg-white/50'"
      />
    </div>
  </div>
</template>
