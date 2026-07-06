import { useI18n } from "@/composables/useI18n";
import { spptStatusClass, spptStatusI18nKey } from "@/utils/spptStatus";

export function useSpptStatus() {
  const { t } = useI18n();

  function statusLabel(status?: string | null): string {
    if (!status) return "—";
    const key = spptStatusI18nKey(status);
    return key ? t(key, status) : status;
  }

  function statusClass(status?: string | null): string {
    return spptStatusClass(status);
  }

  return { statusLabel, statusClass };
}
