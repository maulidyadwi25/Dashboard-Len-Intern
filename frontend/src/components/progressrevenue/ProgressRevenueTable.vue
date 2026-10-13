<script setup>
import { ref, computed, watch, nextTick } from "vue";
import { Download, Loader2, AlertTriangle, Inbox } from "lucide-vue-next";
import { generateProgressRevenuePdf } from "@/utils/pdfExport";

const props = defineProps({
  periods: {
    type: Array,
    default: () => [],
  },
  indicators: {
    type: Array,
    default: () => [],
  },
  baseline: {
    type: Object,
    default: null,
  },
  projectCode: {
    type: String,
    default: "-",
  },
  selectedMonth: {
    type: [String, Number],
    default: "all",
  },
  isLoading: {
    type: Boolean,
    default: false,
  },
  errorMessage: {
    type: String,
    default: "",
  },
});

const emit = defineEmits(["update:selectedMonth"]);

const tableContainerRef = ref(null);

/**
 * Canonical row order. Rows are rendered in this order regardless of the
 * order the API happens to return them in.
 */
const INDICATOR_ORDER = [
  "rkap_weekly_plan",
  "rkap_weekly_cumulative",
  "project_weekly_plan",
  "project_weekly_cumulative",
  "project_weekly_actual",
  "project_actual_cumulative",
  "deviation_vs_rkap",
];

const INDICATOR_LABELS = {
  rkap_weekly_plan: "Rencana Mingguan RKAP",
  rkap_weekly_cumulative: "Akumulasi Mingguan RKAP",
  project_weekly_plan: "Rencana Mingguan Proyek",
  project_weekly_cumulative: "Akumulasi Mingguan Proyek",
  project_weekly_actual: "Realisasi Mingguan Proyek",
  project_actual_cumulative: "Akumulasi Realisasi Proyek",
  deviation_vs_rkap: "Deviasi Proyek vs RKAP",
};

const MONTHS_UPPER = [
  "JANUARI",
  "FEBRUARI",
  "MARET",
  "APRIL",
  "MEI",
  "JUNI",
  "JULI",
  "AGUSTUS",
  "SEPTEMBER",
  "OKTOBER",
  "NOVEMBER",
  "DESEMBER",
];

const MONTH_OPTIONS = [
  { value: "all", label: "Semua Bulan (Jan - Des)" },
  { value: 1, label: "Januari" },
  { value: 2, label: "Februari" },
  { value: 3, label: "Maret" },
  { value: 4, label: "April" },
  { value: 5, label: "Mei" },
  { value: 6, label: "Juni" },
  { value: 7, label: "Juli" },
  { value: 8, label: "Agustus" },
  { value: 9, label: "September" },
  { value: 10, label: "Oktober" },
  { value: 11, label: "November" },
  { value: 12, label: "Desember" },
];

const selectedPeriodLabel = computed(
  () =>
    MONTH_OPTIONS.find((o) => String(o.value) === String(props.selectedMonth))
      ?.label ?? "Semua Bulan (Jan - Des)",
);

/**
 * Filtered periods while preserving the original index in props.periods.
 * The original index pairs each column with the corresponding value in indicators[].values[].
 */
const filteredPeriodEntries = computed(() => {
  // If props.periods is already pre-filtered (e.g. <= 4 periods), map directly
  if (props.periods.length <= 4) {
    return props.periods.map((period, originalIndex) => ({
      period,
      originalIndex,
    }));
  }

  if (!props.selectedMonth || props.selectedMonth === "all") {
    return props.periods.map((period, originalIndex) => ({
      period,
      originalIndex,
    }));
  }

  const targetMonth = Number(props.selectedMonth);
  return props.periods
    .map((period, originalIndex) => ({ period, originalIndex }))
    .filter(({ period }) => Number(period.month) === targetMonth);
});

/**
 * Group filtered periods into month buckets.
 */
const monthGroups = computed(() => {
  const groups = [];

  filteredPeriodEntries.value.forEach(({ period, originalIndex }) => {
    let group = groups[groups.length - 1];

    if (!group || group.month !== period.month) {
      group = {
        month: period.month,
        label: MONTHS_UPPER[period.month - 1] ?? String(period.month),
        weeks: [],
      };
      groups.push(group);
    }

    group.weeks.push({
      label: `W${period.week}`,
      index: originalIndex,
    });
  });

  return groups;
});

/** Flattened week columns so the header and body cells line up 1:1. */
const flatWeeks = computed(() =>
  monthGroups.value.flatMap((group) => group.weeks),
);

/** All 7 indicators, always in canonical order, even if the API order differs. */
const orderedIndicators = computed(() =>
  INDICATOR_ORDER.map((key) => {
    const found = props.indicators.find((i) => i.key === key);
    return {
      key,
      label: found?.label ?? INDICATOR_LABELS[key],
      values: found?.values ?? [],
    };
  }),
);

const hasData = computed(
  () => props.periods.length > 0 && orderedIndicators.value.length > 0,
);

const hasFilteredData = computed(() => flatWeeks.value.length > 0);

// Reset scroll position when filter changes
watch(
  () => props.selectedMonth,
  () => {
    nextTick(() => {
      if (tableContainerRef.value) {
        tableContainerRef.value.scrollLeft = 0;
      }
    });
  },
);

const formatPercentage = (value) => {
  if (value === null || value === undefined || value === "") return "-";

  const num = Number(value);
  if (Number.isNaN(num)) return "-";

  return `${(num * 100).toFixed(2)}%`;
};

