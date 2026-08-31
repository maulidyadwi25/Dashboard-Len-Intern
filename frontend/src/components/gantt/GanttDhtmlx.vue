<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import { gantt } from "dhtmlx-gantt";
import "dhtmlx-gantt/codebase/dhtmlxgantt.css";

const ganttContainer = ref(null);
const currentView = ref("Month");

// Data WBS dengan atribut indikator Plan & Actual
const tasks = {
  data: [
    {
      id: 1,
      text: "1. Engineering",
      start_date: "01-04-2024",
      duration: 180,
      open: true,
      type: "project",
    },
    {
      id: 2,
      text: "1.1 Preliminary Design",
      start_date: "01-04-2024",
      duration: 150,
      progress: 0.85,
      parent: 1,
      plan_col: true,
    },
    {
      id: 3,
      text: "1.2 SPH & HPS",
      start_date: "01-04-2024",
      duration: 50,
      progress: 0.4,
      parent: 1,
      actual_col: true,
    },
    {
      id: 4,
      text: "1.3 End User Certificate",
      start_date: "01-04-2024",
      duration: 130,
      progress: 0.9,
      parent: 1,
      plan_col: true,
      actual_col: true,
    },
    {
      id: 5,
      text: "2. Procurement",
      start_date: "01-09-2024",
      duration: 30,
      open: true,
      type: "project",
    },
  ],
};

onMounted(() => {
  gantt.config.readonly = false;

  // 1. Kolom WBS di kiri dengan penyesuaian styling
  gantt.config.columns = [
    {
      name: "text",
      label: "WBS / ACTIVITY",
      tree: true,
      width: 250,
      template: (obj) => {
        if (obj.type === "project") {
          return `<strong class="text-slate-900 text-sm">${obj.text}</strong>`;
        }
        return `<span class="text-slate-600 font-medium text-xs">${obj.text}</span>`;
      },
    },
  ];

  // 2. Timeline Scale Bertingkat (Bulan & Minggu)
  gantt.config.scales = [
    { unit: "month", step: 1, format: "%M" },
    { unit: "week", step: 1, format: "W%W" },
  ];

  gantt.config.date_format = "%d-%m-%Y";
  gantt.config.row_height = 46;
  gantt.config.bar_height = 20;

  // 3. Custom Class untuk Styling Bar
  gantt.templates.task_class = (start, end, task) => {
    return task.type === "project" ? "parent-task-bar" : "child-task-bar";
  };

  // 4. Tambahkan Dots (Plan/Actual) di samping Bar menggunakan Template
  gantt.templates.rightside_text = (start, end, task) => {
    let dotsHtml = '<div class="flex items-center gap-1.5 ml-2">';
    if (task.plan_col) {
      dotsHtml +=
        '<span class="h-3 w-3 rounded-full bg-amber-500 inline-block ring-2 ring-white shadow-xs" title="Plan"></span>';
    }
    if (task.actual_col) {
      dotsHtml +=
        '<span class="h-3 w-3 rounded-full bg-emerald-500 inline-block ring-2 ring-white shadow-xs" title="Actual"></span>';
    }
    dotsHtml += "</div>";
    return dotsHtml;
  };

  gantt.init(ganttContainer.value);
  gantt.parse(tasks);
});

onUnmounted(() => {
  gantt.clearAll();
});
</script>

