<script setup lang="ts">
import { computed, ref, watch } from "vue";
import {
  ChevronDown,
  ChevronRight,
  LoaderCircle,
  LockKeyhole,
  Save,
  X,
} from "lucide-vue-next";
import WbsGanttChart from "./WbsGanttChart.vue";
import type {
  VisibleWbsItem,
  WbsPeriod,
  WbsStatus,
  WbsTreeItem,
  WbsWeeklyByWbs,
} from "../../utils/wbs.ts";

const props = defineProps<{
  items: VisibleWbsItem[];
  expanded: Record<string, boolean>;
  periods: WbsPeriod[];
  weeklyByWbs: WbsWeeklyByWbs;
  isEditing: boolean;
  savingWbsNo: string | null;
  progressErrors: Record<string, string>;
  saveProgressBatch: (
    changes: Array<{ wbsNo: string; percent: number }>,
  ) => Promise<Record<string, boolean>>;
}>();

const emit = defineEmits<{
  toggle: [wbsNo: string];
  clearProgressError: [wbsNo: string];
  dirtyChange: [dirty: boolean];
  cancelRequested: [];
  finishEdit: [];
}>();

const drafts = ref<Record<string, string>>({});
const validationErrors = ref<Record<string, string>>({});
const errorRows = computed(() =>
  props.items.filter(({ item }) => Boolean(progressError(item))),
);

const months = [
  "Jan",
  "Feb",
  "Mar",
  "Apr",
  "May",
  "Jun",
  "Jul",
  "Aug",
  "Sep",
  "Oct",
  "Nov",
  "Dec",
];

const statusDisplay: Record<WbsStatus, { label: string; className: string }> = {
  not_started: { label: "Belum Mulai", className: "bg-gray-100 text-gray-700" },
  on_track: {
    label: "On Track",
    className: "bg-emerald-100 text-emerald-700",
  },
  overdue: { label: "Overdue", className: "bg-amber-100 text-amber-700" },
  critical: { label: "Critical", className: "bg-red-100 text-red-700" },
};

function getDraft(item: WbsTreeItem): string {
  if (drafts.value[item.wbs_no] !== undefined) {
    return drafts.value[item.wbs_no];
  }

  return item.latest_progress_percent === null
    ? ""
    : String(item.latest_progress_percent);
}

function updateDraft(wbsNo: string, event: Event): void {
  drafts.value[wbsNo] = (event.target as HTMLInputElement).value;
  delete validationErrors.value[wbsNo];
  emit("clearProgressError", wbsNo);
  emit("dirtyChange", hasUnsavedChanges());
}

function isLeaf(item: WbsTreeItem): boolean {
  return item.children.length === 0;
}

function isLocked(item: WbsTreeItem): boolean {
  return (item.latest_progress_percent ?? 0) >= 100;
}

function hasValidDraft(item: WbsTreeItem): boolean {
  const rawValue = getDraft(item).trim();
  if (rawValue === "") return false;
  const value = Number(rawValue);

  return Number.isFinite(value) && value >= 0 && value <= 100;
}

function hasChanged(item: WbsTreeItem): boolean {
  const rawValue = getDraft(item).trim();
  if (rawValue === "") return item.latest_progress_percent !== null;
  if (item.latest_progress_percent === null) return true;

  return Number(rawValue) !== Number(item.latest_progress_percent);
}

function hasUnsavedChanges(): boolean {
  return props.items.some(({ item }) => isLeaf(item) && hasChanged(item));
}

function progressError(item: WbsTreeItem): string | undefined {
  return (
    validationErrors.value[item.wbs_no] ?? props.progressErrors[item.wbs_no]
  );
}

function progressInputId(wbsNo: string): string {
  return `wbs-progress-${wbsNo.replaceAll(".", "-")}`;
}

function focusFirstError(): void {
  const firstError = errorRows.value[0];
  if (!firstError) return;

  const input = document.getElementById(
    progressInputId(firstError.item.wbs_no),
  ) as HTMLInputElement | null;
  input?.scrollIntoView({
    behavior: "smooth",
    block: "center",
    inline: "nearest",
  });
  input?.focus({ preventScroll: true });
}

