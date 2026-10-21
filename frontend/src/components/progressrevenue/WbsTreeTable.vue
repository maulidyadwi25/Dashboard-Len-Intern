<script setup lang="ts">
import { ref } from "vue";
import { ChevronDown, ChevronRight, X, Pencil, Check } from "lucide-vue-next";
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
}>();

const drafts = ref<Record<string, string>>({});
const validationErrors = ref<Record<string, string>>({});
const editingWbsNo = ref<string | null>(null);
const showDiscardConfirmation = ref(false);
const pendingWbsNo = ref<string | null>(null);

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
  not_started: {
    label: "Not Started",
    className: "bg-surface text-on-surface-variant",
  },
  on_track: {
    label: "On Track",
    className: "bg-emerald-50 text-emerald-700",
  },
  overdue: { label: "Overdue", className: "bg-amber-100 text-amber-900" },
  critical: { label: "Critical", className: "bg-error-container text-error" },
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

function isEditableLeaf(item: WbsTreeItem): boolean {
  return isLeaf(item);
}

function activateEdit(item: WbsTreeItem): void {
  drafts.value[item.wbs_no] =
    item.latest_progress_percent === null
      ? ""
      : String(item.latest_progress_percent);
  editingWbsNo.value = item.wbs_no;
}

function startEdit(item: WbsTreeItem): void {
  if (!isEditableLeaf(item)) return;

  const activeItem = props.items.find(
    ({ item: visibleItem }) => visibleItem.wbs_no === editingWbsNo.value,
  )?.item;

  if (activeItem && activeItem.wbs_no !== item.wbs_no) {
    if (hasChanged(activeItem)) {
      pendingWbsNo.value = item.wbs_no;
      showDiscardConfirmation.value = true;
      return;
    }

    cancelRowEdit(activeItem);
  }

  activateEdit(item);
}

function dismissDiscardConfirmation(): void {
  showDiscardConfirmation.value = false;
  pendingWbsNo.value = null;
}

function discardAndSwitchEdit(): void {
  const activeItem = props.items.find(
    ({ item }) => item.wbs_no === editingWbsNo.value,
  )?.item;
  const targetItem = props.items.find(
    ({ item }) => item.wbs_no === pendingWbsNo.value,
  )?.item;

  if (activeItem) {
    delete drafts.value[activeItem.wbs_no];
    delete validationErrors.value[activeItem.wbs_no];
    emit("clearProgressError", activeItem.wbs_no);
  }

  editingWbsNo.value = null;
  dismissDiscardConfirmation();

  if (targetItem) activateEdit(targetItem);

  emit("dirtyChange", hasUnsavedChanges());
}

function hasChanged(item: WbsTreeItem): boolean {
  const rawValue = getDraft(item).trim();
  if (rawValue === "") return item.latest_progress_percent !== null;
  if (item.latest_progress_percent === null) return true;

  return Number(rawValue) !== Number(item.latest_progress_percent);
}

