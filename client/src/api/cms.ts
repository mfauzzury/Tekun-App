import { apiRequest } from "./client";
import type {
  AuditLog,
  Category,
  CategoryInput,
  ChatFavoriteItem,
  ChatMessage,
  ChatSession,
  ChatSuggestion,
  Media,
  MediaMetadataInput,
  Page,
  PageInput,
  Post,
  PostInput,
  PublicSiteSettings,
  Role,
  RoleInput,
  SettingsPayload,
  StorefrontMenuItem,
  UserCawangan,
  UserDetail,
  UserInput,
} from "@/types";
import type { AdminMenuPrefs } from "@/config/admin-menu";

export async function fetchDashboardSummary() {
  return apiRequest<{ data: { counts: { posts: number; pages: number; media: number }; recent: { posts: Post[]; pages: Page[] } } }>(
    "/api/dashboard/summary",
  );
}

export async function listPosts(params = "") {
  return apiRequest<{ data: Post[]; meta: Record<string, unknown> }>(`/api/posts${params}`);
}

export async function getPost(id: number) {
  return apiRequest<{ data: Post }>(`/api/posts/${id}`);
}

export async function createPost(input: PostInput) {
  return apiRequest<{ data: Post }>("/api/posts", { method: "POST", body: JSON.stringify(input) });
}

export async function updatePost(id: number, input: PostInput) {
  return apiRequest<{ data: Post }>(`/api/posts/${id}`, { method: "PUT", body: JSON.stringify(input) });
}

export async function deletePost(id: number) {
  return apiRequest<{ data: { success: boolean } }>(`/api/posts/${id}`, { method: "DELETE" });
}

// Categories
export async function listCategories(params = "") {
  return apiRequest<{ data: Category[]; meta: Record<string, unknown> }>(`/api/categories${params}`);
}

export async function getCategory(id: number) {
  return apiRequest<{ data: Category }>(`/api/categories/${id}`);
}

export async function createCategory(input: CategoryInput) {
  return apiRequest<{ data: Category }>("/api/categories", { method: "POST", body: JSON.stringify(input) });
}

export async function updateCategory(id: number, input: CategoryInput) {
  return apiRequest<{ data: Category }>(`/api/categories/${id}`, { method: "PUT", body: JSON.stringify(input) });
}

export async function deleteCategory(id: number) {
  return apiRequest<{ data: { success: boolean } }>(`/api/categories/${id}`, { method: "DELETE" });
}

export async function listPages(params = "") {
  return apiRequest<{ data: Page[]; meta: Record<string, unknown> }>(`/api/pages${params}`);
}

export async function getPage(id: number) {
  return apiRequest<{ data: Page }>(`/api/pages/${id}`);
}

export async function createPage(input: PageInput) {
  return apiRequest<{ data: Page }>("/api/pages", { method: "POST", body: JSON.stringify(input) });
}

export async function updatePage(id: number, input: PageInput) {
  return apiRequest<{ data: Page }>(`/api/pages/${id}`, { method: "PUT", body: JSON.stringify(input) });
}

export async function deletePage(id: number) {
  return apiRequest<{ data: { success: boolean } }>(`/api/pages/${id}`, { method: "DELETE" });
}

export async function listMedia() {
  return apiRequest<{ data: Media[] }>("/api/media");
}

export async function uploadMedia(file: File) {
  const formData = new FormData();
  formData.append("file", file);
  return apiRequest<{ data: Media }>("/api/media/upload", { method: "POST", body: formData });
}

export async function removeMedia(id: number) {
  return apiRequest<{ data: { success: boolean } }>(`/api/media/${id}`, { method: "DELETE" });
}

export async function updateMediaMetadata(id: number, input: MediaMetadataInput) {
  return apiRequest<{ data: Media }>(`/api/media/${id}`, { method: "PUT", body: JSON.stringify(input) });
}

export async function getSettings() {
  return apiRequest<{ data: SettingsPayload }>("/api/settings");
}

export async function updateSettings(payload: SettingsPayload) {
  return apiRequest<{ data: SettingsPayload }>("/api/settings", {
    method: "PUT",
    body: JSON.stringify(payload),
  });
}

export async function getAdminMenuPrefs() {
  return apiRequest<{ data: AdminMenuPrefs | null }>("/api/settings/admin-menu-prefs");
}

export async function saveAdminMenuPrefs(prefs: AdminMenuPrefs) {
  return apiRequest<{ data: AdminMenuPrefs }>("/api/settings/admin-menu-prefs", {
    method: "PUT",
    body: JSON.stringify(prefs),
  });
}

export async function getStorefrontMenu() {
  return apiRequest<{ data: StorefrontMenuItem[] }>("/api/settings/storefront-menu");
}

export async function saveStorefrontMenu(items: StorefrontMenuItem[]) {
  return apiRequest<{ data: StorefrontMenuItem[] }>("/api/settings/storefront-menu", {
    method: "PUT",
    body: JSON.stringify(items),
  });
}

export async function getPublicSiteSettings() {
  return apiRequest<{ data: PublicSiteSettings }>("/api/public/site");
}

export async function getPublicFrontPage() {
  return apiRequest<{ data: Page; meta?: { source?: string } }>("/api/public/pages/frontpage");
}

export async function getPublicPageBySlug(slug: string) {
  return apiRequest<{ data: Page }>(`/api/public/pages/${encodeURIComponent(slug)}`);
}