function formatOrdinal(ordinal: number | null): string {
  if (ordinal === null || ordinal < 1 || ordinal > 48) return "-";
  const zeroBased = ordinal - 1;
  return `${months[Math.floor(zeroBased / 4)]} W${(zeroBased % 4) + 1}`;
}

function formatRange(start: number | null, finish: number | null): string {
  if (start === null && finish === null) return "-";
  return `${formatOrdinal(start)} - ${formatOrdinal(finish)}`;
}

function planRange(item: WbsTreeItem): string {
  return item.plan_start_period && item.plan_end_period
    ? `${item.plan_start_period} - ${item.plan_end_period}`
    : item.plan_start_period || item.plan_end_period || "-";
}

function formatPercent(value: number | null | undefined): string {
  return value === null || value === undefined
    ? "-"
    : `${Number(value).toLocaleString("id-ID")}%`;
}

function resetDrafts(): void {
  drafts.value = {};
  validationErrors.value = {};
  emit("dirtyChange", false);
}

watch(
  () => props.isEditing,
  (editing) => {
    if (!editing) resetDrafts();
  },
);

function cancelEdit(): void {
  emit("cancelRequested");
}

async function saveChanges(): Promise<void> {
  if (!props.isEditing) return;

  const changes: Array<{ wbsNo: string; percent: number }> = [];

  for (const { item } of props.items) {
    if (!isLeaf(item) || isLocked(item) || !hasChanged(item)) continue;

    const rawValue = getDraft(item).trim();
    const value = Number(rawValue);

    if (rawValue === "" || !Number.isFinite(value)) {
      validationErrors.value[item.wbs_no] = "Progres harus berupa angka.";
      continue;
    }

    if (value < 0 || value > 100) {
      validationErrors.value[item.wbs_no] =
        "Progres harus berada di rentang 0-100.";
      continue;
    }

    delete validationErrors.value[item.wbs_no];
    changes.push({ wbsNo: item.wbs_no, percent: value });
  }

  const results = changes.length ? await props.saveProgressBatch(changes) : {};

  for (const change of changes) {
    if (results[change.wbsNo]) {
      delete drafts.value[change.wbsNo];
      delete validationErrors.value[change.wbsNo];
    }
  }

  emit("dirtyChange", hasUnsavedChanges());

  const hasErrors = props.items.some(({ item }) =>
    Boolean(progressError(item)),
  );
  if (!hasErrors && !hasUnsavedChanges()) {
    emit("finishEdit");
  }
}
</script>

