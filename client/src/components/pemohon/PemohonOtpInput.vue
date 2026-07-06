<script setup lang="ts">
import { nextTick, ref, watch } from "vue";

const props = defineProps<{
  modelValue: string;
  length?: number;
}>();

const emit = defineEmits<{
  (e: "update:modelValue", value: string): void;
}>();

const digitCount = props.length ?? 6;
const digits = ref<string[]>(Array.from({ length: digitCount }, () => ""));
const inputs = ref<Array<HTMLInputElement | null>>([]);

watch(
  () => props.modelValue,
  (value) => {
    const chars = value.padEnd(digitCount, " ").slice(0, digitCount).split("");
    digits.value = chars.map((c) => (c === " " ? "" : c));
  },
  { immediate: true },
);

function emitValue() {
  emit("update:modelValue", digits.value.join(""));
}

function onInput(index: number, event: Event) {
  const target = event.target as HTMLInputElement;
  const value = target.value.replace(/[^0-9]/g, "").slice(-1);
  digits.value[index] = value;
  emitValue();

  if (value && index < digitCount - 1) {
    nextTick(() => inputs.value[index + 1]?.focus());
  }
}

function onKeydown(index: number, event: KeyboardEvent) {
  if (event.key === "Backspace" && !digits.value[index] && index > 0) {
    inputs.value[index - 1]?.focus();
  }
}

function onPaste(event: ClipboardEvent) {
  const pasted = event.clipboardData?.getData("text").replace(/[^0-9]/g, "") ?? "";
  if (!pasted) return;
  event.preventDefault();
  digits.value = pasted.slice(0, digitCount).padEnd(digitCount, "").split("").map((c) => c || "");
  emitValue();
  nextTick(() => {
    const lastFilled = Math.min(pasted.length, digitCount) - 1;
    inputs.value[Math.max(lastFilled, 0)]?.focus();
  });
}
</script>

<template>
  <div class="flex items-center gap-2" @paste="onPaste">
    <input
      v-for="(digit, index) in digits"
      :key="index"
      :ref="(el) => (inputs[index] = el as HTMLInputElement)"
      :value="digit"
      type="text"
      inputmode="numeric"
      maxlength="1"
      class="h-12 w-10 rounded-lg border border-slate-300 text-center text-lg font-semibold text-slate-900 transition-colors focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
      @input="onInput(index, $event)"
      @keydown="onKeydown(index, $event)"
    />
  </div>
</template>
