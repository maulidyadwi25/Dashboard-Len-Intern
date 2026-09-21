<script setup>
import { ref, computed } from "vue";
import { Download } from "lucide-vue-next";

const props = defineProps({
  items: {
    type: Array,
    default: () => [],
  },
  filter: {
    type: String,
    default: "All Packages",
  },
});

const emit = defineEmits(["download", "update:filter"]);

const filters = ["All Packages", "High", "Medium", "Low"];

const normalizedItems = computed(() => {
  const mainItems = props.items.filter((i) => !i.is_sub_item);
  const source = mainItems.length ? mainItems : props.items;
  if (props.filter === "All Packages") return source;
  return source.filter(
    (i) => (i.priority || "").trim().toLowerCase() === props.filter.toLowerCase(),
  );
});

const totalCount = computed(() => normalizedItems.value.length || 27);

const overdueCount = computed(
  () =>
    normalizedItems.value.filter(
      (i) => (i.status || "").toLowerCase() === "overdue",
    ).length,
);

const doneCount = computed(
  () =>
    normalizedItems.value.filter(
      (i) => (i.status || "").toLowerCase() === "done",
    ).length,
);
</script>

<template>
  <div class="mb-8">
    <!-- 4 Metric Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
      <!-- Total Procurement -->
      <div
        class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex flex-col justify-between"
      >
        <p
          class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider"
        >
          Total Procurement
        </p>
        <p class="text-3xl font-black text-slate-900 mt-2">{{ totalCount }}</p>
      </div>

      <!-- Total Realisasi -->
      <div
        class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex flex-col justify-between"
      >
        <p
          class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider"
        >
          Total Realisasi
        </p>
        <p class="text-3xl font-black text-blue-600 mt-2">{{ doneCount }}</p>
      </div>

      <!-- Total Overdue -->
      <div
        class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex flex-col justify-between"
      >
        <p
          class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider"
        >
          Total Overdue
        </p>
        <p class="text-3xl font-black text-slate-900 mt-2">
          {{ overdueCount }}
        </p>
      </div>

      <!-- Pencapaian Kontrak -->
      <div
        class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex flex-col justify-between"
      >
        <p
          class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider"
        >
          Pencapaian Kontrak
        </p>
        <div class="flex items-baseline gap-2 mt-2">
          <span class="text-3xl font-black text-slate-900">12</span>
          <span class="text-lg font-bold text-slate-400">/ 18</span>
          <span
            class="ml-auto px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-700 text-xs font-bold"
          >
            66.7%
          </span>
        </div>
      </div>
    </div>
  </div>
</template>