<template>
  <div class="space-y-2">
    <div
      v-if="isEditing"
      class="flex flex-wrap items-center justify-between gap-3 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900"
    >
      <p>Mode Edit aktif. Ubah progres lalu klik Simpan.</p>
      <div class="flex items-center gap-2">
        <span
          v-if="errorRows.length > 0"
          class="text-xs font-medium text-red-700"
          aria-live="polite"
        >
          {{ errorRows.length }} baris perlu diperbaiki
        </span>
        <button
          v-if="errorRows.length > 0"
          type="button"
          class="h-8 rounded border border-red-300 bg-white px-3 text-xs font-medium text-red-700 hover:bg-red-50"
          @click="focusFirstError"
        >
          Ke baris pertama
        </button>
        <button
          type="button"
          class="inline-flex h-8 items-center gap-2 rounded border border-amber-300 bg-white px-3 font-medium text-amber-900 hover:bg-amber-100 disabled:cursor-wait disabled:opacity-60"
          :disabled="savingWbsNo !== null"
          @click="saveChanges"
        >
          <LoaderCircle
            v-if="savingWbsNo !== null"
            class="h-4 w-4 animate-spin"
          />
          <Save v-else class="h-4 w-4" />
          Simpan
        </button>
        <button
          type="button"
          class="inline-flex h-8 items-center gap-2 rounded border border-amber-300 px-3 font-medium text-amber-900 hover:bg-amber-100 disabled:cursor-wait disabled:opacity-60"
          :disabled="savingWbsNo !== null"
          @click="cancelEdit"
        >
          <X class="h-4 w-4" />
          Batal
        </button>
      </div>
    </div>

    <div
      class="overflow-auto rounded-md border bg-white"
      :class="
        isEditing
          ? 'border-amber-300 outline outline-1 outline-amber-100'
          : 'border-slate-200'
      "
    >
      <table
        class="wbs-tree-table min-w-375 table-fixed border-separate border-spacing-0 text-left text-sm"
      >
        <colgroup>
          <col class="w-16" />
          <col class="w-62.5" />
          <col class="w-27.5" />
          <col class="w-27.5" />
          <col class="w-26.25" />
          <col class="w-47.5" />
          <col class="w-47.5" />
          <col class="w-35" />
          <col :style="{ width: `${periods.length * 36}px` }" />
        </colgroup>
        <thead>
          <tr class="text-xs uppercase text-slate-500">
            <th
              class="sticky left-0 top-0 z-50 min-w-16 border-b border-slate-200 bg-slate-50 px-2 py-3 font-semibold whitespace-nowrap"
            >
              WBS
            </th>
            <th
              class="sticky left-16 top-0 z-50 min-w-62.5 border-b border-slate-200 bg-slate-50 px-3 py-3 font-semibold whitespace-nowrap"
            >
              Aktivitas
            </th>
            <th
              class="sticky top-0 z-30 min-w-27.5 border-b border-slate-200 bg-slate-50 px-3 py-3 text-right font-semibold whitespace-nowrap"
            >
              Bobot Plan
            </th>
            <th
              class="sticky top-0 z-30 min-w-27.5 border-b border-slate-200 bg-slate-50 px-3 py-3 text-right font-semibold whitespace-nowrap"
            >
              Bobot Real
            </th>
            <th
              class="sticky top-0 z-30 min-w-26.25 border-b border-slate-200 bg-slate-50 px-3 py-3 font-semibold whitespace-nowrap"
            >
              Durasi
            </th>
            <th
              class="sticky top-0 z-30 min-w-47.5 border-b border-slate-200 bg-slate-50 px-3 py-3 font-semibold whitespace-nowrap"
            >
              Jadwal
            </th>
            <th
              class="sticky top-0 z-30 min-w-47.5 border-b border-slate-200 bg-slate-50 px-3 py-3 font-semibold whitespace-nowrap"
            >
              Progres
            </th>
            <th
              class="sticky top-0 z-30 min-w-35 border-b border-slate-200 bg-slate-50 px-3 py-3 font-semibold whitespace-nowrap"
            >
              Status
            </th>
            <WbsGanttChart mode="header" :periods="periods" />
          </tr>
        </thead>
        <tbody>
          <tr v-for="entry in items" :key="entry.item.wbs_no" class="group">
            <td
              class="sticky left-0 z-50 h-16 min-w-16 border-b border-slate-100 bg-white px-3 font-medium text-slate-700 group-hover:bg-slate-50"
            >
              {{ entry.item.wbs_no }}
            </td>
            <td
              class="sticky left-12 z-50 h-16 min-w-62.5 border-b border-slate-100 bg-white px-3 text-slate-800 group-hover:bg-slate-50"
            >
              <div
                class="flex items-center gap-1.5 truncate"
                :style="{ paddingLeft: `${entry.depth * 18}px` }"
              >
                <button
                  v-if="entry.item.children.length > 0"
                  type="button"
                  class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded text-slate-500 hover:bg-slate-200 hover:text-slate-900"
                  :aria-label="`Toggle ${entry.item.wbs_no}`"
                  :aria-expanded="Boolean(expanded[entry.item.wbs_no])"
                  @click="emit('toggle', entry.item.wbs_no)"
                >
                  <ChevronDown
                    v-if="expanded[entry.item.wbs_no]"
                    class="h-4 w-4"
                  />
                  <ChevronRight v-else class="h-4 w-4" />
                </button>
                <span v-else class="inline-block h-5 w-5 shrink-0" />
                <span
                  class="truncate"
                  :title="String(entry.item.activity_name ?? '')"
                >
                  {{ entry.item.activity_name ?? "-" }}
                </span>
              </div>
            </td>
            <td
              class="h-16 min-w-27.5 border-b border-slate-100 px-3 text-right tabular-nums text-slate-700 whitespace-nowrap"
            >
              {{ formatPercent(entry.item.bobot_plan) }}
            </td>
            <td
              class="h-16 min-w-27.5 border-b border-slate-100 px-3 text-right tabular-nums text-slate-700 whitespace-nowrap"
            >
              {{ formatPercent(entry.item.bobot_real) }}
            </td>
            <td
              class="h-16 min-w-26.25 border-b border-slate-100 px-3 text-slate-600 whitespace-nowrap"
            >
              {{ entry.item.durasi ?? "-" }}
            </td>
            <td
              class="h-16 min-w-47.5 border-b border-slate-100 px-3 whitespace-nowrap"
            >
              <div
                class="flex h-16 flex-col justify-center gap-0.5 whitespace-nowrap text-xs leading-4"
              >
                <span class="text-slate-600"
                  >P {{ planRange(entry.item) }}</span
                >
                <span class="text-slate-500"
                  >A
                  {{
                    formatRange(
                      entry.item.actual_start_week,
                      entry.item.actual_finish_week,
                    )
                  }}</span
                >
              </div>
            </td>
            <td
              class="h-16 min-w-47.5 border-b border-slate-100 px-3 whitespace-nowrap"
            >
              <div v-if="isLeaf(entry.item)" class="flex items-center gap-2">
                <template v-if="isEditing && !isLocked(entry.item)">
                  <div class="relative flex items-center gap-1.5">
                    <input
                      type="number"
                      min="0"
                      max="100"
                      step="any"
                      :id="progressInputId(entry.item.wbs_no)"
                      :value="getDraft(entry.item)"
                      :aria-label="`Progres ${entry.item.wbs_no}`"
                      :aria-invalid="Boolean(progressError(entry.item))"
                      class="h-8 w-21 rounded border px-2 text-right tabular-nums outline-none ring-1 focus:ring-1"
                      :class="
                        progressError(entry.item)
                          ? 'border-red-500 ring-red-100 focus:ring-red-300'
                          : 'border-blue-500 ring-blue-100 focus:ring-blue-300'
                      "
                      @input="updateDraft(entry.item.wbs_no, $event)"
                      @keydown.enter.prevent
                    />
                    <span class="text-xs text-slate-500">%</span>
                    <span
                      v-if="hasChanged(entry.item)"
                      class="h-2 w-2 rounded-full bg-blue-600"
                      :aria-label="`Perubahan belum disimpan untuk ${entry.item.wbs_no}`"
                      title="Perubahan belum disimpan"
                    />
                    <p
                      v-if="progressError(entry.item)"
                      aria-live="polite"
                      class="absolute left-0 top-full z-30 mt-1 w-56 whitespace-normal rounded border border-red-200 bg-white px-2 py-1.5 text-xs text-red-700 shadow-sm"
                    >
                      {{ progressError(entry.item) }}
                    </p>
                  </div>
                </template>
                <template v-else-if="isLocked(entry.item)">
                  <LockKeyhole
                    class="h-4 w-4 text-slate-500"
                    title="Progres sudah 100%, input dikunci."
                    aria-label="Progres sudah 100%, input dikunci."
                  />
                  <span class="font-medium text-slate-700">Selesai</span>
                </template>
                <span v-else class="font-medium tabular-nums text-slate-700">
                  {{ formatPercent(entry.item.latest_progress_percent) }}
                </span>
              </div>
              <span v-else class="font-medium tabular-nums text-slate-700">
                {{ formatPercent(entry.item.progress_sum) }}
              </span>
            </td>
            <td
              class="h-16 min-w-35 border-b border-slate-100 px-3 whitespace-nowrap"
            >
              <span
                v-if="
                  entry.item.children.length === 0 &&
                  entry.item.status &&
                  statusDisplay[entry.item.status]
                "
                class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold"
                :class="statusDisplay[entry.item.status].className"
              >
                {{ statusDisplay[entry.item.status].label }}
              </span>
            </td>
            <WbsGanttChart
              mode="row"
              :item="entry.item"
              :periods="periods"
              :progress-values="weeklyByWbs[entry.item.wbs_no]?.progress ?? []"
            />
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
