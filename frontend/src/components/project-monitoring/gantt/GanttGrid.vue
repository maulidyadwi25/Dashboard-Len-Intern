<script setup>
const props = defineProps({
  timelineMonths: Array,
  groups: Array,
  expandedGroups: Object,
  computedStatus: Function,
  isDone: Function,
  barStartDate: Function,
  barEndDate: Function,
  getGanttBarClass: Function,
  todayCol: Number,
  todayFraction: Number,
});

const parseDate = (dateStr) => {
  if (!dateStr || dateStr === 'dd/mm/yyyy' || typeof dateStr !== 'string') return null;
  const parts = dateStr.split('/');
  if (parts.length === 3) {
    return new Date(Number(parts[2]), Number(parts[1]) - 1, Number(parts[0]));
  }
  const d = new Date(dateStr);
  return isNaN(d.getTime()) ? null : d;
};

const dateToCol = (date) => {
  if (!date || isNaN(date.getTime())) return 15;
  const month = date.getMonth() + 1;
  const day = date.getDate();
  let weekInMonth;
  if (day >= 1 && day <= 10) weekInMonth = 1;
  else if (day >= 11 && day <= 17) weekInMonth = 2;
  else if (day >= 18 && day <= 24) weekInMonth = 3;
  else weekInMonth = 4;
  return (month - 1) * 4 + weekInMonth;
};

const getDayFraction = (date) => {
  if (!date || isNaN(date.getTime())) return 0;
  const day = date.getDate();
  if (day >= 1 && day <= 10) return (day - 1) / 10;
  if (day >= 11 && day <= 17) return (day - 11) / 7;
  if (day >= 18 && day <= 24) return (day - 18) / 7;
  return (day - 25) / 7;
};

// Returns position in percent (0% to 100%) for any given date
const getDatePositionPercent = (date) => {
  if (!date || isNaN(date.getTime())) return 0;
  const col = Math.min(Math.max(1, dateToCol(date)), 48);
  const frac = Math.min(Math.max(0, getDayFraction(date)), 1);
  return ((col - 1) * (100 / 48)) + ((100 / 48) * frac);
};

const getBarStyle = (item) => {
  try {
    if (!item) return { display: 'none' };

    const start = typeof props.barStartDate === 'function'
      ? props.barStartDate(item)
      : parseDate(item.start_date);

    const isItemDone = typeof props.isDone === 'function' ? props.isDone(item) : false;

    // Hitung posisi start (persentase)
    const startPct = getDatePositionPercent(start);

    // Hitung posisi end (persentase)
    let endPct;
    if (isItemDone) {
      const end = typeof props.barEndDate === 'function'
        ? props.barEndDate(item)
        : parseDate(item.finish_date || item.tgl_selesai || item.target_tgl_pengadaan);
      endPct = getDatePositionPercent(end);
    } else {
      // Live duration: ujung kanan bar tepat berada di garis merah TODAY
      const col = Math.min(Math.max(1, props.todayCol || 1), 48);
      const frac = Math.min(Math.max(0, props.todayFraction || 0), 1);
      endPct = ((col - 1) * (100 / 48)) + ((100 / 48) * frac);
    }

    // Pastikan endPct >= startPct + minimal lebar (0.5% agar bar selalu terlihat)
    const minWidthPct = 100 / 48 / 2; // minimal setengah kolom
    const effectiveEndPct = Math.min(100, Math.max(endPct, startPct + minWidthPct));
    const widthPct = Math.max(minWidthPct, effectiveEndPct - startPct);

    return {
      left: `${startPct}%`,
      width: `${widthPct}%`,
    };
  } catch (error) {
    console.error('Error computing bar style for item:', item, error);
    return { display: 'none' };
  }
};
</script>

<template>
  <div class="min-w-[1500px] shrink-0 bg-slate-50/30">
    <div
      class="sticky top-0 z-10 bg-white border-b border-slate-200 grid grid-cols-48 text-center text-[10px] font-bold text-slate-600 h-[20px]"
    >
      <div
        v-for="m in timelineMonths"
        :key="m.name"
        :title="m.label || m.fullName"
        :class="[
          'col-span-4 py-0.5 border-r border-slate-200 truncate px-1',
          m.current
            ? 'bg-blue-600 text-white font-extrabold'
            : 'bg-slate-100/90',
        ]"
      >
        {{ m.name }}
      </div>
    </div>

    <div
      class="sticky top-[20px] z-10 bg-white border-b border-slate-200 grid grid-cols-48 text-center text-[8.5px] font-semibold text-slate-500 h-[20px]"
    >
      <template v-for="m in timelineMonths" :key="'wk-' + m.name">
        <div
          v-for="(w, idx) in m.weeks"
          :key="m.name + '-' + w.label + '-' + idx"
          :class="[
            'py-0.5 border-r border-slate-200/60',
            m.current ? 'bg-blue-50/40 text-blue-700 font-bold' : '',
          ]"
        >
          {{ w.label }}
        </div>
      </template>
    </div>

    <div class="divide-y divide-slate-100 relative">
      <div
        class="absolute top-0 bottom-0 z-30 pointer-events-none"
        :style="{
          left: `calc(${(todayCol - 1) * (100 / 48)}% + ${(100 / 48) * todayFraction}%)`,
        }"
      >
        <div
          class="w-0.5 bg-red-500 h-full shadow-[0_0_4px_rgba(239,68,68,0.5)]"
        ></div>
        <div
          class="absolute -top-0 left-1/2 -translate-x-1/2 bg-red-500 text-white text-[7px] font-bold px-1 py-0.5 rounded-b whitespace-nowrap leading-none"
        >
          TODAY
        </div>
      </div>

      <template
        v-for="group in groups"
        :key="'gantt-' + (group.id || group.no)"
      >
        <div
          class="grid grid-cols-48 h-[42px] items-center relative hover:bg-slate-100/30"
        >
          <div
            v-for="col in 48"
            :key="'grid-' + col"
            class="h-full border-r border-slate-200/40"
          ></div>

          <div
            class="absolute h-5 rounded-md shadow-xs flex items-center px-2 z-10 transition-all duration-300 border"
            :class="getGanttBarClass(group)"
            :style="getBarStyle(group)"
          >
            <span class="text-[9.5px] font-bold truncate">{{
              group.nama_pengadaan
            }}</span>
          </div>
        </div>

        <template
          v-if="
            expandedGroups[group.main_no] &&
            group.subItems &&
            group.subItems.length > 0
          "
        >
          <div
            v-for="sub in group.subItems"
            :key="'sub-gantt-' + (sub.id || sub.no)"
            class="grid grid-cols-48 h-[36px] items-center relative bg-blue-50/10 hover:bg-blue-50/20"
          >
            <div
              v-for="col in 48"
              :key="'sub-grid-' + col"
              class="h-full border-r border-slate-200/30"
            ></div>
            <div
              class="absolute h-4 rounded-md flex items-center px-2 z-10 transition-all duration-300 border"
              :class="getGanttBarClass(sub)"
              :style="getBarStyle(sub)"
            >
              <span class="text-[8.5px] font-semibold truncate"
                >{{ sub.no }} - {{ sub.nama_pengadaan }}</span
              >
            </div>
          </div>
        </template>
      </template>
    </div>
  </div>
</template>
