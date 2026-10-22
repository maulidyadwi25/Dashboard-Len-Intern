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
  "project_weekly_deviation",
  "deviation_vs_rkap",
];

const INDICATOR_LABELS = {
  rkap_weekly_plan: "Rencana Mingguan RKAP",
  rkap_weekly_cumulative: "Akumulasi Mingguan RKAP",
  project_weekly_plan: "Rencana Mingguan Proyek",
  project_weekly_cumulative: "Akumulasi Mingguan Proyek",
  project_weekly_actual: "Realisasi Mingguan Proyek",
  project_actual_cumulative: "Akumulasi Realisasi Proyek",
  project_weekly_deviation: "Deviasi Proyek",
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

/** All indicators, always in canonical order, including computed Deviasi Proyek. */
const orderedIndicators = computed(() => {
  const actualObj = props.indicators.find(
    (i) => i.key === "project_weekly_actual",
  );
  const planObj = props.indicators.find((i) => i.key === "project_weekly_plan");

  const actualValues = actualObj?.values ?? [];
  const planValues = planObj?.values ?? [];

  // Calculate project_weekly_deviation: realisasi mingguan - rencana mingguan
  const totalCols = Math.max(
    props.periods.length,
    actualValues.length,
    planValues.length,
  );
  const deviationValues = [];

  for (let idx = 0; idx < totalCols; idx++) {
    const act = actualValues[idx];
    const pln = planValues[idx];

    if (
      act === null ||
      act === undefined ||
      act === "" ||
      pln === null ||
      pln === undefined ||
      pln === ""
    ) {
      // If actual is null or plan is null, check if either is defined
      if (act !== null && act !== undefined && act !== "") {
        const numAct = Number(act);
        deviationValues.push(Number.isNaN(numAct) ? null : numAct);
      } else {
        deviationValues.push(null);
      }
    } else {
      const numAct = Number(act);
      const numPln = Number(pln);
      if (Number.isNaN(numAct) || Number.isNaN(numPln)) {
        deviationValues.push(null);
      } else {
        deviationValues.push(numAct - numPln);
      }
    }
  }

  return INDICATOR_ORDER.map((key) => {
    if (key === "project_weekly_deviation") {
      const found = props.indicators.find((i) => i.key === key);
      return {
        key,
        label: found?.label ?? INDICATOR_LABELS[key],
        values: found?.values ?? deviationValues,
      };
    }

    const found = props.indicators.find((i) => i.key === key);
    return {
      key,
      label: found?.label ?? INDICATOR_LABELS[key],
      values: found?.values ?? [],
    };
  });
});

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
    return "text-on-surface-variant";
  }

  const num = Number(value);
  if (Number.isNaN(num)) return "text-on-surface-variant";

  // Tambahkan kondisi khusus untuk angka 0
  if (num === 0) {
    return "text-yellow-800 font-semibold bg-yellow-100";
  }

  return num < 0
    ? "text-error font-semibold bg-error/5"
    : "text-emerald-700 font-semibold bg-emerald-50";
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
    class="overflow-hidden rounded-xl border-outline-variant bg-surface-lowest shadow-sm"
  >
    <!-- Header: title, filter, download -->
    <div
      class="flex flex-col justify-between gap-4 border-outline-variant bg-white px-6 py-5 text-on-surface lg:flex-row lg:items-center"
    >
      <div class="space-y-1">
        <h3 class="text-title-md font-semibold">
          Laporan Progress Pekerjaan Mingguan &amp; Akumulasi RKAP
        </h3>
        <p class="font-mono text-body-sm text-on-surface-variant">
          Kode Proyek: {{ projectCode }}
        </p>
      </div>

      <div class="flex items-center gap-3">
        <label class="text-label-caps text-on-surface-variant uppercase">
          Periode
        </label>
        <!-- Filter / Select Periode -->
        <select
          :value="selectedMonth"
          class="h-10 px-3 py-2 bg-surface-bright border border-outline-variant rounded-lg text-body-sm focus:ring-2 focus:ring-primary outline-none cursor-pointer text-on-surface"
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

        <!-- Tombol Download -->
        <button
          type="button"
          class="h-10 px-4 text-white font-semibold rounded-lg transition-all active:scale-95 text-body-sm flex items-center gap-2 bg-primary hover:bg-primary-fixed-dim disabled:cursor-not-allowed disabled:opacity-50"
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
      class="flex flex-col items-center justify-center gap-3 px-6 py-16 text-on-surface-variant"
    >
      <Loader2 class="h-7 w-7 animate-spin text-primary" />
      <p class="text-sm font-semibold text-on-surface-variant">
        Memuat data progress...
      </p>
    </div>

    <!-- Error -->
    <div
      v-else-if="errorMessage"
      class="flex flex-col items-center justify-center gap-3 rounded border border-error bg-error-container px-6 py-16 text-error"
    >
      <AlertTriangle class="h-7 w-7 text-error" />
      <p class="text-sm font-semibold text-error">{{ errorMessage }}</p>
    </div>

    <!-- Empty (No sync data) -->
    <div
      v-else-if="!hasData"
      class="flex flex-col items-center justify-center gap-3 px-6 py-16"
    >
      <Inbox class="h-7 w-7 text-on-surface-variant" />
      <p class="text-sm font-semibold text-on-surface-variant">
        Belum ada data progress &amp; revenue.
      </p>
      <p class="text-xs text-on-surface-variant">
        Jalankan sinkronisasi Google Sheets terlebih dahulu.
      </p>
    </div>

    <!-- Empty (Filtered month has no data) -->
    <div
      v-else-if="!hasFilteredData"
      class="flex flex-col items-center justify-center gap-3 px-6 py-16"
    >
      <Inbox class="h-7 w-7 text-on-surface-variant" />
      <p class="text-sm font-semibold text-on-surface-variant">
        Tidak ada data progress untuk periode {{ selectedPeriodLabel }}.
      </p>
      <p class="text-xs text-on-surface-variant">
        Pilih bulan lain atau pilih "Semua Bulan (Jan - Des)".
      </p>
    </div>

    <!-- Table -->
    <div
      ref="tableContainerRef"
      v-else
      class="overflow-x-auto border border-outline-variant"
    >
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
          <tr class="bg-primary text-white">
            <th
              rowspan="2"
              class="sticky left-0 z-30 min-w-[220px] bg-primary px-4 py-2 text-left text-[11px] font-extrabold tracking-wider text-white uppercase"
            >
              Indikator
            </th>
            <th
              v-for="group in monthGroups"
              :key="`month-${group.month}`"
              :colspan="group.weeks.length"
              class="border-l border-outline-variant/40 px-2 py-2 text-center text-[11px] font-extrabold tracking-wider uppercase"
            >
              {{ group.label }}
            </th>
          </tr>

          <!-- Week row -->
          <tr class="bg-primary text-white">
            <th
              v-for="week in flatWeeks"
              :key="`hdr-${week.label}-${week.index}`"
              :class="[
                'border border-outline-variant/40 px-1 py-1.5 text-center text-body-sm font-bold',
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
            class="border-t border-outline-variant hover:bg-surface"
          >
            <!-- Sticky indicator label -->
            <th
              scope="row"
              class="sticky left-0 z-20 min-w-[220px] whitespace-nowrap border-r border-outline-variant/50 bg-surface-lowest px-4 py-8 text-left text-body-sm font-bold text-on-surface-variant"
            >
              {{ indicator.label }}
            </th>

            <!-- Values: paired with periods[] by the stored index -->
            <td
              v-for="week in flatWeeks"
              :key="`cell-${indicator.key}-${week.index}`"
              class="min-w-[64px] border-b border-outline-variant/60 px-1 py-2.5 text-center text-body-sm font-semibold tabular-nums"
              :class="
                ['deviation_vs_rkap', 'project_weekly_deviation'].includes(
                  indicator.key,
                )
                  ? deviationClass(indicator.values[week.index])
                  : 'text-on-surface-variant'
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