<template>
  <div class="p-6 bg-slate-50 min-h-screen font-sans space-y-5">
    <!-- 1. Header Card (Metrik Eksekutif) -->
    <div
      class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs flex flex-wrap items-center justify-between gap-4"
    >
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Project Name</h1>
        <p class="text-xs text-slate-500 font-medium mt-1">
          Project Code:
          <span class="font-semibold text-slate-700">1AB-522UD019</span> |
          Project Value:
          <span class="font-semibold text-slate-700">Rp 56.76M</span>
        </p>
      </div>

      <div class="flex items-center gap-8 border-l border-slate-200 pl-8">
        <div>
          <span
            class="text-[10px] font-bold tracking-wider text-slate-400 uppercase block"
            >OVERALL PROGRESS</span
          >
          <div class="flex items-baseline gap-2 mt-0.5">
            <span class="text-2xl font-extrabold text-slate-900">64.5%</span>
            <span class="text-xs font-bold text-emerald-600 flex items-center"
              >↑ 2.1%</span
            >
          </div>
        </div>

        <div class="border-l border-slate-200 pl-8">
          <span
            class="text-[10px] font-bold tracking-wider text-slate-400 uppercase block"
            >SPI</span
          >
          <div class="flex items-baseline gap-2 mt-0.5">
            <span class="text-2xl font-extrabold text-slate-900">0.96</span>
            <span
              class="text-xs font-semibold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md"
              >Slight Delay</span
            >
          </div>
        </div>
      </div>
    </div>

    <!-- 2. Toolbar Filter Controls -->
    <div
      class="bg-white rounded-xl border border-slate-200 p-3.5 shadow-xs flex flex-wrap items-center justify-between gap-4"
    >
      <div class="flex items-center gap-3">
        <div class="flex items-center gap-2">
          <label class="text-xs font-bold text-slate-400">VIEW:</label>
          <select
            v-model="currentView"
            class="text-xs font-semibold bg-slate-50 border border-slate-300 rounded-lg px-3 py-1.5 text-slate-700"
          >
            <option value="Month">Month</option>
            <option value="Week">Week</option>
          </select>
        </div>

        <div class="flex items-center gap-2">
          <input
            type="date"
            value="2024-04-01"
            class="text-xs font-medium bg-slate-50 border border-slate-300 rounded-lg px-2.5 py-1.5 text-slate-700"
          />
          <span class="text-slate-300">-</span>
          <input
            type="date"
            value="2024-09-30"
            class="text-xs font-medium bg-slate-50 border border-slate-300 rounded-lg px-2.5 py-1.5 text-slate-700"
          />
        </div>

        <button
          class="text-xs font-semibold text-blue-600 border border-blue-200 hover:bg-blue-50 px-3.5 py-1.5 rounded-lg transition-colors"
        >
          Reset
        </button>
      </div>

      <button
        class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold px-4 py-2 rounded-lg shadow-xs transition-colors"
      >
        <svg
          class="w-3.5 h-3.5"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
          />
        </svg>
        Download
      </button>
    </div>

    <!-- 3. Gantt Chart Area -->
    <div
      class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden"
    >
      <div ref="ganttContainer" class="h-95 w-full"></div>

      <!-- Legend Footer -->
      <div
        class="flex flex-wrap items-center gap-6 border-t border-slate-200 bg-slate-50/60 p-4 text-xs font-semibold text-slate-600"
      >
        <div class="flex items-center gap-2">
          <span class="h-3 w-6 rounded-full bg-blue-200"></span>
          <span>Durasi Kontrak</span>
        </div>
        <div class="flex items-center gap-2">
          <span class="h-3 w-6 rounded-full bg-blue-600"></span>
          <span>Kontrak Berjalan</span>
        </div>
        <div class="flex items-center gap-2">
          <span class="h-3 w-3 rounded-full bg-amber-500"></span>
          <span>Plan</span>
        </div>
        <div class="flex items-center gap-2">
          <span class="h-3 w-3 rounded-full bg-emerald-500"></span>
          <span>Actual</span>
        </div>
        <div class="flex items-center gap-2">
          <span class="h-3 w-6 rounded-full bg-rose-200"></span>
          <span>Overdue</span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Overrides CSS DHTMLX agar cocok dengan Tailwind UI */
:deep(.gantt_container) {
  border: none;
  font-family: inherit;
}

:deep(.gantt_grid_scale),
:deep(.gantt_task_scale) {
  background-color: #f8fafc !important;
  color: #64748b !important;
  font-weight: 700;
  font-size: 11px;
  border-bottom: 1px solid #e2e8f0 !important;
}

:deep(.gantt_grid_data .gantt_row) {
  border-bottom: 1px solid #f1f5f9;
}

:deep(.gantt_task_row) {
  border-bottom: 1px solid #f1f5f9;
}

/* Base Bar (Durasi Kontrak / Light Blue) */
:deep(.child-task-bar) {
  background-color: #bfdbfe !important;
  border-radius: 9999px !important;
  border: none !important;
}

/* Progress Inner Bar (Kontrak Berjalan / Dark Blue) */
:deep(.child-task-bar .gantt_task_progress) {
  background-color: #2563eb !important;
  border-radius: 9999px !important;
}

/* Parent Task Bar Style */
:deep(.parent-task-bar) {
  background-color: transparent !important;
  border: none !important;
}
:deep(.parent-task-bar .gantt_task_progress) {
  display: none;
}

/* Grid lines styling */
:deep(.gantt_task_cell) {
  border-right: 1px solid #f1f5f9 !important;
}
</style>
