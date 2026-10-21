export type WbsStatus = "not_started" | "on_track" | "overdue" | "critical";
export type WbsWeeklyType = "plan" | "actual" | "progress";

export interface WbsItem {
  id: number | string;
  wbs_no: string;
  parent_wbs_no: string | null;
  level: number;
  latest_progress_percent: number | null;
  progress_sum: number | null;
  bobot_plan: number;
  bobot_real: number;
  actual_start_week: number | null;
  actual_finish_week: number | null;
  actual_start_ordinal?: number | null;
  actual_finish_ordinal?: number | null;
  actual_progress_start_ordinal?: number | null;
  actual_progress_finish_ordinal?: number | null;
  plan_start_ordinal?: number | null;
  plan_end_ordinal?: number | null;
  status: WbsStatus | null;
  plan_start_period: string | null;
  plan_end_period: string | null;
  durasi: string | null;
  [key: string]: unknown;
}

export interface WbsTreeItem extends WbsItem {
  children: WbsTreeItem[];
}

export interface WbsWeeklyValue {
  wbs_no: string;
  type: WbsWeeklyType;
  year: number;
  month: number;
  week: number;
  period_label: string;
  value: number | null;
}

export type WbsWeeklyByWbs = Record<
  string,
  Partial<Record<WbsWeeklyType, WbsWeeklyValue[]>>
>;

export interface WbsPeriod {
  year: number;
  month: number;
  week: number;
  label: string;
  key: string;
  ordinal?: number;
}

export interface WbsBarSegment {
  kind: "plan" | "actual" | "extension" | "parent";
  tone: "plan" | "actual" | "critical" | "parent";
  startOrdinal: number;
  endOrdinal: number;
  startColumn: number;
  columnSpan: number;
  tooltip: string;
}

export interface VisibleWbsItem {
  item: WbsTreeItem;
  depth: number;
}

const MONTHS = [
  "jan",
  "feb",
  "mar",
  "apr",
  "may",
  "jun",
  "jul",
  "aug",
  "sep",
  "oct",
  "nov",
  "dec",
];

export function buildTree(items: WbsItem[]): WbsTreeItem[] {
  const nodes = new Map<string, WbsTreeItem>();

  for (const item of items) {
    nodes.set(item.wbs_no, { ...item, children: [] });
  }

  const roots: WbsTreeItem[] = [];

  for (const item of items) {
    const node = nodes.get(item.wbs_no)!;
    const parent = item.parent_wbs_no
      ? nodes.get(item.parent_wbs_no)
      : undefined;

    if (parent) {
      parent.children.push(node);
    } else {
      roots.push(node);
    }
  }

  return roots;
}

export function flattenVisible(
  tree: WbsTreeItem[],
  expanded: Record<string, boolean>,
): VisibleWbsItem[] {
  const visible: VisibleWbsItem[] = [];

  function visit(nodes: WbsTreeItem[], depth: number): void {
    for (const item of nodes) {
      visible.push({ item, depth });

      if (item.children.length > 0 && expanded[item.wbs_no]) {
        visit(item.children, depth + 1);
      }
    }
  }

  visit(tree, 0);
  return visible;
}

export function mapWeeklyByWbs(values: WbsWeeklyValue[]): WbsWeeklyByWbs {
  const result: WbsWeeklyByWbs = {};

  for (const value of values) {
    const byType = (result[value.wbs_no] ??= {});
    (byType[value.type] ??= []).push(value);
  }

  return result;
}

export function weekOrdinal(
  period: string | null | undefined,
  year = 0,
): number | null {
  if (!period) return null;

  const match = period.trim().match(/^([a-z]{3})\s+w([1-4])$/i);
  if (!match) return null;

  const monthIndex = MONTHS.indexOf(match[1].toLowerCase());
  if (monthIndex < 0) return null;

  return year * 48 + monthIndex * 4 + Number(match[2]);
}

export function currentJakartaWeekOrdinal(date: Date = new Date()): number {
  const parts = new Intl.DateTimeFormat("en-US", {
    timeZone: "Asia/Jakarta",
    year: "numeric",
    month: "numeric",
    day: "numeric",
  }).formatToParts(date);
  const year = Number(parts.find((part) => part.type === "year")?.value);
  const month = Number(parts.find((part) => part.type === "month")?.value);
  const day = Number(parts.find((part) => part.type === "day")?.value);
  const week = day <= 10 ? 1 : day <= 17 ? 2 : day <= 24 ? 3 : 4;

  return year * 48 + (month - 1) * 4 + week;
}

