<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { useRouter } from "vue-router";
import {
  Bell,
  CheckCircle2,
  Clock3,
  RefreshCw,
  XCircle,
} from "lucide-vue-next";
import { useAuth } from "@/composables/useAuth";
import { useProject } from "@/composables/useProject";
import { wbsService } from "@/services/wbsService.js";

const router = useRouter();
const { user } = useAuth();
const { activeProjectId, fetchProjects } = useProject();
const notifications = ref([]);
const counts = ref({ pending: 0, approved: 0, rejected: 0 });
const loading = ref(false);
const error = ref("");
const isOpen = ref(false);
const root = ref(null);
let refreshTimer;
let requestSequence = 0;

const isVp = computed(() => user.value?.role === "VP");
const notificationCount = computed(() =>
  isVp.value
    ? counts.value.pending
    : counts.value.pending + counts.value.approved + counts.value.rejected,
);
const buttonLabel = computed(() =>
  isVp.value ? "Notifikasi pengajuan progres" : "Status pengajuan progres saya",
);

function statusLabel(status) {
  if (status === "approved") return "Disetujui";
  if (status === "rejected") return "Ditolak";
  return "Menunggu persetujuan";
}

function statusClass(status) {
  if (status === "approved") return "bg-emerald-50 text-emerald-700";
  if (status === "rejected") return "bg-rose-50 text-rose-700";
  return "bg-amber-50 text-amber-800";
}

function formatPercent(value) {
  if (value === null || value === undefined) return "Belum ada";
  return `${Number(value).toLocaleString("id-ID")}%`;
}

function formatDate(value) {
  if (!value) return "-";
  return new Intl.DateTimeFormat("id-ID", {
    dateStyle: "medium",
    timeStyle: "short",
  }).format(new Date(value));
}

async function loadNotifications() {
  const projectId = activeProjectId.value;
  const role = user.value?.role;
  const sequence = ++requestSequence;

  if (!projectId || !["PA", "VP"].includes(role)) {
    notifications.value = [];
    counts.value = { pending: 0, approved: 0, rejected: 0 };
    error.value = "";
    return;
  }

  loading.value = true;
  error.value = "";

  try {
    const response = isVp.value
      ? await wbsService.getProgressChangeRequests(projectId, 1, "pending")
      : await wbsService.getMyProgressChangeRequests(projectId, 1, "all");

    if (sequence !== requestSequence) return;

    const body = response.data ?? {};
    notifications.value = body.data ?? [];
    counts.value = {
      pending: Number(body.meta?.counts?.pending ?? 0),
      approved: Number(body.meta?.counts?.approved ?? 0),
      rejected: Number(body.meta?.counts?.rejected ?? 0),
    };
  } catch (requestError) {
    if (sequence === requestSequence) {
      error.value =
        requestError.message || "Notifikasi progres gagal dimuat.";
    }
  } finally {
    if (sequence === requestSequence) loading.value = false;
  }
}

function openApprovalQueue() {
  isOpen.value = false;
  void router.push({
    name: "ProgressApproval",
    query: { project: activeProjectId.value, tab: "pending" },
  });
}

function handleOutsideClick(event) {
  if (root.value && !root.value.contains(event.target)) isOpen.value = false;
}

function handleKeydown(event) {
  if (event.key === "Escape") isOpen.value = false;
}

watch(
  [activeProjectId, () => user.value?.role],
  () => void loadNotifications(),
);

onMounted(async () => {
  await fetchProjects();
  await loadNotifications();
  refreshTimer = window.setInterval(() => {
    void loadNotifications();
  }, 60_000);
  document.addEventListener("click", handleOutsideClick);
  document.addEventListener("keydown", handleKeydown);
});

onBeforeUnmount(() => {
  if (refreshTimer) window.clearInterval(refreshTimer);
  document.removeEventListener("click", handleOutsideClick);
  document.removeEventListener("keydown", handleKeydown);
});
</script>

