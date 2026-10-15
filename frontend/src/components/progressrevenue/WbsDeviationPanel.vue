<script setup lang="ts">
import { computed } from "vue";

interface DeviationItem {
  wbs_no: string;
  activity_name: string;
  status: string;
  latest_progress_percent: number;
  planned_percent: number;
  deviation_percent: number;
}

const props = defineProps<{
  items: DeviationItem[];
}>();

const worstItems = computed(() =>
  [...props.items]
    .sort(
      (left, right) =>
        Math.abs(right.deviation_percent) - Math.abs(left.deviation_percent),
    )
    .slice(0, 5),
);

function formatPercent(value: number): string {
  return `${Number(value ?? 0).toLocaleString("id-ID", { maximumFractionDigits: 2 })}%`;
}
</script>

<template>
  <section
    aria-label="Deviasi WBS terbesar"
    class="flex flex-col overflow-hidden rounded-2xl border-2 border-outline-variant bg-surface-lowest shadow-sm"
  >
    <header
      class="border-b border-outline-variant bg-primary-50 px-4 py-3 text-on-surface"
    >
      <h3 class="text-title-md">Deviasi Terbesar</h3>
    </header>
    <ol
      v-if="worstItems.length"
      class="space-y-2 p-3"
    >
      <li
        v-for="item in worstItems"
        :key="item.wbs_no"
        class="rounded-2xl border-2 border-outline-variant bg-surface p-3 transition-shadow hover:shadow-sm"
      >
        <div class="flex items-start justify-between gap-2">
          <div class="min-w-0">
            <p class="text-body-sm font-medium text-on-surface-variant">
              {{ item.wbs_no }}
            </p>
            <p
              class="truncate text-body-sm font-semibold text-on-surface"
              :title="item.activity_name"
            >
              {{ item.activity_name }}
            </p>
          </div>
          <strong
            class="shrink-0 rounded px-2 py-1 text-xs tabular-nums"
            :class="
              item.deviation_percent < 0
                ? 'bg-error-container text-error'
                : 'bg-emerald-100 text-emerald-800'
            "
          >
            {{ item.deviation_percent > 0 ? "+" : ""
            }}{{ formatPercent(item.deviation_percent) }}
          </strong>
        </div>
        <div class="mt-3 flex items-center justify-between gap-2 text-xs">
          <span class="text-on-surface-variant">Progres aktual</span>
          <span class="font-semibold tabular-nums text-on-surface">{{
            formatPercent(item.latest_progress_percent)
          }}</span>
        </div>
        <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-surface">
          <div
            class="h-full rounded-full bg-emerald-500"
            :style="{
              width: `${Math.min(100, Math.max(0, item.latest_progress_percent))}%`,
            }"
          />
        </div>
        <div class="mt-2 flex justify-between gap-2 text-xs tabular-nums">
          <span class="text-on-surface-variant">Rencana</span>
          <span class="font-medium text-on-surface-variant">{{
            formatPercent(item.planned_percent)
          }}</span>
        </div>
      </li>
    </ol>
    <p v-else class="p-4 text-sm text-on-surface-variant">
      Belum ada deviasi untuk ditampilkan.
    </p>
  </section>
</template>
