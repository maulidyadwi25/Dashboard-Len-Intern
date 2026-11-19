<script setup>
import { onMounted, ref, watch } from "vue";
import { Check, CheckCircle, RefreshCw, X, XCircle } from "lucide-vue-next";
import { wbsService } from "@/services/wbsService.js";

const props = defineProps({
  projectId: {
    type: [Number, String],
    default: null,
  },
});

const emit = defineEmits(["update:counts"]);

const requests = ref([]);
const loading = ref(false);
const error = ref("");
const reviewingId = ref(null);
const currentPage = ref(1);
const lastPage = ref(1);
const total = ref(0);

// --- Modal konfirmasi ---
const modal = ref(null); // { request, decision: 'approved'|'rejected' }
const modalNote = ref("");
const modalNoteError = ref("");

function openModal(request, decision) {
  modal.value = { request, decision };
  modalNote.value = "";
  modalNoteError.value = "";
}

function closeModal() {
  modal.value = null;
  modalNote.value = "";
  modalNoteError.value = "";
}
// -----------------------

// --- Toast notification ---
const toast = ref(null); // { type: 'success'|'error'|'info', message: string }
let toastTimer = null;

function showToast(type, message) {
  if (toastTimer) clearTimeout(toastTimer);
  toast.value = { type, message };
  toastTimer = setTimeout(() => {
    toast.value = null;
  }, 3500);
}
// -------------------------

function formatPercent(value) {
  return value === null || value === undefined
    ? "Belum ada"
    : `${Number(value).toLocaleString("id-ID")}%`;
}

function formatDecimalPercent(value) {
  if (value === null || value === undefined) return "0.00%";
  return `${Number(value).toFixed(2)}%`;
}

function formatSimplePercent(value) {
  if (value === null || value === undefined) return "-";
  const num = Number(value);
  return num % 1 === 0 ? `${num.toFixed(0)}%` : `${num.toFixed(2)}%`;
}

function getDiff(item) {
  const prev = Number(item.previous_percent ?? 0);
  const next = Number(item.requested_percent ?? 0);
  return next - prev;
}

function formatDelta(item) {
  const diff = getDiff(item);
  const sign = diff > 0 ? "+" : "";
  return `${sign}${diff.toFixed(2)}%`;
}

const statusConfig = {
  on_track: {
    label: "On Track",
    className: "bg-emerald-50 text-emerald-700 border-emerald-200/80",
  },
  overdue: {
    label: "Overdue",
    className: "bg-amber-50 text-amber-700 border-amber-200/80",
  },
  critical: {
    label: "Critical",
    className: "bg-rose-50 text-rose-700 border-rose-200/80",
  },
  not_started: {
    label: "Not Started",
    className: "bg-slate-100 text-slate-600 border-slate-200/80",
  },
  completed: {
    label: "Completed",
    className: "bg-blue-50 text-blue-700 border-blue-200/80",
  },
};

function formatWbsStatus(status) {
  if (!status) return statusConfig.on_track;
  const key = String(status).toLowerCase().replace(/[-\s]/g, "_");
  return (
    statusConfig[key] || {
      label: status.replace(/_/g, " "),
      className: "bg-slate-100 text-slate-600 border-slate-200/80",
    }
  );
}

function formatDate(value) {
  if (!value) return "-";
  return new Intl.DateTimeFormat("id-ID", {
    dateStyle: "medium",
    timeStyle: "short",
  }).format(new Date(value));
}

async function loadRequests(page = 1) {
  if (props.projectId === null || props.projectId === undefined) {
    requests.value = [];
    total.value = 0;
    return;
  }

  loading.value = true;
  error.value = "";

  try {
    const response = await wbsService.getProgressChangeRequests(
      props.projectId,
      page,
    );
    const body = response.data ?? {};
    requests.value = body.data ?? [];
    currentPage.value = body.meta?.current_page ?? 1;
    lastPage.value = body.meta?.last_page ?? 1;
    total.value = body.meta?.total ?? 0;

    if (body.meta?.counts) {
      emit("update:counts", body.meta.counts);
    }
  } catch (requestError) {
    error.value =
      requestError.message || "Gagal memuat pengajuan perubahan progres.";
  } finally {
    loading.value = false;
  }
}

