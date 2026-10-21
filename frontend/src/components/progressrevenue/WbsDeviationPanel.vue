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
    class="flex flex-col overflow-hidden rounded-2xl border border-outline-variant bg-surface-lowest shadow-sm"
  >
    <header
      class="flex items-center justify-between gap-3 bg-surface-container-low px-5 py-4"
    >
      <h2 class="text-title-md font-semibold text-on-surface">
        Deviasi Terbesar
      </h2>
    </header>
    <ol v-if="worstItems.length" class="space-y-3 p-4">
      <li
        v-for="item in worstItems"
        :key="item.wbs_no"
        class="rounded-xl border border-outline-variant/60 bg-surface p-4 transition-all hover:border-primary/40 hover:shadow-md"
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
              item.deviation < 0
                ? 'bg-error-container text-error'
                : 'bg-emerald-100 text-emerald-800'
            "
          >
            Deviasi {{ item.deviation > 0 ? "+" : "" }}{{
              formatWeight(item.deviation)
            }}
          </strong>
        </div>
        <div class="mt-3 grid grid-cols-2 gap-3 border-t border-outline-variant/40 pt-3 text-xs tabular-nums">
          <div class="flex flex-col gap-1">
            <span class="text-on-surface-variant">Bobot Real</span>
            <span class="font-semibold text-on-surface">{{
              formatWeight(item.bobot_real)
            }}</span>
          </div>
          <div class="flex flex-col gap-1 text-right">
            <span class="text-on-surface-variant">Bobot Plan</span>
            <span class="font-semibold text-on-surface">{{
              formatWeight(item.bobot_plan)
            }}</span>
          </div>
        </div>
      </li>
    </ol>
    <p v-else class="p-4 text-sm text-on-surface-variant">
      Belum ada deviasi untuk ditampilkan.
    </p>
  </section>
</template>
