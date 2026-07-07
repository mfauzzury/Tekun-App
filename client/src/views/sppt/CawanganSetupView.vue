<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import { Building2, Pencil, Plus, RefreshCw, Search, Trash2, X } from "lucide-vue-next";
import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import { useConfirmDialog } from "@/composables/useConfirmDialog";
import { useToast } from "@/composables/useToast";
import {
  createSpptCawangan,
  deleteSpptCawangan,
  listSpptCawangan,
  listSpptCawanganNegeriOptions,
  updateSpptCawangan,
} from "@/api/sppt";
import type { SpptCawangan, SpptCawanganBranchType, SpptCawanganInput } from "@/types";

const toast = useToast();
const { confirm } = useConfirmDialog();

const loading = ref(false);
const saving = ref(false);
const rows = ref<SpptCawangan[]>([]);
const negeriOptions = ref<string[]>([]);
const total = ref(0);
const page = ref(1);
const limit = ref(20);

const q = ref("");
const filterNegeri = ref("");
const filterType = ref("");

const showModal = ref(false);
const editing = ref<SpptCawangan | null>(null);
const form = ref<SpptCawanganInput>(emptyForm());

const branchTypeLabels: Record<SpptCawanganBranchType, string> = {
  negeri: "Pejabat Negeri",
  cawangan: "Cawangan",
  ibu_pejabat: "Ibu Pejabat",
};

const totalPages = computed(() => Math.max(1, Math.ceil(total.value / limit.value)));

function emptyForm(): SpptCawanganInput {
  return {
    code: "",
    name: "",
    branchType: "cawangan",
    negeri: "",
    locality: "",
    postalCode: "",
    address: "",
    phone: "",
    fax: "",
    contactPerson: "",
    isActive: true,
    sortOrder: 0,
  };
}

function slugify(text: string) {
  return text
    .toLowerCase()
    .replace(/[^a-z0-9\s-]/g, "")
    .trim()
    .replace(/\s+/g, "-")
    .slice(0, 100);
}

function autofillCodeFromName() {
  if (!form.value.code && form.value.name) {
    form.value.code = slugify(form.value.name);
  }
}

async function loadNegeriOptions() {
  try {
    const res = await listSpptCawanganNegeriOptions();
    negeriOptions.value = res.data;
  } catch {
    negeriOptions.value = [];
  }
}

async function loadRows() {
  loading.value = true;
  try {
    const res = await listSpptCawangan({
      page: page.value,
      limit: limit.value,
      q: q.value || undefined,
      negeri: filterNegeri.value || undefined,
      branch_type: filterType.value || undefined,
      sort_by: "sort_order",
      sort_dir: "asc",
    });
    rows.value = res.data;
    total.value = Number(res.meta?.total ?? res.data.length);
  } catch (e) {
    toast.error(e instanceof Error ? e.message : "Gagal memuatkan cawangan");
  } finally {
    loading.value = false;
  }
}

function openAdd() {
  editing.value = null;
  form.value = emptyForm();
  showModal.value = true;
}

function openEdit(row: SpptCawangan) {
  editing.value = row;
  form.value = {
    code: row.code,
    name: row.name,
    branchType: row.branchType ?? "cawangan",
    negeri: row.negeri ?? "",
    locality: row.locality ?? "",
    postalCode: row.postalCode ?? "",
    address: row.address ?? "",
    phone: row.phone ?? "",
    fax: row.fax ?? "",
    contactPerson: row.contactPerson ?? "",
    externalId: row.externalId ?? "",
    isActive: row.isActive ?? true,
    sortOrder: row.sortOrder ?? 0,
  };
  showModal.value = true;
}

async function save() {
  if (!form.value.name.trim() || !form.value.code.trim()) {
    toast.error("Kod dan nama cawangan wajib diisi");
    return;
  }

  saving.value = true;
  try {
    const payload: SpptCawanganInput = {
      ...form.value,
      code: form.value.code.trim(),
      name: form.value.name.trim(),
      negeri: form.value.negeri?.trim() || null,
      locality: form.value.locality?.trim() || null,
      postalCode: form.value.postalCode?.trim() || null,
      address: form.value.address?.trim() || null,
      phone: form.value.phone?.trim() || null,
      fax: form.value.fax?.trim() || null,
      contactPerson: form.value.contactPerson?.trim() || null,
    };

    if (editing.value) {
      await updateSpptCawangan(editing.value.id, payload);
      toast.success("Cawangan dikemaskini");
    } else {
      await createSpptCawangan(payload);
      toast.success("Cawangan ditambah");
    }

    showModal.value = false;
    await loadRows();
    await loadNegeriOptions();
  } catch (e) {
    toast.error(e instanceof Error ? e.message : "Gagal menyimpan cawangan");
  } finally {
    saving.value = false;
  }
}

