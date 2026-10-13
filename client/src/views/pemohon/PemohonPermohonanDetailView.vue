<script setup lang="ts">
import { computed } from "vue";
import { useRoute } from "vue-router";
import { FileSearch } from "lucide-vue-next";

import DummyApplicationDetail from "@/components/pemohon/status/DummyApplicationDetail.vue";
import RealApplicationDetail from "@/components/pemohon/status/RealApplicationDetail.vue";
import PButton from "@/components/pemohon/ui/PButton.vue";
import PEmptyState from "@/components/pemohon/ui/PEmptyState.vue";
import PemohonLayout from "@/layouts/PemohonLayout.vue";
import { usePemohonStore } from "@/stores/pemohon";

const route = useRoute();
const pemohon = usePemohonStore();

const idParam = computed(() => String(route.params.id ?? ""));

/** Numeric id → a real backend application the user owns (token kept in the store). */
const real = computed(() => (/^\d+$/.test(idParam.value) ? pemohon.permohonanSaya.find((p) => p.id === Number(idParam.value)) : undefined));
/** Otherwise → a sample history record (e.g. SPPT-2024-001234). */
const dummy = computed(() => (real.value ? undefined : pemohon.permohonanList.find((p) => p.id === idParam.value)));
</script>

<template>
  <PemohonLayout>
    <RealApplicationDetail v-if="real" :key="real.id" :item="real" />
    <DummyApplicationDetail v-else-if="dummy" :key="dummy.id" :item="dummy" />
    <div v-else class="pt-10">
      <div class="rounded-2xl border border-slate-200/80 bg-white">
        <PEmptyState
          :icon="FileSearch"
          title="Permohonan tidak dijumpai"
          description="Permohonan ini tiada dalam akaun anda atau pautan telah tamat tempoh."
        >
          <PButton to="/pemohon/permohonan" variant="outline">Kembali ke senarai permohonan</PButton>
        </PEmptyState>
      </div>
    </div>
  </PemohonLayout>
</template>