// Users
export async function listUsers() {
  return apiRequest<{ data: UserDetail[] }>("/api/users");
}

export async function getUser(id: number) {
  return apiRequest<{ data: UserDetail }>(`/api/users/${id}`);
}

export async function createUser(input: UserInput) {
  return apiRequest<{ data: UserDetail }>("/api/users", { method: "POST", body: JSON.stringify(input) });
}

export async function updateUser(id: number, input: UserInput) {
  return apiRequest<{ data: UserDetail }>(`/api/users/${id}`, { method: "PUT", body: JSON.stringify(input) });
}

export async function deleteUser(id: number) {
  return apiRequest<{ data: { success: boolean } }>(`/api/users/${id}`, { method: "DELETE" });
}

export async function listUserCawanganOptions() {
  return apiRequest<{ data: UserCawangan[] }>("/api/users/cawangan-options");
}

// Roles
export async function listRoles() {
  return apiRequest<{ data: Role[] }>("/api/roles");
}

export async function getRole(id: number) {
  return apiRequest<{ data: Role }>(`/api/roles/${id}`);
}

export async function createRole(input: RoleInput) {
  return apiRequest<{ data: Role }>("/api/roles", { method: "POST", body: JSON.stringify(input) });
}

export async function updateRole(id: number, input: RoleInput) {
  return apiRequest<{ data: Role }>(`/api/roles/${id}`, { method: "PUT", body: JSON.stringify(input) });
}

export async function deleteRole(id: number) {
  return apiRequest<{ data: { success: boolean } }>(`/api/roles/${id}`, { method: "DELETE" });
}

// Audit Logs
export async function listAuditLogs(params = "") {
  return apiRequest<{ data: AuditLog[]; meta: Record<string, unknown> }>(`/api/audit-logs${params}`);
}

// Developers Guide
export async function getDevelopersGuide() {
  return apiRequest<{ data: { content: string; syncFiles: { filename: string; path?: string; exists: boolean; inSync: boolean; readOnly?: boolean; role?: "canonical" | "mirror" }[] } }>("/api/developers-guide");
}

export async function updateDevelopersGuide(content: string) {
  return apiRequest<{ data: { success: boolean; syncFiles: { filename: string; path?: string; exists: boolean; inSync: boolean; readOnly?: boolean; role?: "canonical" | "mirror" }[] } }>("/api/developers-guide", {
    method: "PUT",
    body: JSON.stringify({ content }),
  });
}

// AINA User Chat (KB-only assistant)
export async function newUserChatSession(opt?: { moduleFilter?: string; title?: string }) {
  const body: Record<string, unknown> = {};
  if (opt?.moduleFilter) body.moduleFilter = opt.moduleFilter;
  if (opt?.title) body.title = opt.title;
  return apiRequest<{ data: { session: ChatSession; messages: ChatMessage[] } }>("/api/chat/user/sessions", {
    method: "POST",
    body: JSON.stringify(body),
  });
}

export async function getMyUserChatSessions() {
  return apiRequest<{ data: ChatSession[] }>("/api/chat/user/sessions");
}

export async function getUserChatSession(sessionId: number) {
  return apiRequest<{ data: ChatSession }>(`/api/chat/user/sessions/${sessionId}`);
}

export async function sendUserChatMessage(sessionId: number, message: string, files?: File[]) {
  if (files && files.length > 0) {
    const form = new FormData();
    form.append("message", message);
    files.forEach((f) => form.append("attachments[]", f));
    return apiRequest<{ data: ChatMessage }>(`/api/chat/user/sessions/${sessionId}/messages`, {
      method: "POST",
      body: form,
    });
  }
  return apiRequest<{ data: ChatMessage }>(`/api/chat/user/sessions/${sessionId}/messages`, {
    method: "POST",
    body: JSON.stringify({ message }),
  });
}

export async function updateUserChatSession(sessionId: number, opts: { moduleFilter?: string }) {
  const body: Record<string, unknown> = {};
  if (opts.moduleFilter !== undefined) body.moduleFilter = opts.moduleFilter;
  return apiRequest<{ data: ChatSession }>(`/api/chat/user/sessions/${sessionId}`, {
    method: "PUT",
    body: JSON.stringify(body),
  });
}

export async function deleteUserChatSession(sessionId: number) {
  return apiRequest<{ data: { success: boolean } }>(`/api/chat/user/sessions/${sessionId}`, {
    method: "DELETE",
  });
}

export async function toggleUserChatSessionFavorite(sessionId: number) {
  return apiRequest<{ data: { favorited: boolean } }>(`/api/chat/user/sessions/${sessionId}/favorite`, {
    method: "POST",
  });
}

export async function searchUserChatMessages(sessionId: number, q: string) {
  return apiRequest<{ data: ChatMessage[]; meta: { count: number } }>(
    `/api/chat/user/sessions/${sessionId}/messages/search?q=${encodeURIComponent(q)}`,
  );
}

export async function listUserChatFavorites(params = "") {
  return apiRequest<{ data: ChatFavoriteItem[]; meta: Record<string, unknown> }>(
    `/api/chat/user/favorites${params}`,
  );
}

export async function toggleUserChatMessageFavorite(messageId: number) {
  return apiRequest<{ data: { favorited: boolean } }>(`/api/chat/user/messages/${messageId}/favorite`, {
    method: "POST",
  });
}

export async function getUserChatSuggestions() {
  return apiRequest<{ data: ChatSuggestion[] }>("/api/chat/user/suggestions");
}
