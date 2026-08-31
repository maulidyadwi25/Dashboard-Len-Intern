<script setup>
import { ref } from "vue";

// State Filter
const selectedView = ref("Month");
const startDate = ref("2024-04-01");
const endDate = ref("2024-09-30");

// Header Timeline (Bulan & Minggu)
const months = [
  { name: "APR", weeks: ["W1", "W2", "W3", "W4"] },
  { name: "MAY", weeks: ["W1", "W2", "W3", "W4"] },
  { name: "JUN", weeks: ["W1", "W2", "W3", "W4"] },
  { name: "JUL", weeks: ["W1", "W2", "W3", "W4"] },
  { name: "AUG", weeks: ["W1", "W2", "W3", "W4"] },
  { name: "SEP", weeks: ["W1", "W2", "W3", "W4"] },
];

const totalWeeks = 24; // 6 Bulan x 4 Minggu

// Mock Data WBS sesuai struktur Gambar Acuan
const wbsItems = ref([
  {
    id: "1",
    name: "1. Engineering",
    isHeader: true,
  },
  {
    id: "1.1",
    name: "1.1 Preliminary Design",
    isHeader: false,
    startCol: 1, // APR W1
    spanCol: 22, // Durasi Kontrak
    progressSpan: 20, // Kontrak Berjalan
    planDotCol: 21, // Orange Dot (Plan)
    actualDotCol: null,
  },
  {
    id: "1.2",
    name: "1.2 SPH & HPS",
    isHeader: false,
    startCol: 1, // APR W1
    spanCol: 11, // Durasi Kontrak
    progressSpan: 6, // Kontrak Berjalan
    actualDotCol: 6, // Green Dot (Actual)
    planDotCol: null,
  },
  {
    id: "1.3",
    name: "1.3 End User Certificate",
    isHeader: false,
    startCol: 1, // APR W1
    spanCol: 18, // Durasi Kontrak
    progressSpan: 16, // Kontrak Berjalan
    planDotCol: 14, // Orange Dot
    actualDotCol: 16, // Green Dot
  },
  {
    id: "2",
    name: "2. Procurement",
    isHeader: true,
  },
]);

const handleReset = () => {
  startDate.value = "2024-04-01";
  endDate.value = "2024-09-30";
  selectedView.value = "Month";
};
</script>

