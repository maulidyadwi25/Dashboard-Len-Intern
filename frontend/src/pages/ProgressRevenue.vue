<script setup>
import { ref, computed, watch } from "vue";
import { onBeforeRouteLeave } from "vue-router";
import ExecutiveHeader from "@/components/common/ExecutiveHeader.vue";
import ProjectSelectorBar from "@/components/common/ProjectSelectorBar.vue";
import ProjectProfileCard from "@/components/common/ProjectProfileCard.vue";
import ProgressRevenueTable from "@/components/progressrevenue/ProgressRevenueTable.vue";
import WbsSummaryCards from "@/components/progressrevenue/WbsSummaryCards.vue";
import WbsTreeTable from "@/components/progressrevenue/WbsTreeTable.vue";
import WbsWeekSummary from "@/components/progressrevenue/WbsWeekSummary.vue";
import WbsDeviationPanel from "@/components/progressrevenue/WbsDeviationPanel.vue";
import WbsAiSummary from "@/components/progressrevenue/WbsAiSummary.vue";

import Footer from "@/components/common/Footer.vue";
import { budgetService } from "@/services/budgetServices";
import { wbsService } from "@/services/wbsService.js";
import { useWbs } from "@/composables/useWbs";
import { useProject } from "@/composables/useProject";
import { generateWbsPdf } from "@/utils/pdfExport";

const { activeProjectId } = useProject();

const {
  summary: wbsSummary,
  items: wbsItems,
  periods: wbsPeriods,
  weeklyByWbs,
  visibleItems: wbsVisibleItems,
  selectedMonth: wbsSelectedMonth,
  expanded: wbsExpanded,
  loading: wbsLoading,
  weeklyLoading: wbsWeeklyLoading,
  syncing: wbsSyncing,
  savingWbsNo,
  progressErrors,
  error: wbsError,
  toggleExpanded,
  clearProgressError,
  sync: syncWbs,
  saveProgressBatch,
} = useWbs(activeProjectId);

const selectedMonth = ref("all");
const weekSummary = ref({ week_label: "", items: [] });
const weekSummaryLoading = ref(true);
const weekSummaryError = ref("");
const isWbsEditing = ref(false);
const hasUnsavedWbsChanges = ref(false);
const showWbsDiscardDialog = ref(false);
let resolveRouteLeave = null;

function requestCancelWbsEdit() {
  if (!hasUnsavedWbsChanges.value) {
    isWbsEditing.value = false;
    return;
  }

  showWbsDiscardDialog.value = true;
}

function keepWbsChanges() {
  showWbsDiscardDialog.value = false;

  if (resolveRouteLeave) {
    resolveRouteLeave(false);
    resolveRouteLeave = null;
  }
}

function discardWbsChanges() {
  showWbsDiscardDialog.value = false;
  hasUnsavedWbsChanges.value = false;
  isWbsEditing.value = false;

  if (resolveRouteLeave) {
    resolveRouteLeave(true);
    resolveRouteLeave = null;
  }
}

function toggleWbsEdit() {
  if (!isWbsEditing.value) {
    isWbsEditing.value = true;
    return;
  }

  requestCancelWbsEdit();
}

onBeforeRouteLeave(() => {
  if (!hasUnsavedWbsChanges.value) return true;

  showWbsDiscardDialog.value = true;
  return new Promise((resolve) => {
    resolveRouteLeave = resolve;
  });
});

async function loadWeekSummary() {
  if (activeProjectId.value === null) {
    weekSummary.value = { week_label: "", items: [] };
    weekSummaryLoading.value = false;
    return;
  }

  weekSummaryLoading.value = true;
  weekSummaryError.value = "";

  try {
    const response = await wbsService.getWeekSummary(activeProjectId.value);
    weekSummary.value = response.data?.data ?? { week_label: "", items: [] };
  } catch (error) {
    weekSummaryError.value =
      error.message || "Gagal memuat ringkasan pekan ini.";
  } finally {
    weekSummaryLoading.value = false;
  }
}

const isLoading = ref(true);
const errorMessage = ref("");
const payload = ref({
  project: null,
  baseline: null,
  periods: [],
  indicators: [],
});

const fetchProgressRevenue = async () => {
  if (activeProjectId.value === null) {
    payload.value = {
      project: null,
      baseline: null,
      periods: [],
      indicators: [],
    };
    isLoading.value = false;
    return;
  }

  isLoading.value = true;
  errorMessage.value = "";

  try {
    const res = await budgetService.getProgressRevenueData(
      activeProjectId.value,
      "all",
    );
    const body = res.data?.data ?? res.data ?? {};

    payload.value = {
      project: body.project ?? null,
      baseline: body.baseline ?? null,
      periods: body.periods ?? [],
      indicators: body.indicators ?? [],
    };
  } catch (err) {
    console.error("Failed to load progress revenue data:", err);
    errorMessage.value =
      err.response?.data?.message ||
      "Gagal menghubungi backend Laravel. Pastikan server backend aktif.";
    payload.value = {
      project: null,
      baseline: null,
      periods: [],
      indicators: [],
    };
  } finally {
    isLoading.value = false;
  }
};

watch(activeProjectId, (projectId) => {
  if (projectId === null) return;
  void fetchProgressRevenue();
  void loadWeekSummary();
});

void fetchProgressRevenue();
void loadWeekSummary();

const formatContractValue = (value) => {
  if (value === null || value === undefined || value === "") return "-";
  return `Rp ${Number(value).toLocaleString("id-ID")}`;
};

