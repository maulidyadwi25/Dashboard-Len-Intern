<script setup>
import { onMounted, ref, watch } from "vue";
import { RefreshCw } from "lucide-vue-next";
import { wbsService } from "@/services/wbsService.js";

const props = defineProps({
  projectId: {
    type: [Number, String],
    default: null,
  },
});

const changes = ref([]);
const loading = ref(false);
const error = ref("");
const currentPage = ref(1);
const lastPage = ref(1);
const total = ref(0);

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
  const next = Number(item.new_percent ?? item.requested_percent ?? 0);
  return next - prev;
}

function formatDelta(item) {
  const diff = getDiff(item);
  const sign = diff > 0 ? "+" : "";
  return `${sign}${diff.toFixed(2)}%`;
}

function formatPeriod(change) {
  if (!change.year || !change.month) return "-";
  const month = new Intl.DateTimeFormat("id-ID", { month: "short" }).format(
    new Date(change.year, change.month - 1, 1),
  );
  return `${month} W${change.week}`;
}

function formatDecisionTime(dateStr) {
  if (!dateStr) return "-";
  const d = new Date(dateStr);
  const day = String(d.getDate()).padStart(2, "0");
  const months = [
    "Jan",
    "Feb",
    "Mar",
    "Apr",
    "Mei",
    "Jun",
    "Jul",
    "Ags",
    "Sep",
    "Okt",
    "Nov",
    "Des",
  ];
  const month = months[d.getMonth()] || "";
  const year = d.getFullYear();
  const hours = String(d.getHours()).padStart(2, "0");
  const minutes = String(d.getMinutes()).padStart(2, "0");
  return `${day} ${month} ${year}, ${hours}:${minutes}`;
}

async function loadChanges(page = 1) {
  if (props.projectId === null || props.projectId === undefined) {
    changes.value = [];
    total.value = 0;
    return;
  }

  loading.value = true;
  error.value = "";

  try {
    const response = await wbsService.getProgressChanges(props.projectId, page);
    const body = response.data ?? {};
    changes.value = body.data ?? [];
    currentPage.value = body.meta?.current_page ?? 1;
    lastPage.value = body.meta?.last_page ?? 1;
    total.value = body.meta?.total ?? 0;
  } catch (requestError) {
    error.value =
      requestError.message || "Gagal memuat riwayat perubahan progres.";
  } finally {
    loading.value = false;
  }
}

watch(
  () => props.projectId,
  () => {
    void loadChanges(1);
  },
);

onMounted(() => {
  void loadChanges(1);
});
</script>