function periodLabel(ordinal: number): string {
  const zeroBased = (ordinal - 1) % 48;
  const year = Math.floor((ordinal - 1) / 48);
  return `${MONTHS[Math.floor(zeroBased / 4)]} W${(zeroBased % 4) + 1} ${year}`;
}

function clipToVisiblePeriods(
  startOrdinal: number,
  endOrdinal: number,
  periods: WbsPeriod[],
): { startColumn: number; columnSpan: number } | null {
  const visibleColumns = periods
    .map((period, index) => ({ index, ordinal: (period.ordinal ?? (period.year * 48 + (period.month - 1) * 4 + period.week - 1)) + 1 }))
    .filter(
      (period): period is { index: number; ordinal: number } =>
        period.ordinal !== null &&
        period.ordinal >= startOrdinal &&
        period.ordinal <= endOrdinal,
    );

  if (visibleColumns.length === 0) return null;

  return {
    startColumn: visibleColumns[0].index + 1,
    columnSpan: visibleColumns.length,
  };
}

function progressEntriesFor(
  item: WbsTreeItem,
  weeklyValues: WbsWeeklyValue[],
): Array<{ ordinal: number; value: number; label: string }> {
  return weeklyValues
    .filter(
      (value) => value.wbs_no === item.wbs_no && value.type === "progress",
    )
    .map((value) => ({
      ordinal: weekOrdinal(value.period_label, value.year),
      value: value.value ?? 0,
      label: value.period_label,
    }))
    .filter(
      (value): value is { ordinal: number; value: number; label: string } =>
        value.ordinal !== null,
    )
    .sort((left, right) => left.ordinal - right.ordinal);
}

function appendSegment(
  segments: WbsBarSegment[],
  kind: WbsBarSegment["kind"],
  tone: WbsBarSegment["tone"],
  startOrdinal: number,
  endOrdinal: number,
  periods: WbsPeriod[],
  tooltip: string,
): void {
  if (startOrdinal > endOrdinal) return;
  const columns = clipToVisiblePeriods(startOrdinal, endOrdinal, periods);
  if (!columns) return;

  segments.push({
    kind,
    tone,
    startOrdinal,
    endOrdinal,
    ...columns,
    tooltip,
  });
}

function leafRanges(
  item: WbsTreeItem,
  weeklyValues: WbsWeeklyValue[],
): Array<{ start: number; finish: number }> {
  const progressEntries = progressEntriesFor(item, weeklyValues);
  const progressStart =
    item.actual_progress_start_ordinal ?? progressEntries[0]?.ordinal ?? null;
  if (progressStart === null) return [];

  const latestProgress = progressEntries[progressEntries.length - 1];
  const progressFinish =
    item.actual_progress_finish_ordinal ??
    (item.latest_progress_percent !== null && item.latest_progress_percent >= 100
      ? latestProgress?.ordinal ?? null
      : null);

  return [{
    start: progressStart,
    finish: progressFinish ?? currentJakartaWeekOrdinal(),
  }];
}

function descendantLeaves(item: WbsTreeItem): WbsTreeItem[] {
  if (item.children.length === 0) return [item];
  return item.children.flatMap(descendantLeaves);
}

