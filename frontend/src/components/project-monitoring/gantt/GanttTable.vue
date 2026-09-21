<script setup>
import { ChevronRight, ChevronDown, AlertTriangle } from "lucide-vue-next";

const props = defineProps({
  groups: Array,
  expandedGroups: Object,
  getPriorityBadgeClass: Function,
  getStatusBadgeClass: Function,
  formatToNativeDate: Function,
  computedStatus: Function,
  isDone: Function,
  getGanttBarClass: Function,
  sortKey: String,
  sortOrder: String,
});

const emit = defineEmits(["toggleGroup", "updateStartDate", "sort"]);

const isOverdue = (item) => {
  return props.computedStatus?.(item) === "OVERDUE";
};

const getComputedStatus = (item) =>
  props.computedStatus?.(item) ||
  (props.isDone?.(item) ? "DONE" : "IN PROGRESS");

const parseDateString = (dateStr) => {
  if (!dateStr || dateStr === "dd/mm/yyyy" || typeof dateStr !== "string")
    return null;
  if (dateStr.includes("/")) {
    const [day, month, year] = dateStr.split("/").map(Number);
    const parsed = new Date(year, month - 1, day);
    return isNaN(parsed.getTime()) ? null : parsed;
  }
  const d = new Date(dateStr);
  return isNaN(d.getTime()) ? null : d;
};

const handleSort = (key) => {
  emit("sort", key);
};
</script>

