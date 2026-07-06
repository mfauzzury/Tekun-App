<script setup lang="ts">
defineProps<{
  inputClass?: string;
}>();

const model = defineModel<string>({ default: "" });
</script>

<template>
  <input v-model="model" type="time" :class="inputClass" />
</template>
