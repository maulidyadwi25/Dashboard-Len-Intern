<script setup lang="ts">
import { computed } from "vue";

interface WeekSummaryItem {
  wbs_no: string;
  activity_name: string;
  status: string;
  latest_progress_percent: number;
  planned_percent: number;
  deviation_percent: number;
}

const props = defineProps<{
  weekLabel: string;
  items: WeekSummaryItem[];
  loading: boolean;
  error: string;
}>();

const criticalItems = computed(() =>
  props.items.filter((item) => item.status === "critical"),
);

const statusLabels: Record<string, string> = {
  not_started: "Belum Mulai",
  on_track: "On Track",
  overdue: "Terlambat",
  critical: "Kritis",
};

const statusClasses: Record<string, string> = {
  not_started: "bg-surface-container text-on-surface-variant",
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
    class="flex min-h-[30rem] max-h-[70vh] flex-col overflow-hidden rounded-2xl bg-surface-container-lowest transition-all"
  >
    <!-- Header -->
    <header
      class="flex items-center justify-between gap-3 bg-surface-container-low px-5 py-4"
    >
      <h2 class="text-title-md font-semibold text-on-surface">
        Ringkasan WBS Pekan Ini
      </h2>
      <span
        v-if="weekLabel"
        class="text-xs font-medium text-on-surface-variant bg-surface-container px-2.5 py-1 rounded-full"
      >
        {{ weekLabel }}
      </span>
    </header>

    <!-- State: Error, Loading, Empty -->
    <div
      v-if="error"
      role="alert"
      class="m-4 rounded-xl bg-error-container p-4 text-sm text-error"
    >
      {{ error }}
    </div>
    <div
      v-else-if="loading"
      role="status"
      class="p-8 text-center text-sm text-on-surface-variant animate-pulse"
    >
      Memuat ringkasan pekan ini...
    </div>
    <div
      v-else-if="criticalItems.length === 0"
      class="p-8 text-center text-sm text-on-surface-variant"
    >
      Tidak ada aktivitas WBS berstatus Critical pekan ini.
    </div>

    <div v-else class="min-h-0 flex-1 space-y-3 overflow-y-auto p-4">
      <article
        v-for="item in criticalItems"
        :key="item.wbs_no"
        class="group relative flex flex-col gap-3 rounded-xl bg-surface-container-low p-4 pl-5 transition-all duration-200 hover:bg-surface-container/50 hover:translate-x-0.5"
      >
        <!-- Garis Aksen Sisi Kiri (Pengganti Border Luar) -->
        <div
          class="absolute inset-y-0 left-0 w-1.5 rounded-l-xl bg-primary"
        ></div>

        <!-- Top Info: WBS, Name, & Status -->
        <div class="flex items-start justify-between gap-4">
          <div class="space-y-0.5 min-w-0">
            <span
              class="inline-block text-xs font-bold tracking-wider text-primary uppercase"
            >
              WBS {{ item.wbs_no }}
            </span>
            <h3
              class="font-medium text-on-surface line-clamp-1"
              :title="item.activity_name"
            >
              {{ item.activity_name }}
            </h3>
          </div>
          <span
            class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold"
            :class="
              statusClasses[item.status] ??
              'bg-surface-container text-on-surface-variant'
            "
          >
            {{ statusLabels[item.status] ?? item.status }}
          </span>
        </div>

        <!-- Progress Bar Section -->
        <div class="space-y-1.5 pt-1">
          <div class="flex justify-between text-xs">
            <span class="text-on-surface-variant">Progres</span>
            <span class="font-semibold tabular-nums text-on-surface">
              {{ formatPercent(item.latest_progress_percent) }}
            </span>
          </div>
          <div
            class="h-2 w-full overflow-hidden rounded-full bg-surface-container"
          >
            <div
              class="h-full rounded-full bg-primary transition-all duration-500"
              :style="{
                width: `${Math.min(100, Math.max(0, item.latest_progress_percent))}%`,
              }"
            />
          </div>
        </div>

        <!-- Footer Info: Deviation -->
        <div
          class="flex items-center justify-between pt-2.5 text-xs border-t border-outline-variant/20"
        >
          <span class="text-on-surface-variant">Deviasi Jadwal</span>
          <span
            class="font-semibold tabular-nums"
            :class="
              item.deviation_percent < 0 ? 'text-error' : 'text-emerald-600'
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