<template>
  <div
    class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-xs"
  >
    <!-- Header: Title, Badge, and Refresh -->
    <div
      class="flex flex-col gap-4 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between"
    >
      <div class="flex items-center gap-3">
        <h2
          class="text-base font-bold tracking-tight text-slate-900 sm:text-lg"
        >
          Log Perubahan Langsung VP
        </h2>
        <span
          class="rounded-full bg-slate-100 px-3 py-0.5 text-xs font-semibold text-slate-600"
        >
          {{ total }} Log Tercatat
        </span>
      </div>

      <div class="flex items-center gap-2">
        <button
          type="button"
          class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-slate-200 px-3 text-xs font-medium text-slate-600 hover:bg-slate-50 disabled:opacity-50"
          :disabled="loading || projectId === null"
          title="Muat Ulang Data"
          @click="loadChanges(currentPage)"
        >
          <RefreshCw class="h-3.5 w-3.5" :class="{ 'animate-spin': loading }" />
          <span>Muat Ulang</span>
        </button>
      </div>
    </div>

    <!-- Error State -->
    <div
      v-if="error"
      class="m-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"
    >
      {{ error }}
    </div>

    <!-- Loading State -->
    <div
      v-else-if="loading && changes.length === 0"
      class="py-16 text-center text-sm font-medium text-slate-400"
    >
      Memuat riwayat perubahan langsung...
    </div>

    <!-- Empty State -->
    <div
      v-else-if="changes.length === 0"
      class="py-16 text-center text-sm font-medium text-slate-400"
    >
      Belum ada perubahan progres langsung yang tercatat untuk proyek ini.
    </div>

    <!-- Table -->
    <div v-else class="overflow-x-auto">
      <table class="w-full min-w-[980px] border-collapse text-left">
        <thead>
          <tr
            class="border-b border-slate-100 bg-[#f8fafc] text-[11px] font-bold uppercase tracking-wider text-slate-500"
          >
            <th scope="col" class="px-6 py-3.5">Kode WBS</th>
            <th scope="col" class="px-6 py-3.5">Aktivitas Pekerjaan</th>
            <th scope="col" class="px-6 py-3.5 min-w-[260px]">
              KOMPARASI PROGRES FISIK
            </th>
            <th scope="col" class="px-6 py-3.5">Periode</th>
            <th scope="col" class="px-6 py-3.5">Pelaku (Eksekutor)</th>
            <th scope="col" class="px-6 py-3.5">Catatan / Alasan</th>
            <th scope="col" class="px-6 py-3.5">Waktu Perubahan</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-sm">
          <tr
            v-for="item in changes"
            :key="item.id"
            class="transition hover:bg-slate-50/70"
          >
            <!-- Kode WBS -->
            <td
              class="whitespace-nowrap px-6 py-4 font-mono text-sm font-bold tracking-tight text-slate-900"
            >
              WBS {{ item.wbs_no }}
            </td>

            <!-- Aktivitas Pekerjaan -->
            <td class="max-w-[340px] px-6 py-4">
              <div class="flex flex-wrap items-center gap-1.5 mb-1">
                <span
                  v-if="item.wbs_status"
                  class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-bold tracking-wide uppercase"
                  :class="formatWbsStatus(item.wbs_status).className"
                >
                  {{ formatWbsStatus(item.wbs_status).label }}
                </span>
              </div>
              <p class="font-bold text-slate-900 line-clamp-2">
                {{ item.activity_name || "Nama aktivitas tidak tersedia" }}
              </p>
            </td>

            <!-- Komparasi Progres Fisik -->
            <td class="px-6 py-4 min-w-[260px]">
              <!-- Top row: Strikethrough previous, arrow, requested, delta badge -->
              <div class="flex items-center justify-between gap-2 mb-1.5">
                <div
                  class="flex items-center gap-1.5 font-mono text-sm tabular-nums"
                >
                  <span class="line-through text-slate-400 font-medium">
                    {{ formatDecimalPercent(item.previous_percent) }}
                  </span>
                  <span
                    class="text-blue-600 font-bold text-xs"
                    aria-hidden="true"
                    >→</span
                  >
                  <span
                    class="font-extrabold"
                    :class="
                      getDiff(item) < 0 ? 'text-red-600' : 'text-emerald-700'
                    "
                  >
                    {{
                      formatDecimalPercent(
                        item.new_percent ?? item.requested_percent,
                      )
                    }}
                  </span>
                </div>

                <!-- Delta badge -->
                <span
                  class="px-2 py-0.5 rounded text-xs font-bold tabular-nums font-mono"
                  :class="
                    getDiff(item) < 0
                      ? 'bg-rose-50 text-rose-600 border border-rose-100'
                      : 'bg-emerald-50 text-emerald-700 border border-emerald-100'
                  "
                >
                  {{ formatDelta(item) }}
                </span>
              </div>

              <!-- Progress bar -->
              <div
                class="w-full h-1.5 bg-blue-100/70 rounded-full overflow-hidden"
              >
                <div
                  class="h-full rounded-full transition-all duration-300"
                  :class="getDiff(item) < 0 ? 'bg-rose-600' : 'bg-emerald-600'"
                  :style="{
                    width: `${Math.min(
                      Math.max(
                        Number(item.new_percent ?? item.requested_percent ?? 0),
                        0,
                      ),
                      100,
                    )}%`,
                  }"
                />
              </div>

              <!-- Bottom row: Baseline and Sebelum -->
              <div
                class="flex items-center justify-between text-[11px] text-slate-500 font-medium mt-1.5"
              >
                <span>
                  Baseline:
                  {{
                    formatSimplePercent(
                      item.baseline_percent ??
                        (Number(item.new_percent) >
                        Number(item.previous_percent)
                          ? item.new_percent
                          : item.previous_percent),
                    )
                  }}
                </span>
                <span>
                  Sebelum: {{ formatSimplePercent(item.previous_percent) }}
                </span>
              </div>
            </td>

            <!-- Periode WBS -->
            <td
              class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-slate-700"
            >
              <span
                class="rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-700"
              >
                {{ formatPeriod(item) }}
              </span>
            </td>

            <!-- Pelaku (Eksekutor) -->
            <td
              class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-700"
            >
              <p class="font-bold text-slate-900">
                {{ item.actor_name || "-" }}
              </p>
              <p class="text-xs text-slate-500 uppercase tracking-wider">
                {{ item.actor_role || "VP" }}
              </p>
            </td>

            <!-- Catatan / Alasan -->
            <td class="max-w-[280px] px-6 py-4 text-sm text-slate-600">
              <p v-if="item.comment" class="line-clamp-2 italic">
                "{{ item.comment }}"
              </p>
              <span v-else class="text-slate-400">-</span>
            </td>

            <!-- Waktu Perubahan -->
            <td
              class="whitespace-nowrap px-6 py-4 text-sm text-slate-500 tabular-nums"
            >
              {{ formatDecisionTime(item.created_at) }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination Footer -->
    <div
      v-if="total > 0"
      class="flex items-center justify-end gap-4 border-t border-slate-100 px-6 py-4 text-xs font-medium text-slate-500"
    >
      <span>
        Menampilkan {{ (currentPage - 1) * 10 + 1 }}–{{
          Math.min(currentPage * 10, total)
        }}
        dari {{ total }} log
      </span>
      <button
        type="button"
        class="h-8 rounded-lg border border-slate-200 px-3 hover:bg-slate-50 disabled:opacity-40"
        :disabled="loading || currentPage <= 1"
        @click="loadChanges(currentPage - 1)"
      >
        Sebelumnya
      </button>
      <span>Halaman {{ currentPage }} dari {{ lastPage }}</span>
      <button
        type="button"
        class="h-8 rounded-lg border border-slate-200 px-3 hover:bg-slate-50 disabled:opacity-40"
        :disabled="loading || currentPage >= lastPage"
        @click="loadChanges(currentPage + 1)"
      >
        Berikutnya
      </button>
    </div>
  </div>
</template>