const projectMeta = computed(() => {
  const p = payload.value.project;

  if (!p) {
    return {
      title: "Progress & Revenue",
      code: "-",
      nilaiKontrak: "-",
      pimpinanProyek: "-",
      projectAnalyst: "-",
    };
  }

  return {
    title: p.project_name || "-",
    code: p.project_code || "-",
    nilaiKontrak: formatContractValue(p.contract_value),
    pimpinanProyek: p.project_lead_name || "-",
    projectAnalyst: p.report_owner_name || "-",
  };
});

function formatWeekOrdinal(value) {
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
  if (value === null || value < 1 || value > 48) return "";
  const zeroBased = value - 1;
  return `${months[Math.floor(zeroBased / 4)]} W${(zeroBased % 4) + 1}`;
}

function downloadWbsPdf() {
  if (wbsItems.value.length === 0) return;

  generateWbsPdf({
    projectCode: projectMeta.value.code,
    projectName: projectMeta.value.title,
    items: wbsItems.value,
    formatWeekOrdinal,
  });
}
</script>

<template>
  <div
    class="min-h-screen flex flex-col bg-[#f4f7fa] font-sans text-slate-800 antialiased"
  >
    <ExecutiveHeader />

    <main
      class="flex-1 max-w-[1600px] w-full mx-auto px-4 sm:px-6 py-6 space-y-5"
    >
      <ProjectSelectorBar />

      <ProjectProfileCard :projectInfo="projectMeta" />

      <ProgressRevenueTable
        :periods="payload.periods"
        :indicators="payload.indicators"
        :baseline="payload.baseline"
        :projectCode="projectMeta.code"
        :selectedMonth="selectedMonth"
        :isLoading="isLoading"
        :errorMessage="errorMessage"
        @update:selectedMonth="selectedMonth = $event"
      />

      <section
        aria-label="WBS Monitoring"
        class="space-y-4 border-t border-slate-200 pt-6"
        :aria-busy="wbsLoading || wbsWeeklyLoading"
      >
        <WbsSummaryCards
          :summary="wbsSummary"
          :editing="isWbsEditing"
          :can-download="wbsItems.length > 0"
          @toggle-edit="toggleWbsEdit"
          @download="downloadWbsPdf"
        />

        <p
          v-if="wbsError && wbsVisibleItems.length > 0"
          role="alert"
          class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
        >
          {{ wbsError }}
        </p>

        <section aria-label="WBS table" class="min-h-0">
          <div
            v-if="wbsLoading && wbsVisibleItems.length === 0"
            class="flex min-h-48 items-center justify-center rounded-md border border-slate-200 bg-white text-sm text-slate-500"
            role="status"
          >
            Memuat data WBS dan Gantt...
          </div>
          <div
            v-else-if="wbsError && wbsVisibleItems.length === 0"
            role="alert"
            class="flex min-h-48 items-center justify-center rounded-md border border-red-200 bg-red-50 px-4 text-center text-sm text-red-700"
          >
            {{ wbsError }}
          </div>
          <div
            v-else-if="wbsVisibleItems.length === 0"
            class="flex min-h-48 flex-col items-center justify-center gap-1 rounded-md border border-dashed border-slate-300 bg-white text-center"
          >
            <p class="text-sm font-semibold text-slate-700">
              Belum ada data WBS, klik Sync
            </p>
            <p class="text-xs text-slate-500">
              Gunakan tombol Sync WBS untuk mengambil struktur terbaru.
            </p>
          </div>
          <WbsTreeTable
            v-else
            :items="wbsVisibleItems"
            :expanded="wbsExpanded"
            :periods="wbsPeriods"
            :weekly-by-wbs="weeklyByWbs"
            :is-editing="isWbsEditing"
            :saving-wbs-no="savingWbsNo"
            :progress-errors="progressErrors"
            :save-progress-batch="saveProgressBatch"
            @toggle="toggleExpanded"
            @clear-progress-error="clearProgressError"
            @dirty-change="hasUnsavedWbsChanges = $event"
            @cancel-requested="requestCancelWbsEdit"
            @finish-edit="isWbsEditing = false"
          />
        </section>
        <section aria-label="Ringkasan WBS Pekan Ini" class="space-y-4">
          <WbsWeekSummary
            :week-label="weekSummary.week_label"
            :items="weekSummary.items"
            :loading="weekSummaryLoading"
            :error="weekSummaryError"
          />
          <div class="grid items-start gap-4 xl:grid-cols-2">
            <WbsDeviationPanel :items="weekSummary.items" />
            <WbsAiSummary :project-id="activeProjectId" />
          </div>
        </section>
      </section>
    </main>

    <Footer />

    <div
      v-if="showWbsDiscardDialog"
      class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/40 p-4"
      role="alertdialog"
      aria-modal="true"
      aria-labelledby="wbs-discard-title"
    >
      <section
        class="w-full max-w-md rounded-md border border-slate-200 bg-white p-5 shadow-xl"
      >
        <h2
          id="wbs-discard-title"
          class="text-base font-semibold text-slate-900"
        >
          Perubahan belum tersimpan. Buang perubahan?
        </h2>
        <div class="mt-5 flex justify-end gap-2">
          <button
            type="button"
            class="h-9 rounded border border-slate-300 px-4 text-sm font-medium text-slate-700 hover:bg-slate-50"
            @click="keepWbsChanges"
          >
            Kembali
          </button>
          <button
            type="button"
            class="h-9 rounded bg-red-700 px-4 text-sm font-medium text-white hover:bg-red-800"
            @click="discardWbsChanges"
          >
            Buang
          </button>
        </div>
      </section>
    </div>
  </div>
</template>
