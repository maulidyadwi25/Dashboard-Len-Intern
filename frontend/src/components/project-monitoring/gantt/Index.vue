<script setup>
import { ref, toRefs } from "vue";
import { useProcurementGantt } from "@/composables/useProcurementGantt";
import GanttHeader from "./GanttHeader.vue";
import GanttTable from "./GanttTable.vue";
import GanttGrid from "./GanttGrid.vue";

const props = defineProps({
  items: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(["download"]);

const { items } = toRefs(props);
const ganttContainerRef = ref(null);

const {
  // State & Filters
  searchQuery,
  activeFilter,
  filterOptions,
  selectedStatus,
  statusOptions,
  isFilterActive,
  resetFilters,

  // Sorting
  sortKey,
  sortOrder,
  handleSort,
  isSortActive,
  resetSort,

  // Gantt Timeline & Data
  ganttYear,
  timelineMonths,
  todayCol,
  todayFraction,
  groupedProcurements,
  expandedGroups,
  toggleGroup,
  scrollGantt,

  // Item Logic & Helpers
  getStatusBadgeClass,
  getPriorityBadgeClass,
  computedStatus,
  isDone,
  barStartDate,
  barEndDate,
  getGanttBarClass,
  updateItemStartDate,
  formatToNativeDate,
} = useProcurementGantt(items, ganttContainerRef);
</script>

<template>
  <div
    class="mt-6 overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm"
  >
    <!-- Header Controls (Gabungan Filter & Navigasi Timeline) -->
    <GanttHeader
      v-model:search-query="searchQuery"
      v-model:active-filter="activeFilter"
      v-model:selected-status="selectedStatus"
      :gantt-year="ganttYear"
      :filter-options="filterOptions"
      :status-options="statusOptions"
      :is-filter-active="isFilterActive"
      :is-sort-active="isSortActive"
      @scroll="scrollGantt"
      @download="emit('download')"
      @reset="resetFilters"
      @reset-sort="resetSort"
    />

    <!-- Gantt Body Area -->
    <div class="flex w-full">
      <!-- Side Table (Fixed Left) -->
      <div class="relative z-20 w-[795px] shrink-0 border-r border-slate-200">
        <GanttTable
          :groups="groupedProcurements"
          :expanded-groups="expandedGroups"
          :get-priority-badge-class="getPriorityBadgeClass"
          :get-status-badge-class="getStatusBadgeClass"
          :format-to-native-date="formatToNativeDate"
          :computed-status="computedStatus"
          :is-done="isDone"
          :get-gantt-bar-class="getGanttBarClass"
          :sort-key="sortKey"
          :sort-order="sortOrder"
          @sort="handleSort"
          @toggle-group="toggleGroup"
          @update-start-date="updateItemStartDate"
        />
      </div>

      <!-- Timeline Grid (Scrollable Right) -->
      <div ref="ganttContainerRef" class="min-w-0 flex-1 overflow-x-auto">
        <GanttGrid
          :timeline-months="timelineMonths"
          :groups="groupedProcurements"
          :expanded-groups="expandedGroups"
          :computed-status="computedStatus"
          :is-done="isDone"
          :bar-start-date="barStartDate"
          :bar-end-date="barEndDate"
          :get-gantt-bar-class="getGanttBarClass"
          :today-col="todayCol"
          :today-fraction="todayFraction"
        />
      </div>
    </div>
  </div>
</template>
