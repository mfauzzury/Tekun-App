<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { Check, GripVertical, Plus, RefreshCw, Save, Trash2, X } from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import { useI18n } from "@/composables/useI18n";
import { useConfirmDialog } from "@/composables/useConfirmDialog";
import { fetchSpptSetup, updateSpptSetupCategory } from "@/api/sppt";
import type { SpptSetupCategory, SpptSetupStatusItem } from "@/types";

const { t, tp } = useI18n();
const { confirm } = useConfirmDialog();

const COLOR_OPTIONS = [
  { value: "slate", label: "Kelabu", class: "bg-slate-100 text-slate-700" },
  { value: "amber", label: "Kuning", class: "bg-amber-100 text-amber-700" },
  { value: "emerald", label: "Hijau", class: "bg-emerald-100 text-emerald-700" },
  { value: "blue", label: "Biru", class: "bg-blue-100 text-blue-700" },
  { value: "rose", label: "Merah", class: "bg-rose-100 text-rose-700" },
  { value: "violet", label: "Ungu", class: "bg-violet-100 text-violet-700" },
];

const loading = ref(true);
const saving = ref(false);
const saved = ref(false);
const error = ref("");
const categories = ref<SpptSetupCategory[]>([]);
const selectedKey = ref("");
const editItems = ref<SpptSetupStatusItem[]>([]);
const showAddForm = ref(false);
const newItem = ref<SpptSetupStatusItem>({ value: "", label: "", color: "slate", active: true, sort: 0 });

const selectedCategory = computed(() => categories.value.find((c) => c.key === selectedKey.value));

const isDirty = computed(() => {
  const original = selectedCategory.value?.items ?? [];
  return JSON.stringify(original) !== JSON.stringify(editItems.value);
});

function colorClass(color?: string) {
  return COLOR_OPTIONS.find((c) => c.value === color)?.class ?? "bg-slate-100 text-slate-700";
}

function slugify(text: string) {
  return text
    .toLowerCase()
    .replace(/[^a-z0-9\s-]/g, "")
    .trim()
    .replace(/\s+/g, "-");
}

async function loadSetup() {
  loading.value = true;
  error.value = "";
  try {
    const res = await fetchSpptSetup();
    categories.value = res.data;
    if (!selectedKey.value && categories.value.length) {
      selectedKey.value = categories.value[0].key;
    }
    syncEditItems();
  } catch {
    error.value = t("sppt.setupLoadError");
  } finally {
    loading.value = false;
  }
}

function syncEditItems() {
  const cat = selectedCategory.value;
  editItems.value = cat ? cat.items.map((item) => ({ ...item })) : [];
}

function selectCategory(key: string) {
  if (isDirty.value) {
    confirm({
      title: tp("Perubahan belum disimpan"),
      message: tp("Adakah anda pasti mahu menukar kategori? Perubahan semasa akan hilang."),
      destructive: true,
    }).then((ok) => {
      if (ok) {
        selectedKey.value = key;
        syncEditItems();
        showAddForm.value = false;
      }
    });
    return;
  }
  selectedKey.value = key;
  syncEditItems();
  showAddForm.value = false;
}

function addItem() {
  if (!newItem.value.label.trim()) return;
  const value = newItem.value.value.trim() || slugify(newItem.value.label);
  if (editItems.value.some((i) => i.value === value)) return;

  editItems.value.push({
    value,
    label: newItem.value.label.trim(),
    color: newItem.value.color ?? "slate",
    active: newItem.value.active ?? true,
    sort: editItems.value.length + 1,
  });
  newItem.value = { value: "", label: "", color: "slate", active: true, sort: 0 };
  showAddForm.value = false;
}

async function removeItem(index: number) {
  const ok = await confirm({
    title: tp("Padam status"),
    message: tp("Adakah anda pasti mahu memadam status ini?"),
    destructive: true,
  });
  if (ok) editItems.value.splice(index, 1);
}

