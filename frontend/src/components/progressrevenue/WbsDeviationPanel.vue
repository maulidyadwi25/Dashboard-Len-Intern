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
    class="rounded-md border border-slate-200 bg-white"
  >
    <div class="border-b border-slate-200 px-4 py-3">
      <h3 class="text-sm font-semibold text-slate-900">Deviasi Terbesar</h3>
    </div>
    <ol v-if="worstItems.length" class="divide-y divide-slate-100">
      <li
        v-for="item in worstItems"
        :key="item.wbs_no"
        class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 px-4 py-3"
      >
        <div class="min-w-0">
          <p class="text-xs font-medium text-slate-500">{{ item.wbs_no }}</p>
          <p
            class="truncate text-sm font-medium text-slate-800"
            :title="item.activity_name"
          >
            {{ item.activity_name }}
          </p>
        </div>
        <div class="flex shrink-0 items-center gap-4 text-xs tabular-nums">
          <span class="text-slate-500"
            >Rencana {{ formatPercent(item.planned_percent) }}</span
          >
          <span class="text-slate-500"
            >Aktual {{ formatPercent(item.latest_progress_percent) }}</span
          >
          <strong
            :class="
              item.deviation_percent < 0 ? 'text-rose-700' : 'text-emerald-700'
            "
          >
            {{ item.deviation_percent > 0 ? "+" : ""
            }}{{ formatPercent(item.deviation_percent) }}
          </strong>
        </div>
      </li>
    </ol>
    <p v-else class="px-4 py-4 text-sm text-slate-500">
      Belum ada deviasi untuk ditampilkan.
    </p>
  </section>
</template>
