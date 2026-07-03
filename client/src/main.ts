import { createApp, watch } from "vue";
import { createPinia } from "pinia";

import App from "./App.vue";
import router, { applyDocumentTitle } from "./router";
import { useUiLocaleStore } from "@/stores/uiLocale";
import { useUiThemeStore } from "@/stores/uiTheme";
import "./style.css";

const app = createApp(App);
const pinia = createPinia();
app.use(pinia);
useUiThemeStore(pinia).initFromStorage();
const uiLocale = useUiLocaleStore(pinia);
uiLocale.initFromStorage();
app.use(router);

watch(
  () => uiLocale.language,
  () => {
    applyDocumentTitle(router.currentRoute.value);
  },
);

app.mount("#app");