async function saveCategory() {
  if (!selectedKey.value) return;
  saving.value = true;
  saved.value = false;
  error.value = "";
  try {
    const res = await updateSpptSetupCategory(selectedKey.value, editItems.value);
    const idx = categories.value.findIndex((c) => c.key === selectedKey.value);
    if (idx >= 0) categories.value[idx] = res.data;
    editItems.value = res.data.items.map((item) => ({ ...item }));
    saved.value = true;
    setTimeout(() => { saved.value = false; }, 2500);
  } catch {
    error.value = t("sppt.setupSaveError");
  } finally {
    saving.value = false;
  }
}

function resetCategory() {
  syncEditItems();
  showAddForm.value = false;
}

onMounted(loadSetup);
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <SpptPageHeader
        title="Tetapan (Setup)"
        :breadcrumb="[{ label: 'Tetapan (Setup)' }]"
      />

      <p class="text-sm text-slate-600">
        {{ tp("Urus kod rujukan dan status untuk modul Pengurusan Pembiayaan. Perubahan akan digunakan dalam dropdown dan penapisan sistem.") }}
      </p>

      <div v-if="error" class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
        {{ error }}
      </div>

      <div class="flex flex-col gap-4 lg:flex-row">
        <!-- Category sidebar -->
        <aside class="w-full shrink-0 lg:w-64">
          <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-4 py-3">
              <h2 class="text-sm font-semibold text-slate-900">{{ tp("Kategori Tetapan") }}</h2>
            </div>
            <nav class="p-2">
              <button
                v-for="cat in categories"
                :key="cat.key"
                type="button"
                class="flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm transition-colors"
                :class="selectedKey === cat.key ? 'bg-violet-50 font-medium text-violet-800' : 'text-slate-700 hover:bg-slate-50'"
                @click="selectCategory(cat.key)"
              >
                <span>{{ tp(cat.label) }}</span>
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-500">{{ cat.items.length }}</span>
              </button>
            </nav>
          </div>
        </aside>

        <!-- Status editor -->
        <article class="min-w-0 flex-1 rounded-lg border border-slate-200 bg-white shadow-sm">
          <div v-if="loading" class="px-4 py-12 text-center text-sm text-slate-500">
            {{ t("common.loading") }}
          </div>

          <template v-else-if="selectedCategory">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
              <div>
                <h2 class="text-sm font-semibold text-slate-900">{{ tp(selectedCategory.label) }}</h2>
                <p v-if="selectedCategory.description" class="mt-0.5 text-xs text-slate-500">{{ tp(selectedCategory.description) }}</p>
              </div>
              <div class="flex items-center gap-2">
                <button
                  type="button"
                  class="flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50"
                  :disabled="!isDirty"
                  @click="resetCategory"
                >
                  <RefreshCw class="h-4 w-4" />
                  {{ t("common.reset") }}
                </button>
                <button
                  type="button"
                  class="flex items-center gap-1.5 rounded-lg bg-slate-900 px-3 py-1.5 text-sm font-medium text-white hover:bg-slate-800 disabled:opacity-50"
                  :disabled="saving || !isDirty"
                  @click="saveCategory"
                >
                  <Save class="h-4 w-4" />
                  {{ saving ? t("common.saving") : t("common.save") }}
                </button>
              </div>
            </div>

            <div v-if="saved" class="mx-4 mt-3 flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
              <Check class="h-4 w-4" />
              {{ tp("Tetapan berjaya disimpan") }}
            </div>

            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead>
                  <tr class="border-b border-slate-100 text-left">
                    <th class="w-8 px-4 py-2" />
                    <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ tp("Kod") }}</th>
                    <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ tp("Label") }}</th>
                    <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ tp("Warna") }}</th>
                    <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.status") }}</th>
                    <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.actions") }}</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-for="(item, index) in editItems" :key="item.value" class="hover:bg-slate-50">
                    <td class="px-4 py-2 text-slate-300">
                      <GripVertical class="h-4 w-4" />
                    </td>
                    <td class="px-4 py-2">
                      <input
                        v-model="item.value"
                        type="text"
                        class="w-full rounded border border-slate-200 px-2 py-1 font-mono text-xs text-slate-700 focus:border-violet-400 focus:outline-none focus:ring-1 focus:ring-violet-400"
                      />
                    </td>
                    <td class="px-4 py-2">
                      <input
                        v-model="item.label"
                        type="text"
                        class="w-full rounded border border-slate-200 px-2 py-1 text-slate-700 focus:border-violet-400 focus:outline-none focus:ring-1 focus:ring-violet-400"
                      />
                    </td>
                    <td class="px-4 py-2">
                      <select
                        v-model="item.color"
                        class="rounded border border-slate-200 px-2 py-1 text-slate-700 focus:border-violet-400 focus:outline-none"
                      >
                        <option v-for="opt in COLOR_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                      </select>
                      <span class="ml-2 inline-flex rounded-full px-2 py-0.5 text-xs font-medium" :class="colorClass(item.color)">
                        {{ item.label || "—" }}
                      </span>
                    </td>
                    <td class="px-4 py-2">
                      <label class="inline-flex cursor-pointer items-center gap-2">
                        <input v-model="item.active" type="checkbox" class="rounded border-slate-300 text-violet-600 focus:ring-violet-500" />
                        <span class="text-xs text-slate-600">{{ item.active ? tp("Aktif") : tp("Tidak Aktif") }}</span>
                      </label>
                    </td>
                    <td class="px-4 py-2 text-right">
                      <button
                        type="button"
                        class="rounded p-1 text-slate-400 hover:bg-rose-50 hover:text-rose-600"
                        @click="removeItem(index)"
                      >
                        <Trash2 class="h-4 w-4" />
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Add new status -->
            <div class="border-t border-slate-100 px-4 py-3">
              <button
                v-if="!showAddForm"
                type="button"
                class="flex items-center gap-1.5 text-sm font-medium text-violet-700 hover:text-violet-900"
                @click="showAddForm = true"
              >
                <Plus class="h-4 w-4" />
                {{ tp("Tambah Status") }}
              </button>

              <div v-else class="flex flex-wrap items-end gap-3 rounded-lg border border-dashed border-slate-200 bg-slate-50 p-3">
                <div class="min-w-[120px] flex-1">
                  <label class="mb-1 block text-xs font-medium text-slate-600">{{ tp("Kod") }}</label>
                  <input
                    v-model="newItem.value"
                    type="text"
                    :placeholder="tp('Auto jika kosong')"
                    class="w-full rounded border border-slate-200 px-2 py-1.5 text-sm focus:border-violet-400 focus:outline-none"
                  />
                </div>
                <div class="min-w-[180px] flex-1">
                  <label class="mb-1 block text-xs font-medium text-slate-600">{{ tp("Label") }}</label>
                  <input
                    v-model="newItem.label"
                    type="text"
                    :placeholder="tp('Contoh: Dalam Proses')"
                    class="w-full rounded border border-slate-200 px-2 py-1.5 text-sm focus:border-violet-400 focus:outline-none"
                  />
                </div>
                <div>
                  <label class="mb-1 block text-xs font-medium text-slate-600">{{ tp("Warna") }}</label>
                  <select v-model="newItem.color" class="rounded border border-slate-200 px-2 py-1.5 text-sm">
                    <option v-for="opt in COLOR_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                  </select>
                </div>
                <div class="flex gap-2">
                  <button
                    type="button"
                    class="flex items-center gap-1 rounded-lg bg-violet-700 px-3 py-1.5 text-sm font-medium text-white hover:bg-violet-800"
                    @click="addItem"
                  >
                    <Plus class="h-4 w-4" />
                    {{ tp("Tambah") }}
                  </button>
                  <button
                    type="button"
                    class="rounded-lg border border-slate-200 p-1.5 text-slate-500 hover:bg-white"
                    @click="showAddForm = false"
                  >
                    <X class="h-4 w-4" />
                  </button>
                </div>
              </div>
            </div>
          </template>
        </article>
      </div>
    </div>
  </AdminLayout>
</template>