async function confirmReview() {
  if (!modal.value) return;

  // Alasan wajib diisi untuk penolakan
  if (modal.value.decision === "rejected" && !modalNote.value.trim()) {
    modalNoteError.value = "Alasan penolakan wajib diisi.";
    return;
  }

  modalNoteError.value = "";
  const { request, decision } = modal.value;
  const note = modalNote.value.trim();

  closeModal();

  reviewingId.value = request.id;
  error.value = "";

  try {
    await wbsService.reviewProgressChangeRequest(
      props.projectId,
      request.id,
      decision,
      note,
    );
    await loadRequests(currentPage.value);

    if (decision === "approved") {
      showToast(
        "success",
        `Pengajuan "${request.activity_name || "WBS " + request.wbs_no}" berhasil disetujui.`,
      );
    } else {
      showToast(
        "info",
        `Pengajuan "${request.activity_name || "WBS " + request.wbs_no}" ditolak.`,
      );
    }
  } catch (requestError) {
    const msg =
      requestError.response?.data?.message ||
      requestError.message ||
      "Gagal memproses pengajuan perubahan progres.";
    error.value = msg;
    showToast("error", msg);
  } finally {
    reviewingId.value = null;
  }
}

watch(
  () => props.projectId,
  () => {
    void loadRequests();
  },
);

onMounted(() => {
  void loadRequests();
});
</script>

