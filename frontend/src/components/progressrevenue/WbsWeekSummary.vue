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
  overdue: "Overdue",
  critical: "Critical",
};

const statusClasses: Record<string, string> = {
  not_started: "bg-slate-100 text-slate-700",
  on_track: "bg-emerald-100 text-emerald-800",
  overdue: "bg-amber-100 text-amber-800",
  critical: "bg-rose-100 text-rose-800",
};

function formatPercent(value: number): string {
  return `${Number(value ?? 0).toLocaleString("id-ID", { maximumFractionDigits: 2 })}%`;
}
</script>

<template>
  <section aria-label="Ringkasan WBS pekan ini" class="space-y-3">
    <div class="flex flex-wrap items-baseline justify-between gap-2">
      <h2 class="text-lg font-semibold text-slate-900">
        Ringkasan WBS Pekan Ini
      </h2>
      <p v-if="weekLabel" class="text-sm text-slate-500">{{ weekLabel }}</p>
    </div>

    <p
      v-if="error"
      role="alert"
      class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
    >
      {{ error }}
    </p>
    <p
      v-else-if="loading"
      role="status"
      class="rounded-md border border-slate-200 bg-white px-3 py-4 text-sm text-slate-500"
    >
      Memuat ringkasan pekan ini...
    </p>
    <p
      v-else-if="items.length === 0"
      class="rounded-md border border-dashed border-slate-300 bg-white px-3 py-4 text-sm text-slate-500"
    >
      Tidak ada aktivitas WBS aktif pekan ini.
    </p>
    <div v-else class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
      <article
        v-for="item in items"
        :key="item.wbs_no"
        class="min-w-0 rounded-md border border-slate-200 bg-white p-3"
      >
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <p class="text-xs font-semibold text-slate-500">
              {{ item.wbs_no }}
            </p>
            <h3
              class="mt-1 truncate text-sm font-semibold text-slate-900"
              :title="item.activity_name"
            >
              {{ item.activity_name }}
            </h3>
          </div>
          <span
            class="shrink-0 rounded px-2 py-1 text-xs font-medium"
            :class="statusClasses[item.status] ?? 'bg-slate-100 text-slate-700'"
          >
            {{ statusLabels[item.status] ?? item.status }}
          </span>
        </div>
        <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
          <div>
            <p class="text-xs text-slate-500">Progres</p>
            <p class="mt-0.5 font-semibold tabular-nums text-slate-800">
              {{ formatPercent(item.latest_progress_percent) }}
            </p>
          </div>
          <div>
            <p class="text-xs text-slate-500">Deviasi</p>
            <p
              class="mt-0.5 font-semibold tabular-nums"
              :class="
                item.deviation_percent < 0
                  ? 'text-rose-700'
                  : 'text-emerald-700'
              "
            >
              {{ item.deviation_percent > 0 ? "+" : ""
              }}{{ formatPercent(item.deviation_percent) }}
            </p>
          </div>
        </div>
      </article>
    </div>
  </section>
</template>
