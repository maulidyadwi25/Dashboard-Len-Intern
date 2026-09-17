<template>
  <div class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden flex flex-col h-full relative table-card-container">
    <!-- Priority Tabs Filter -->
    <div class="p-3 px-4 border-b border-gray-200 bg-white shrink-0">
      <div class="flex items-center space-x-2 border-b border-gray-200 pb-2">
        <button 
          v-for="tab in priorityTabs" 
          :key="tab.name"
          @click="activeTab = tab.name"
          class="px-3 py-1.5 text-xs font-bold transition-all duration-200 flex items-center space-x-1.5 rounded-md shadow-xs cursor-pointer border"
          :class="getTabBadgeStyle(tab.name)"
        >
          <span class="transition-colors">{{ tab.name }}</span>
          <span class="px-1.5 py-0.2 rounded text-[10px] font-extrabold bg-white/20">
            {{ getCountByPriority(tab.name) }}
          </span>
        </button>
      </div>
    </div>

    <!-- Filter Controls & Download -->
    <div class="p-3 px-4 bg-gray-50/50 flex flex-wrap justify-between items-center gap-3 border-b border-gray-200 hide-on-print shrink-0">
      <div class="flex flex-wrap items-center gap-2">
        <div class="relative">
          <input 
            type="text" 
            v-model="searchQuery" 
            placeholder="Search variable, nominal, month, year..."
            class="bg-white border border-gray-300 rounded-md py-1.5 pl-8 pr-3 text-xs text-gray-700 focus:outline-none w-64 shadow-xs"
          />
          <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
          </svg>
        </div>

        <!-- Filter berdasarkan Status -->
        <select v-model="statusFilter" class="bg-white border border-gray-300 rounded-md py-1.5 px-3 text-xs text-gray-700 focus:outline-none shadow-xs cursor-pointer">
          <option value="">Filter Status (All)</option>
          <option value="In Progress">In Progress</option>
          <option value="Done">Done</option>
          <option value="Delayed">Delayed</option>
        </select>

        <!-- Filter berdasarkan Priority -->
        <select v-model="priorityFilter" class="bg-white border border-gray-300 rounded-md py-1.5 px-3 text-xs text-gray-700 focus:outline-none shadow-xs cursor-pointer">
          <option value="">Filter Priority (All)</option>
          <option value="High">High</option>
          <option value="Medium">Medium</option>
          <option value="Low">Low</option>
        </select>

        <!-- Filter berdasarkan Jenis -->
        <select v-model="jenisFilter" class="bg-white border border-gray-300 rounded-md py-1.5 px-3 text-xs text-gray-700 focus:outline-none shadow-xs cursor-pointer">
          <option value="">Filter Jenis (All)</option>
          <option value="Barang">Barang</option>
          <option value="Jasa">Jasa</option>
          <option value="BARANG & JASA">Barang & Jasa</option>
        </select>

        <!-- Tombol Reset -->
        <button @click="resetFilters" class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-xs font-semibold shadow-xs flex items-center space-x-1.5 transition-colors cursor-pointer">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
          </svg>
          <span>Reset</span>
        </button>
      </div>

      <!-- Tombol Download -->
      <button @click="downloadData" class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-xs font-semibold shadow-xs flex items-center space-x-1.5 transition-colors cursor-pointer">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
        </svg>
        <span>Download</span>
      </button>
    </div>

    <!-- TAMPILAN TABEL SLIDE HORIZONTAL -->
    <div class="overflow-x-auto flex-1 screen-table-view custom-scrollbar w-full">
      <table class="text-left border-collapse text-xs whitespace-nowrap w-full">
        <thead>
          <tr class="bg-blue-600 text-white font-semibold uppercase tracking-wider text-[11px]">
            <th rowspan="2" @click="sort('no')" class="p-3 text-center w-12 border-r border-blue-500 cursor-pointer hover:bg-blue-700 transition-colors">NO ↕</th>
            <th rowspan="2" @click="sort('name')" class="p-3 border-r border-blue-500 min-w-[220px] cursor-pointer hover:bg-blue-700 transition-colors">NAMA PENGADAAN ↕</th>
            <th rowspan="2" @click="sort('vendor')" class="p-3 border-r border-blue-500 min-w-[160px] cursor-pointer hover:bg-blue-700 transition-colors">NAMA VENDOR ↕</th>
            <th rowspan="2" @click="sort('targetToa')" class="p-3 border-r border-blue-500 cursor-pointer hover:bg-blue-700 transition-colors">TARGET TGL PENGADAAN ↕</th>
            <th rowspan="2" @click="sort('status')" class="p-3 text-center border-r border-blue-500 cursor-pointer hover:bg-blue-700 transition-colors">STATUS ↕</th>
            <th rowspan="2" @click="sort('progress')" class="p-3 text-center border-r border-blue-500 cursor-pointer hover:bg-blue-700 transition-colors">PROGRESS ↕</th>
            <th rowspan="2" @click="sort('priority')" class="p-3 text-center border-r border-blue-500 cursor-pointer hover:bg-blue-700 transition-colors">PRIORITY ↕</th>
            <th rowspan="2" @click="sort('jenis')" class="p-3 border-r border-blue-500 cursor-pointer hover:bg-blue-700 transition-colors">JENIS ↕</th>
            <th rowspan="2" @click="sort('paymentType')" class="p-3 border-r border-blue-500 cursor-pointer hover:bg-blue-700 transition-colors">PAYMENT TYPE ↕</th>
            <th rowspan="2" @click="sort('procPolicy')" class="p-3 border-r border-blue-500 cursor-pointer hover:bg-blue-700 transition-colors">PROC. POLICY ↕</th>
            <th rowspan="2" @click="sort('nilaiRab')" class="p-3 text-right border-r border-blue-500 cursor-pointer hover:bg-blue-700 transition-colors">NILAI RAB/RAP ({{ activeCurrency }}) ↕</th>
            <th rowspan="2" @click="sort('realisasi')" class="p-3 text-right border-r border-blue-500 cursor-pointer hover:bg-blue-700 transition-colors">REALISASI ({{ activeCurrency }}) ↕</th>
            <th rowspan="2" @click="sort('mataUang')" class="p-3 text-center border-r border-blue-500 cursor-pointer hover:bg-blue-700 transition-colors">MATA UANG ↕</th>
            <th rowspan="2" @click="sort('efisiensi')" class="p-3 text-center border-r border-blue-500 cursor-pointer hover:bg-blue-700 transition-colors">EFISIENSI ↕</th>
            <th rowspan="2" @click="sort('dueDate')" class="p-3 border-r border-blue-500 cursor-pointer hover:bg-blue-700 transition-colors">DUE DATE ↕</th>

            <th colspan="4" class="p-2 border-r border-blue-500 text-center bg-blue-700">DP</th>
            <th colspan="4" class="p-2 border-r border-blue-500 text-center bg-blue-700">TERMIN 1</th>
            <th colspan="4" class="p-2 border-r border-blue-500 text-center bg-blue-700">TERMIN 2</th>
            <th colspan="4" class="p-2 border-r border-blue-500 text-center bg-blue-700">TERMIN 3</th>
            
            <th rowspan="2" class="p-3 border-r border-blue-500">PAYMENT PRIORITY</th>
            <th rowspan="2" class="p-3 border-r border-blue-500">ESTIMATED DELIVERY</th>
            <th rowspan="2" class="p-3 border-r border-blue-500">DELIVERY STATUS</th>
            <th colspan="4" class="p-2 border-r border-blue-500 text-center bg-blue-800">PAYMENT STATUS</th>
            <th rowspan="2" class="p-3 text-center bg-blue-700 min-w-[90px]">ACTION</th>
          </tr>
          <tr class="bg-blue-600 text-white font-medium text-[10px]">
            <template v-for="tPrefix in ['dp', 'termin1', 'termin2', 'termin3']" :key="tPrefix">
              <th class="p-2 border-r border-blue-500 text-center bg-blue-700">TGL</th>
              <th class="p-2 border-r border-blue-500 text-center bg-blue-700">NILAI</th>
              <th class="p-2 border-r border-blue-500 text-center bg-blue-700">OK</th>
              <th class="p-2 border-r border-blue-500 text-center bg-blue-700">BPP</th>
            </template>
            <template v-for="pKey in ['dp', 't1', 't2', 't3']" :key="pKey">
              <th class="p-2 border-r border-blue-500 bg-blue-800 text-center">{{ pKey.toUpperCase() }}</th>
            </template>
          </tr>
        </thead>
        
        <tbody class="divide-y divide-gray-100 bg-white">
          <tr v-if="hierarchicalData.length === 0">
            <td colspan="54" class="text-center py-10 text-gray-400 font-medium">Data tidak ditemukan.</td>
          </tr>
          
          <template v-else v-for="item in hierarchicalData" :key="item.no">
            <!-- PARENT ROW -->
            <tr class="hover:bg-slate-50 transition-colors">
              <td class="p-3 text-center text-gray-800 font-bold border-r border-gray-100">
                <div class="flex items-center justify-center gap-1">
                  <button 
                    v-if="item.children && item.children.length > 0" 
                    @click="toggleRow(item.no)" 
                    class="w-5 h-5 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-[10px] hover:bg-blue-200 cursor-pointer"
                  >
                    {{ expandedRows.includes(item.no) ? '-' : '+' }}
                  </button>
                  <span v-else class="w-5 h-5"></span>
                  <span>{{ item.no }}</span>
                </div>
              </td>
              <td class="p-3 font-semibold text-gray-800 border-r border-gray-100 truncate max-w-[260px]" :title="item.name">{{ item.name }}</td>
              <td class="p-3 text-gray-600 border-r border-gray-100">{{ item.vendor }}</td>
              <td class="p-3 text-gray-600 border-r border-gray-100">{{ item.targetToa }}</td>
              <td class="p-3 text-center border-r border-gray-100">
                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full" :class="getStatusBadgeClass(item.status)">{{ item.status }}</span>
              </td>
              <!-- PROGRESS DROPDOWN -->
              <td class="p-3 text-center border-r border-gray-100">
                <select 
                  v-model="item.progress" 
                  @change="saveField(item, 'progress', item.progress)"
                  class="border rounded px-2 py-1 text-[11px] font-bold focus:outline-none shadow-xs cursor-pointer transition-colors"
                  :class="getProgressSelectStyle(item.progress)"
                >
                  <option v-for="progOpt in progressOptions" :key="progOpt" :value="progOpt" class="bg-white text-gray-700 font-normal">
                    {{ progOpt }}
                  </option>
                </select>
              </td>
              <!-- PRIORITY DROPDOWN -->
              <td class="p-3 text-center border-r border-gray-100">
                <select 
                  v-model="item.priority" 
                  @change="saveField(item, 'priority', item.priority)"
                  class="border rounded px-2 py-1 text-[11px] font-bold focus:outline-none shadow-xs cursor-pointer transition-colors"
                  :class="getPrioritySelectStyle(item.priority)"
                >
                  <option v-for="pOpt in priorityOptions" :key="pOpt" :value="pOpt" class="bg-white text-gray-700 font-normal">
                    {{ pOpt }}
                  </option>
                </select>
              </td>
              <td class="p-3 text-gray-600 border-r border-gray-100">{{ item.jenis }}</td>
              <td class="p-3 text-gray-600 border-r border-gray-100">{{ item.paymentType }}</td>
              <td class="p-3 text-gray-600 border-r border-gray-100">{{ item.procPolicy || '-' }}</td>
              <td class="p-3 text-right font-semibold text-gray-700 border-r border-gray-100">{{ formatCurrency(item.nilaiRab, item.mataUang) }}</td>
              <td class="p-3 text-right font-semibold text-gray-700 border-r border-gray-100">{{ formatCurrency(item.realisasi, item.mataUang) }}</td>
              <td class="p-3 text-center border-r border-gray-100">
                <span class="px-2 py-0.5 text-[10px] font-bold rounded-md shadow-2xs" :class="getCurrencyBadgeStyle(item.mataUang)">
                  {{ item.mataUang || 'IDR' }}
                </span>
              </td>
              <td class="p-3 text-center text-gray-600 border-r border-gray-100 font-semibold">{{ calculateEfficiency(item) }}</td>
              <td class="p-3 text-gray-600 border-r border-gray-100">{{ item.dueDate || '-' }}</td>

              <!-- SCROLLABLE TDs -->
              <template v-for="tKey in ['dp', 'termin1', 'termin2', 'termin3']" :key="tKey">
                <td class="p-2 text-gray-600 border-r border-gray-100 text-center">{{ item[tKey]?.tanggalSubmit || '-' }}</td>
                <td class="p-2 text-right text-gray-700 border-r border-gray-100">{{ item[tKey]?.nilai ? formatCurrency(item[tKey].nilai, item.mataUang) : '-' }}</td>
                <td class="p-2 text-gray-600 border-r border-gray-100 text-center">{{ item[tKey]?.grSes || '-' }}</td>
                <td class="p-2 text-gray-600 border-r border-gray-100 text-center">{{ item[tKey]?.noSpp || '-' }}</td>
              </template>

              <td class="p-2 text-gray-600 border-r border-gray-100 text-center">{{ item.paymentPriority || '-' }}</td>
              <td class="p-2 text-gray-600 border-r border-gray-100 text-center">{{ item.estimatedDelivery || '-' }}</td>
              <td class="p-2 text-gray-600 border-r border-gray-100 text-center">{{ item.deliveryStatus || '-' }}</td>

              <template v-for="pKey in ['dp', 't1', 't2', 't3']" :key="pKey">
                <td class="p-2 text-center border-r border-gray-100">
                  <span v-if="item.paymentStatus?.[pKey]" class="text-[9px] font-bold px-1.5 py-0.5 rounded" :class="getPaymentBadgeClass(item.paymentStatus[pKey])">{{ item.paymentStatus[pKey] }}</span>
                  <span v-else>-</span>
                </td>
              </template>

              <!-- ACTION COLUMN BUTTON -->
              <td class="p-2 text-center">
                <button 
                  @click="saveRowAction(item)" 
                  class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-[11px] font-semibold shadow-xs transition-colors cursor-pointer flex items-center justify-center space-x-1 mx-auto"
                >
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                  </svg>
                  <span>Save</span>
                </button>
              </td>
            </tr>

            <!-- CHILD ROWS (SUB NOMOR) -->
            <template v-if="expandedRows.includes(item.no) && item.children && item.children.length > 0">
              <tr v-for="child in item.children" :key="child.no" class="bg-blue-50/30 hover:bg-blue-50/60 transition-colors">
                <td class="p-3 pl-8 text-left text-gray-500 text-[10px] font-semibold border-r border-gray-100">
                  ↳ {{ child.no }}
                </td>
                <td class="p-3 pl-6 font-medium text-gray-700 border-r border-gray-100 truncate max-w-[260px]" :title="child.name">{{ child.name }}</td>
                <td class="p-3 text-gray-500 border-r border-gray-100">{{ child.vendor }}</td>
                <td class="p-3 text-gray-500 border-r border-gray-100">{{ child.targetToa }}</td>
                <td class="p-3 text-center border-r border-gray-100">
                  <span class="px-2 py-0.5 text-[10px] font-bold rounded-full" :class="getStatusBadgeClass(child.status)">{{ child.status }}</span>
                </td>
                <!-- PROGRESS ANAK DROPDOWN -->
                <td class="p-3 text-center border-r border-gray-100">
                  <select 
                    v-model="child.progress" 
                    @change="saveField(child, 'progress', child.progress)"
                    class="border rounded px-2 py-1 text-[11px] font-bold focus:outline-none shadow-xs cursor-pointer transition-colors"
                    :class="getProgressSelectStyle(child.progress)"
                  >
                    <option v-for="progOpt in progressOptions" :key="progOpt" :value="progOpt" class="bg-white text-gray-700 font-normal">
                      {{ progOpt }}
                    </option>
                  </select>
                </td>
                <!-- PRIORITY ANAK DROPDOWN -->
                <td class="p-3 text-center border-r border-gray-100">
                  <select 
                    v-model="child.priority" 
                    @change="saveField(child, 'priority', child.priority)"
                    class="border rounded px-2 py-1 text-[11px] font-bold focus:outline-none shadow-xs cursor-pointer transition-colors"
                    :class="getPrioritySelectStyle(child.priority)"
                  >
                    <option v-for="pOpt in priorityOptions" :key="pOpt" :value="pOpt" class="bg-white text-gray-700 font-normal">
                      {{ pOpt }}
                    </option>
                  </select>
                </td>
                <td class="p-3 text-gray-500 border-r border-gray-100">{{ child.jenis }}</td>
                <td class="p-3 text-gray-500 border-r border-gray-100">{{ child.paymentType }}</td>
                <td class="p-3 text-gray-500 border-r border-gray-100">{{ child.procPolicy || '-' }}</td>
                <td class="p-3 text-right font-medium text-gray-600 border-r border-gray-100">{{ formatCurrency(child.nilaiRab, child.mataUang) }}</td>
                <td class="p-3 text-right font-medium text-gray-600 border-r border-gray-100">{{ formatCurrency(child.realisasi, child.mataUang) }}</td>
                <td class="p-3 text-center border-r border-gray-100">
                  <span class="px-2 py-0.5 text-[10px] font-bold rounded-md shadow-2xs" :class="getCurrencyBadgeStyle(child.mataUang)">
                    {{ child.mataUang || 'IDR' }}
                  </span>
                </td>
                <td class="p-3 text-center text-gray-500 border-r border-gray-100 font-medium">{{ calculateEfficiency(child) }}</td>
                <td class="p-3 text-gray-500 border-r border-gray-100">{{ child.dueDate || '-' }}</td>

                <!-- SCROLLABLE TDs CHILD -->
                <template v-for="tKey in ['dp', 'termin1', 'termin2', 'termin3']" :key="tKey">
                  <td class="p-2 text-gray-500 border-r border-gray-100 text-center">{{ child[tKey]?.tanggalSubmit || '-' }}</td>
                  <td class="p-2 text-right text-gray-600 border-r border-gray-100">{{ child[tKey]?.nilai ? formatCurrency(child[tKey].nilai, child.mataUang) : '-' }}</td>
                  <td class="p-2 text-gray-500 border-r border-gray-100 text-center">{{ child[tKey]?.grSes || '-' }}</td>
                  <td class="p-2 text-gray-500 border-r border-gray-100 text-center">{{ child[tKey]?.noSpp || '-' }}</td>
                </template>
                <td class="p-2 text-gray-500 border-r border-gray-100 text-center">{{ child.paymentPriority || '-' }}</td>
                <td class="p-2 text-gray-500 border-r border-gray-100 text-center">{{ child.estimatedDelivery || '-' }}</td>
                <td class="p-2 text-gray-500 border-r border-gray-100 text-center">{{ child.deliveryStatus || '-' }}</td>
                <template v-for="pKey in ['dp', 't1', 't2', 't3']" :key="pKey">
                  <td class="p-2 text-center border-r border-gray-100">
                    <span v-if="child.paymentStatus?.[pKey]" class="text-[9px] font-bold px-1.5 py-0.5 rounded" :class="getPaymentBadgeClass(child.paymentStatus[pKey])">{{ child.paymentStatus[pKey] }}</span>
                    <span v-else>-</span>
                  </td>
                </template>

                <!-- ACTION COLUMN BUTTON (CHILD) -->
                <td class="p-2 text-center">
                  <button 
                    @click="saveRowAction(child)" 
                    class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-[11px] font-semibold shadow-xs transition-colors cursor-pointer flex items-center justify-center space-x-1 mx-auto"
                  >
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span>Save</span>
                  </button>
                </td>
              </tr>
            </template>
          </template>
        </tbody>
      </table>
    </div>

    <!-- Footer Counter -->
    <div class="p-3 px-4 bg-gray-50 border-t border-gray-200 text-xs text-gray-500 hide-on-print shrink-0">
      <span>Menampilkan {{ hierarchicalData.length }} Parent Data Utama</span>
    </div>

    <!-- Floating Toast Notification -->
    <transition enter-active-class="transform transition ease-out duration-300" enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2" enter-to-class="translate-y-0 opacity-100 sm:translate-x-0" leave-active-class="transition ease-in duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="toast.show" class="fixed bottom-5 right-5 z-50 flex items-center gap-2.5 px-4 py-3 rounded-xl shadow-2xl text-xs font-semibold backdrop-blur-xs border transition-all" :class="toast.isError ? 'bg-red-600 text-white border-red-700' : 'bg-emerald-700 text-white border-emerald-800'">
        <svg v-if="!toast.isError" class="w-4 h-4 text-white shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
        <svg v-else class="w-4 h-4 text-white shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        <span>{{ toast.message }}</span>
      </div>
    </transition>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'ProcurementTable3',
  props: {
    tableData: { type: Array, default: () => [] },
    selectedCurrency: { type: String, default: '' },
    exchangeRates: { type: Object, default: () => null },
    masterProjectId: { type: [Number, String], default: 1 },
  },
  data() {
    return {
      activeTab: 'All',
      searchQuery: '',
      statusFilter: '',  
      priorityFilter: '', 
      jenisFilter: '',    
      currencyFilter: this.selectedCurrency || '', 
      expandedRows: [],
      priorityTabs: [{ name: 'All' }, { name: 'High' }, { name: 'Medium' }, { name: 'Low' }],
      sortKey: 'no',
      sortOrder: 'asc',
      progressOptions: ['SPH', 'HPS', 'RKS', 'PR', 'RFQ', 'NEGOSIASI', 'PO', 'KONTRAK', 'PRODUCTION', 'DELIVERED'],
      priorityOptions: ['High', 'Medium', 'Low'],
      toast: {
        show: false,
        message: '',
        isError: false,
        timer: null
      }
    }
  },
  watch: {
    selectedCurrency(newVal) {
      this.currencyFilter = newVal || '';
    }
  },
  computed: {
    activeCurrency() {
      return (this.currencyFilter || this.selectedCurrency || 'IDR').toUpperCase();
    },
    hierarchicalData() {
      if (!Array.isArray(this.tableData)) return [];
      
      const parents = [];
      const childrenMap = {}; 

      const normalizePriority = (val) => {
        const s = String(val || '').trim().toLowerCase();
        if (s === 'high') return 'High';
        if (s === 'low') return 'Low';
        if (s === 'normal' || s === 'medium') return 'Medium';
        return 'Medium';
      };

      const filtered = this.tableData.map(item => ({
        ...item,
        priority: normalizePriority(item.priority)
      })).filter(item => {
        const matchesTab = this.activeTab === 'All' || String(item.priority).toLowerCase() === this.activeTab.toLowerCase();
        
        let matchesSearch = true;
        if (this.searchQuery) {
          const query = this.searchQuery.trim().toLowerCase();
          
          const searchableText = [
            item.no,
            item.name,
            item.vendor,
            item.targetToa,
            item.status,
            item.progress,
            item.priority,
            item.jenis,
            item.paymentType,
            item.procPolicy,
            item.mataUang,
            item.efisiensi,
            item.dueDate,
            item.nilaiRab,
            item.realisasi,
            ...(item.children ? item.children.map(c => Object.values(c).join(' ')) : [])
          ].join(' ').toLowerCase();

          const cleanQuery = query.replace(/[.,\s]/g, '');
          const cleanSearchable = searchableText.replace(/[.,\s]/g, '');

          const isTextMatch = searchableText.includes(query);
          const isNominalMatch = cleanQuery && !isNaN(cleanQuery) && cleanSearchable.includes(cleanQuery);

          matchesSearch = isTextMatch || isNominalMatch;
        }
        
        const matchesStatusDropdown = !this.statusFilter || String(item.status).toLowerCase() === this.statusFilter.toLowerCase();
        const matchesPriorityDropdown = !this.priorityFilter || String(item.priority).toLowerCase() === this.priorityFilter.toLowerCase();
        
        let matchesJenisDropdown = true;
        if (this.jenisFilter) {
          const itemJenis = String(item.jenis || '').toUpperCase();
          const filterVal = String(this.jenisFilter).toUpperCase();
          if (filterVal === 'BARANG & JASA') {
            matchesJenisDropdown = itemJenis.includes('BARANG') && itemJenis.includes('JASA');
          } else {
            matchesJenisDropdown = itemJenis.includes(filterVal) && !itemJenis.includes('&');
          }
        }

        // Tampilkan semua baris (data sudah terkonversi ke currency yang dipilih)
        return matchesTab && matchesSearch && matchesStatusDropdown && matchesPriorityDropdown && matchesJenisDropdown;
      });

      filtered.forEach((item) => {
        const noStr = String(item.no).trim();
        if (noStr.includes('.')) {
          const parentNo = noStr.split('.')[0];
          if (!childrenMap[parentNo]) childrenMap[parentNo] = [];
          childrenMap[parentNo].push(item);
        } else {
          parents.push({ ...item, children: [] });
        }
      });

      parents.forEach(parent => {
        const parentNo = String(parent.no).trim();
        if (childrenMap[parentNo]) {
          parent.children = childrenMap[parentNo].sort((a, b) => {
            const aSub = parseFloat(String(a.no).replace(/[^0-9.]/g, ''));
            const bSub = parseFloat(String(b.no).replace(/[^0-9.]/g, ''));
            return aSub - bSub;
          });
        }
      });

      return parents.sort((a, b) => {
        let valA = a[this.sortKey];
        let valB = b[this.sortKey];

        const isEmptyA = valA === undefined || valA === null || valA === '-' || valA === '';
        const isEmptyB = valB === undefined || valB === null || valB === '-' || valB === '';

        if (isEmptyA && isEmptyB) return 0;
        if (isEmptyA) return 1;  
        if (isEmptyB) return -1;

        const isDateKey = ['targetToa', 'dueDate'].includes(this.sortKey);
        if (isDateKey) {
          valA = new Date(valA).getTime() || 0;
          valB = new Date(valB).getTime() || 0;
        } else {
          const cleanA = typeof valA === 'string' ? valA.replace(/\./g, '') : valA;
          const cleanB = typeof valB === 'string' ? valB.replace(/\./g, '') : valB;

          if (!isNaN(cleanA) && !isNaN(cleanB) && cleanA !== '' && cleanB !== '') {
            valA = Number(cleanA);
            valB = Number(cleanB);
          } else {
            valA = String(valA).toLowerCase();
            valB = String(valB).toLowerCase();
          }
        }

        if (valA < valB) return this.sortOrder === 'asc' ? -1 : 1;
        if (valA > valB) return this.sortOrder === 'asc' ? 1 : -1;
        return 0;
      });
    }
  },
  methods: {
    toggleRow(parentNo) {
      const index = this.expandedRows.indexOf(parentNo);
      if (index === -1) {
        this.expandedRows.push(parentNo);
      } else {
        this.expandedRows.splice(index, 1);
      }
    },
    sort(key) {
      if (this.sortKey === key) {
        this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
      } else {
        this.sortKey = key;
        this.sortOrder = 'asc';
      }
    },
    resetFilters() {
      this.activeTab = 'All';
      this.searchQuery = '';
      this.statusFilter = '';
      this.priorityFilter = '';
      this.jenisFilter = '';
      this.currencyFilter = '';
      this.expandedRows = [];
      this.sortKey = 'no';
      this.sortOrder = 'asc';
      this.$emit('update:selectedCurrency', '');
      this.$emit('filter-currency', '');
    },
    onCurrencyChange() {
      this.$emit('update:selectedCurrency', this.currencyFilter);
      this.$emit('filter-currency', this.currencyFilter);
    },
    getCurrencyBadgeStyle(currency) {
      const c = String(currency || 'IDR').toUpperCase();
      if (c === 'USD') return 'bg-emerald-100 text-emerald-800 border border-emerald-300';
      if (c === 'EUR') return 'bg-purple-100 text-purple-800 border border-purple-300';
      if (c === 'SGD') return 'bg-amber-100 text-amber-800 border border-amber-300';
      return 'bg-blue-50 text-blue-700 border border-blue-200';
    },
    showToast(message, isError = false) {
      if (this.toast.timer) clearTimeout(this.toast.timer);
      this.toast.show = true;
      this.toast.message = message;
      this.toast.isError = isError;
      this.toast.timer = setTimeout(() => {
        this.toast.show = false;
      }, 3500);
    },
    async saveRowAction(rowItem) {
      try {
        const pId = this.masterProjectId || 1;
        const [resProg, resPrio] = await Promise.all([
          axios.post('http://127.0.0.1:8000/api/procurements/update', {
            master_project_id: pId,
            no: rowItem.no,
            field: 'progress',
            value: rowItem.progress,
          }),
          axios.post('http://127.0.0.1:8000/api/procurements/update', {
            master_project_id: pId,
            no: rowItem.no,
            field: 'priority',
            value: rowItem.priority,
          }),
        ]);
        this.showToast(`Nomor ${rowItem.no} tersimpan di Database ✓`);
        this.$emit('update-row', rowItem);
      } catch (err) {
        console.error('Failed to save row action:', err);
        this.showToast(`Gagal menyimpan nomor ${rowItem.no}: ${err.response?.data?.message || err.message}`, true);
      }
    },
    async saveField(item, field, value) {
      // 1. Update item lokal & tableData langsung agar UI tidak rollback
      item[field] = value;
      if (Array.isArray(this.tableData)) {
        const orig = this.tableData.find(x => String(x.no) === String(item.no));
        if (orig) orig[field] = value;
      }
      this.$emit('update-item-field', { no: item.no, field, value });

      try {
        const pId = this.masterProjectId || 1;
        const res = await axios.post('http://127.0.0.1:8000/api/procurements/update', {
          master_project_id: pId,
          no: item.no,
          field: field,
          value: value,
        });
        if (res.data?.status === 'success') {
          this.showToast(`No. ${item.no}: ${field.toUpperCase()} → "${value}" (Tersimpan di Database ✓)`);
        } else {
          this.showToast(`Gagal mengupdate ${field} nomor ${item.no}`, true);
        }
      } catch (err) {
        console.error('Failed to save procurement field:', err);
        this.showToast(`Gagal menyimpan: ${err.response?.data?.message || err.message}`, true);
      }
    },
    calculateEfficiency(item) {
      const rab = parseFloat(item.nilaiRab);
      const real = parseFloat(item.realisasi);
      if (!rab || isNaN(rab) || rab === 0) return item.efisiensi || '0%';
      if (!real || isNaN(real)) return item.efisiensi || '0%';
      
      const diff = rab - real;
      const percentage = (diff / rab) * 100;
      return percentage.toFixed(2) + '%';
    },
    getCountByPriority(pName) {
      if (!Array.isArray(this.tableData)) return 0;
      if (pName === 'All') return this.tableData.length;
      return this.tableData.filter(i => {
        const pr = String(i.priority || '').toLowerCase();
        const tab = pName.toLowerCase();
        if (tab === 'low') return pr === 'low' || pr === 'normal';
        return pr === tab;
      }).length;
    },
    getTabBadgeStyle(tabName) {
      const active = this.activeTab === tabName;
      if (tabName === 'High') {
        return active 
          ? 'bg-red-500 text-white border-red-500 shadow-sm' 
          : 'bg-white text-gray-700 border-gray-200 hover:bg-red-500 hover:text-white hover:border-red-500 hover:shadow-sm';
      }
      if (tabName === 'Medium') {
        return active 
          ? 'bg-yellow-400 text-white border-yellow-400 shadow-sm' 
          : 'bg-white text-gray-700 border-gray-200 hover:bg-yellow-400 hover:text-white hover:border-yellow-400 hover:shadow-sm';
      }
      if (tabName === 'Low' || tabName === 'Normal') {
        return active 
          ? 'bg-emerald-500 text-white border-emerald-500 shadow-sm' 
          : 'bg-white text-gray-700 border-gray-200 hover:bg-emerald-500 hover:text-white hover:border-emerald-500 hover:shadow-sm';
      }
      return active 
        ? 'bg-blue-600 text-white border-blue-600 shadow-sm' 
        : 'bg-white text-gray-700 border-gray-200 hover:bg-blue-600 hover:text-white hover:border-blue-600 hover:shadow-sm';
    },
    getStatusBadgeClass(s) {
      const st = String(s).toLowerCase();
      if (st.includes('progress')) return 'bg-amber-100 text-amber-700 border border-amber-200';
      if (st.includes('completed') || st.includes('done')) return 'bg-emerald-100 text-emerald-700 border border-emerald-200';
      return 'bg-rose-100 text-rose-700 border border-rose-200';
    },
    getProgressSelectStyle(progress) {
      const pr = String(progress || '').toUpperCase();
      if (pr === 'PR') return 'bg-amber-100 text-amber-800 border-amber-300';
      if (pr === 'PO') return 'bg-emerald-100 text-emerald-800 border-emerald-300';
      if (pr === 'KONTRAK') return 'bg-emerald-100 text-emerald-700 border-emerald-200';
      if (pr === 'DELIVERED') return 'bg-emerald-700 text-white border-emerald-800 font-bold';
      if (pr === 'PRODUCTION') return 'bg-sky-100 text-sky-800 border-sky-300';
      if (pr === 'NEGOSIASI' || pr === 'RFQ') return 'bg-gray-100 text-gray-800 border-gray-300';
      return 'bg-white text-gray-700 border-gray-300';
    },
    getPrioritySelectStyle(priority) {
      const pr = String(priority || '').toLowerCase();
      if (pr.includes('high')) return 'bg-red-500 text-white border-red-600 font-bold';
      if (pr.includes('medium')) return 'bg-yellow-400 text-white border-yellow-500 font-bold';
      if (pr.includes('normal') || pr.includes('low')) return 'bg-emerald-500 text-white border-emerald-600 font-bold';
      return 'bg-white text-gray-700 border-gray-300';
    },
    getPriorityBadgeClass(p) {
      const pr = String(p).toLowerCase();
      if (pr.includes('high')) return 'bg-red-500 text-white';
      if (pr.includes('medium')) return 'bg-yellow-400 text-white';
      if (pr.includes('normal') || pr.includes('low')) return 'bg-emerald-500 text-white';
      return 'bg-gray-100 text-gray-700';
    },
    formatCurrency(val, currency = '') {
      if (!val && val !== 0) return '-';
      const num = Number(val);
      if (isNaN(num)) return '-';
      const curr = (currency || this.currencyFilter || this.selectedCurrency || 'IDR').toUpperCase();
      if (curr === 'EUR') {
        return '€ ' + new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num);
      }
      if (curr === 'USD') {
        return '$ ' + new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num);
      }
      if (curr === 'SGD') {
        return 'S$ ' + new Intl.NumberFormat('en-SG', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num);
      }
      return 'Rp ' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(num);
    },
    getPaymentBadgeClass(s) {
      if (!s) return '';
      const st = String(s).toLowerCase();
      if (st.includes('not paid')) return 'bg-rose-100 text-rose-500';
      if (st.includes('paid')) return 'bg-emerald-100 text-emerald-700';
      return 'bg-gray-100 text-gray-700';
    },
    downloadData() { window.print(); }
  }
}
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
  height: 10px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: #f1f5f9; 
  border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #cbd5e1; 
  border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #94a3b8; 
}

@media print {
  .table-card-container {
    height: auto !important;
    max-height: none !important;
    overflow: visible !important;
    border: none !important;
    box-shadow: none !important;
  }

  .screen-table-view {
    overflow: visible !important;
    width: 100% !important;
    height: auto !important;
  }

  .screen-table-view table {
    width: 100% !important;
    table-layout: auto !important;
    zoom: 0.48 !important; /* Pas agar semua 39 kolom dari NO sampai ACTION tampil utuh */
  }

  .screen-table-view th, 
  .screen-table-view td {
    padding: 3px 4px !important;
    white-space: nowrap !important;
  }
}
</style>