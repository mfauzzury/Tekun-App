import { ref, onMounted, type Ref } from "vue";
import { fetchSpptDataset, listAkaun, listJaminan, listKutipan, listPengeluaranDana, listPermohonan, listUsahawan } from "@/api/sppt";

export function useSpptDataset<T>(module: string, key: string, fallback: T) {
  const data = ref(fallback) as Ref<T>;
  const loading = ref(true);
  const error = ref<string | null>(null);

  async function load() {
    loading.value = true;
    error.value = null;
    try {
      const res = await fetchSpptDataset(module, key);
      data.value = res.data as T;
    } catch (e) {
      error.value = e instanceof Error ? e.message : "Failed to load data";
    } finally {
      loading.value = false;
    }
  }

  onMounted(load);

  return { data, loading, error, reload: load };
}

export function useSpptList<T>(fetcher: () => Promise<{ data: T[] }>, fallback: T[] = []) {
  const items = ref(fallback) as Ref<T[]>;
  const loading = ref(true);
  const error = ref<string | null>(null);

  async function load() {
    loading.value = true;
    error.value = null;
    try {
      const res = await fetcher();
      items.value = res.data;
    } catch (e) {
      error.value = e instanceof Error ? e.message : "Failed to load data";
    } finally {
      loading.value = false;
    }
  }

  onMounted(load);

  return { items, loading, error, reload: load };
}

export const spptLoaders = {
  akaun: () => listAkaun({ limit: 100 }),
  pengeluaranDana: () => listPengeluaranDana({ limit: 100 }),
  jaminan: () => listJaminan({ limit: 100 }),
  kutipan: () => listKutipan({ limit: 100 }),
  permohonanPenilaian: () => listPermohonan({ limit: 100, penilaian: 1 }),
  usahawanRekod: () => listUsahawan({ limit: 100, rekod: 1 }),
};
