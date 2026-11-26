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
  isLoading: {
    type: Boolean,
    default: false,
  },
  errorMessage: {
    type: String,
    default: "",
  },
});

const tableContainerRef = ref(null);
const startPeriod = ref({ week: "", month: "", year: "" });
const endPeriod = ref({ week: "", month: "", year: "" });

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

const monthOptions = MONTHS_UPPER.map((label, index) => ({
  value: String(index + 1),
  label: label.charAt(0) + label.slice(1).toLowerCase(),
}));
const weekOptions = [1, 2, 3, 4];
const yearOptions = computed(() =>
  [...new Set(props.periods.map((period) => Number(period.year)).filter(Number.isFinite))]
    .sort((left, right) => left - right)
    .map(String),
);

function periodOrdinal(selection) {
  if (!selection.week || !selection.month || !selection.year) return null;
  return (
    Number(selection.year) * 48 +
    (Number(selection.month) - 1) * 4 +
    Number(selection.week)
  );
}

const startOrdinal = computed(() => periodOrdinal(startPeriod.value));
const endOrdinal = computed(() => periodOrdinal(endPeriod.value));
const hasDateRange = computed(
  () => startOrdinal.value !== null && endOrdinal.value !== null,
);
const hasDateSelection = computed(() =>
  [startPeriod.value, endPeriod.value].some((period) =>
    period.week || period.month || period.year,
  ),
);
const hasIncompleteDateRange = computed(
  () => hasDateSelection.value && !hasDateRange.value,
);
const isDateRangeInvalid = computed(
  () => hasDateRange.value && startOrdinal.value > endOrdinal.value,
);
const selectedPeriodLabel = computed(() =>
  hasDateRange.value
    ? `${startPeriod.value.week}/${startPeriod.value.month}/${startPeriod.value.year} - ${endPeriod.value.week}/${endPeriod.value.month}/${endPeriod.value.year}`
    : "Semua Periode",
);

function resetDateRange() {
  startPeriod.value = { week: "", month: "", year: "" };
  endPeriod.value = { week: "", month: "", year: "" };
}

/**
 * Filtered periods while preserving the original index in props.periods.
 * The original index pairs each column with the corresponding value in indicators[].values[].
 */
const filteredPeriodEntries = computed(() => {
  const indexedPeriods = props.periods.map((period, originalIndex) => ({
    period,
    originalIndex,
    ordinal: periodOrdinal(period),
  }));

  if (isDateRangeInvalid.value) return [];

  if (!hasDateRange.value) {
    return indexedPeriods.map(({ period, originalIndex }) => ({
      period,
      originalIndex,
    }));
  }

  return indexedPeriods
    .filter(
      ({ ordinal }) => ordinal !== null &&
        ordinal >= startOrdinal.value && ordinal <= endOrdinal.value,
    )
    .map(({ period, originalIndex }) => ({ period, originalIndex }));
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
watch([startOrdinal, endOrdinal], () => {
  nextTick(() => {
    if (tableContainerRef.value) tableContainerRef.value.scrollLeft = 0;
  });
});

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
    <div class="space-y-4 border-b border-outline-variant bg-white px-6 py-5 text-on-surface">
      <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div class="space-y-1">
        <h3 class="text-title-md font-semibold">
          Laporan Progress Pekerjaan Mingguan &amp; Akumulasi RKAP
        </h3>
        </div>
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

      <div class="grid grid-cols-1 items-end gap-4 rounded-xl border border-outline-variant bg-surface px-4 py-4 lg:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)_auto]">
        <fieldset class="min-w-0">
          <legend class="mb-2 text-label-caps font-semibold text-on-surface-variant">DARI</legend>
          <div class="grid grid-cols-3 gap-2">
          <select v-model="startPeriod.week" aria-label="Minggu mulai" class="h-10 min-w-0 w-full rounded-lg border border-outline-variant bg-surface-lowest px-3 text-body-sm text-on-surface focus:outline-none focus:ring-2 focus:ring-primary">
            <option value="">W</option>
            <option v-for="week in weekOptions" :key="week" :value="String(week)">W{{ week }}</option>
          </select>
          <select v-model="startPeriod.month" aria-label="Bulan mulai" class="h-10 min-w-0 w-full rounded-lg border border-outline-variant bg-surface-lowest px-3 text-body-sm text-on-surface focus:outline-none focus:ring-2 focus:ring-primary">
            <option value="">Bulan</option>
            <option v-for="month in monthOptions" :key="month.value" :value="month.value">{{ month.label }}</option>
          </select>
          <select v-model="startPeriod.year" aria-label="Tahun mulai" class="h-10 min-w-0 w-full rounded-lg border border-outline-variant bg-surface-lowest px-3 text-body-sm text-on-surface focus:outline-none focus:ring-2 focus:ring-primary">
            <option value="">Tahun</option>
            <option v-for="year in yearOptions" :key="year" :value="year">{{ year }}</option>
          </select>
          </div>
        </fieldset>

        <span class="hidden px-2 text-xl text-on-surface-variant lg:block" aria-hidden="true">→</span>

        <fieldset class="min-w-0">
          <legend class="mb-2 text-label-caps font-semibold text-on-surface-variant">SAMPAI</legend>
          <div class="grid grid-cols-3 gap-2">
          <select v-model="endPeriod.week" aria-label="Minggu akhir" class="h-10 min-w-0 w-full rounded-lg border border-outline-variant bg-surface-lowest px-3 text-body-sm text-on-surface focus:outline-none focus:ring-2 focus:ring-primary">
            <option value="">W</option>
            <option v-for="week in weekOptions" :key="week" :value="String(week)">W{{ week }}</option>
          </select>
          <select v-model="endPeriod.month" aria-label="Bulan akhir" class="h-10 min-w-0 w-full rounded-lg border border-outline-variant bg-surface-lowest px-3 text-body-sm text-on-surface focus:outline-none focus:ring-2 focus:ring-primary">
            <option value="">Bulan</option>
            <option v-for="month in monthOptions" :key="month.value" :value="month.value">{{ month.label }}</option>
          </select>
          <select v-model="endPeriod.year" aria-label="Tahun akhir" class="h-10 min-w-0 w-full rounded-lg border border-outline-variant bg-surface-lowest px-3 text-body-sm text-on-surface focus:outline-none focus:ring-2 focus:ring-primary">
            <option value="">Tahun</option>
            <option v-for="year in yearOptions" :key="year" :value="year">{{ year }}</option>
          </select>
          </div>
        </fieldset>

        <button
          type="button"
          class="h-10 whitespace-nowrap rounded-lg border border-primary px-4 text-body-sm font-semibold text-primary transition hover:bg-primary/5 disabled:cursor-not-allowed disabled:opacity-50"
          :disabled="!hasDateSelection"
          @click="resetDateRange"
        >
          Reset Filter
        </button>
      </div>
      <p v-if="isDateRangeInvalid" class="text-sm text-error" role="alert">
        Tanggal mulai harus lebih awal dari atau sama dengan tanggal akhir.
      </p>
      <p v-else-if="hasIncompleteDateRange" class="text-sm text-on-surface-variant" role="status">
        Lengkapi minggu, bulan, dan tahun pada tanggal mulai dan akhir untuk menerapkan filter.
      </p>
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
        :style="{ minWidth: `${220 + flatWeeks.length * 64}px` }"
        :class="[
          'w-full border-collapse text-sm',
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
                'w-16 min-w-16',
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
