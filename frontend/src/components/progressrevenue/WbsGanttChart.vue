<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from "vue";
import {
  buildWbsBarLayout,
  currentJakartaWeekOrdinal,
  type WbsBarSegment,
  type WbsPeriod,
  type WbsTreeItem,
  type WbsWeeklyValue,
} from "../../utils/wbs";

const props = defineProps<{
  mode: "header" | "row";
  periods: WbsPeriod[];
  item?: WbsTreeItem;
  progressValues?: WbsWeeklyValue[];
}>();

const monthNames = [
  "Januari",
  "Februari",
  "Maret",
  "April",
  "Mei",
  "Juni",
  "Juli",
  "Agustus",
  "September",
  "Oktober",
  "November",
  "Desember",
];
const trackWidth = 36;
const clockTick = ref(0);
let clockInterval: ReturnType<typeof setInterval> | undefined;

onMounted(() => {
  clockInterval = setInterval(() => {
    clockTick.value += 1;
  }, 60_000);
});

onUnmounted(() => {
  if (clockInterval !== undefined) clearInterval(clockInterval);
});

const timelinePeriods = computed(() => {
  clockTick.value;
  const periods = [...props.periods];
  if (periods.length === 0) return periods;

  const isContinuous = periods.every((period, index) =>
    index === 0 ||
    (period.ordinal ?? (period.year * 48 + (period.month - 1) * 4 + period.week - 1)) ===
      (periods[index - 1].ordinal ?? (periods[index - 1].year * 48 + (periods[index - 1].month - 1) * 4 + periods[index - 1].week - 1)) + 1,
  );
  if (!isContinuous) return periods;

  const currentOrdinal = currentJakartaWeekOrdinal();
  let lastOrdinal = (periods[periods.length - 1].ordinal ?? (periods[periods.length - 1].year * 48 + (periods[periods.length - 1].month - 1) * 4 + periods[periods.length - 1].week - 1)) + 1;

  while (lastOrdinal < currentOrdinal) {
    const ordinal = lastOrdinal + 1;
    const year = Math.floor((ordinal - 1) / 48);
    const weekOfYear = (ordinal - 1) % 48;
    const month = Math.floor(weekOfYear / 4) + 1;
    const week = (weekOfYear % 4) + 1;
    periods.push({
      year,
      month,
      week,
      label: `${monthNames[month - 1]} W${week}`,
      key: `${year}-${month}-${week}`,
      ordinal: ordinal - 1,
    });
    lastOrdinal = ordinal;
  }

  return periods;
});

const gridWidth = computed(() => `${timelinePeriods.value.length * trackWidth}px`);
const gridColumns = computed(() =>
  timelinePeriods.value.length > 0
    ? `repeat(${timelinePeriods.value.length}, ${trackWidth}px)`
    : "none",
);

const monthGroups = computed(() => {
  const groups: Array<{ month: number; year: number; label: string; span: number }> = [];

  for (const period of timelinePeriods.value) {
    const last = groups[groups.length - 1];
    if (last?.month === period.month && last.year === period.year) {
      last.span += 1;
    } else {
      groups.push({
        month: period.month,
        year: period.year,
        label: `${monthNames[period.month - 1] ?? String(period.month)} ${period.year}`,
        span: 1,
      });
    }
  }

  return groups;
});

const segments = computed(() => {
  clockTick.value;
  if (props.mode !== "row" || !props.item) return [];
  return buildWbsBarLayout(
    props.item,
    timelinePeriods.value,
    props.progressValues ?? [],
  );
});

const currentWeekColumn = computed(() => {
  clockTick.value;
  return timelinePeriods.value.findIndex(
    (period) => (period.ordinal ?? (period.year * 48 + (period.month - 1) * 4 + period.week - 1)) + 1 === currentJakartaWeekOrdinal(),
  );
});

const toneClasses: Record<WbsBarSegment["tone"], string> = {
  plan: "bg-blue-200",
  actual: "bg-blue-600",
  critical: "bg-red-700",
  parent: "bg-slate-500",
};

function segmentTop(segment: WbsBarSegment): string {
  if (segment.kind === "plan") return "14px";
  if (segment.kind === "parent") return "27px";
  return "42px";
}
</script>

<template>
  <th
    v-if="mode === 'header'"
    :style="{ width: gridWidth, minWidth: gridWidth }"
    class="sticky top-0 z-30 border-b border-l border-slate-200 bg-primary p-0 align-top overflow-hidden"
    aria-label="Gantt mingguan"
  >
    <div class="text-xs font-semibold text-white">
      <div
        class="grid h-7 border-b border-slate-200"
        :style="{ gridTemplateColumns: gridColumns }"
      >
        <div
          v-for="group in monthGroups"
          :key="group.month"
          class="flex items-center justify-center border-r border-slate-200"
          :style="{ gridColumn: `span ${group.span}` }"
        >
          {{ group.label }}
        </div>
      </div>
      <div class="grid h-7" :style="{ gridTemplateColumns: gridColumns }">
        <div
          v-for="period in timelinePeriods"
          :key="period.key"
          class="flex items-center justify-center border-r border-slate-200 text-[10px]"
        >
          W{{ period.week }}
        </div>
      </div>
    </div>
    <span
      v-if="currentWeekColumn >= 0"
      class="pointer-events-none absolute bottom-0 top-0 z-10 w-px bg-rose-500"
      :style="{ left: `${currentWeekColumn * trackWidth + trackWidth / 2}px` }"
      aria-hidden="true"
    />
  </th>

  <td
    v-else
    :style="{ width: gridWidth, minWidth: gridWidth }"
    class="relative z-0 h-16 border-b border-l border-slate-100 p-0 overflow-hidden"
  >
    <div
      class="relative isolate h-16 overflow-hidden"
      :style="{ width: gridWidth }"
    >
      <div
        class="absolute inset-0 grid"
        :style="{ gridTemplateColumns: gridColumns }"
        aria-hidden="true"
      >
        <span
          v-for="period in timelinePeriods"
          :key="period.key"
          class="border-r border-slate-100"
        />
      </div>
      <span
        v-if="currentWeekColumn >= 0"
        class="pointer-events-none absolute bottom-0 top-0 z-10 w-px bg-rose-500/80"
        :style="{
          left: `${currentWeekColumn * trackWidth + trackWidth / 2}px`,
        }"
        aria-hidden="true"
      />
      <div
        class="absolute inset-0 grid"
        :style="{ gridTemplateColumns: gridColumns }"
      >
        <div
          v-for="(segment, index) in segments"
          :key="`${segment.kind}-${index}`"
          class="absolute z-20 h-2.5 rounded-full"
          :class="toneClasses[segment.tone]"
          :style="{
            gridColumn: `${segment.startColumn} / span ${segment.columnSpan}`,
            top: segmentTop(segment),
            left: '2px',
            right: '2px',
          }"
          :title="segment.tooltip"
          :aria-label="segment.tooltip"
        />
      </div>
    </div>
  </td>
</template>