const deviationClass = (value) => {
  if (value === null || value === undefined || value === "") {
    return "text-slate-400";
  }

  const num = Number(value);
  if (Number.isNaN(num) || num === 0) return "text-slate-700";

  return num < 0
    ? "text-rose-600 font-semibold bg-rose-50"
    : "text-emerald-600 font-semibold bg-emerald-50";
};

const handleDownload = () => {
  generateProgressRevenuePdf({
    projectCode: props.projectCode,
    periodLabel: selectedPeriodLabel.value,
    monthGroups: monthGroups.value,
    indicators: orderedIndicators.value,
    formatPercentage,
  });
};
</script>

<template>
  <div
    class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden"
  >
    <!-- Header: title, filter, download -->
    <div
      class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 px-6 py-5 border-b border-slate-200"
    >
      <div class="space-y-1">
        <h3 class="text-base font-extrabold text-slate-900 tracking-tight">
          Laporan Progress Pekerjaan Mingguan &amp; Akumulasi RKAP
        </h3>
        <p class="text-xs font-mono text-slate-500">
          Kode Proyek: {{ projectCode }}
        </p>
      </div>

      <div class="flex items-center gap-3">
        <label
          class="text-[11px] font-bold text-slate-500 uppercase tracking-wider"
        >
          Periode
        </label>
        <select
          :value="selectedMonth"
          class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500"
          @change="emit('update:selectedMonth', $event.target.value)"
        >
          <option
            v-for="option in MONTH_OPTIONS"
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }}
          </option>
        </select>

        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500/40 disabled:opacity-50 disabled:cursor-not-allowed"
          :disabled="isLoading || !hasData"
          @click="handleDownload"
        >
          <Download class="w-4 h-4" />
          Download
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div
      v-if="isLoading"
      class="flex flex-col items-center justify-center gap-3 px-6 py-16"
    >
      <Loader2 class="w-7 h-7 animate-spin text-blue-600" />
      <p class="text-sm font-semibold text-slate-500">
        Memuat data progress...
      </p>
    </div>

    <!-- Error -->
    <div
      v-else-if="errorMessage"
      class="flex flex-col items-center justify-center gap-3 px-6 py-16"
    >
      <AlertTriangle class="w-7 h-7 text-rose-500" />
      <p class="text-sm font-semibold text-rose-600">{{ errorMessage }}</p>
    </div>

    <!-- Empty (No sync data) -->
    <div
      v-else-if="!hasData"
      class="flex flex-col items-center justify-center gap-3 px-6 py-16"
    >
      <Inbox class="w-7 h-7 text-slate-400" />
      <p class="text-sm font-semibold text-slate-500">
        Belum ada data progress &amp; revenue.
      </p>
      <p class="text-xs text-slate-400">
        Jalankan sinkronisasi Google Sheets terlebih dahulu.
      </p>
    </div>

    <!-- Empty (Filtered month has no data) -->
    <div
      v-else-if="!hasFilteredData"
      class="flex flex-col items-center justify-center gap-3 px-6 py-16"
    >
      <Inbox class="w-7 h-7 text-slate-400" />
      <p class="text-sm font-semibold text-slate-500">
        Tidak ada data progress untuk periode {{ selectedPeriodLabel }}.
      </p>
      <p class="text-xs text-slate-400">
        Pilih bulan lain atau pilih "Semua Bulan (Jan - Des)".
      </p>
    </div>

    <!-- Table -->
    <div ref="tableContainerRef" v-else class="overflow-x-auto">
      <table
        :class="[
          'border-collapse text-sm',
          selectedMonth === 'all'
            ? 'w-full min-w-[3200px]'
            : 'w-full min-w-full',
        ]"
      >
        <thead>
          <!-- Month group row -->
          <tr class="bg-blue-600 text-white">
            <th
              rowspan="2"
              class="sticky left-0 z-30 bg-blue-600 px-4 py-2 text-left text-[11px] font-extrabold uppercase tracking-wider border-r border-blue-700 min-w-[220px]"
            >
              Indikator
            </th>
            <th
              v-for="group in monthGroups"
              :key="`month-${group.month}`"
              :colspan="group.weeks.length"
              class="px-2 py-2 text-center text-[11px] font-extrabold uppercase tracking-wider border-l border-blue-500"
            >
              {{ group.label }}
            </th>
          </tr>

          <!-- Week row -->
          <tr class="bg-blue-50 text-slate-700">
            <th
              v-for="week in flatWeeks"
              :key="`hdr-${week.label}-${week.index}`"
              :class="[
                'px-1 py-1.5 text-center text-[10px] font-bold border-l border-slate-200',
                selectedMonth === 'all'
                  ? 'min-w-[64px]'
                  : 'min-w-[100px] w-1/4',
              ]"
            >
              {{ week.label }}
            </th>
          </tr>
        </thead>

        <tbody>
          <tr
            v-for="indicator in orderedIndicators"
            :key="indicator.key"
            class="border-t border-slate-200 hover:bg-slate-50/60"
          >
            <!-- Sticky indicator label -->
            <th
              scope="row"
              class="sticky left-0 z-20 bg-white px-4 py-8 text-left text-xs font-bold text-slate-700 border-r border-slate-200 min-w-[220px] whitespace-nowrap"
            >
              {{ indicator.label }}
            </th>

            <!-- Values: paired with periods[] by the stored index -->
            <td
              v-for="week in flatWeeks"
              :key="`cell-${indicator.key}-${week.index}`"
              class="px-1 py-2.5 text-center text-xs font-semibold tabular-nums border-l border-slate-200 min-w-[64px]"
              :class="
                indicator.key === 'deviation_vs_rkap'
                  ? deviationClass(indicator.values[week.index])
                  : 'text-slate-700'
              "
            >
              {{ formatPercentage(indicator.values[week.index]) }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
