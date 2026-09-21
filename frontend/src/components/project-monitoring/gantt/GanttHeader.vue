<script setup>
import {
  Search,
  ChevronDown,
  RefreshCw,
  Calendar,
  ChevronLeft,
  ChevronRight,
  Download,
} from "lucide-vue-next";

defineProps({
  // Search & Filter Props
  searchQuery: String,
  activeFilter: String,
  selectedStatus: String,
  filterOptions: Array,
  statusOptions: Array,
  isFilterActive: Boolean,
  isSortActive: Boolean,

  // Gantt Props
  ganttYear: { type: [Number, String], required: true },
});

const emit = defineEmits([
  "update:searchQuery",
  "update:activeFilter",
  "update:selectedStatus",
  "reset",
  "resetSort",
  "scroll",
  "download",
]);

const handleResetAll = () => {
  emit("reset");
  emit("resetSort");
};
</script>
<template>
  <div
    class="flex flex-wrap items-center justify-between gap-4 px-4 py-3 border-b border-slate-200 bg-white shadow-sm"
  >
    <!-- Kiri: Search & Filters -->
    <div class="flex flex-wrap items-center gap-3 flex-1">
      <div class="relative w-full max-w-[220px]">
        <Search
          class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400"
        />
        <input
          type="text"
          :value="searchQuery"
          @input="emit('update:searchQuery', $event.target.value)"
          placeholder="Cari paket, vendor..."
          class="w-full h-8 rounded-lg border border-slate-300 bg-slate-50 py-1 pl-8 pr-7 text-xs text-slate-800 outline-none transition focus:bg-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
        />
        <button
          v-if="searchQuery"
          @click="emit('update:searchQuery', '')"
          class="absolute right-2.5 top-1/2 -translate-y-1/2 text-xs text-slate-400 hover:text-slate-600 transition-colors cursor-pointer"
        >
          ✕
        </button>
      </div>

      <div class="h-4 w-px bg-slate-200 hidden sm:block"></div>

      <div class="relative">
        <select
          :value="activeFilter"
          @change="emit('update:activeFilter', $event.target.value)"
          class="h-8 cursor-pointer appearance-none rounded-lg border border-slate-300 bg-white pl-3 pr-8 text-xs font-medium text-slate-700 outline-none transition focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs"
        >
          <option v-for="f in filterOptions" :key="f" :value="f">
            {{ f }}
          </option>
        </select>
        <ChevronDown
          class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400"
        />
      </div>

      <div class="relative">
        <select
          :value="selectedStatus"
          @change="emit('update:selectedStatus', $event.target.value)"
          class="h-8 cursor-pointer appearance-none rounded-lg border border-slate-300 bg-white pl-3 pr-8 text-xs font-medium text-slate-700 outline-none transition focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs"
        >
          <option value="">Semua Status</option>
          <option v-for="st in statusOptions" :key="st" :value="st">
            {{ st }}
          </option>
        </select>
        <ChevronDown
          class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400"
        />
      </div>

      <button
        v-if="isFilterActive || isSortActive"
        @click="handleResetAll"
        class="inline-flex items-center gap-1.5 h-8 px-2 text-xs font-medium text-slate-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors cursor-pointer"
      >
        <RefreshCw class="w-3 h-3" />
        <span>Reset</span>
      </button>
    </div>

    <!-- Kanan: Timeline Nav & Actions -->
    <div class="flex items-center gap-3 shrink-0">
      <div
        class="flex items-center bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-600 h-8 shadow-2xs overflow-hidden"
      >
        <div
          class="flex items-center gap-1.5 px-3 h-full border-r border-slate-200 bg-slate-50"
        >
          <Calendar class="w-3.5 h-3.5 text-blue-500" />
          <span>{{ ganttYear }}</span>
        </div>
        <button
          @click="emit('scroll', 'left')"
          class="px-2 h-full hover:bg-slate-100 border-r border-slate-200 text-slate-600 transition-colors cursor-pointer"
        >
          <ChevronLeft class="w-4 h-4" />
        </button>
        <button
          @click="emit('scroll', 'right')"
          class="px-2 h-full hover:bg-slate-100 text-slate-600 transition-colors cursor-pointer"
        >
          <ChevronRight class="w-4 h-4" />
        </button>
      </div>

      <button
        @click="emit('download')"
        class="inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white px-3.5 h-8 rounded-lg text-xs font-medium transition-colors shadow-2xs cursor-pointer"
      >
        <Download class="w-3.5 h-3.5" />
        <span>Download</span>
      </button>
    </div>
  </div>
</template>
