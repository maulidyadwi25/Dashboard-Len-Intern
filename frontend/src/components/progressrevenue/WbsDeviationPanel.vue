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
  return `${Number(value ?? 0).toLocaleString("id-ID", { maximumFractionDigits: 0 })}%`;
}

function barWidth(value: number): string {
  const weight = Number(value ?? 0);
  return `${Number.isFinite(weight) ? Math.min(100, Math.max(0, weight)) : 0}%`;
}
</script>

<template>
  <section
    aria-label="Deviasi WBS terbesar"
    class="flex min-h-[30rem] max-h-[70vh] flex-col overflow-hidden rounded-2xl bg-surface-container-lowest transition-all"
  >
    <header
      class="flex items-center justify-between gap-3 bg-surface-container-low px-5 py-4"
    >
      <h2 class="text-title-md font-semibold text-on-surface">
        Deviasi Terbesar
      </h2>
    </header>
    <ol
      v-if="worstItems.length"
      class="min-h-0 flex-1 space-y-3 overflow-y-auto p-4"
    >
      <li
        v-for="item in worstItems"
        :key="item.wbs_no"
        class="group rounded-2xl bg-surface-container-low p-4 transition-all duration-200 hover:bg-surface-container/50 hover:translate-x-0.5"
      >
        <div class="flex items-start gap-3.5">
          <span
            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-primary text-xs font-bold text-on-primary"
          >
            {{ worstItems.indexOf(item) + 1 }}
          </span>
          <div class="min-w-0 flex-1 space-y-3">
            <div class="flex flex-wrap items-start justify-between gap-2">
              <div class="min-w-0 space-y-1">
                <span
                  class="inline-block text-xs font-bold tracking-wider text-primary uppercase"
                >
                  WBS {{ item.wbs_no }}
                </span>
                <h3
                  class="font-semibold leading-snug text-on-surface"
                  :title="item.activity_name"
                >
                  {{ item.activity_name }}
                </h3>
              </div>
              <span
                class="shrink-0 rounded-md px-2 py-0.5 text-xs font-semibold tabular-nums"
                :class="
                  item.deviation < 0
                    ? 'bg-error-container text-error'
                    : 'bg-primary/10 text-primary'
                "
              >
                Deviasi {{ item.deviation > 0 ? "+" : ""
                }}{{ formatWeight(item.deviation) }}
              </span>
            </div>

            <div class="space-y-2.5 border-t border-outline-variant/40 pt-3">
              <div class="space-y-1">
                <div class="flex justify-between gap-3 text-xs">
                  <span class="text-on-surface-variant">Bobot Real</span>
                  <span class="font-semibold tabular-nums text-on-surface">{{
                    formatWeight(item.bobot_real)
                  }}</span>
                </div>
                <div
                  class="h-2 w-full overflow-hidden rounded-full bg-surface-container"
                >
                  <div
                    role="progressbar"
                    :aria-label="`Bobot real WBS ${item.wbs_no}`"
                    :aria-valuenow="
                      Math.min(100, Math.max(0, Number(item.bobot_real) || 0))
                    "
                    aria-valuemin="0"
                    aria-valuemax="100"
                    class="h-full rounded-full bg-primary transition-all duration-500"
                    :style="{ width: barWidth(item.bobot_real) }"
                  />
                </div>
              </div>
              <div class="space-y-1">
                <div class="flex justify-between gap-3 text-xs">
                  <span class="text-on-surface-variant">Bobot Plan</span>
                  <span class="font-semibold tabular-nums text-on-surface">{{
                    formatWeight(item.bobot_plan)
                  }}</span>
                </div>
                <div
                  class="h-2 w-full overflow-hidden rounded-full bg-surface-container"
                >
                  <div
                    role="progressbar"
                    :aria-label="`Bobot plan WBS ${item.wbs_no}`"
                    :aria-valuenow="
                      Math.min(100, Math.max(0, Number(item.bobot_plan) || 0))
                    "
                    aria-valuemin="0"
                    aria-valuemax="100"
                    class="h-full rounded-full bg-secondary transition-all duration-500"
                    :style="{ width: barWidth(item.bobot_plan) }"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>
      </li>
    </ol>
    <p v-else class="p-6 text-center text-sm text-on-surface-variant italic">
      Belum ada deviasi untuk ditampilkan.
    </p>
  </section>
</template>
