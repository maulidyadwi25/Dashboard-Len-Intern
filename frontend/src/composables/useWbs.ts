import { computed, ref, watch } from "vue";
import { DEFAULT_WBS_PROJECT_ID, wbsService } from "../services/wbsService.js";
import {
  buildTree,
  flattenVisible,
  mapWeeklyByWbs,
  type WbsItem,
  type WbsPeriod,
  type WbsTreeItem,
  type WbsWeeklyValue,
} from "../utils/wbs";

type MonthFilter = number | "all";

interface WbsResponse {
  data?: {
    project?: {
      id: number;
      project_code: string;
      project_name: string;
    };
    summary?: WbsSummary;
    items?: WbsItem[];
  };
}

interface WeeklyResponse {
  data?: {
    periods?: WbsPeriod[];
    values?: WbsWeeklyValue[];
  };
}

interface WbsSummary {
  total_items: number;
  total_on_track: number;
  total_overdue: number;
  total_critical: number;
  total_not_started: number;
  total_bobot_plan: number;
  total_bobot_real: number;
  progres: number;
}

const EMPTY_SUMMARY: WbsSummary = {
  total_items: 0,
  total_on_track: 0,
  total_overdue: 0,
  total_critical: 0,
  total_not_started: 0,
  total_bobot_plan: 0,
  total_bobot_real: 0,
  progres: 0,
};

function errorMessage(error: unknown): string {
  return error instanceof Error ? error.message : "Unable to load WBS data.";
}

export function useWbs(projectId: number | string = DEFAULT_WBS_PROJECT_ID) {
  // Project ID 1 is a temporary default until project selection is connected.
  const items = ref<WbsItem[]>([]);
  const project = ref<WbsResponse["data"]["project"] | null>(null);
  const summary = ref<WbsSummary>({ ...EMPTY_SUMMARY });
  const periods = ref<WbsPeriod[]>([]);
  const weeklyValues = ref<WbsWeeklyValue[]>([]);
  const selectedMonth = ref<MonthFilter>("all");
  const expanded = ref<Record<string, boolean>>({});
  const loading = ref(false);
  const weeklyLoading = ref(false);
  const syncing = ref(false);
  const savingWbsNo = ref<string | null>(null);
  const progressErrors = ref<Record<string, string>>({});
  const error = ref<string | null>(null);

  const tree = computed<WbsTreeItem[]>(() => buildTree(items.value));
  const visibleItems = computed(() =>
    flattenVisible(tree.value, expanded.value),
  );
  const weeklyByWbs = computed(() => mapWeeklyByWbs(weeklyValues.value));

  function setError(value: unknown): void {
    error.value = errorMessage(value);
  }

  function clearProgressError(wbsNo: string): void {
    delete progressErrors.value[wbsNo];
  }

  async function fetchWbs(): Promise<void> {
    const response = (await wbsService.getWbs(projectId)) as {
      data?: WbsResponse;
    };
    const payload = response.data?.data;
    items.value = payload?.items ?? [];
    project.value = payload?.project ?? null;
    summary.value = payload?.summary ?? { ...EMPTY_SUMMARY };
  }

  async function fetchWeekly(month: MonthFilter): Promise<void> {
    const response = (await wbsService.getWbsWeekly(projectId, month)) as {
      data?: WeeklyResponse;
    };
    periods.value = response.data?.data?.periods ?? [];
    weeklyValues.value = response.data?.data?.values ?? [];
  }

  async function reload(): Promise<boolean> {
    loading.value = true;
    error.value = null;

    try {
      await Promise.all([fetchWbs(), fetchWeekly(selectedMonth.value)]);
      return true;
    } catch (requestError) {
      setError(requestError);
      return false;
    } finally {
      loading.value = false;
    }
  }

  function toggleExpanded(wbsNo: string): void {
    expanded.value[wbsNo] = !expanded.value[wbsNo];
  }

  function setExpanded(wbsNo: string, value: boolean): void {
    expanded.value[wbsNo] = value;
  }

  async function sync(): Promise<boolean> {
    syncing.value = true;
    error.value = null;

    try {
      await wbsService.syncWbs(projectId);
      return await reload();
    } catch (requestError) {
      setError(requestError);
      return false;
    } finally {
      syncing.value = false;
    }
  }

  async function saveProgressBatch(
    changes: Array<{ wbsNo: string; percent: number }>,
  ): Promise<Record<string, boolean>> {
    const results: Record<string, boolean> = {};
    let hasSuccessfulUpdate = false;

    for (const change of changes) {
      savingWbsNo.value = change.wbsNo;
      clearProgressError(change.wbsNo);

      try {
        await wbsService.updateWbsProgress(
          projectId,
          change.wbsNo,
          change.percent,
        );
        results[change.wbsNo] = true;
        hasSuccessfulUpdate = true;
      } catch (requestError) {
        progressErrors.value[change.wbsNo] = errorMessage(requestError);
        results[change.wbsNo] = false;
      }
    }

    savingWbsNo.value = null;

    if (hasSuccessfulUpdate) {
      await reload();
    }

    return results;
  }

  watch(selectedMonth, async (month) => {
    weeklyLoading.value = true;
    error.value = null;

    try {
      await fetchWeekly(month);
    } catch (requestError) {
      setError(requestError);
    } finally {
      weeklyLoading.value = false;
    }
  });

  void reload();

  return {
    items,
    project,
    summary,
    periods,
    weeklyValues,
    weeklyByWbs,
    tree,
    visibleItems,
    selectedMonth,
    expanded,
    loading,
    weeklyLoading,
    syncing,
    savingWbsNo,
    progressErrors,
    error,
    reload,
    toggleExpanded,
    setExpanded,
    clearProgressError,
    sync,
    saveProgressBatch,
  };
}