<template>
  <section
    aria-labelledby="wbs-progress-requests-title"
    class="overflow-hidden rounded-xl border border-outline-variant bg-surface-lowest shadow-sm"
    :aria-busy="loading"
  >
    <header
      class="flex flex-wrap items-center justify-between gap-3 border-b border-outline-variant/70 px-4 py-3"
    >
      <div>
        <h2
          id="wbs-progress-requests-title"
          class="text-sm font-semibold text-on-surface"
        >
          Pengajuan pengurangan progres
        </h2>
        <p class="mt-0.5 text-xs text-on-surface-variant">
          {{ total }} pengajuan menunggu persetujuan
        </p>
      </div>
      <button
        type="button"
        class="inline-flex h-9 items-center gap-2 rounded-lg border border-outline-variant px-3 text-xs font-medium text-on-surface-variant transition hover:bg-surface-container disabled:cursor-not-allowed disabled:opacity-50"
        :disabled="loading || projectId === null"
        @click="loadRequests(currentPage)"
      >
        <RefreshCw class="h-3.5 w-3.5" :class="{ 'animate-spin': loading }" />
        Muat ulang
      </button>
    </header>

    <p
      v-if="error"
      role="alert"
      class="m-4 rounded-lg border border-error/30 bg-error-container px-3 py-2 text-sm text-error"
    >
      {{ error }}
    </p>
    <p
      v-else-if="loading && requests.length === 0"
      class="px-4 py-8 text-center text-sm text-on-surface-variant"
    >
      Memuat pengajuan...
    </p>
    <p
      v-else-if="requests.length === 0"
      class="px-4 py-8 text-center text-sm text-on-surface-variant"
    >
      Tidak ada pengajuan yang menunggu persetujuan.
    </p>

    <div v-else class="overflow-x-auto">
      <table class="w-full min-w-[900px] border-collapse text-left">
        <thead class="bg-surface-container-low">
          <tr
            class="text-[11px] font-semibold uppercase tracking-wide text-on-surface-variant"
          >
            <th scope="col" class="px-4 py-3">WBS &amp; aktivitas pekerjaan</th>
            <th scope="col" class="px-4 py-3">Pengaju</th>
            <th scope="col" class="px-4 py-3 min-w-[240px]">Komparasi Progres Fisik</th>
            <th scope="col" class="px-4 py-3">Justifikasi</th>
            <th scope="col" class="px-4 py-3 text-right">Tindakan</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-outline-variant/60">
          <tr
            v-for="request in requests"
            :key="request.id"
            class="align-top text-sm"
          >
            <td class="max-w-[340px] px-4 py-3">
              <div class="flex flex-wrap items-center gap-1.5 mb-1.5">
                <span
                  class="rounded bg-surface-container px-1.5 py-0.5 font-mono text-xs font-semibold text-on-surface-variant"
                >
                  WBS {{ request.wbs_no }}
                </span>
                <span
                  v-if="request.wbs_status"
                  class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-bold tracking-wide uppercase"
                  :class="formatWbsStatus(request.wbs_status).className"
                >
                  {{ formatWbsStatus(request.wbs_status).label }}
                </span>
              </div>
              <p class="font-semibold text-on-surface line-clamp-2">
                {{ request.activity_name || "Nama task tidak tersedia" }}
              </p>
            </td>
            <td class="whitespace-nowrap px-4 py-3">
              <p class="font-medium text-on-surface">
                {{ request.requester_name }}
              </p>
              <time
                v-if="request.created_at"
                class="mt-1 block text-xs text-on-surface-variant"
                :datetime="request.created_at"
              >
                {{ formatDate(request.created_at) }}
              </time>
            </td>
            <td class="px-4 py-3 min-w-[240px]">
              <!-- Top row: Strikethrough previous, arrow, requested, delta badge -->
              <div class="flex items-center justify-between gap-2 mb-1.5">
                <div class="flex items-center gap-1.5 font-mono text-sm tabular-nums">
                  <span class="line-through text-slate-400 font-medium">
                    {{ formatDecimalPercent(request.previous_percent) }}
                  </span>
                  <span class="text-blue-600 font-bold text-xs" aria-hidden="true">→</span>
                  <span
                    class="font-extrabold"
                    :class="getDiff(request) < 0 ? 'text-red-600' : 'text-emerald-700'"
                  >
                    {{ formatDecimalPercent(request.requested_percent) }}
                  </span>
                </div>

                <!-- Delta badge -->
                <span
                  class="px-2 py-0.5 rounded text-xs font-bold tabular-nums font-mono"
                  :class="
                    getDiff(request) < 0
                      ? 'bg-rose-50 text-rose-600 border border-rose-100'
                      : 'bg-emerald-50 text-emerald-700 border border-emerald-100'
                  "
                >
                  {{ formatDelta(request) }}
                </span>
              </div>

              <!-- Progress bar -->
              <div class="w-full h-1.5 bg-blue-100/70 rounded-full overflow-hidden">
                <div
                  class="h-full rounded-full transition-all duration-300"
                  :class="getDiff(request) < 0 ? 'bg-rose-600' : 'bg-emerald-600'"
                  :style="{
                    width: `${Math.min(Math.max(Number(request.requested_percent ?? 0), 0), 100)}%`,
                  }"
                />
              </div>

              <!-- Bottom row: Baseline and Sebelum -->
              <div class="flex items-center justify-between text-[11px] text-slate-500 font-medium mt-1.5">
                <span>
                  Baseline: {{ formatSimplePercent(request.baseline_percent ?? (request.requested_percent > request.previous_percent ? request.requested_percent : request.previous_percent)) }}
                </span>
                <span>
                  Sebelum: {{ formatSimplePercent(request.previous_percent) }}
                </span>
              </div>
            </td>
            <td
              class="max-w-[360px] whitespace-normal px-4 py-3 text-on-surface"
            >
              {{ request.comment }}
            </td>
            <td class="px-4 py-3">
              <div class="flex justify-end gap-2">
                <button
                  type="button"
                  class="inline-flex h-8 items-center gap-1 rounded-lg bg-primary px-3 text-xs font-semibold text-on-primary transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50"
                  :disabled="reviewingId !== null"
                  @click="openModal(request, 'approved')"
                >
                  <Check class="h-3.5 w-3.5" />
                  Setujui
                </button>
                <button
                  type="button"
                  class="inline-flex h-8 items-center gap-1 rounded-lg border border-outline-variant px-3 text-xs font-semibold text-on-surface-variant transition hover:bg-surface-container disabled:cursor-not-allowed disabled:opacity-50"
                  :disabled="reviewingId !== null"
                  @click="openModal(request, 'rejected')"
                >
                  <X class="h-3.5 w-3.5" />
                  Tolak
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <footer
      v-if="lastPage > 1"
      class="flex items-center justify-between border-t border-outline-variant/70 px-4 py-3"
    >
      <button
        type="button"
        class="h-8 rounded-md border border-outline-variant px-3 text-xs font-medium text-on-surface-variant hover:bg-surface-container disabled:cursor-not-allowed disabled:opacity-50"
        :disabled="loading || currentPage <= 1"
        @click="loadRequests(currentPage - 1)"
      >
        Sebelumnya
      </button>
      <span class="text-xs text-on-surface-variant">
        Halaman {{ currentPage }} dari {{ lastPage }}
      </span>
      <button
        type="button"
        class="h-8 rounded-md border border-outline-variant px-3 text-xs font-medium text-on-surface-variant hover:bg-surface-container disabled:cursor-not-allowed disabled:opacity-50"
        :disabled="loading || currentPage >= lastPage"
        @click="loadRequests(currentPage + 1)"
      >
        Berikutnya
      </button>
    </footer>
  </section>

  <!-- Modal konfirmasi + alasan -->
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="modal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="'modal-title'"
        @mousedown.self="closeModal"
      >
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" />

        <!-- Panel -->
        <div
          class="relative w-full max-w-md rounded-2xl bg-white shadow-2xl ring-1 ring-black/5"
        >
          <!-- Header -->
          <div
            class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-4"
          >
            <div>
              <h2
                id="modal-title"
                class="text-sm font-bold text-slate-900"
                :class="modal.decision === 'approved' ? 'text-emerald-700' : 'text-red-700'"
              >
                {{ modal.decision === "approved" ? "✅ Konfirmasi Persetujuan" : "❌ Konfirmasi Penolakan" }}
              </h2>
              <p class="mt-0.5 text-xs text-slate-500">
                WBS {{ modal.request.wbs_no }} —
                {{ modal.request.activity_name || "Nama task tidak tersedia" }}
              </p>
            </div>
            <button
              type="button"
              class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition"
              aria-label="Tutup"
              @click="closeModal"
            >
              <X class="h-4 w-4" />
            </button>
          </div>

          <!-- Body -->
          <div class="px-5 py-4 space-y-4">
            <!-- Info ringkas request -->
            <div class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3 text-xs space-y-1.5">
              <div class="flex justify-between">
                <span class="text-slate-500">Pengaju</span>
                <span class="font-semibold text-slate-800">{{ modal.request.requester_name }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-500">Perubahan progres</span>
                <span class="font-semibold tabular-nums text-slate-800">
                  <span class="line-through text-slate-400">{{ formatPercent(modal.request.previous_percent) }}</span>
                  <span class="mx-1 text-slate-400">→</span>
                  <span class="text-red-600">{{ formatPercent(modal.request.requested_percent) }}</span>
                </span>
              </div>
              <div v-if="modal.request.comment" class="flex flex-col gap-0.5">
                <span class="text-slate-500">Justifikasi PA</span>
                <span class="text-slate-700 italic">"{{ modal.request.comment }}"</span>
              </div>
            </div>

            <!-- Input alasan reviewer -->
            <div>
              <label
                for="reviewer-note"
                class="block text-xs font-semibold text-slate-700 mb-1.5"
              >
                Catatan
                <span v-if="modal.decision === 'rejected'" class="text-red-500">*</span>
                <span v-else class="text-slate-400 font-normal">(opsional)</span>
              </label>
              <textarea
                id="reviewer-note"
                v-model="modalNote"
                rows="3"
                class="w-full resize-none rounded-lg border px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 transition"
                :class="modalNoteError
                  ? 'border-red-300 focus:ring-red-300'
                  : 'border-slate-200 focus:ring-primary/30'"
                :placeholder="modal.decision === 'approved'
                  ? 'Tambahkan catatan persetujuan (opsional)…'
                  : 'Tuliskan alasan penolakan…'"
              />
              <p v-if="modalNoteError" class="mt-1 text-xs text-red-600">
                {{ modalNoteError }}
              </p>
            </div>
          </div>

          <!-- Footer -->
          <div
            class="flex justify-end gap-2 border-t border-slate-100 px-5 py-4"
          >
            <button
              type="button"
              class="h-9 rounded-lg border border-slate-200 px-4 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition"
              @click="closeModal"
            >
              Batal
            </button>
            <button
              type="button"
              class="h-9 rounded-lg px-4 text-xs font-semibold text-white transition disabled:opacity-50"
              :class="modal.decision === 'approved'
                ? 'bg-emerald-600 hover:bg-emerald-700'
                : 'bg-red-600 hover:bg-red-700'"
              @click="confirmReview"
            >
              {{ modal.decision === "approved" ? "Ya, Setujui" : "Ya, Tolak" }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>

  <!-- Toast notification -->
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="translate-y-4 opacity-0"
      enter-to-class="translate-y-0 opacity-100"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="translate-y-0 opacity-100"
      leave-to-class="translate-y-4 opacity-0"
    >
      <div
        v-if="toast"
        role="status"
        aria-live="polite"
        class="fixed bottom-6 right-6 z-[60] flex items-start gap-3 rounded-xl px-4 py-3 shadow-lg min-w-[280px] max-w-sm"
        :class="{
          'bg-emerald-50 border border-emerald-200 text-emerald-800': toast.type === 'success',
          'bg-red-50 border border-red-200 text-red-800': toast.type === 'error',
          'bg-slate-50 border border-slate-200 text-slate-800': toast.type === 'info',
        }"
      >
        <CheckCircle
          v-if="toast.type === 'success'"
          class="mt-0.5 h-5 w-5 shrink-0 text-emerald-500"
        />
        <XCircle
          v-else-if="toast.type === 'error'"
          class="mt-0.5 h-5 w-5 shrink-0 text-red-500"
        />
        <Check v-else class="mt-0.5 h-5 w-5 shrink-0 text-slate-500" />
        <p class="text-sm font-medium leading-snug">{{ toast.message }}</p>
        <button
          type="button"
          class="ml-auto shrink-0 opacity-50 hover:opacity-100 transition"
          aria-label="Tutup notifikasi"
          @click="toast = null"
        >
          <X class="h-4 w-4" />
        </button>
      </div>
    </Transition>
  </Teleport>
</template>
