<script setup lang="ts">
import { ExternalLink, FileImage, FileText, Paperclip } from "lucide-vue-next";

import { pemohonPermohonanDocumentViewUrl } from "@/api/pemohonPermohonan";
import PBadge from "@/components/pemohon/ui/PBadge.vue";
import PCard from "@/components/pemohon/ui/PCard.vue";
import PEmptyState from "@/components/pemohon/ui/PEmptyState.vue";
import type { DocumentVerification, PermohonanAttachment } from "@/types";
import type { PTone } from "@/utils/pemohon-status";

defineProps<{ attachments: PermohonanAttachment[] }>();

const VERIFY: Record<DocumentVerification["status"], { label: string; tone: PTone }> = {
  verified: { label: "Disahkan", tone: "success" },
  failed: { label: "Perlu semakan", tone: "warning" },
  pending: { label: "Menunggu", tone: "info" },
  skipped: { label: "Diterima", tone: "neutral" },
};

function formatSize(bytes: number) {
  if (!bytes) return "";
  if (bytes < 1024 * 1024) return `${Math.max(1, Math.round(bytes / 1024))} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

const isImage = (a: PermohonanAttachment) => (a.mimeType ?? "").startsWith("image/") || /\.(png|jpe?g|webp|heic)$/i.test(a.name);
</script>

<template>
  <PCard title="Dokumen dihantar" :subtitle="attachments.length ? `${attachments.length} dokumen` : undefined" :padded="attachments.length === 0">
    <PEmptyState v-if="!attachments.length" :icon="Paperclip" title="Tiada dokumen" description="Tiada dokumen dilampirkan pada permohonan ini." />
    <ul v-else class="divide-y divide-slate-100 border-t border-slate-100">
      <li v-for="a in attachments" :key="a.id" class="flex items-center gap-3 px-5 py-3">
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-500">
          <FileImage v-if="isImage(a)" class="h-5 w-5" aria-hidden="true" />
          <FileText v-else class="h-5 w-5" aria-hidden="true" />
        </span>
        <div class="min-w-0 flex-1">
          <p class="truncate text-sm font-medium text-ink-900">{{ a.documentClassLabel || a.documentClassOther || "Dokumen sokongan" }}</p>
          <p class="truncate text-xs text-slate-500">{{ a.name }}<template v-if="a.size"> · {{ formatSize(a.size) }}</template></p>
        </div>
        <PBadge v-if="a.verification" :tone="VERIFY[a.verification.status]?.tone ?? 'neutral'" class="shrink-0">
          {{ VERIFY[a.verification.status]?.label ?? a.verification.status }}
        </PBadge>
        <a
          v-if="a.url"
          :href="pemohonPermohonanDocumentViewUrl(a.url)"
          target="_blank"
          rel="noopener"
          class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-ink-900"
          :aria-label="`Buka ${a.name}`"
        >
          <ExternalLink class="h-4 w-4" aria-hidden="true" />
        </a>
      </li>
    </ul>
  </PCard>
</template>
