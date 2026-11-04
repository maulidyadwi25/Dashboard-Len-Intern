<template>
  <div class="w-full mt-4">
    <!-- Filter Bar -->
    <div class="mb-4 flex flex-wrap justify-between items-center gap-4 bg-transparent no-print">
      <div class="flex flex-wrap items-center gap-3">
        <!-- View Selector -->
        <div class="flex items-center space-x-2">
          <span class="text-body-sm font-bold text-gray-600 uppercase tracking-wider">VIEW:</span>
          <div class="relative">
            <select 
              v-model="selectedViewFilter"
              class="bg-white border border-outline-variant rounded-md py-1.5 px-3 text-body-sm text-gray-700 font-medium focus:outline-none cursor-pointer pr-7 appearance-none shadow-xs"
            >
              <option value="all">All</option>
              <option value="overdue">Overdue</option>
              <option value="ontrack">On-Track</option>
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </div>
          </div>
        </div>

        <span class="text-gray-300">|</span>

        <!-- Date Range Filter -->
        <div class="flex items-center space-x-2 bg-white border border-outline-variant rounded-md py-1.5 px-3 text-body-sm text-gray-700 shadow-xs">
          <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
          </svg>
          <input 
            type="date" 
            v-model="startDate" 
            class="text-body-sm text-gray-700 bg-transparent focus:outline-none cursor-pointer"
          />
        </div>

        <span class="text-gray-400 font-bold text-body-sm">-</span>

        <div class="flex items-center space-x-2 bg-white border border-outline-variant rounded-md py-1.5 px-3 text-body-sm text-gray-700 shadow-xs">
          <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
          </svg>
          <input 
            type="date" 
            v-model="endDate" 
            class="text-body-sm text-gray-700 bg-transparent focus:outline-none cursor-pointer"
          />
        </div>

        <button 
          @click="resetFilter"
          class="px-3.5 py-1.5 bg-white hover:bg-gray-50 text-blue-600 rounded-md text-body-sm font-semibold transition-colors border border-outline-variant shadow-xs"
        >
          Reset
        </button>
      </div>

      <div>
        <button 
          @click="exportToPDF"
          class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-body-sm font-semibold transition-colors shadow-xs flex items-center space-x-1.5"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
          </svg>
          <span>Download</span>
        </button>
      </div>
    </div>

    <!-- Main Gantt Table -->
    <div class="bg-white rounded-xl border border-outline-variant shadow-sm overflow-hidden">
      <div class="overflow-x-auto max-w-full gantt-print-container">
        <table class="text-left border-collapse table-fixed w-full gantt-table" :style="{ width: tableCalculatedWidth }">
          <thead>
            <tr class="bg-[#f8fafc] text-gray-500 text-body-sm font-bold uppercase border-b border-outline-variant">
              
              <!-- WBS & Activity Header -->
              <th 
                rowspan="2" 
                class="p-3 w-[320px] min-w-[320px] sticky left-0 bg-[#f8fafc] z-30 border-r border-outline-variant text-label-caps font-bold text-gray-500 tracking-wider align-middle wbs-col"
              >
                WBS / ACTIVITY
              </th>

              <!-- Month Header -->
              <template v-if="filteredMonths.length > 0">
                <th 
                  v-for="month in filteredMonths" 
                  :key="month.name" 
                  :colspan="month.weeks.length"
                  class="text-center py-2 px-3 border-r border-outline-variant tracking-wider text-label-caps font-bold text-gray-600 bg-[#f8fafc]"
                >
                  {{ formatMonthShort(month.name) }}
                </th>
              </template>
              <th v-else class="text-center py-2 px-3 border-r border-outline-variant text-label-caps w-full bg-[#f8fafc] tracking-wider">
                PERIODE
              </th>
            </tr>

            <!-- Week Header -->
            <tr class="bg-[#f8fafc] text-gray-400 text-label-caps font-semibold border-b border-outline-variant tracking-wider">
              <template v-if="filteredMonths.length > 0">
                <template v-for="month in filteredMonths" :key="'weeks-' + month.name">
                  <th 
                    v-for="week in month.weeks" 
                    :key="week" 
                    class="text-center py-1.5 w-14 min-w-[56px] bg-[#f8fafc] text-label-caps text-gray-400 font-medium week-col tracking-wider"
                    :class="isLastWeekOfMonth(`${month.name}_${week}`) ? 'border-r-2 border-outline-variant' : 'border-r border-gray-100'"
                  >
                    {{ week }}
                  </th>
                </template>
              </template>
              <th v-else class="text-center py-1 border-r border-gray-100 bg-[#f8fafc]">-</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-gray-100 text-sm bg-white">
            <tr v-if="filteredMonths.length === 0 || !displayActivities || displayActivities.length === 0">
              <td colspan="100" class="text-center py-16 bg-gray-50/30">
                <div class="flex flex-col items-center justify-center space-y-2">
                  <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                  <span class="text-body-base font-semibold text-gray-600">Data Tidak Ditemukan</span>
                  <span class="text-body-sm text-gray-400">Data proyek hanya tersedia untuk periode tahun 2026.</span>
                </div>
              </td>
            </tr>

            <tr 
              v-else
              v-for="(item, index) in displayActivities" 
              :key="index"
              class="hover:bg-slate-50/60 transition-colors group relative"
            >
              <!-- Table Font & Padding -->
              <td 
                class="p-3 w-[320px] min-w-[320px] sticky left-0 z-30 border-r border-outline-variant bg-white group-hover:bg-[#f8fafc] wbs-col align-middle"
                :class="getWbsPaddingClass(item)"
              >
                <div class="flex items-center space-x-2">
                  <span v-if="item.no" :class="getWbsTextClass(item)">{{ item.no }}</span>
                  <span class="truncate max-w-[210px] wbs-text" :title="item.name" :class="getWbsTextClass(item)">
                    {{ item.name }}
                  </span>
                </div>
              </td>

              <!-- Weekly Timeline Cells -->
              <td 
                v-for="weekKey in flatWeeks" 
                :key="weekKey"
                class="p-0 h-12 relative align-middle week-col overflow-visible"
                :class="isLastWeekOfMonth(weekKey) ? 'border-r-2 border-outline-variant' : 'border-r border-gray-100/60'"
              >
                <div class="w-full h-full flex items-center justify-center relative">
                  
                  <!-- Progress Bar Container -->
                  <div 
                    v-if="getBarStructure(item, weekKey).isStart"
                    class="absolute h-7 rounded-md z-10 overflow-hidden flex items-center bg-[#c6d2fe]"
                    :style="{
                      left: '0px',
                      width: `calc(${getBarStructure(item, weekKey).totalSpan * 100}%)`
                    }"
                  >
                    <!-- Actual Progress Layer -->
                    <div 
                      v-if="getBarStructure(item, weekKey).actualSpan > 0"
                      class="absolute inset-y-0 left-0 bg-[#a7f3d0] transition-all"
                      :style="{
                        width: `${(getBarStructure(item, weekKey).actualSpan / getBarStructure(item, weekKey).totalSpan) * 100}%`
                      }"
                    ></div>

                    <!-- Overdue Highlight -->
                    <div 
                      v-if="getBarStructure(item, weekKey).hasOverdue"
                      class="absolute inset-y-0 transition-all z-10"
                      :style="getBarStructure(item, weekKey).overdueStyle"
                    ></div>

                        <!-- Plan & Actual Markers -->
                    <div class="w-full h-full flex items-center justify-between relative z-20">
                      <div 
                        v-for="wKey in getBarStructure(item, weekKey).includedWeeks" 
                        :key="wKey"
                        class="flex-1 h-full flex items-center justify-center gap-1 px-0.5"
                      >
                        <!-- Planned Date Marker -->
                        <div 
                          v-if="item.planWeeks && item.planWeeks[wKey]" 
                          class="w-3.5 h-3.5 rounded-full bg-[#f59e0b] shrink-0 border border-white/60 shadow-xs"
                          :title="'Plan: ' + item.planWeeks[wKey]"
                        ></div>

                        <!-- Actual Date Marker -->
                        <div 
                          v-if="item.actualWeeks && item.actualWeeks[wKey]" 
                          class="w-3.5 h-3.5 rounded-full bg-[#10b981] shrink-0 border border-white/60 shadow-xs"
                          :title="'Actual: ' + item.actualWeeks[wKey]"
                        ></div>
                      </div>
                    </div>

                  </div>

                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Footer Legend -->
      <div class="p-3.5 px-4 border-t border-outline-variant bg-white flex items-center justify-start">
        <div class="flex flex-wrap items-center gap-6 text-body-sm font-medium text-gray-600">
          <div class="flex items-center space-x-2">
            <span class="w-3.5 h-3.5 rounded-full bg-[#f59e0b] inline-block"></span>
            <span class="text-body-sm text-gray-700">Plan</span>
          </div>
          <div class="flex items-center space-x-2">
            <span class="w-3.5 h-3.5 rounded-full bg-[#10b981] inline-block"></span>
            <span class="text-body-sm text-gray-700">Actual</span>
          </div>
          <div class="flex items-center space-x-2">
            <span class="w-5 h-3 rounded-sm bg-[#ef4444] border border-red-300 inline-block"></span>
            <span class="text-body-sm text-gray-700">Overdue</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'GanttChart',
  props: {
    ganttData: {
      type: Object,
      default: () => ({ months: [], activities: [] })
    }
  },
  data() {
    return {
      selectedViewFilter: 'all',
      startDate: '2026-01-01',
      endDate: '2026-12-31'
    };
  },
  computed: {
    allFlatWeeks() {
      const weeks = [];
      const monthNames = [
        'JANUARI', 'FEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI',
        'JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOVEMBER', 'DESEMBER'
      ];
      monthNames.forEach(m => {
        ['W1', 'W2', 'W3', 'W4'].forEach(w => {
          weeks.push(`${m}_${w}`);
        });
      });
      return weeks;
    },

    monthMap() {
      return {
        'JANUARI': 0, 'JAN': 0,
        'FEBRUARI': 1, 'FEB': 1,
        'MARET': 2, 'MAR': 2,
        'APRIL': 3, 'APR': 3,
        'MEI': 4, 'MAY': 4,
        'JUNI': 5, 'JUN': 5,
        'JULI': 6, 'JUL': 6,
        'AGUSTUS': 7, 'AUG': 7,
        'SEPTEMBER': 8, 'SEP': 8,
        'OKTOBER': 9, 'OCT': 9,
        'NOVEMBER': 10, 'NOV': 10,
        'DESEMBER': 11, 'DEC': 11
      };
    },

    displayActivities() {
      if (!this.ganttData || !Array.isArray(this.ganttData.activities)) return [];
      
      const allActivities = this.ganttData.activities.filter(item => {
        if (!item) return false;
        const itemString = JSON.stringify(item).toLowerCase();
        return !(itemString.includes('nama client') || itemString.includes('client'));
      });

      if (!this.selectedViewFilter || this.selectedViewFilter === 'all') {
        return allActivities;
      }

      const matchesFilter = (act) => {
        if (!act) return false;
        const isOverdue = this.isActivityOverdue(act);

        if (this.selectedViewFilter === 'overdue') {
          return isOverdue;
        }
        if (this.selectedViewFilter === 'ontrack') {
          return !isOverdue;
        }
        return true;
      };

      return allActivities.filter(item => {
        const isLeaf = (item.planWeeks && Object.keys(item.planWeeks).length > 0) ||
                       (item.actualWeeks && Object.keys(item.actualWeeks).length > 0);
        
        if (isLeaf) {
          return matchesFilter(item);
        }

        // Header: tampilkan jika ada sub-aktivitas / anak yang cocok
        if (item.no) {
          const prefix = String(item.no).trim() + '.';
          return allActivities.some(other => {
            if (other === item) return false;
            const otherNo = String(other.no || '').trim();
            if (otherNo.startsWith(prefix)) {
              return matchesFilter(other);
            }
            return false;
          });
        }

        return false;
      });
    },

    filteredMonths() {
      if (!this.ganttData || !Array.isArray(this.ganttData.months)) return [];

      const start = this.startDate ? new Date(this.startDate) : null;
      const end = this.endDate ? new Date(this.endDate) : null;

      if (start && start.getFullYear() < 2026 && end && end.getFullYear() < 2026) {
        return [];
      }

      if (start && end && start > end) return [];

      return this.ganttData.months.filter(m => {
        if (!m || !m.name) return false;
        const monthIndex = this.monthMap[String(m.name).toUpperCase()];
        if (monthIndex === undefined) return true;

        const monthStart = new Date(2026, monthIndex, 1);
        const monthEnd = new Date(2026, monthIndex + 1, 0);

        if (start && monthEnd < start) return false;
        if (end && monthStart > end) return false;

        return true;
      });
    },

    flatWeeks() {
      const weeks = [];
      this.filteredMonths.forEach(m => {
        if (m && Array.isArray(m.weeks)) {
          m.weeks.forEach(w => {
            weeks.push(`${m.name}_${w}`);
          });
        }
      });
      return weeks;
    },

    totalWeeks() {
      return this.flatWeeks.length;
    },

    tableCalculatedWidth() {
      const wbsWidth = 320;
      const weekWidth = 56;
      if (this.totalWeeks === 0) return '100%';
      return `${wbsWidth + (this.totalWeeks * weekWidth)}px`;
    }
  },
  methods: {
    formatMonthShort(name) {
      if (!name) return '';
      const map = {
        'JANUARI': 'JAN', 'JAN': 'JAN',
        'FEBRUARI': 'FEB', 'FEB': 'FEB',
        'MARET': 'MAR', 'MAR': 'MAR',
        'APRIL': 'APR', 'APR': 'APR',
        'MEI': 'MAY', 'MAY': 'MAY',
        'JUNI': 'JUN', 'JUN': 'JUN',
        'JULI': 'JUL', 'JUL': 'JUL',
        'AGUSTUS': 'AUG', 'AUG': 'AUG',
        'SEPTEMBER': 'SEP', 'SEP': 'SEP',
        'OKTOBER': 'OCT', 'OCT': 'OCT',
        'NOVEMBER': 'NOV', 'NOV': 'NOV',
        'DESEMBER': 'DEC', 'DEC': 'DEC'
      };
      return map[String(name).toUpperCase()] || String(name).substring(0, 3).toUpperCase();
    },

    getWbsPaddingClass(item) {
      if (!item) return 'pl-3';
      if (item.level === 1 || (!item.no && item.isHeader)) return 'pl-3';
      if (item.level === 2) return 'pl-6';
      return 'pl-9';
    },

    getWbsTextClass(item) {
      if (!item) return 'font-normal text-body-sm text-gray-700';
      if (item.level === 1 || (!item.no && item.isHeader)) return 'font-extrabold text-body-base text-gray-900 tracking-tight';
      if (item.level === 2) return 'font-medium text-body-sm text-gray-800';
      return 'font-normal text-body-sm text-gray-700';
    },

    isLastWeekOfMonth(weekKey) {
      const idx = this.flatWeeks.indexOf(weekKey);
      if (idx === -1 || idx === this.flatWeeks.length - 1) return false;

      const currentMonth = weekKey.split('_')[0];
      const nextWeekKey = this.flatWeeks[idx + 1];
      const nextMonth = nextWeekKey ? nextWeekKey.split('_')[0] : null;

      return currentMonth !== nextMonth;
    },

    resetFilter() {
      this.selectedViewFilter = 'all';
      this.startDate = '2026-06-01';
      this.endDate = '2026-12-31';
    },

    exportToPDF() {
      window.print();
    },

    isActivityOverdue(item) {
      if (!item || !item.planWeeks || !item.actualWeeks) return false;
      const planKeys = Object.keys(item.planWeeks);
      const actualKeys = Object.keys(item.actualWeeks);
      if (planKeys.length === 0 || actualKeys.length === 0) return false;

      const allWeeks = this.allFlatWeeks || this.flatWeeks;
      const planIndices = planKeys.map(k => allWeeks.indexOf(k)).filter(i => i !== -1);
      const actualIndices = actualKeys.map(k => allWeeks.indexOf(k)).filter(i => i !== -1);

      if (planIndices.length === 0 || actualIndices.length === 0) return false;

      for (const aIdx of actualIndices) {
        const precedingPlans = planIndices.filter(p => p <= aIdx);
        if (precedingPlans.length > 0) {
          const closestPlanIdx = Math.max(...precedingPlans);
          if (aIdx > closestPlanIdx) return true;
        }
      }
      return false;
    },

    getBarStructure(item, currentWeekKey) {
      if (!item) return { isStart: false };

      const planKeys = item.planWeeks ? Object.keys(item.planWeeks) : [];
      const actualKeys = item.actualWeeks ? Object.keys(item.actualWeeks) : [];

      const targetKeys = [...new Set([...planKeys, ...actualKeys])];

      const activeIndices = targetKeys
        .map(k => this.flatWeeks.indexOf(k))
        .filter(i => i !== -1)
        .sort((a, b) => a - b);

      if (activeIndices.length === 0) return { isStart: false };

      const minIdx = activeIndices[0];
      const maxIdx = activeIndices[activeIndices.length - 1];
      const currentIdx = this.flatWeeks.indexOf(currentWeekKey);

      if (currentIdx !== minIdx) return { isStart: false };

      const totalSpan = (maxIdx - minIdx) + 1;
      const includedWeeks = this.flatWeeks.slice(minIdx, maxIdx + 1);

      const actualIndices = actualKeys.map(k => this.flatWeeks.indexOf(k)).filter(i => i !== -1);
      let actualSpan = 0;
      if (actualIndices.length > 0) {
        const maxActual = Math.max(...actualIndices);
        actualSpan = (maxActual - minIdx) + 1;
      }

      const planIndices = planKeys.map(k => this.flatWeeks.indexOf(k)).filter(i => i !== -1);
      let hasOverdue = false;
      let overdueStyle = {};

      if (planIndices.length > 0 && actualIndices.length > 0) {
        const overdueWeekIndices = [];
        for (const aIdx of actualIndices) {
          const precedingPlans = planIndices.filter(p => p <= aIdx);
          if (precedingPlans.length > 0) {
            const closestPlanIdx = Math.max(...precedingPlans);
            if (aIdx > closestPlanIdx) {
              for (let oIdx = closestPlanIdx; oIdx <= aIdx; oIdx++) {
                if (!overdueWeekIndices.includes(oIdx)) overdueWeekIndices.push(oIdx);
              }
            }
          }
        }

        if (overdueWeekIndices.length > 0) {
          hasOverdue = true;
          const minOverdue = Math.min(...overdueWeekIndices);
          const maxOverdue = Math.max(...overdueWeekIndices);

          const overlayCount = (maxOverdue - minOverdue) + 1;
          const startOffset = ((minOverdue - minIdx) / totalSpan) * 100;
          const widthPercent = (overlayCount / totalSpan) * 100;

          overdueStyle = {
            left: `${startOffset}%`,
            width: `${widthPercent}%`,
            backgroundColor: '#ef4444' // Merah Overdue
          };
        }
      }

      return {
        isStart: true,
        totalSpan,
        actualSpan,
        hasOverdue,
        overdueStyle,
        includedWeeks
      };
    }
  }
};
</script>

<style scoped>
@media print {
  * {
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
    color-adjust: exact !important;
  }

  @page {
    size: A4 landscape;
    margin: 4mm;
  }

  html, body {
    zoom: 75% !important;
    background-color: #ffffff !important;
  }

  .no-print {
    display: none !important;
  }

  .gantt-print-container {
    overflow: visible !important;
    max-width: 100% !important;
    width: 100% !important;
  }

  .gantt-table {
    width: 100% !important;
    table-layout: auto !important;
  }

  .wbs-col, .sticky {
    position: static !important;
    box-shadow: none !important;
    width: 180px !important;
    min-width: 180px !important;
    max-width: 180px !important;
  }

  .wbs-text {
    max-width: 110px !important;
  }

  .week-col {
    width: auto !important;
    min-width: 0 !important;
  }

  th, td {
    padding-left: 1px !important;
    padding-right: 1px !important;
    font-size: 7.5px !important;
    border-color: #e5e7eb !important;
  }
}
</style>