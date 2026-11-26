<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref } from "vue";
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
  expanded?: boolean;
}>();

const emit = defineEmits<{
  toggle: [wbsNo: string];
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
const activeTooltipIndex = ref<number | null>(null);
const tooltipElement = ref<HTMLElement | null>(null);
const tooltipPosition = ref({ left: 0, top: 0 });
let clockInterval: ReturnType<typeof setInterval> | undefined;
let tooltipTimer: ReturnType<typeof setTimeout> | undefined;

onMounted(() => {
  clockInterval = setInterval(() => {
    clockTick.value += 1;
  }, 60_000);
});

onUnmounted(() => {
  if (clockInterval !== undefined) clearInterval(clockInterval);
  if (tooltipTimer !== undefined) clearTimeout(tooltipTimer);
});

const timelinePeriods = computed(() => {
  clockTick.value;
  const periods = [...props.periods];
  if (periods.length === 0) return periods;

  const isContinuous = periods.every(
    (period, index) =>
      index === 0 ||
      (period.ordinal ??
        period.year * 48 + (period.month - 1) * 4 + period.week - 1) ===
        (periods[index - 1].ordinal ??
          periods[index - 1].year * 48 +
            (periods[index - 1].month - 1) * 4 +
            periods[index - 1].week -
            1) +
          1,
  );
  if (!isContinuous) return periods;

  const currentOrdinal = currentJakartaWeekOrdinal();
  let lastOrdinal =
    (periods[periods.length - 1].ordinal ??
      periods[periods.length - 1].year * 48 +
        (periods[periods.length - 1].month - 1) * 4 +
        periods[periods.length - 1].week -
        1) + 1;

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

const gridWidth = computed(
  () => `${timelinePeriods.value.length * trackWidth}px`,
);
const gridColumns = computed(() =>
  timelinePeriods.value.length > 0
    ? `repeat(${timelinePeriods.value.length}, ${trackWidth}px)`
    : "none",
);

const monthGroups = computed(() => {
  const groups: Array<{
    month: number;
    year: number;
    label: string;
    span: number;
  }> = [];

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
    (period) =>
      (period.ordinal ??
        period.year * 48 + (period.month - 1) * 4 + period.week - 1) +
        1 ===
      currentJakartaWeekOrdinal(),
  );
});

const toneClasses: Record<WbsBarSegment["tone"], string> = {
  plan: "border border-slate-200 bg-blue-200 rounded-lg",
  delay: "border border-red-100 bg-red-200 rounded-lg",
  actual: "border border-blue-200 bg-blue-600 rounded-lg",
  critical: "border border-red-200 bg-red-700 rounded-lg",
  parent: "border border-slate-300 bg-slate-600 rounded-lg",
};

function segmentTop(segment: WbsBarSegment): string {
  if (segment.kind === "parent") return "24px";
  if (segment.kind === "plan" || segment.kind === "delay") return "18px";
  return "22px";
}

function segmentClass(segment: WbsBarSegment, index: number): string[] {
  const classes = [
    "absolute rounded-md transition-all",
    toneClasses[segment.tone],
  ];

  if (segment.kind === "parent") {
    classes.push("z-20 h-2");
  } else if (segment.kind === "plan") {
    classes.push("z-10 h-5");
  } else if (segment.kind === "delay") {
    classes.push("z-20 h-5 rounded-lg");
  } else {
    classes.push("z-30 h-3");
  }

  const isScheduleSegment =
    segment.kind === "plan" || segment.kind === "delay";
  const isActualSegment =
    segment.kind === "actual" || segment.kind === "critical";
  const sharesTrack = (candidate: WbsBarSegment) =>
    isScheduleSegment
      ? candidate.kind === "plan" || candidate.kind === "delay"
      : isActualSegment &&
        (candidate.kind === "actual" || candidate.kind === "critical");

  const joinsPrevious = segments.value.some(
    (candidate, candidateIndex) =>
      candidateIndex !== index &&
      sharesTrack(candidate) &&
      candidate.endOrdinal + 1 === segment.startOrdinal,
  );
  const joinsNext = segments.value.some(
    (candidate, candidateIndex) =>
      candidateIndex !== index &&
      sharesTrack(candidate) &&
      segment.endOrdinal + 1 === candidate.startOrdinal,
  );

  if (joinsPrevious) classes.push("rounded-l-none border-l-0");
  if (joinsNext) classes.push("rounded-r-none border-r-0");

  return classes;
}

function formatOrdinal(ordinal: number): string {
  const year = Math.floor((ordinal - 1) / 48);
  const weekOfYear = (ordinal - 1) % 48;
  return `${monthNames[Math.floor(weekOfYear / 4)]} W${(weekOfYear % 4) + 1} ${year}`;
}

async function positionTooltip(
  target: HTMLElement,
  pointerX: number,
  pointerY: number,
): Promise<void> {
  await nextTick();
  if (!tooltipElement.value) return;

  const tooltipRect = tooltipElement.value.getBoundingClientRect();
  const tableHeaderBottom =
    target
      .closest(".wbs-table-scroll")
      ?.querySelector("thead")
      ?.getBoundingClientRect().bottom ?? 0;
  const gap = 12;
  const preferredLeft = pointerX + gap;
  const left =
    preferredLeft + tooltipRect.width <= window.innerWidth - 8
      ? preferredLeft
      : pointerX - tooltipRect.width - gap;
  const aboveTop = pointerY - tooltipRect.height - gap;
  const top = aboveTop >= tableHeaderBottom + 8 ? aboveTop : pointerY + gap;

  tooltipPosition.value = {
    left: Math.max(
      8,
      Math.min(left, window.innerWidth - tooltipRect.width - 8),
    ),
    top: Math.max(
      8,
      Math.min(top, window.innerHeight - tooltipRect.height - 8),
    ),
  };
}

function showTooltip(index: number, event: MouseEvent): void {
  if (tooltipTimer !== undefined) clearTimeout(tooltipTimer);
  const target = event.currentTarget;
  if (!(target instanceof HTMLElement)) return;
  const { clientX, clientY } = event;

  tooltipTimer = setTimeout(() => {
    activeTooltipIndex.value = index;
    void positionTooltip(target, clientX, clientY);
  }, 200);
}

function hideTooltip(): void {
  if (tooltipTimer !== undefined) clearTimeout(tooltipTimer);
  activeTooltipIndex.value = null;
}

function progressLabel(): string {
  const progressEntries = [...(props.progressValues ?? [])]
    .filter((value) => value.type === "progress")
    .sort((left, right) => {
      const leftOrdinal = left.year * 48 + (left.month - 1) * 4 + left.week;
      const rightOrdinal = right.year * 48 + (right.month - 1) * 4 + right.week;
      return leftOrdinal - rightOrdinal;
    });
  const latestProgress = progressEntries[progressEntries.length - 1]?.value;
  const progress = latestProgress ?? props.item?.latest_progress_percent;
  return progress === null || progress === undefined ? "-" : `${progress}%`;
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
  </th>

  <td
    v-else
    :style="{ width: gridWidth, minWidth: gridWidth }"
    class="relative z-0 h-14 border-b border-l border-slate-100 p-0 overflow-visible"
  >
    <div
      class="relative isolate h-14 overflow-hidden"
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
          :class="segmentClass(segment, index)"
          :style="{
            gridColumn: `${segment.startColumn} / span ${segment.columnSpan}`,
            top: segmentTop(segment),
            left: '0',
            right: '0',
          }"
          :aria-label="segment.tooltip"
          @mouseenter="showTooltip(index, $event)"
          @mouseleave="hideTooltip"
        />
      </div>
    </div>

    <Teleport to="body">
      <div
        v-if="
          activeTooltipIndex !== null && item && segments[activeTooltipIndex]
        "
        ref="tooltipElement"
        class="pointer-events-none fixed z-[9999] w-max max-w-64 rounded-lg border border-slate-200 bg-white px-3 py-2 text-left text-[11px] leading-4 text-slate-700 shadow-lg"
        :style="{
          left: `${tooltipPosition.left}px`,
          top: `${tooltipPosition.top}px`,
        }"
        role="tooltip"
      >
        <p class="mb-1 max-w-60 truncate font-semibold text-slate-900">
          {{ item.activity_name ?? item.wbs_no }}
        </p>
        <p>
          Mulai: {{ formatOrdinal(segments[activeTooltipIndex].startOrdinal) }}
        </p>
        <p>
          Selesai: {{ formatOrdinal(segments[activeTooltipIndex].endOrdinal) }}
        </p>
        <p>Progres: {{ progressLabel() }}</p>
      </div>
    </Teleport>
  </td>
</template>