<template>
  <div
    class="w-[795px] shrink-0 border-r border-slate-200 bg-white z-20 shadow-sm"
  >
    <!-- Header Tabel -->
    <div
      class="grid grid-cols-[40px_190px_120px_75px_115px_85px_85px_85px] text-[10px] font-bold text-slate-500 uppercase border-b border-slate-200 bg-slate-100/80 py-2 gap-0 items-center h-[40px]"
    >
      <button
        type="button"
        @click="handleSort('no')"
        class="flex items-center justify-center gap-1 hover:text-slate-800 transition-colors w-full h-full cursor-pointer"
      >
        <span>NO</span>
        <span v-if="sortKey === 'no'" class="text-[9px]">{{
          sortOrder === "asc" ? "▲" : "▼"
        }}</span>
      </button>

      <button
        type="button"
        @click="handleSort('nama_pengadaan')"
        class="flex items-center gap-1 pl-3 text-left hover:text-slate-800 transition-colors w-full h-full min-w-0 cursor-pointer"
      >
        <span class="truncate">NAMA PENGADAAN</span>
        <span v-if="sortKey === 'nama_pengadaan'" class="text-[9px] shrink-0">{{
          sortOrder === "asc" ? "▲" : "▼"
        }}</span>
      </button>

      <button
        type="button"
        @click="handleSort('nama_vendor')"
        class="flex items-center gap-1 pl-2 text-left hover:text-slate-800 transition-colors w-full h-full min-w-0 cursor-pointer"
      >
        <span class="truncate">VENDOR</span>
        <span v-if="sortKey === 'nama_vendor'" class="text-[9px] shrink-0">{{
          sortOrder === "asc" ? "▲" : "▼"
        }}</span>
      </button>

      <button
        type="button"
        @click="handleSort('priority')"
        class="flex items-center justify-center gap-1 hover:text-slate-800 transition-colors w-full h-full cursor-pointer"
      >
        <span>PRIORITY</span>
        <span v-if="sortKey === 'priority'" class="text-[9px]">{{
          sortOrder === "asc" ? "▲" : "▼"
        }}</span>
      </button>

      <button
        type="button"
        @click="handleSort('start_date')"
        class="flex items-center justify-center gap-1 hover:text-slate-800 transition-colors w-full h-full cursor-pointer"
      >
        <span>START DATE</span>
        <span v-if="sortKey === 'start_date'" class="text-[9px]">{{
          sortOrder === "asc" ? "▲" : "▼"
        }}</span>
      </button>

      <button
        type="button"
        @click="handleSort('target_tgl_pengadaan')"
        class="flex items-center justify-center gap-1 hover:text-slate-800 transition-colors w-full h-full cursor-pointer"
      >
        <span>TARGET</span>
        <span v-if="sortKey === 'target_tgl_pengadaan'" class="text-[9px]">{{
          sortOrder === "asc" ? "▲" : "▼"
        }}</span>
      </button>

      <button
        type="button"
        @click="handleSort('tgl_selesai')"
        class="flex items-center justify-center gap-1 hover:text-slate-800 transition-colors w-full h-full cursor-pointer"
      >
        <span>FINISH</span>
        <span v-if="sortKey === 'tgl_selesai'" class="text-[9px]">{{
          sortOrder === "asc" ? "▲" : "▼"
        }}</span>
      </button>

      <button
        type="button"
        @click="handleSort('status')"
        class="flex items-center justify-center gap-1 hover:text-slate-800 transition-colors w-full h-full cursor-pointer"
      >
        <span>STATUS</span>
        <span v-if="sortKey === 'status'" class="text-[9px]">{{
          sortOrder === "asc" ? "▲" : "▼"
        }}</span>
      </button>
    </div>

    <!-- Body Tabel -->
    <div class="divide-y divide-slate-100">
      <template
        v-for="(group, groupIndex) in groups"
        :key="group.id || group.no"
      >
        <!-- Row Utama -->
        <div
          class="grid grid-cols-[40px_190px_120px_75px_115px_85px_85px_85px] text-[11px] items-center gap-0 font-semibold h-[42px] hover:bg-slate-50/80 transition-colors"
          :class="
            group.subItems && group.subItems.length > 0 ? 'bg-slate-50/50' : ''
          "
        >
          <div class="text-center font-bold text-blue-600">
            {{ group.no || group.nomor || groupIndex + 1 }}
          </div>

          <div class="min-w-0 pl-3 flex items-center gap-1.5">
            <button
              v-if="group.subItems && group.subItems.length > 0"
              @click="emit('toggleGroup', group.main_no)"
              class="text-slate-400 hover:text-slate-700 shrink-0 cursor-pointer"
            >
              <ChevronDown
                v-if="expandedGroups[group.main_no]"
                class="w-3.5 h-3.5"
              />
              <ChevronRight v-else class="w-3.5 h-3.5" />
            </button>
            <span
              class="font-bold text-slate-900 truncate"
              :title="group.nama_pengadaan"
            >
              {{ group.nama_pengadaan }}
            </span>
            <div
              v-if="isOverdue(group)"
              class="ml-1 shrink-0"
              title="Project overdue"
            >
              <AlertTriangle class="w-3 h-3 text-rose-500" />
            </div>
          </div>

          <span
            class="text-slate-600 truncate text-[10.5px] pl-2"
            :title="group.nama_vendor"
          >
            {{ group.nama_vendor || "-" }}
          </span>

          <div class="flex justify-center">
            <span
              :class="[
                'inline-flex items-center px-1.5 py-0.5 rounded-md text-[9px] font-bold border',
                getPriorityBadgeClass?.(group.priority),
              ]"
            >
              {{ group.priority || "-" }}
            </span>
          </div>

          <div class="flex justify-center px-1">
            <input
              type="date"
              :value="formatToNativeDate?.(group.tgl_mulai || group.start_date)"
              @change="emit('updateStartDate', group, $event)"
              class="w-full max-w-[105px] text-[9.5px] font-mono text-slate-700 border border-slate-200 rounded px-1 py-1 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition-all cursor-pointer"
            />
          </div>

          <span
            class="text-slate-600 text-[10px] font-mono whitespace-nowrap text-center"
          >
            {{ group.target_tgl_pengadaan || "-" }}
          </span>
          <span
            class="text-slate-600 text-[10px] font-mono whitespace-nowrap text-center"
          >
            {{ group.tgl_selesai || group.finish_date || "-" }}
          </span>

          <div class="flex justify-center">
            <span
              :class="[
                'inline-flex items-center px-1.5 py-0.5 rounded-md text-[9.5px] font-bold border',
                getStatusBadgeClass?.(getComputedStatus(group)),
              ]"
            >
              {{ getComputedStatus(group) }}
            </span>
          </div>
        </div>

        <!-- Sub Items -->
        <template
          v-if="
            expandedGroups[group.main_no] &&
            group.subItems &&
            group.subItems.length > 0
          "
        >
          <div
            v-for="(sub, subIndex) in group.subItems"
            :key="sub.id || sub.no"
            class="grid grid-cols-[40px_190px_120px_75px_115px_85px_85px_85px] text-[10.5px] items-center gap-0 h-[36px] bg-blue-50/20 hover:bg-blue-50/40 border-l-2 border-l-blue-400"
          >
            <div class="text-center text-slate-400 text-[10px] font-mono">
              {{ sub.no || sub.nomor || subIndex + 1 }}
            </div>

            <div class="min-w-0 pl-4 flex items-center gap-1">
              <span class="text-blue-400 text-[10px]">&#9492;</span>
              <span
                class="text-slate-700 truncate"
                :title="sub.nama_pengadaan"
                >{{ sub.nama_pengadaan }}</span
              >
              <div
                v-if="isOverdue(sub)"
                class="ml-1 shrink-0"
                title="Project overdue"
              >
                <AlertTriangle class="w-2.5 h-2.5 text-rose-500" />
              </div>
            </div>

            <span
              class="text-slate-500 truncate text-[10px] pl-2"
              :title="sub.nama_vendor"
              >{{ sub.nama_vendor || "-" }}</span
            >

            <div class="flex justify-center">
              <span
                :class="[
                  'inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-semibold border',
                  getPriorityBadgeClass?.(sub.priority),
                ]"
              >
                {{ sub.priority || "-" }}
              </span>
            </div>

            <div class="flex justify-center px-1">
              <input
                type="date"
                :value="formatToNativeDate?.(sub.tgl_mulai || sub.start_date)"
                @change="emit('updateStartDate', sub, $event)"
                class="w-full max-w-[105px] text-[9.5px] font-mono text-slate-600 border border-slate-200/80 rounded px-1 py-0.5 bg-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition-all cursor-pointer"
              />
            </div>

            <span
              class="text-slate-500 text-[10px] font-mono whitespace-nowrap text-center"
              >{{ sub.target_tgl_pengadaan || "-" }}</span
            >
            <span
              class="text-slate-500 text-[10px] font-mono whitespace-nowrap text-center"
              >{{ sub.tgl_selesai || sub.finish_date || "-" }}</span
            >

            <div class="flex justify-center">
              <span
                :class="[
                  'inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-semibold border',
                  getStatusBadgeClass?.(getComputedStatus(sub)),
                ]"
              >
                {{ getComputedStatus(sub) }}
              </span>
            </div>
          </div>
        </template>
      </template>
    </div>
  </div>
</template>