<template>
  <div class="space-y-4 font-sans text-slate-800">
    <!-- Toolbar Control (Filter & Action) -->
    <div
      class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-xs"
    >
      <div class="flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-2">
          <label class="text-xs font-bold uppercase text-slate-500"
            >VIEW:</label
          >
          <select
            v-model="selectedView"
            class="rounded-lg border border-slate-300 bg-slate-50 px-3 py-1.5 text-sm font-medium text-slate-700 focus:outline-hidden focus:ring-2 focus:ring-blue-500"
          >
            <option value="Month">Month</option>
            <option value="Week">Week</option>
          </select>
        </div>

        <div class="flex items-center gap-2">
          <input
            type="date"
            v-model="startDate"
            class="rounded-lg border border-slate-300 bg-slate-50 px-3 py-1.5 text-sm font-medium text-slate-700"
          />
          <span class="text-slate-400">-</span>
          <input
            type="date"
            v-model="endDate"
            class="rounded-lg border border-slate-300 bg-slate-50 px-3 py-1.5 text-sm font-medium text-slate-700"
          />
        </div>

        <button
          @click="handleReset"
          class="rounded-lg border border-slate-300 bg-white px-4 py-1.5 text-sm font-semibold text-blue-600 hover:bg-slate-50"
        >
          Reset
        </button>
      </div>

      <button
        class="flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white shadow-xs hover:bg-blue-700"
      >
        <svg
          class="h-4 w-4"
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

    <!-- Main Gantt Table Container -->
    <div
      class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs"
    >
      <div class="overflow-x-auto">
        <div class="min-w-225">
          <!-- Header Table -->
          <div
            class="flex border-b border-slate-200 bg-slate-50/70 text-center text-xs font-bold text-slate-500"
          >
            <!-- Sidebar WBS Header -->
            <div class="w-64 shrink-0 border-r border-slate-200 p-3 text-left">
              WBS / ACTIVITY
            </div>

            <!-- Timeline Months & Weeks Header -->
            <div class="flex flex-1">
              <div
                v-for="(m, idx) in months"
                :key="idx"
                class="flex-1 border-r border-slate-200 last:border-r-0"
              >
                <div
                  class="border-b border-slate-200 py-2 uppercase tracking-wider"
                >
                  {{ m.name }}
                </div>
                <div class="flex text-[10px] text-slate-400">
                  <span
                    v-for="w in m.weeks"
                    :key="w"
                    class="flex-1 border-r border-slate-100 py-1 last:border-r-0"
                  >
                    {{ w }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Body Table Rows -->
          <div class="divide-y divide-slate-100">
            <div
              v-for="item in wbsItems"
              :key="item.id"
              class="flex min-h-12 items-center"
              :class="
                item.isHeader
                  ? 'bg-slate-50/40 font-bold text-slate-900'
                  : 'hover:bg-slate-50/50'
              "
            >
              <!-- WBS Item Name Column -->
              <div
                class="w-64 shrink-0 border-r border-slate-200 px-4 py-3 text-sm"
                :class="
                  item.isHeader ? 'text-base font-bold' : 'pl-8 text-slate-700'
                "
              >
                {{ item.name }}
              </div>

              <!-- Timeline Bar Grid Area -->
              <div class="relative flex-1 h-12 flex items-center">
                <!-- Grid Lines Background -->
                <div class="absolute inset-0 flex pointer-events-none">
                  <div
                    v-for="i in totalWeeks"
                    :key="i"
                    class="flex-1 border-r border-slate-100 last:border-r-0"
                  ></div>
                </div>

                <!-- Custom Gantt Bar (Only for sub-activities) -->
                <div
                  v-if="!item.isHeader"
                  class="relative h-6 w-full flex items-center px-1"
                >
                  <!-- Base Contract Bar (Durasi Kontrak - Light Blue) -->
                  <div
                    class="absolute h-5 rounded-full bg-blue-100/80"
                    :style="{
                      left: `calc(${((item.startCol - 1) / totalWeeks) * 100}% + 2px)`,
                      width: `calc(${(item.spanCol / totalWeeks) * 100}% - 4px)`,
                    }"
                  >
                    <!-- Progress Bar (Kontrak Berjalan - Solid Blue) -->
                    <div
                      class="h-full rounded-full bg-blue-600 transition-all duration-300"
                      :style="{
                        width: `${(item.progressSpan / item.spanCol) * 100}%`,
                      }"
                    ></div>
                  </div>

                  <!-- Milestone Dot: Plan (Orange) -->
                  <div
                    v-if="item.planDotCol"
                    class="absolute z-10 h-3.5 w-3.5 rounded-full bg-amber-500 border-2 border-white shadow-xs"
                    :style="{
                      left: `calc(${((item.planDotCol - 0.5) / totalWeeks) * 100}%)`,
                    }"
                    title="Plan Milestone"
                  ></div>

                  <!-- Milestone Dot: Actual (Green) -->
                  <div
                    v-if="item.actualDotCol"
                    class="absolute z-10 h-3.5 w-3.5 rounded-full bg-emerald-500 border-2 border-white shadow-xs"
                    :style="{
                      left: `calc(${((item.actualDotCol - 0.5) / totalWeeks) * 100}%)`,
                    }"
                    title="Actual Milestone"
                  ></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Legend Footer -->
      <div
        class="flex flex-wrap items-center gap-6 border-t border-slate-200 bg-slate-50/50 p-4 text-xs font-semibold text-slate-600"
      >
        <div class="flex items-center gap-2">
          <span class="h-3 w-6 rounded-full bg-blue-100"></span>
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