<template>
  <div v-if="user?.role === 'PA' || user?.role === 'VP'" ref="root" class="relative">
    <button
      type="button"
      class="relative inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
      :aria-label="buttonLabel"
      :aria-expanded="isOpen"
      aria-haspopup="dialog"
      @click="isOpen = !isOpen"
    >
      <Bell class="h-4 w-4" />
      <span
        v-if="notificationCount > 0"
        class="absolute -right-1 -top-1 inline-flex min-w-4 items-center justify-center rounded-full bg-rose-600 px-1 text-[9px] font-bold leading-4 text-white"
      >
        {{ notificationCount > 99 ? "99+" : notificationCount }}
      </span>
    </button>

    <section
      v-if="isOpen"
      class="absolute right-0 top-full z-[80] mt-2 w-[min(92vw,26rem)] overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl"
      role="dialog"
      aria-label="Notifikasi progres"
    >
      <header class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
        <div>
          <h2 class="text-sm font-semibold text-slate-900">
            {{ isVp ? "Pengajuan untuk ditindaklanjuti" : "Status pengajuan saya" }}
          </h2>
          <p class="mt-0.5 text-xs text-slate-500">
            {{ isVp ? `${counts.pending} menunggu persetujuan` : `${counts.pending} menunggu · ${counts.approved} disetujui · ${counts.rejected} ditolak` }}
          </p>
        </div>
        <button
          type="button"
          class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 disabled:opacity-50"
          aria-label="Muat ulang notifikasi"
          :disabled="loading"
          @click="loadNotifications"
        >
          <RefreshCw class="h-3.5 w-3.5" :class="{ 'animate-spin': loading }" />
        </button>
      </header>

      <p
        v-if="error"
        role="alert"
        class="m-3 rounded-lg bg-rose-50 px-3 py-2 text-xs text-rose-700"
      >
        {{ error }}
      </p>
      <p
        v-else-if="loading && notifications.length === 0"
        class="px-4 py-8 text-center text-xs text-slate-500"
      >
        Memuat notifikasi...
      </p>
      <p
        v-else-if="notifications.length === 0"
        class="px-4 py-8 text-center text-xs text-slate-500"
      >
        {{ isVp ? "Tidak ada pengajuan yang perlu ditindaklanjuti." : "Belum ada pengajuan progres." }}
      </p>

      <ul v-else class="max-h-[min(60vh,26rem)] divide-y divide-slate-100 overflow-y-auto">
        <li v-for="notification in notifications" :key="notification.id" class="px-4 py-3">
          <div class="flex items-start gap-2.5">
            <component
              :is="
                notification.status === 'approved'
                  ? CheckCircle2
                  : notification.status === 'rejected'
                    ? XCircle
                    : Clock3
              "
              class="mt-0.5 h-4 w-4 shrink-0"
              :class="
                notification.status === 'approved'
                  ? 'text-emerald-600'
                  : notification.status === 'rejected'
                    ? 'text-rose-600'
                    : 'text-amber-600'
              "
            />
            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-center gap-1.5">
                <span class="font-mono text-[11px] font-semibold text-slate-600">
                  WBS {{ notification.wbs_no }}
                </span>
                <span
                  class="rounded-full px-2 py-0.5 text-[10px] font-semibold"
                  :class="statusClass(notification.status)"
                >
                  {{ statusLabel(notification.status) }}
                </span>
              </div>
              <p class="mt-1 truncate text-xs font-semibold text-slate-800">
                {{ notification.activity_name || "Nama aktivitas tidak tersedia" }}
              </p>
              <p class="mt-1 text-[11px] text-slate-500">
                {{ formatPercent(notification.previous_percent) }}
                <span aria-hidden="true">→</span>
                {{ formatPercent(notification.requested_percent) }}
                · {{ formatDate(notification.reviewed_at || notification.created_at) }}
              </p>
              <p v-if="!isVp && notification.comment" class="mt-1 line-clamp-2 text-[11px] text-slate-500">
                Alasan: {{ notification.comment }}
              </p>
            </div>
          </div>
        </li>
      </ul>

      <footer
        v-if="isVp"
        class="border-t border-slate-100 p-3"
      >
        <button
          type="button"
          class="w-full rounded-lg bg-blue-700 px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-800"
          @click="openApprovalQueue"
        >
          Buka antrean persetujuan
        </button>
      </footer>
    </section>
  </div>
</template>
