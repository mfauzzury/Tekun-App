import { computed } from "vue";

import {
  translate,
  translateMenuLabel,
  translatePhrase,
  translateRouteTitle,
} from "@/i18n";
import { useUiLocaleStore } from "@/stores/uiLocale";

export function useI18n() {
  const uiLocale = useUiLocaleStore();

  function t(key: string, fallback?: string) {
    return translate(uiLocale.language, key, fallback);
  }

  function tp(source: string) {
    return translatePhrase(uiLocale.language, source);
  }

  function menuLabel(id: string, fallback: string) {
    return translateMenuLabel(uiLocale.language, id, fallback);
  }

  function routeTitle(routeName: string, fallback: string) {
    return translateRouteTitle(uiLocale.language, routeName, fallback);
  }

  return {
    language: computed(() => uiLocale.language),
    t,
    tp,
    menuLabel,
    routeTitle,
  };
}
