<script setup lang="ts">
import { Edit, Plus, Trash2 } from "lucide-vue-next";
import type { WfAuthorizedRole, WfProcessDetail } from "@/types";

defineProps<{
  loading: boolean;
  details: WfProcessDetail[];
  roles: WfAuthorizedRole[];
}>();

const emit = defineEmits<{
  addDetail: [];
  editDetail: [detail: WfProcessDetail];
  deleteDetail: [detail: WfProcessDetail];
  addRole: [];
  editRole: [role: WfAuthorizedRole];
  deleteRole: [role: WfAuthorizedRole];
}>();
</script>

<template>
  <div class="space-y-4 px-3 py-3">
    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
      Butiran proses &amp; peranan dibenarkan
    </p>

    <div v-if="loading" class="text-sm text-slate-400">Memuatkan...</div>

    <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <!-- Process details -->
      <div class="space-y-2">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold text-slate-700">Butiran proses</span>
          <button
            type="button"
            class="rounded p-0.5 text-slate-400 hover:bg-slate-100 hover:text-[var(--accent-600)]"
            title="Tambah butiran"
            @click="emit('addDetail')"
          >
            <Plus class="h-3.5 w-3.5" />
          </button>
        </div>

        <div
          v-if="details.length === 0"
          class="rounded border border-dashed border-slate-200 p-3 text-center text-xs text-slate-400"
        >
          Tiada butiran proses
        </div>

        <div v-else class="space-y-1">
          <div
            v-for="detail in details"
            :key="detail.wpdProcessDetailsId"
            class="flex items-center justify-between gap-2 rounded-lg border border-slate-100 bg-slate-50 px-2.5 py-1.5"
          >
            <div class="min-w-0 flex-1">
              <p class="truncate text-xs font-medium text-slate-700">{{ detail.wpdStatusCode }}</p>
              <p v-if="detail.wpdStatusDesc" class="truncate text-xs text-slate-500">
                {{ detail.wpdStatusDesc }}
              </p>
            </div>
            <span class="shrink-0 rounded bg-slate-100 px-1.5 py-0.5 text-xs text-slate-600">
              Susunan: {{ detail.wpdOrder ?? "-" }}
            </span>
            <div class="flex shrink-0 items-center gap-1">
              <button
                type="button"
                class="rounded p-0.5 text-slate-400 hover:text-[var(--accent-600)]"
                @click="emit('editDetail', detail)"
              >
                <Edit class="h-3 w-3" />
              </button>
              <button
                type="button"
                class="rounded p-0.5 text-slate-400 hover:text-red-500"
                @click="emit('deleteDetail', detail)"
              >
                <Trash2 class="h-3 w-3" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Authorized roles -->
      <div class="space-y-2">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold text-slate-700">Peranan dibenarkan</span>
          <button
            type="button"
            class="rounded p-0.5 text-slate-400 hover:bg-slate-100 hover:text-[var(--accent-600)]"
            title="Tambah peranan"
            @click="emit('addRole')"
          >
            <Plus class="h-3.5 w-3.5" />
          </button>
        </div>

        <div
          v-if="roles.length === 0"
          class="rounded border border-dashed border-slate-200 p-3 text-center text-xs text-slate-400"
        >
          Tiada peranan dibenarkan
        </div>

        <div v-else class="space-y-1">
          <div
            v-for="role in roles"
            :key="role.warAuthorizedRoleId"
            class="flex items-center justify-between rounded-lg border border-slate-100 bg-slate-50 px-2.5 py-1.5"
          >
            <div class="min-w-0 flex-1">
              <p class="truncate text-xs font-medium text-slate-700">
                {{ role.warGroupName ? `${role.warGroupName} (${role.warGroupCode})` : role.warGroupCode }}
              </p>
              <p
                v-if="role.warLimitMin !== null || role.warLimitMax !== null"
                class="text-xs text-slate-500"
              >
                Had {{ role.warLimitMin ?? "-" }} - {{ role.warLimitMax ?? "-" }}
              </p>
            </div>
            <div class="flex shrink-0 items-center gap-1">
              <button
                type="button"
                class="rounded p-0.5 text-slate-400 hover:text-[var(--accent-600)]"
                @click="emit('editRole', role)"
              >
                <Edit class="h-3 w-3" />
              </button>
              <button
                type="button"
                class="rounded p-0.5 text-slate-400 hover:text-red-500"
                @click="emit('deleteRole', role)"
              >
                <Trash2 class="h-3 w-3" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
