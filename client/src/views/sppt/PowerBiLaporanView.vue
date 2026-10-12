<script setup lang="ts">
import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import PowerBiReportEmbed from "@/components/sppt/PowerBiReportEmbed.vue";
</script>

<template>
  <AdminLayout>
    <div class="-mb-3 flex h-[calc(100dvh-2.5rem-0.75rem)] flex-col gap-2 md:-mb-4 md:h-[calc(100dvh-2.5rem-1rem)]">
      <SpptPageHeader
        class="shrink-0"
        title="Dashboard Power BI"
        route-name="laporan-power-bi"
        :breadcrumb="[
          { label: 'Laporan', to: '/admin/laporan' },
          { label: 'Dashboard Power BI' },
        ]"
      />

      <PowerBiReportEmbed title="Dashboard Power BI — SPPT" class="min-h-[calc(100dvh-9rem)] flex-1" />
    </div>
  </AdminLayout>
</template>