async function remove(row: SpptCawangan) {
  const ok = await confirm({
    title: "Padam cawangan?",
    message: `Padam "${row.name}"?`,
    destructive: true,
  });
  if (!ok) return;

  try {
    await deleteSpptCawangan(row.id);
    toast.success("Cawangan dipadam");
    await loadRows();
  } catch (e) {
    toast.error(e instanceof Error ? e.message : "Gagal memadam cawangan");
  }
}

watch([filterNegeri, filterType], () => {
  page.value = 1;
  loadRows();
});

watch(page, loadRows);

onMounted(async () => {
  await Promise.all([loadRows(), loadNegeriOptions()]);
});
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <SpptPageHeader
        title="Tetapan Cawangan"
        subtitle="Urus senarai pejabat negeri dan cawangan TEKUN Nasional"
        :breadcrumbs="[
          { label: 'Pembiayaan' },
          { label: 'Tetapan', to: '/admin/pembiayaan/tetapan' },
          { label: 'Cawangan' },
        ]"
      />

      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
          <div class="flex items-center gap-2">
            <Building2 class="h-4 w-4 text-violet-600" />
            <h2 class="text-sm font-semibold text-slate-900">Senarai Cawangan</h2>
            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ total }} rekod</span>
          </div>
          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg bg-[var(--accent-600)] px-3 py-1.5 text-sm font-medium text-white hover:bg-[var(--accent-700)]"
            @click="openAdd"
          >
            <Plus class="h-4 w-4" />
            Tambah Cawangan
          </button>
        </div>

        <div class="flex flex-wrap items-center gap-2 border-b border-slate-100 px-4 py-3">
          <div class="relative min-w-[220px] flex-1">
            <Search class="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <input
              v-model="q"
              type="search"
              placeholder="Cari nama, kod, bandar, pegawai..."
              class="w-full rounded-lg border border-slate-200 py-1.5 pl-8 pr-3 text-sm focus:border-[var(--accent-500)] focus:outline-none"
              @keyup.enter="page = 1; loadRows()"
            />
          </div>
          <select
            v-model="filterNegeri"
            class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm"
          >
            <option value="">Semua Negeri</option>
            <option v-for="n in negeriOptions" :key="n" :value="n">{{ n }}</option>
          </select>
          <select
            v-model="filterType"
            class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm"
          >
            <option value="">Semua Jenis</option>
            <option value="negeri">Pejabat Negeri</option>
            <option value="cawangan">Cawangan</option>
            <option value="ibu_pejabat">Ibu Pejabat</option>
          </select>
          <button
            type="button"
            class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-50"
            @click="page = 1; loadRows()"
          >
            <RefreshCw class="h-4 w-4" />
            Muat semula
          </button>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Nama</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Negeri</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Bandar</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Telefon</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Jenis</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Tindakan</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-if="loading">
                <td colspan="7" class="px-4 py-8 text-center text-slate-400">Memuatkan...</td>
              </tr>
              <tr v-for="row in rows" v-else :key="row.id" class="hover:bg-slate-50">
                <td class="px-4 py-2">
                  <p class="font-medium text-slate-900">{{ row.name }}</p>
                  <p class="text-xs text-slate-500">{{ row.code }}</p>
                </td>
                <td class="px-4 py-2 text-slate-600">{{ row.negeri || "—" }}</td>
                <td class="px-4 py-2 text-slate-600">{{ row.locality || "—" }}</td>
                <td class="px-4 py-2 text-slate-600">{{ row.phone || "—" }}</td>
                <td class="px-4 py-2">
                  <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">
                    {{ branchTypeLabels[(row.branchType ?? 'cawangan') as SpptCawanganBranchType] }}
                  </span>
                </td>
                <td class="px-4 py-2">
                  <span
                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                    :class="row.isActive ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'"
                  >
                    {{ row.isActive ? "Aktif" : "Tidak aktif" }}
                  </span>
                </td>
                <td class="px-4 py-2 text-right">
                  <div class="inline-flex items-center gap-1">
                    <button type="button" class="rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-violet-600" @click="openEdit(row)">
                      <Pencil class="h-4 w-4" />
                    </button>
                    <button type="button" class="rounded p-1 text-slate-400 hover:bg-red-50 hover:text-red-600" @click="remove(row)">
                      <Trash2 class="h-4 w-4" />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!loading && rows.length === 0">
                <td colspan="7" class="px-4 py-8 text-center text-slate-400">Tiada cawangan dijumpai.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="totalPages > 1" class="flex items-center justify-between border-t border-slate-100 px-4 py-3 text-sm">
          <span class="text-slate-500">Halaman {{ page }} / {{ totalPages }}</span>
          <div class="flex gap-2">
            <button
              type="button"
              class="rounded border border-slate-200 px-3 py-1 disabled:opacity-40"
              :disabled="page <= 1"
              @click="page--"
            >
              Sebelum
            </button>
            <button
              type="button"
              class="rounded border border-slate-200 px-3 py-1 disabled:opacity-40"
              :disabled="page >= totalPages"
              @click="page++"
            >
              Seterusnya
            </button>
          </div>
        </div>
      </article>
    </div>

    <Teleport to="body">
      <div
        v-if="showModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
        @click.self="showModal = false"
      >
        <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-xl bg-white shadow-xl">
          <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <h3 class="text-sm font-semibold text-slate-800">
              {{ editing ? "Kemaskini Cawangan" : "Tambah Cawangan" }}
            </h3>
            <button type="button" class="text-slate-400 hover:text-slate-600" @click="showModal = false">
              <X class="h-4 w-4" />
            </button>
          </div>

          <div class="space-y-3 px-5 py-4">
            <div class="grid gap-3 md:grid-cols-2">
              <div>
                <label class="mb-1 block text-xs font-medium text-slate-700">Kod *</label>
                <input
                  v-model="form.code"
                  type="text"
                  class="w-full rounded-lg border border-slate-200 px-3 py-1.5 text-sm"
                  @blur="autofillCodeFromName"
                />
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-slate-700">Jenis</label>
                <select v-model="form.branchType" class="w-full rounded-lg border border-slate-200 px-3 py-1.5 text-sm">
                  <option value="negeri">Pejabat Negeri</option>
                  <option value="cawangan">Cawangan</option>
                  <option value="ibu_pejabat">Ibu Pejabat</option>
                </select>
              </div>
            </div>

            <div>
              <label class="mb-1 block text-xs font-medium text-slate-700">Nama *</label>
              <input v-model="form.name" type="text" class="w-full rounded-lg border border-slate-200 px-3 py-1.5 text-sm" />
            </div>

            <div class="grid gap-3 md:grid-cols-3">
              <div>
                <label class="mb-1 block text-xs font-medium text-slate-700">Negeri</label>
                <input v-model="form.negeri" type="text" list="negeri-list" class="w-full rounded-lg border border-slate-200 px-3 py-1.5 text-sm" />
                <datalist id="negeri-list">
                  <option v-for="n in negeriOptions" :key="n" :value="n" />
                </datalist>
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-slate-700">Bandar</label>
                <input v-model="form.locality" type="text" class="w-full rounded-lg border border-slate-200 px-3 py-1.5 text-sm" />
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-slate-700">Poskod</label>
                <input v-model="form.postalCode" type="text" class="w-full rounded-lg border border-slate-200 px-3 py-1.5 text-sm" />
              </div>
            </div>

            <div>
              <label class="mb-1 block text-xs font-medium text-slate-700">Alamat</label>
              <textarea v-model="form.address" rows="2" class="w-full rounded-lg border border-slate-200 px-3 py-1.5 text-sm" />
            </div>

            <div class="grid gap-3 md:grid-cols-2">
              <div>
                <label class="mb-1 block text-xs font-medium text-slate-700">Telefon</label>
                <input v-model="form.phone" type="text" class="w-full rounded-lg border border-slate-200 px-3 py-1.5 text-sm" />
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-slate-700">Faks</label>
                <input v-model="form.fax" type="text" class="w-full rounded-lg border border-slate-200 px-3 py-1.5 text-sm" />
              </div>
            </div>

            <div>
              <label class="mb-1 block text-xs font-medium text-slate-700">Pegawai / Orang Dihubungi</label>
              <input v-model="form.contactPerson" type="text" class="w-full rounded-lg border border-slate-200 px-3 py-1.5 text-sm" />
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-700">
              <input v-model="form.isActive" type="checkbox" class="h-4 w-4 rounded border-slate-300" />
              Aktif
            </label>
          </div>

          <div class="flex justify-end gap-2 border-t border-slate-100 px-5 py-3">
            <button type="button" class="rounded-lg border border-slate-200 px-4 py-1.5 text-sm" @click="showModal = false">
              Batal
            </button>
            <button
              type="button"
              class="rounded-lg bg-[var(--accent-600)] px-4 py-1.5 text-sm font-medium text-white disabled:opacity-50"
              :disabled="saving"
              @click="save"
            >
              {{ saving ? "Menyimpan..." : "Simpan" }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </AdminLayout>
</template>
