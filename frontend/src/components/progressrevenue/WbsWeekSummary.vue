<script setup lang="ts">
interface WeekSummaryItem {
  wbs_no: string;
  activity_name: string;
  status: string;
  latest_progress_percent: number;
  planned_percent: number;
  deviation_percent: number;
}

defineProps<{
  weekLabel: string;
  items: WeekSummaryItem[];
  loading: boolean;
  error: string;
}>();

const statusLabels: Record<string, string> = {
  not_started: "Belum Mulai",
  on_track: "On Track",
  overdue: "Terlambat",
  critical: "Kritis",
};

const statusClasses: Record<string, string> = {
  not_started: "bg-surface text-on-surface-variant",
  on_track: "bg-emerald-50 text-emerald-700",
  overdue: "bg-amber-100 text-amber-900",
  critical: "bg-error-container text-error",
};

function formatPercent(value: number): string {
  return `${Number(value ?? 0).toLocaleString("id-ID", { maximumFractionDigits: 2 })}%`;
}
</script>

<template>
  <section
    aria-label="Ringkasan WBS pekan ini"
    class="flex flex-col overflow-hidden rounded-2xl border-2 border-outline-variant bg-surface-lowest shadow-sm"
  >
    <header
      class="flex items-center justify-between gap-2 border-b border-outline-variant bg-primary-50 px-4 py-3 text-on-surface"
    >
      <h2 class="text-title-md">Ringkasan WBS Pekan Ini</h2>
      <p v-if="weekLabel" class="text-body-sm text-on-surface-variant">
        {{ weekLabel }}
      </p>
    </header>

    <p
      v-if="error"
      role="alert"
      class="m-3 rounded border border-error bg-error-container px-3 py-2 text-body-sm text-error"
    >
      {{ error }}
    </p>
    <p
      v-else-if="loading"
      role="status"
      class="m-3 text-sm text-on-surface-variant"
    >
      Memuat ringkasan pekan ini...
    </p>
    <p
      v-else-if="items.length === 0"
      class="m-3 text-sm text-on-surface-variant"
    >
      Tidak ada aktivitas WBS aktif pekan ini.
    </p>
    <div v-else class="space-y-2 p-3">
      <article
        v-for="item in items"
        :key="item.wbs_no"
        class="min-w-0 rounded-2xl border-2 border-outline-variant bg-surface p-3 transition-shadow hover:shadow-sm"
      >
        <div class="flex items-start justify-between gap-2">
          <div class="min-w-0">
            <p class="text-body-sm font-semibold text-on-surface-variant">
              {{ item.wbs_no }}
            </p>
            <h3
              class="mt-1 truncate text-body-sm font-semibold text-on-surface"
              :title="item.activity_name"
            >
              {{ item.activity_name }}
            </h3>
          </div>
          <span
            class="shrink-0 rounded px-2 py-1 text-body-sm font-medium"
            :class="
              statusClasses[item.status] ?? 'bg-surface text-on-surface-variant'
            "
          >
            {{ statusLabels[item.status] ?? item.status }}
          </span>
        </div>
        <div class="mt-3 flex items-center justify-between gap-3 text-body-sm">
          <span class="text-on-surface-variant">Progres</span>
          <span class="font-semibold tabular-nums text-on-surface">
            {{ formatPercent(item.latest_progress_percent) }}
          </span>
        </div>
        <div
          class="mt-1 h-1.5 overflow-hidden rounded-full bg-surface-container"
          role="progressbar"
          :aria-valuenow="item.latest_progress_percent"
          aria-valuemin="0"
          aria-valuemax="100"
          :aria-label="`Progres ${item.wbs_no}`"
        >
          <div
            class="h-full rounded-full bg-emerald-500"
            :style="{
              width: `${Math.min(100, Math.max(0, item.latest_progress_percent))}%`,
            }"
          />
        </div>
        <div class="mt-2 flex items-center justify-between gap-3 text-body-sm">
          <span class="text-on-surface-variant">Deviasi</span>
          <span
            class="font-semibold tabular-nums"
            :class="
              item.deviation_percent < 0 ? 'text-error' : 'text-emerald-800'
            "
          >
            {{ item.deviation_percent > 0 ? "+" : ""
            }}{{ formatPercent(item.deviation_percent) }}
          </span>
        </div>
      </article>
    </div>
  </section>
</template>
