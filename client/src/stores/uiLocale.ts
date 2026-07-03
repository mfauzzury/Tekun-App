import { ref, watch } from "vue";
import { defineStore } from "pinia";

import type { AppLanguage } from "@/types";

const LOCALE_KEY = "admin.language";

const APP_LANGUAGES: AppLanguage[] = ["bm", "bi"];

function isAppLanguage(value: string | null): value is AppLanguage {
  return !!value && APP_LANGUAGES.includes(value as AppLanguage);
}

export const useUiLocaleStore = defineStore("ui-locale", () => {
  const language = ref<AppLanguage>("bm");

  function applyToDocument() {
    if (typeof document === "undefined") return;
    const root = document.documentElement;
    root.dataset.appLanguage = language.value;
    root.lang = language.value === "bm" ? "ms" : "en";
  }

  function persist() {
    if (typeof window === "undefined") return;
    localStorage.setItem(LOCALE_KEY, language.value);
  }

  function initFromStorage() {
    if (typeof window === "undefined") return;

    const savedLanguage = localStorage.getItem(LOCALE_KEY);

    if (isAppLanguage(savedLanguage)) language.value = savedLanguage;

    applyToDocument();
  }

  function setLanguage(next: AppLanguage) {
    language.value = next;
  }

  watch(language, () => {
    persist();
    applyToDocument();
  }, { immediate: true });

  return {
    language,
    initFromStorage,
    setLanguage,
  };
});