function isValidDraft(item: WbsTreeItem): boolean {
  const rawValue = getDraft(item).trim();
  const value = Number(rawValue);

  return (
    rawValue !== "" && Number.isFinite(value) && value >= 0 && value <= 100
  );
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

function formatActualPeriodRange(
  start: number | null | undefined,
  finish: number | null | undefined,
): string {
  if (start === null || start === undefined) return "-";

  const formatPeriod = (ordinal: number) => {
    const weekOfYear = (ordinal - 1) % 48;
    return `${months[Math.floor(weekOfYear / 4)]} W${(weekOfYear % 4) + 1}`;
  };

  return `${formatPeriod(start)} - ${finish == null ? "-" : formatPeriod(finish)}`;
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

function cancelRowEdit(item: WbsTreeItem): void {
  if (editingWbsNo.value !== item.wbs_no) return;

  delete drafts.value[item.wbs_no];
  delete validationErrors.value[item.wbs_no];
  editingWbsNo.value = null;
  emit("clearProgressError", item.wbs_no);
  emit("dirtyChange", hasUnsavedChanges());
}

async function saveRow(item: WbsTreeItem): Promise<void> {
  if (editingWbsNo.value !== item.wbs_no || !hasChanged(item)) return;

  const rawValue = getDraft(item).trim();
  const value = Number(rawValue);

  if (rawValue === "" || !Number.isFinite(value) || value < 0 || value > 100) {
    validationErrors.value[item.wbs_no] =
      "Progres harus berupa angka antara 0 dan 100.";
    return;
  }

  delete validationErrors.value[item.wbs_no];

  try {
    const results = await props.saveProgressBatch([
      { wbsNo: item.wbs_no, percent: value },
    ]);

    if (!results[item.wbs_no]) return;

    delete drafts.value[item.wbs_no];
    delete validationErrors.value[item.wbs_no];
    editingWbsNo.value = null;
    emit("dirtyChange", hasUnsavedChanges());
  } catch (error) {
    validationErrors.value[item.wbs_no] =
      error instanceof Error ? error.message : "Gagal menyimpan progres.";
  }
}
</script>

<template>
  <div class="space-y-3">
    <!-- Table Container -->
    <div
      class="overflow-auto rounded-2xl border border-outline-variant/60 bg-white shadow-sm transition-colors"
    >
      <table
        class="wbs-tree-table table-fixed border-separate border-spacing-0 w-full text-left text-sm"
      >
        <colgroup>
          <col class="w-[50px]" />
          <col class="w-[330px]" />
          <col class="w-[80px]" />
          <col class="w-[80px]" />
          <col class="w-[80px]" />
          <col class="w-[160px]" />
          <col class="w-[100px]" />
          <col class="w-[100px]" />
          <col class="w-[80px]" />
        </colgroup>
        <thead>
          <tr class="text-xs uppercase text-white text-center">
            <th
              class="sticky left-0 top-0 z-40 w-[50px] bg-primary px-2 py-3.5 font-semibold whitespace-nowrap text-white border-b border-primary/80"
            >
              WBS
            </th>
            <th
              class="sticky left-[50px] top-0 z-40 w-[330px] bg-primary px-3 py-3.5 font-semibold whitespace-nowrap text-white border-b border-primary/80"
            >
              Aktivitas
            </th>
            <th
              class="sticky top-0 z-20 w-[80px] border-b border-primary/80 bg-primary px-2 py-3.5 font-semibold whitespace-nowrap"
            >
              Plan
            </th>
            <th
              class="sticky top-0 z-20 w-[80px] border-b border-primary/80 bg-primary px-2 py-3.5 font-semibold whitespace-nowrap"
            >
              Real
            </th>
            <th
              class="sticky top-0 z-20 w-[80px] border-b border-primary/80 bg-primary px-3 py-3.5 font-semibold whitespace-nowrap"
            >
              Durasi
            </th>
            <th
              class="sticky top-0 z-20 w-[160px] border-b border-primary/80 bg-primary px-3 py-3.5 font-semibold whitespace-nowrap"
            >
              Jadwal
            </th>
            <th
              class="sticky top-0 z-20 w-[100px] border-b border-primary/80 bg-primary px-3 py-3.5 font-semibold whitespace-nowrap"
            >
              Progres
            </th>
            <th
              class="sticky top-0 z-20 w-[100px] border-b border-primary/80 bg-primary px-3 py-3.5 font-semibold whitespace-nowrap"
            >
              Status
            </th>
            <th
              class="sticky top-0 z-20 w-[80px] border-b border-primary/80 bg-primary px-3 py-3.5 font-semibold whitespace-nowrap"
            >
              Aksi
            </th>
            <WbsGanttChart mode="header" :periods="periods" />
          </tr>
        </thead>
        <tbody>
          <tr v-for="entry in items" :key="entry.item.wbs_no" class="group">
            <td
              class="sticky left-0 z-10 h-16 w-[50px] bg-white px-2 font-medium text-on-surface-variant border-b border-r border-outline-variant/60 group-hover:bg-slate-50 transition-colors text-center"
            >
              {{ entry.item.wbs_no }}
            </td>

            <td
              class="sticky left-[50px] z-10 h-16 w-[330px] bg-white px-3 text-on-surface border-b border-outline-variant/60 group-hover:bg-slate-50 transition-colors"
            >
              <div
                class="flex items-center gap-1.5 truncate"
                :style="{ paddingLeft: `${entry.depth * 18}px` }"
              >
                <button
                  v-if="entry.item.children.length > 0"
                  type="button"
                  class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded text-on-surface-variant hover:bg-surface-container hover:text-on-surface"
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
                  class="truncate font-medium"
                  :title="String(entry.item.activity_name ?? '')"
                >
                  {{ entry.item.activity_name ?? "-" }}
                </span>
              </div>
            </td>

            <!-- Sisa Kolom dengan bg-white solid agar bersih saat digulir horizontal -->
            <td
              class="h-16 w-[80px] border-b border-outline-variant/60 px-2 text-right tabular-nums text-on-surface whitespace-nowrap bg-white group-hover:bg-slate-50 transition-colors"
            >
              {{ formatPercent(entry.item.bobot_plan) }}
            </td>
            <td
              class="h-16 w-[80px] border-b border-outline-variant/60 px-2 text-right tabular-nums text-on-surface whitespace-nowrap bg-white group-hover:bg-slate-50 transition-colors"
            >
              {{ formatPercent(entry.item.bobot_real) }}
            </td>
            <td
              class="h-16 w-[80px] border-b border-outline-variant/60 px-3 text-on-surface-variant whitespace-nowrap bg-white group-hover:bg-slate-50 transition-colors"
            >
              {{ entry.item.durasi ?? "-" }}
            </td>
            <td
              class="h-16 w-[160px] border-b border-outline-variant/60 px-3 whitespace-nowrap bg-white group-hover:bg-slate-50 transition-colors"
            >
              <div
                class="flex h-16 flex-col justify-center gap-0.5 whitespace-nowrap text-xs leading-4"
              >
                <span class="text-on-surface-variant"
                  >P {{ planRange(entry.item) }}</span
                >
                <span class="text-on-surface-variant">
                  A
                  {{
                    formatActualPeriodRange(
                      entry.item.actual_progress_start_ordinal,
                      entry.item.actual_progress_finish_ordinal,
                    )
                  }}
                </span>
              </div>
            </td>
            <td
              class="h-16 w-[100px] border-b border-outline-variant/60 px-3 whitespace-nowrap bg-white group-hover:bg-slate-50 transition-colors"
            >
              <div v-if="isLeaf(entry.item)" class="flex items-center gap-2">
                <template v-if="editingWbsNo === entry.item.wbs_no">
                  <div class="relative flex items-center gap-1">
                    <input
                      type="number"
                      min="0"
                      max="100"
                      step="any"
                      :id="progressInputId(entry.item.wbs_no)"
                      :value="getDraft(entry.item)"
                      :aria-label="`Progres ${entry.item.wbs_no}`"
                      :aria-invalid="Boolean(progressError(entry.item))"
                      class="h-8 w-14 rounded-lg border px-1 text-right tabular-nums outline-none ring-1 focus:ring-1 bg-white"
                      :class="
                        progressError(entry.item)
                          ? 'border-error ring-error/10 focus:ring-error/30'
                          : 'border-primary ring-primary/10 focus:ring-primary/30'
                      "
                      @input="updateDraft(entry.item.wbs_no, $event)"
                      @keydown.enter.prevent="saveRow(entry.item)"
                      @keydown.esc.prevent="cancelRowEdit(entry.item)"
                    />
                    <span class="text-xs text-on-surface-variant">%</span>
                    <span
                      v-if="hasChanged(entry.item)"
                      class="h-2 w-2 rounded-full bg-primary"
                      :aria-label="`Perubahan belum disimpan untuk ${entry.item.wbs_no}`"
                      title="Perubahan belum disimpan"
                    />
                    <p
                      v-if="progressError(entry.item)"
                      aria-live="polite"
                      class="absolute left-0 top-full z-30 mt-1 w-56 whitespace-normal rounded-xl border border-error/30 bg-white px-3 py-2 text-xs text-error shadow-md"
                    >
                      {{ progressError(entry.item) }}
                    </p>
                  </div>
                </template>
                <span v-else class="font-medium tabular-nums text-on-surface">
                  {{ formatPercent(entry.item.latest_progress_percent) }}
                </span>
              </div>
              <span v-else class="font-medium tabular-nums text-on-surface">
                {{ formatPercent(entry.item.progress_sum) }}
              </span>
            </td>
            <td
              class="h-16 w-[100px] border-b border-outline-variant/60 px-3 whitespace-nowrap bg-white group-hover:bg-slate-50 transition-colors"
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
            <td
              class="h-16 w-[80px] border-b border-outline-variant/60 px-3 bg-white group-hover:bg-slate-50 transition-colors text-center"
            >
              <div v-if="editingWbsNo === entry.item.wbs_no" class="flex gap-1">
                <button
                  type="button"
                  class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-primary text-on-primary transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50"
                  :aria-label="`Simpan progres ${entry.item.wbs_no}`"
                  title="Simpan"
                  :disabled="
                    !isValidDraft(entry.item) ||
                    !hasChanged(entry.item) ||
                    savingWbsNo !== null
                  "
                  @click="saveRow(entry.item)"
                >
                  <Check class="h-4 w-4" />
                </button>
                <button
                  type="button"
                  class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-outline-variant text-on-surface-variant transition hover:bg-surface-container"
                  :aria-label="`Batalkan edit progres ${entry.item.wbs_no}`"
                  title="Batal"
                  @click="cancelRowEdit(entry.item)"
                >
                  <X class="h-4 w-4" />
                </button>
              </div>
              <button
                v-else-if="isEditableLeaf(entry.item)"
                type="button"
                class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-yellow-100 text-yellow-700 transition hover:bg-yellow-200"
                aria-label="Edit"
                title="Edit"
                @click="startEdit(entry.item)"
              >
                <Pencil class="h-4 w-4" />
              </button>
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

    <div
      v-if="showDiscardConfirmation"
      class="fixed inset-0 z-[100] flex items-center justify-center bg-on-surface/40 p-4"
      role="alertdialog"
      aria-modal="true"
      aria-labelledby="wbs-row-discard-title"
    >
      <section
        class="w-full max-w-md rounded-xl border border-outline-variant bg-surface-lowest p-5 shadow-xl"
      >
        <h2
          id="wbs-row-discard-title"
          class="text-base font-semibold text-on-surface"
        >
          Perubahan belum tersimpan. Buang perubahan?
        </h2>
        <div class="mt-5 flex justify-end gap-2">
          <button
            type="button"
            class="h-9 rounded border border-outline-variant px-4 text-sm font-medium text-on-surface-variant hover:bg-surface"
            @click="dismissDiscardConfirmation"
          >
            Kembali
          </button>
          <button
            type="button"
            class="h-9 rounded bg-error px-4 text-sm font-medium text-white hover:bg-error/90"
            @click="discardAndSwitchEdit"
          >
            Buang
          </button>
        </div>
      </section>
    </div>
  </div>
</template>
