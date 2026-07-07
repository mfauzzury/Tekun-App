/** Row shape returned by GET /api/sppt/permohonan?penilaian=1 */
export type PermohonanPenilaianRow = {
  id: string;
  permohonanId?: number;
  _dbId?: number;
  nama: string;
  jumlah?: string;
  jumlahPermohonan?: number;
  tarikh?: string;
  status: string;
  jenisPermohonan?: string;
  kategoriPembiayaan?: string;
};

export function resolvePermohonanDbId(row: PermohonanPenilaianRow | Record<string, unknown>): number | null {
  const id = row.permohonanId ?? row._dbId;
  if (typeof id === "number" && Number.isFinite(id) && id > 0) {
    return id;
  }
  const parsed = Number(id);
  return Number.isFinite(parsed) && parsed > 0 ? parsed : null;
}

export function mapPermohonanPenilaianRow(row: PermohonanPenilaianRow | Record<string, unknown>) {
  const dbId = resolvePermohonanDbId(row);

  return {
    id: dbId ?? 0,
    noRujukan: String(row.id ?? row.noRujukan ?? ""),
    nama: String(row.nama ?? ""),
    jumlah: String(row.jumlah ?? row.jumlahPermohonan ?? "–"),
    status: String(row.status ?? "Menunggu"),
    jenisPermohonan: String(row.jenisPermohonan ?? row.kategoriPembiayaan ?? "–"),
  };
}
