<script setup lang="ts">
import { computed } from "vue";

interface DeviationItem {
  wbs_no: string;
  activity_name: string;
  bobot_real: number;
  bobot_plan: number;
}

const props = defineProps<{
  items: DeviationItem[];
}>();

const worstItems = computed(() =>
  [...props.items]
    .map((item) => ({
      ...item,
      deviation: Number(item.bobot_real) - Number(item.bobot_plan),
    }))
    .filter((item) => Math.abs(item.deviation) > 0.00005)
    .sort((left, right) => Math.abs(right.deviation) - Math.abs(left.deviation))
    .slice(0, 5),
);

function formatWeight(value: number): string {
  return `${Number(value ?? 0).toLocaleString("id-ID", { maximumFractionDigits: 4 })}%`;
}
</script>

<template>
  <section
    aria-label="Deviasi WBS terbesar"
    class="flex flex-col overflow-hidden rounded-2xl bg-surface-container-lowest transition-all"
  >
    <header
      class="flex items-center justify-between gap-3 bg-surface-container-low px-5 py-4"
    >
      <h2 class="text-title-md font-semibold text-on-surface">
        Deviasi Terbesar
      </h2>
    </header>
    <ol v-if="worstItems.length" class="space-y-4 p-4">
      <li
        v-for="item in worstItems"
        :key="item.wbs_no"
        class="group relative flex flex-col gap-3 rounded-2xl bg-surface-container-lowest p-4 transition-all duration-200 hover:bg-surface-container-lowest/90"
      >
        <!-- Top Row: WBS, Activity Name, & Deviation Badge -->
        <div class="flex items-start justify-between gap-3">
          <div class="space-y-1 min-w-0 flex-1">
            <span
              class="text-xs font-bold tracking-wider text-primary uppercase"
            >
              WBS {{ item.wbs_no }}
            </span>
            <p
              class="font-semibold text-on-surface text-sm leading-snug line-clamp-2"
              :title="item.activity_name"
            >
              {{ item.activity_name }}
            </p>
          </div>
          <span
            class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold tabular-nums"
            :class="
              item.deviation < 0
                ? 'bg-error-container text-error'
                : 'bg-emerald-100 text-emerald-800'
            "
          >
            {{ item.deviation > 0 ? "+" : ""
            }}{{ formatWeight(item.deviation) }}
          </span>
        </div>

        <!-- Divider Line -->
        <div class="border-b border-surface-container-low"></div>

        <!-- Bottom Row: Horizontal Weights (Bobot Real & Bobot Plan) -->
        <div
          class="flex items-center justify-between text-xs tabular-nums text-on-surface-variant"
        >
          <div>
            <span>Bobot Real: </span>
            <strong class="font-semibold text-on-surface">{{
              formatWeight(item.bobot_real)
            }}</strong>
          </div>
          <div>
            <span>Bobot Plan: </span>
            <strong class="font-semibold text-on-surface">{{
              formatWeight(item.bobot_plan)
            }}</strong>
          </div>
        </div>
      </li>
    </ol>
    <p v-else class="p-6 text-center text-sm text-on-surface-variant italic">
      Belum ada deviasi untuk ditampilkan.
    </p>
  </section>
</template>