export function buildWbsBarLayout(
  item: WbsTreeItem,
  periods: WbsPeriod[],
  weeklyValues: WbsWeeklyValue[],
): WbsBarSegment[] {
  if (periods.length === 0) return [];

  if (item.children.length > 0) {
    const leaves = descendantLeaves(item);
    const segments: WbsBarSegment[] = [];
    const planRanges = leaves
      .map((leaf) => ({
        start: leaf.plan_start_ordinal ?? null,
        finish: leaf.plan_end_ordinal ?? null,
      }))
      .filter(
        (range): range is { start: number; finish: number } =>
          range.start !== null && range.finish !== null,
      );
    if (planRanges.length > 0) {
      const planStart = Math.min(...planRanges.map((range) => range.start));
      const planFinish = Math.max(...planRanges.map((range) => range.finish));
      appendSegment(
        segments,
        "plan",
        "plan",
        planStart,
        planFinish,
        periods,
        `Plan: ${periodLabel(planStart)} - ${periodLabel(planFinish)}`,
      );
    }

    const actualRanges = leaves.flatMap((leaf) => leafRanges(leaf, weeklyValues));
    if (actualRanges.length > 0) {
      const actualStart = Math.min(...actualRanges.map((range) => range.start));
      const allLeavesComplete = leaves.every((leaf) => {
        const leafEntries = progressEntriesFor(leaf, weeklyValues);
        return (
          leaf.actual_progress_finish_ordinal !== null &&
          leaf.actual_progress_finish_ordinal !== undefined
        ) ||
          (leaf.latest_progress_percent !== null &&
            leaf.latest_progress_percent >= 100) ||
          (leafEntries.length > 0 && leafEntries[leafEntries.length - 1].value >= 100);
      });
      const actualFinish = allLeavesComplete
        ? Math.max(...actualRanges.map((range) => range.finish))
        : currentJakartaWeekOrdinal();
      appendSegment(
        segments,
        "parent",
        "parent",
        actualStart,
        actualFinish,
        periods,
        `Actual: ${periodLabel(actualStart)} - ${periodLabel(actualFinish)}`,
      );
    }
    return segments;
  }

  const segments: WbsBarSegment[] = [];
  const progressEntries = progressEntriesFor(item, weeklyValues);
  const progressStart =
    item.actual_progress_start_ordinal ?? progressEntries[0]?.ordinal ?? null;
  const latestProgress = progressEntries[progressEntries.length - 1];
  const planStart = item.plan_start_ordinal ?? null;
  const planFinish = item.plan_end_ordinal ?? null;
  const actualStart = progressStart ?? item.actual_start_ordinal ?? null;
  const actualFinish =
    item.actual_finish_ordinal ?? latestProgress?.ordinal ?? null;
  const progressLabel = latestProgress
    ? `Progres: ${latestProgress.value}% (${latestProgress.label})`
    : item.latest_progress_percent === null
      ? "Progres: -"
      : `Progres: ${item.latest_progress_percent}%`;
  const rangeLabel = (start: number, finish: number) =>
    `${periodLabel(start)} - ${periodLabel(finish)}`;

  if (planStart !== null && planFinish !== null) {
    appendSegment(
      segments,
      "plan",
      "plan",
      planStart,
      planFinish,
      periods,
      `Plan: ${rangeLabel(planStart, planFinish)}`,
    );
  }

  if (actualStart === null || actualFinish === null) return segments;

  const status = item.status ?? "on_track";
  const startsLate = planFinish !== null && actualStart > planFinish;
  const lateTone = status === "overdue" || status === "critical" || startsLate
    ? "critical"
    : "actual";
  const actualTooltip = `Actual: ${rangeLabel(actualStart, actualFinish)} · ${progressLabel}`;

  if (planFinish !== null && actualFinish > planFinish) {
    appendSegment(
      segments,
      "actual",
      startsLate ? "critical" : "actual",
      actualStart,
      Math.min(actualFinish, planFinish),
      periods,
      actualTooltip,
    );
    appendSegment(
      segments,
      "extension",
      lateTone,
      Math.max(actualStart, planFinish + 1),
      actualFinish,
      periods,
      actualTooltip,
    );
  } else {
    appendSegment(
      segments,
      "actual",
      "actual",
      actualStart,
      actualFinish,
      periods,
      actualTooltip,
    );
  }

  return segments;
}

export function formatStatus(
  status: WbsStatus | null | undefined,
): { label: string; className: string } | null {
  const statuses = {
    not_started: {
      label: "Belum dimulai",
      className: "bg-gray-100 text-gray-700",
    },
    on_track: {
      label: "Sesuai rencana",
      className: "bg-green-100 text-green-800",
    },
    overdue: {
      label: "Terlambat",
      className: "bg-yellow-100 text-yellow-800",
    },
    critical: {
      label: "Kritis",
      className: "bg-red-100 text-red-800",
    },
  } satisfies Record<WbsStatus, { label: string; className: string }>;

  return status ? (statuses[status] ?? null) : null;
}
