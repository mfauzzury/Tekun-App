<script setup lang="ts">
import { computed } from "vue";
import { useRoute } from "vue-router";
import AppConfirmDialog from "@/components/AppConfirmDialog.vue";
import PemohonChatWidget from "@/components/pemohon/PemohonChatWidget.vue";

const route = useRoute();
const isPemohonRoute = computed(() => route.path.startsWith("/pemohon"));
</script>

<template>
  <router-view />
  <AppConfirmDialog />
  <PemohonChatWidget v-if="isPemohonRoute" />
</template>
