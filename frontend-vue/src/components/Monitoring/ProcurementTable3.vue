<template>
  <div class="bg-white rounded-xl border border-outline-variant shadow-xs overflow-hidden flex flex-col h-full relative table-card-container">
    <!-- Priority Tabs Filter -->
    <div class="p-3 px-4 border-b border-outline-variant bg-white shrink-0">
      <div class="flex items-center space-x-2 border-b border-outline-variant pb-2">
        <button 
          v-for="tab in priorityTabs" 
          :key="tab.name"
          @click="activeTab = tab.name"
          class="px-3 py-1.5 text-body-sm font-bold transition-all duration-200 flex items-center space-x-1.5 rounded-md shadow-xs cursor-pointer border"
          :class="getTabBadgeStyle(tab.name)"
        >
          <span class="transition-colors">{{ tab.name }}</span>
          <span class="px-1.5 py-0.2 rounded text-label-caps font-extrabold bg-white/20 tracking-wider">
            {{ getCountByPriority(tab.name) }}
          </span>
        </button>
      </div>
    </div>

    <!-- Filter Controls & Download -->
    <div class="p-3 px-4 bg-background/50 flex flex-wrap justify-between items-center gap-3 border-b border-outline-variant hide-on-print shrink-0">
      <div class="flex flex-wrap items-center gap-2">
        <div class="relative">
          <input
            type="text"
            v-model="searchQuery"
            placeholder="Search procurement..."
            class="bg-white border border-outline-variant rounded-md py-1.5 pl-8 pr-3 text-body-sm text-on-surface-variant focus:outline-none w-64 shadow-xs"
          />
          <svg class="w-3.5 h-3.5 text-slate-500 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
          </svg>
        </div>

        <!-- Status Filter -->
        <select v-model="statusFilter" class="bg-white border border-outline-variant rounded-md py-1.5 px-3 text-body-sm text-on-surface-variant focus:outline-none shadow-xs cursor-pointer">
          <option value="">Procurement Status</option>
          <option v-for="sOpt in statusOptions" :key="sOpt" :value="sOpt">{{ sOpt }}</option>
        </select>

        <!-- Delivery Status Filter -->
        <select v-model="deliveryStatusFilter" class="bg-white border border-outline-variant rounded-md py-1.5 px-3 text-body-sm text-on-surface-variant focus:outline-none shadow-xs cursor-pointer">
          <option value="">Delivery Status</option>
          <option v-for="dOpt in deliveryStatusOptions" :key="dOpt" :value="dOpt">{{ dOpt }}</option>
        </select>
      </div>

      <div class="flex items-center space-x-2">
        <!-- Advanced Filters (hidden to match reference layout) -->
        <button
          @click="showAdvancedFilters = !showAdvancedFilters"
          class="w-8 h-8 border border-outline-variant hover:bg-[#737686]/15 bg-white rounded-md shadow-xs flex items-center justify-center transition-colors cursor-pointer"
          :class="showAdvancedFilters ? 'bg-primary-500 border-primary-500 text-white' : 'text-slate-500'"
          title="Filter lanjutan"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
          </svg>
        </button>

        <!-- Download Button -->
        <button @click="downloadData" class="px-3.5 py-1.5 bg-primary-500 hover:bg-primary-600 text-white rounded-md text-body-sm font-semibold shadow-xs flex items-center space-x-1.5 transition-colors cursor-pointer">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
          </svg>
          <span>Download</span>
        </button>
      </div>
    </div>

    <!-- Advanced Filters (kept for future use, currently hidden) -->
    <div v-if="showAdvancedFilters" class="p-3 px-4 bg-background/50 flex flex-wrap items-center gap-2 border-b border-outline-variant hide-on-print shrink-0">
      <span class="text-label-caps font-bold uppercase tracking-wider text-slate-500 mr-1">Filter Lanjutan:</span>

      <select v-model="priorityFilter" class="bg-white border border-outline-variant rounded-md py-1.5 px-3 text-body-sm text-on-surface-variant focus:outline-none shadow-xs cursor-pointer">
        <option value="">Filter Priority (All)</option>
        <option value="High">High</option>
        <option value="Medium">Medium</option>
        <option value="Low">Low</option>
      </select>

      <select v-model="jenisFilter" class="bg-white border border-outline-variant rounded-md py-1.5 px-3 text-body-sm text-on-surface-variant focus:outline-none shadow-xs cursor-pointer">
        <option value="">Filter Jenis (All)</option>
        <option value="Barang">Barang</option>
        <option value="Jasa">Jasa</option>
        <option value="BARANG & JASA">Barang & Jasa</option>
      </select>

      <!-- Toggle Payment Columns (DP & Termin) -->
      <button
        @click="showPaymentColumns = !showPaymentColumns"
        class="px-3.5 py-1.5 border border-outline-variant hover:bg-[#737686]/15 text-on-surface-variant bg-white rounded-md text-body-sm font-semibold shadow-xs flex items-center space-x-1.5 transition-colors cursor-pointer"
        :title="showPaymentColumns ? 'Sembunyikan Kolom Pembayaran' : 'Tampilkan Kolom Pembayaran'"
      >
        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path v-if="showPaymentColumns" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"></path>
          <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
        </svg>
        <span>{{ showPaymentColumns ? 'Hide DP & Termin' : 'Show DP & Termin' }}</span>
      </button>

      <!-- Reset Filters Button -->
      <button @click="resetFilters" class="px-3.5 py-1.5 bg-primary-500 hover:bg-primary-600 text-white rounded-md text-body-sm font-semibold shadow-xs flex items-center space-x-1.5 transition-colors cursor-pointer">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
        </svg>
        <span>Reset</span>
      </button>
    </div>

    <!-- Scrollable Table View -->
    <div class="overflow-x-auto flex-1 screen-table-view custom-scrollbar w-full">
      <table class="text-left border-collapse text-body-sm whitespace-nowrap w-full">
        <thead>
          <tr class="bg-primary-500 text-white font-semibold uppercase tracking-wider text-label-caps">
            <th :rowspan="showPaymentColumns ? 2 : 1" @click="sort('no')" class="px-2 py-3 text-center w-16 min-w-[64px] max-w-[64px] border-r border-primary-400 cursor-pointer hover:bg-primary-600 transition-colors sticky left-0 z-30 bg-primary-500">NO ↕</th>
            <th :rowspan="showPaymentColumns ? 2 : 1" @click="sort('name')" class="p-3 border-r border-primary-400 w-[240px] min-w-[240px] max-w-[240px] cursor-pointer hover:bg-primary-600 transition-colors sticky left-16 z-30 bg-primary-500">NAMA PENGADAAN ↕</th>
            <th :rowspan="showPaymentColumns ? 2 : 1" class="p-3 border-r border-primary-400 w-[200px] min-w-[200px] max-w-[200px] transition-colors sticky left-[304px] z-30 bg-primary-500 shadow-[4px_0_8px_-3px_rgba(0,0,0,0.25)]">NAMA PROYEK</th>
            <th :rowspan="showPaymentColumns ? 2 : 1" @click="sort('vendor')" class="p-3 border-r border-primary-400 min-w-[160px] cursor-pointer hover:bg-primary-600 transition-colors">NAMA VENDOR ↕</th>
            <th :rowspan="showPaymentColumns ? 2 : 1" @click="sort('targetToa')" class="p-3 border-r border-primary-400 cursor-pointer hover:bg-primary-600 transition-colors">TARGET TGL PENGADAAN ↕</th>
            <th :rowspan="showPaymentColumns ? 2 : 1" @click="sort('status')" class="p-3 text-center border-r border-primary-400 cursor-pointer hover:bg-primary-600 transition-colors">STATUS ↕</th>
            <th :rowspan="showPaymentColumns ? 2 : 1" @click="sort('progress')" class="p-3 text-center border-r border-primary-400 cursor-pointer hover:bg-primary-600 transition-colors">STATUS TAHAPAN ↕</th>
            <th :rowspan="showPaymentColumns ? 2 : 1" @click="sort('priority')" class="p-3 text-center border-r border-primary-400 cursor-pointer hover:bg-primary-600 transition-colors">PRIORITY ↕</th>
            <th :rowspan="showPaymentColumns ? 2 : 1" @click="sort('jenis')" class="p-3 border-r border-primary-400 cursor-pointer hover:bg-primary-600 transition-colors">JENIS ↕</th>
            <th :rowspan="showPaymentColumns ? 2 : 1" @click="sort('paymentType')" class="p-3 border-r border-primary-400 cursor-pointer hover:bg-primary-600 transition-colors">PAYMENT TYPE ↕</th>
            <th :rowspan="showPaymentColumns ? 2 : 1" @click="sort('procPolicy')" class="p-3 border-r border-primary-400 cursor-pointer hover:bg-primary-600 transition-colors">PROC. POLICY ↕</th>
            <th :rowspan="showPaymentColumns ? 2 : 1" @click="sort('noHps')" class="p-3 border-r border-primary-400 cursor-pointer hover:bg-primary-600 transition-colors">NO HPS ↕</th>
            <th :rowspan="showPaymentColumns ? 2 : 1" @click="sort('tanggalHps')" class="p-3 border-r border-primary-400 cursor-pointer hover:bg-primary-600 transition-colors">TANGGAL HPS ↕</th>
            <th :rowspan="showPaymentColumns ? 2 : 1" @click="sort('rks')" class="p-3 border-r border-primary-400 cursor-pointer hover:bg-primary-600 transition-colors">RKS ↕</th>
            <th :rowspan="showPaymentColumns ? 2 : 1" @click="sort('tanggalRks')" class="p-3 border-r border-primary-400 cursor-pointer hover:bg-primary-600 transition-colors">TANGGAL RKS ↕</th>
            <th :rowspan="showPaymentColumns ? 2 : 1" @click="sort('picOme')" class="p-3 border-r border-primary-400 cursor-pointer hover:bg-primary-600 transition-colors">PIC OME ↕</th>
            <th :rowspan="showPaymentColumns ? 2 : 1" @click="sort('noPr')" class="p-3 border-r border-primary-400 cursor-pointer hover:bg-primary-600 transition-colors">NO PR ↕</th>
            <th :rowspan="showPaymentColumns ? 2 : 1" @click="sort('procOps')" class="p-3 border-r border-primary-400 cursor-pointer hover:bg-primary-600 transition-colors">PROC. OPS ↕</th>
            <th :rowspan="showPaymentColumns ? 2 : 1" @click="sort('noPo')" class="p-3 border-r border-primary-400 cursor-pointer hover:bg-primary-600 transition-colors">NO PO ↕</th>
            <th :rowspan="showPaymentColumns ? 2 : 1" @click="sort('kontrakStatus')" class="p-3 text-center border-r border-primary-400 cursor-pointer hover:bg-primary-600 transition-colors">STATUS KONTRAK ↕</th>
            <th :rowspan="showPaymentColumns ? 2 : 1" @click="sort('nilaiRab')" class="p-3 text-right border-r border-primary-400 cursor-pointer hover:bg-primary-600 transition-colors">NILAI RAB/RAP ({{ activeCurrency }}) ↕</th>
            <th :rowspan="showPaymentColumns ? 2 : 1" @click="sort('realisasi')" class="p-3 text-right border-r border-primary-400 cursor-pointer hover:bg-primary-600 transition-colors">REALISASI ({{ activeCurrency }}) ↕</th>
            <th :rowspan="showPaymentColumns ? 2 : 1" @click="sort('mataUang')" class="p-3 text-center border-r border-primary-400 cursor-pointer hover:bg-primary-600 transition-colors">MATA UANG ↕</th>
            <th :rowspan="showPaymentColumns ? 2 : 1" @click="sort('efisiensi')" class="p-3 text-center border-r border-primary-400 cursor-pointer hover:bg-primary-600 transition-colors">EFISIENSI ↕</th>
            <th :rowspan="showPaymentColumns ? 2 : 1" @click="sort('dueDate')" class="p-3 border-r border-primary-400 cursor-pointer hover:bg-primary-600 transition-colors">DUE DATE ↕</th>
            <th :rowspan="showPaymentColumns ? 2 : 1" @click="sort('estimatedDelivery')" class="p-3 border-r border-primary-400 cursor-pointer hover:bg-primary-600 transition-colors">ESTIMATED DELIVERY ↕</th>
            <th :rowspan="showPaymentColumns ? 2 : 1" @click="sort('deliveryStatus')" class="p-3 border-r border-primary-400 cursor-pointer hover:bg-primary-600 transition-colors">DELIVERY STATUS ↕</th>
            <!-- Payment & Delivery Columns (can be hidden) -->
            <template v-if="showPaymentColumns">
              <th colspan="4" class="p-2 border-r border-primary-400 text-center bg-primary-600">DP</th>
              <th colspan="4" class="p-2 border-r border-primary-400 text-center bg-primary-600">TERMIN 1</th>
              <th colspan="4" class="p-2 border-r border-primary-400 text-center bg-primary-600">TERMIN 2</th>
              <th colspan="4" class="p-2 border-r border-primary-400 text-center bg-primary-600">TERMIN 3</th>
              
              <th rowspan="2" class="p-3 border-r border-primary-400">PAYMENT PRIORITY</th>
              <th colspan="4" class="p-2 border-r border-primary-400 text-center bg-primary-700">PAYMENT STATUS</th>
            </template>

            <th :rowspan="showPaymentColumns ? 2 : 1" class="p-3 text-center bg-primary-600 min-w-[110px]">ACTION</th>
          </tr>
          <tr v-if="showPaymentColumns" class="bg-primary-500 text-white font-medium text-label-caps tracking-wider">
            <template v-for="tPrefix in ['dp', 'termin1', 'termin2', 'termin3']" :key="tPrefix">
              <th class="p-2 border-r border-primary-400 text-center bg-primary-600">TGL</th>
              <th class="p-2 border-r border-primary-400 text-center bg-primary-600">NILAI</th>
              <th class="p-2 border-r border-primary-400 text-center bg-primary-600">OK</th>
              <th class="p-2 border-r border-primary-400 text-center bg-primary-600">BPP</th>
            </template>
            <template v-for="pKey in ['dp', 't1', 't2', 't3']" :key="pKey">
              <th class="p-2 border-r border-primary-400 bg-primary-700 text-center">{{ pKey.toUpperCase() }}</th>
            </template>
          </tr>
        </thead>
        
        <tbody class="divide-y divide-gray-100 bg-white">
          <tr v-if="hierarchicalData.length === 0">
            <td :colspan="showPaymentColumns ? 49 : 28" class="text-center py-10 text-slate-500 font-medium">Data tidak ditemukan.</td>
          </tr>
          
          <template v-else v-for="(item, pIdx) in pagedData" :key="item.no">
            <!-- Main Row -->
            <tr class="group transition-colors" :class="pIdx % 2 === 1 ? 'bg-background hover:bg-slate-100' : 'bg-white hover:bg-slate-50'">
              <td class="px-2 py-3 text-center text-on-surface font-bold border-r border-gray-100 sticky left-0 z-20 w-16 min-w-[64px] max-w-[64px]" :class="[pIdx % 2 === 1 ? 'bg-background group-hover:bg-slate-100' : 'bg-white group-hover:bg-slate-50']">
                <div class="flex items-center justify-center gap-1">
                  <button 
                    v-if="item.children && item.children.length > 0" 
                    @click="toggleRow(item.no)" 
                    class="w-5 h-5 rounded-full bg-primary-400/20 text-primary-500 flex items-center justify-center font-bold text-label-caps hover:bg-primary-400 cursor-pointer tracking-wider"
                  >
                    {{ expandedRows.includes(item.no) ? '-' : '+' }}
                  </button>
                  <span v-else class="w-5 h-5"></span>
                  <span>{{ item.no }}</span>
                </div>
              </td>
              <!-- Fixed Column: Procurement Name -->
              <td class="p-3 font-semibold text-on-surface border-r border-gray-100 truncate w-[240px] min-w-[240px] max-w-[240px] sticky left-16 z-20 name-col" :class="[pIdx % 2 === 1 ? 'bg-background group-hover:bg-slate-100' : 'bg-white group-hover:bg-slate-50']" :title="item.name">{{ item.name }}</td>
              <!-- Fixed Column: Project Name -->
              <td class="p-3 text-on-surface-variant border-r border-gray-100 truncate w-[200px] min-w-[200px] max-w-[200px] sticky left-[304px] z-20 shadow-[4px_0_8px_-3px_rgba(0,0,0,0.06)] name-col" :class="[pIdx % 2 === 1 ? 'bg-background group-hover:bg-slate-100' : 'bg-white group-hover:bg-slate-50']" :title="projectName">{{ projectName || '-' }}</td>
              <td class="p-3 text-on-surface-variant border-r border-gray-100">{{ item.vendor }}</td>
              <td class="p-3 text-on-surface-variant border-r border-gray-100">{{ item.targetToa }}</td>
              <td class="p-3 text-center border-r border-gray-100 relative">
                <span class="px-2 py-0.5 text-label-caps font-bold rounded-full pointer-events-none tracking-wider" :class="getStatusBadgeClass(item.status)">{{ item.status }}</span>
                <select
                  v-if="editingNo === String(item.no)"
                  v-model="item.status"
                  @change="saveField(item, 'status', item.status)"
                  title="Klik untuk mengubah" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                >
                  <option v-for="sOpt in statusOptions" :key="sOpt" :value="sOpt">
                    {{ sOpt }}
                  </option>
                </select>
              </td>
              <!-- Progress Badge (click to edit inline) -->
              <td class="p-3 text-center border-r border-gray-100 relative">
                <span class="px-2 py-0.5 text-label-caps font-bold rounded-full whitespace-nowrap inline-flex items-center gap-1.5 pointer-events-none tracking-wider" :class="getProgressBadgeClass(item.progress)"><span class="w-1.5 h-1.5 rounded-full bg-current"></span>{{ progressTahapanLabel(item.progress) }}</span>
                <select
                  v-if="editingNo === String(item.no)"
                  v-model="item.progress"
                  @change="saveField(item, 'progress', item.progress)"
                  title="Klik untuk mengubah" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                >
                  <option v-for="progOpt in progressOptions" :key="progOpt" :value="progOpt">
                    {{ progOpt }}
                  </option>
                </select>
              </td>
              <!-- Priority (click to edit inline) -->
              <td class="p-3 text-center border-r border-gray-100 relative">
                <span class="pointer-events-none text-body-sm font-bold whitespace-nowrap" :class="getPriorityTextClass(item.priority)">{{ priorityMark(item.priority) }} {{ item.priority }}</span>
                <select
                  v-if="editingNo === String(item.no)"
                  v-model="item.priority"
                  @change="saveField(item, 'priority', item.priority)"
                  title="Klik untuk mengubah" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                >
                  <option v-for="pOpt in priorityOptions" :key="pOpt" :value="pOpt">
                    {{ pOpt }}
                  </option>
                </select>
              </td>
              <td class="p-3 text-on-surface-variant border-r border-gray-100">{{ item.jenis }}</td>
              <td class="p-3 text-on-surface-variant border-r border-gray-100">{{ item.paymentType }}</td>
              <td class="p-3 text-on-surface-variant border-r border-gray-100">{{ item.procPolicy || '-' }}</td>
              <td class="p-3 text-on-surface-variant border-r border-gray-100">{{ item.noHps || '-' }}</td>
              <td class="p-3 text-on-surface-variant border-r border-gray-100 whitespace-nowrap">{{ item.tanggalHps || '-' }}</td>
              <td class="p-3 text-on-surface-variant border-r border-gray-100">{{ item.rks || '-' }}</td>
              <td class="p-3 text-on-surface-variant border-r border-gray-100 whitespace-nowrap">{{ item.tanggalRks || '-' }}</td>
              <td class="p-3 text-on-surface-variant border-r border-gray-100">{{ item.picOme || '-' }}</td>
              <td class="p-3 text-on-surface-variant border-r border-gray-100">{{ item.noPr || '-' }}</td>
              <td class="p-3 text-on-surface-variant border-r border-gray-100">{{ item.procOps || '-' }}</td>
              <td class="p-3 text-on-surface-variant border-r border-gray-100">{{ item.noPo || '-' }}</td>
              <td class="p-3 text-center border-r border-gray-100 relative">
                <span v-if="item.kontrakStatus && item.kontrakStatus !== '-'" class="px-2 py-0.5 text-label-caps font-bold rounded-full whitespace-nowrap inline-flex items-center gap-1.5 pointer-events-none tracking-wider" :class="getKontrakBadgeClass(item.kontrakStatus)"><span class="w-1.5 h-1.5 rounded-full bg-current"></span>{{ item.kontrakStatus }}</span>
                <span v-else class="text-slate-500 pointer-events-none">-</span>
                <select
                  v-if="editingNo === String(item.no)"
                  v-model="item.kontrakStatus"
                  @change="saveField(item, 'kontrak_status', item.kontrakStatus)"
                  title="Klik untuk mengubah" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                >
                  <option value="">- (kosong)</option>
                  <option v-for="kOpt in kontrakOptions" :key="kOpt" :value="kOpt">
                    {{ kOpt }}
                  </option>
                </select>
              </td>
              <td class="p-3 text-right font-semibold text-on-surface-variant border-r border-gray-100 num-col">{{ formatCurrency(item.nilaiRab, item.mataUang) }}</td>
              <td class="p-3 text-right font-semibold text-on-surface-variant border-r border-gray-100 num-col">{{ formatCurrency(item.realisasi, item.mataUang) }}</td>
              <td class="p-3 text-center border-r border-gray-100">
                <span class="px-2 py-0.5 text-label-caps font-bold rounded-md shadow-2xs tracking-wider" :class="getCurrencyBadgeStyle(item.mataUang)">
                  {{ item.mataUang || 'IDR' }}
                </span>
              </td>
              <td class="p-3 text-center text-on-surface-variant border-r border-gray-100 font-semibold">{{ calculateEfficiency(item) }}</td>
              <td class="p-3 text-on-surface-variant border-r border-gray-100">{{ item.dueDate || '-' }}</td>
              <td class="p-3 text-on-surface-variant border-r border-gray-100">{{ item.estimatedDelivery || '-' }}</td>
              <td class="p-3 text-on-surface-variant border-r border-gray-100 relative">
                <span class="pointer-events-none">{{ item.deliveryStatus || '-' }}</span>
                <select
                  v-if="editingNo === String(item.no)"
                  v-model="item.deliveryStatus"
                  @change="saveField(item, 'delivery_status', item.deliveryStatus)"
                  title="Klik untuk mengubah" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                >
                  <option v-for="dOpt in deliveryStatusOptions2" :key="dOpt" :value="dOpt">
                    {{ dOpt }}
                  </option>
                </select>
              </td>
              <!-- Payment Details (can be hidden) -->
              <template v-if="showPaymentColumns">
                <template v-for="tKey in ['dp', 'termin1', 'termin2', 'termin3']" :key="tKey">
                  <td class="p-2 text-on-surface-variant border-r border-gray-100 text-center">{{ item[tKey]?.tanggalSubmit || '-' }}</td>
                  <td class="p-2 text-right text-on-surface-variant border-r border-gray-100">{{ item[tKey]?.nilai ? formatCurrency(item[tKey].nilai, item.mataUang) : '-' }}</td>
                  <td class="p-2 text-on-surface-variant border-r border-gray-100 text-center">{{ item[tKey]?.grSes || '-' }}</td>
                  <td class="p-2 text-on-surface-variant border-r border-gray-100 text-center">{{ item[tKey]?.noSpp || '-' }}</td>
                </template>

                <td class="p-2 text-on-surface-variant border-r border-gray-100 text-center relative">
                  <span class="pointer-events-none">{{ item.paymentPriority || '-' }}</span>
                  <select
                    v-if="editingNo === String(item.no)"
                    v-model="item.paymentPriority"
                    @change="saveField(item, 'payment_priority', item.paymentPriority)"
                    title="Klik untuk mengubah" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                  >
                    <option v-for="ppOpt in paymentPriorityOptions" :key="ppOpt" :value="ppOpt">
                      {{ ppOpt }}
                    </option>
                  </select>
                </td>

                <template v-for="pKey in ['dp', 't1', 't2', 't3']" :key="pKey">
                  <td class="p-2 text-center border-r border-gray-100">
                    <span v-if="item.paymentStatus?.[pKey]" class="text-[9px] font-bold px-1.5 py-0.5 rounded" :class="getPaymentBadgeClass(item.paymentStatus[pKey])">{{ item.paymentStatus[pKey] }}</span>
                    <span v-else>-</span>
                  </td>
                </template>
              </template>

              <!-- Action Buttons -->
              <td class="p-2 text-center whitespace-nowrap">
                <div class="flex items-center justify-center gap-1.5">
                  <button
                    @click="goToDetail(item.no)"
                    title="Lihat detail"
                    class="w-7 h-7 text-slate-500 hover:text-primary-500 flex items-center justify-center transition-colors cursor-pointer"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                  </button>
                  <button
                    @click="toggleEdit(item.no)"
                    :title="editingNo === String(item.no) ? 'Selesai edit' : 'Ubah baris ini'"
                    class="w-7 h-7 flex items-center justify-center transition-colors cursor-pointer rounded-md"
                    :class="editingNo === String(item.no) ? 'bg-primary-500 text-white shadow-xs' : 'text-slate-500 hover:text-on-surface'"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                  </button>
                  <button
                    @click="openModal(item, 'delete')"
                    title="Hapus data"
                    class="w-7 h-7 text-slate-500 hover:text-on-surface flex items-center justify-center transition-colors cursor-pointer"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                  </button>
                </div>
              </td>
            </tr>

            <!-- Sub-Item Rows -->
            <template v-if="expandedRows.includes(item.no) && item.children && item.children.length > 0">
              <tr v-for="child in item.children" :key="child.no" class="group bg-blue-50/30 hover:bg-blue-50/60 transition-colors">
                <td class="px-2 py-3 text-center text-slate-500 text-label-caps font-semibold border-r border-gray-100 sticky left-0 z-20 bg-[#f4f8fe] group-hover:bg-[#e8f1fc] w-16 min-w-[64px] max-w-[64px] tracking-wider">
                  ↳ {{ child.no }}
                </td>
                <!-- Fixed Column: Sub-Item Name -->
                <td class="p-3 pl-6 font-medium text-on-surface-variant border-r border-gray-100 truncate w-[240px] min-w-[240px] max-w-[240px] sticky left-16 z-20 bg-[#f4f8fe] group-hover:bg-[#e8f1fc] name-col" :title="child.name">{{ child.name }}</td>
                <!-- Fixed Column: Project Name (Sub-Item) -->
                <td class="p-3 text-slate-500 border-r border-gray-100 truncate w-[200px] min-w-[200px] max-w-[200px] sticky left-[304px] z-20 bg-[#f4f8fe] group-hover:bg-[#e8f1fc] shadow-[4px_0_8px_-3px_rgba(0,0,0,0.06)] name-col" :title="projectName">{{ projectName || '-' }}</td>
                <td class="p-3 text-slate-500 border-r border-gray-100">{{ child.vendor }}</td>
                <td class="p-3 text-slate-500 border-r border-gray-100">{{ child.targetToa }}</td>
                <td class="p-3 text-center border-r border-gray-100 relative">
                  <span class="px-2 py-0.5 text-label-caps font-bold rounded-full pointer-events-none tracking-wider" :class="getStatusBadgeClass(child.status)">{{ child.status }}</span>
                <select
                  v-if="editingNo === String(child.no)"
                  v-model="child.status"
                    @change="saveField(child, 'status', child.status)"
                    title="Klik untuk mengubah" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                  >
                    <option v-for="sOpt in statusOptions" :key="sOpt" :value="sOpt">
                      {{ sOpt }}
                    </option>
                  </select>
                </td>
                <!-- Progress Badge - Sub-Item (click to edit inline) -->
                <td class="p-3 text-center border-r border-gray-100 relative">
                  <span class="px-2 py-0.5 text-label-caps font-bold rounded-full whitespace-nowrap inline-flex items-center gap-1.5 pointer-events-none tracking-wider" :class="getProgressBadgeClass(child.progress)"><span class="w-1.5 h-1.5 rounded-full bg-current"></span>{{ progressTahapanLabel(child.progress) }}</span>
                <select
                  v-if="editingNo === String(child.no)"
                  v-model="child.progress"
                    @change="saveField(child, 'progress', child.progress)"
                    title="Klik untuk mengubah" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                  >
                    <option v-for="progOpt in progressOptions" :key="progOpt" :value="progOpt">
                      {{ progOpt }}
                    </option>
                  </select>
                </td>
                <!-- Priority - Sub-Item (click to edit inline) -->
                <td class="p-3 text-center border-r border-gray-100 relative">
                  <span class="pointer-events-none text-body-sm font-bold whitespace-nowrap" :class="getPriorityTextClass(child.priority)">{{ priorityMark(child.priority) }} {{ child.priority }}</span>
                <select
                  v-if="editingNo === String(child.no)"
                  v-model="child.priority"
                    @change="saveField(child, 'priority', child.priority)"
                    title="Klik untuk mengubah" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                  >
                    <option v-for="pOpt in priorityOptions" :key="pOpt" :value="pOpt">
                      {{ pOpt }}
                    </option>
                  </select>
                </td>
                <td class="p-3 text-slate-500 border-r border-gray-100">{{ child.jenis }}</td>
                <td class="p-3 text-slate-500 border-r border-gray-100">{{ child.paymentType }}</td>
                <td class="p-3 text-slate-500 border-r border-gray-100">{{ child.procPolicy || '-' }}</td>
                <td class="p-3 text-slate-500 border-r border-gray-100">{{ child.noHps || '-' }}</td>
                <td class="p-3 text-slate-500 border-r border-gray-100 whitespace-nowrap">{{ child.tanggalHps || '-' }}</td>
                <td class="p-3 text-slate-500 border-r border-gray-100">{{ child.rks || '-' }}</td>
                <td class="p-3 text-slate-500 border-r border-gray-100 whitespace-nowrap">{{ child.tanggalRks || '-' }}</td>
                <td class="p-3 text-slate-500 border-r border-gray-100">{{ child.picOme || '-' }}</td>
                <td class="p-3 text-slate-500 border-r border-gray-100">{{ child.noPr || '-' }}</td>
                <td class="p-3 text-slate-500 border-r border-gray-100">{{ child.procOps || '-' }}</td>
                <td class="p-3 text-slate-500 border-r border-gray-100">{{ child.noPo || '-' }}</td>
                <td class="p-3 text-center border-r border-gray-100 relative">
                  <span v-if="child.kontrakStatus && child.kontrakStatus !== '-'" class="px-2 py-0.5 text-label-caps font-bold rounded-full whitespace-nowrap inline-flex items-center gap-1.5 pointer-events-none tracking-wider" :class="getKontrakBadgeClass(child.kontrakStatus)"><span class="w-1.5 h-1.5 rounded-full bg-current"></span>{{ child.kontrakStatus }}</span>
                  <span v-else class="text-slate-500 pointer-events-none">-</span>
                <select
                  v-if="editingNo === String(child.no)"
                  v-model="child.kontrakStatus"
                    @change="saveField(child, 'kontrak_status', child.kontrakStatus)"
                    title="Klik untuk mengubah" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                  >
                    <option value="">- (kosong)</option>
                    <option v-for="kOpt in kontrakOptions" :key="kOpt" :value="kOpt">
                      {{ kOpt }}
                    </option>
                  </select>
                </td>
                <td class="p-3 text-right font-medium text-on-surface-variant border-r border-gray-100 num-col">{{ formatCurrency(child.nilaiRab, child.mataUang) }}</td>
                <td class="p-3 text-right font-medium text-on-surface-variant border-r border-gray-100 num-col">{{ formatCurrency(child.realisasi, child.mataUang) }}</td>
                <td class="p-3 text-center border-r border-gray-100">
                  <span class="px-2 py-0.5 text-label-caps font-bold rounded-md shadow-2xs tracking-wider" :class="getCurrencyBadgeStyle(child.mataUang)">
                    {{ child.mataUang || 'IDR' }}
                  </span>
                </td>
                <td class="p-3 text-center text-slate-500 border-r border-gray-100 font-medium">{{ calculateEfficiency(child) }}</td>
                <td class="p-3 text-slate-500 border-r border-gray-100">{{ child.dueDate || '-' }}</td>
                <td class="p-3 text-slate-500 border-r border-gray-100">{{ child.estimatedDelivery || '-' }}</td>
                <td class="p-3 text-slate-500 border-r border-gray-100 relative">
                  <span class="pointer-events-none">{{ child.deliveryStatus || '-' }}</span>
                <select
                  v-if="editingNo === String(child.no)"
                  v-model="child.deliveryStatus"
                    @change="saveField(child, 'delivery_status', child.deliveryStatus)"
                    title="Klik untuk mengubah" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                  >
                    <option v-for="dOpt in deliveryStatusOptions2" :key="dOpt" :value="dOpt">
                      {{ dOpt }}
                    </option>
                  </select>
                </td>

                <!-- Payment Details - Sub-Items -->
                <template v-if="showPaymentColumns">
                  <template v-for="tKey in ['dp', 'termin1', 'termin2', 'termin3']" :key="tKey">
                    <td class="p-2 text-slate-500 border-r border-gray-100 text-center">{{ child[tKey]?.tanggalSubmit || '-' }}</td>
                    <td class="p-2 text-right text-on-surface-variant border-r border-gray-100">{{ child[tKey]?.nilai ? formatCurrency(child[tKey].nilai, child.mataUang) : '-' }}</td>
                    <td class="p-2 text-slate-500 border-r border-gray-100 text-center">{{ child[tKey]?.grSes || '-' }}</td>
                    <td class="p-2 text-slate-500 border-r border-gray-100 text-center">{{ child[tKey]?.noSpp || '-' }}</td>
                  </template>
                    <td class="p-2 text-slate-500 border-r border-gray-100 text-center relative">
                      <span class="pointer-events-none">{{ child.paymentPriority || '-' }}</span>
                  <select
                    v-if="editingNo === String(child.no)"
                    v-model="child.paymentPriority"
                        @change="saveField(child, 'payment_priority', child.paymentPriority)"
                        title="Klik untuk mengubah" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                      >
                        <option v-for="ppOpt in paymentPriorityOptions" :key="ppOpt" :value="ppOpt">
                          {{ ppOpt }}
                        </option>
                      </select>
                    </td>
                    <template v-for="pKey in ['dp', 't1', 't2', 't3']" :key="pKey">
                    <td class="p-2 text-center border-r border-gray-100">
                      <span v-if="child.paymentStatus?.[pKey]" class="text-[9px] font-bold px-1.5 py-0.5 rounded" :class="getPaymentBadgeClass(child.paymentStatus[pKey])">{{ child.paymentStatus[pKey] }}</span>
                      <span v-else>-</span>
                    </td>
                  </template>
                </template>

                <!-- Action Buttons - Sub-Items -->
                <td class="p-2 text-center whitespace-nowrap">
                  <div class="flex items-center justify-center gap-1.5">
                    <button
                      @click="goToDetail(child.no)"
                      title="Lihat detail"
                      class="w-7 h-7 text-slate-500 hover:text-primary-500 flex items-center justify-center transition-colors cursor-pointer"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    </button>
                    <button
                      @click="toggleEdit(child.no)"
                      :title="editingNo === String(child.no) ? 'Selesai edit' : 'Ubah baris ini'"
                      class="w-7 h-7 flex items-center justify-center transition-colors cursor-pointer rounded-md"
                      :class="editingNo === String(child.no) ? 'bg-primary-500 text-white shadow-xs' : 'text-slate-500 hover:text-on-surface'"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    </button>
                    <button
                      @click="openModal(child, 'delete')"
                      title="Hapus data"
                      class="w-7 h-7 text-slate-500 hover:text-on-surface flex items-center justify-center transition-colors cursor-pointer"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </button>
                  </div>
                </td>
              </tr>
            </template>
          </template>
        </tbody>
      </table>
    </div>

    <!-- Footer Pagination -->
    <div class="p-3 px-4 bg-white border-t border-outline-variant text-body-sm text-slate-500 hide-on-print shrink-0 flex flex-wrap items-center justify-between gap-3">
      <span>Showing {{ pageStartIndex }} to {{ pageEndIndex }} of {{ hierarchicalData.length }} entries</span>
      <div class="flex items-center gap-4">
        <label class="flex items-center gap-2">
          <span class="font-semibold uppercase text-label-caps tracking-wider">Show Rows:</span>
          <select v-model.number="rowsPerPage" class="border border-outline-variant rounded-md px-2 py-1 text-body-sm text-on-surface-variant bg-white focus:outline-none cursor-pointer shadow-xs">
            <option :value="10">10</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
          </select>
        </label>
        <div class="flex items-center gap-1">
          <button
            @click="currentPage = Math.max(1, currentPage - 1)"
            :disabled="currentPage <= 1"
            class="px-2.5 py-1 border border-outline-variant rounded-md bg-white text-on-surface-variant hover:bg-[#737686]/10 disabled:opacity-40 disabled:cursor-not-allowed transition-colors cursor-pointer"
          >Prev</button>
          <button
            v-for="p in totalPages"
            :key="p"
            @click="currentPage = p"
            class="w-7 h-7 border rounded-md text-body-sm font-semibold transition-colors cursor-pointer"
            :class="p === currentPage
              ? 'bg-primary-500 border-primary-500 text-white shadow-xs'
              : 'bg-white border-outline-variant text-on-surface-variant hover:bg-[#737686]/10'"
          >{{ p }}</button>
          <button
            @click="currentPage = Math.min(totalPages, currentPage + 1)"
            :disabled="currentPage >= totalPages"
            class="px-2.5 py-1 border border-outline-variant rounded-md bg-white text-on-surface-variant hover:bg-[#737686]/10 disabled:opacity-40 disabled:cursor-not-allowed transition-colors cursor-pointer"
          >Next</button>
        </div>
      </div>
    </div>

    <!-- Modal Delete -->
    <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="modal.show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/50" @click="closeModal"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[85vh] overflow-y-auto custom-scrollbar">
          <!-- Modal Header -->
          <div class="flex items-center justify-between px-5 py-4 border-b border-outline-variant sticky top-0 bg-white rounded-t-2xl">
            <h3 class="text-sm font-bold text-on-surface">Hapus Pengadaan No. {{ modal.item?.no }}?</h3>
            <button @click="closeModal" class="w-7 h-7 rounded-full hover:bg-[#737686]/15 text-slate-500 hover:text-on-surface-variant flex items-center justify-center cursor-pointer">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
          </div>

          <!-- Delete Confirmation -->
          <div class="px-5 py-4 text-body-sm text-on-surface-variant">
            <p>Yakin hapus <span class="font-bold text-on-surface">"{{ modal.item?.name }}"</span> (No. {{ modal.item?.no }}) dari database website?</p>
            <p class="mt-2 text-amber-600 font-medium">Catatan: data di Google Sheet milik mentor tidak ikut terhapus, jadi baris ini akan muncul lagi saat sinkronisasi penuh berikutnya.</p>
          </div>

          <!-- Modal Footer -->
          <div class="flex items-center justify-end gap-2 px-5 py-4 border-t border-outline-variant">
            <button @click="closeModal" class="px-4 py-1.5 bg-[#737686]/15 hover:bg-[#737686] text-on-surface-variant rounded-lg text-body-sm font-semibold transition-colors cursor-pointer">Batal</button>
            <button @click="confirmDelete" :disabled="modal.saving" class="px-4 py-1.5 bg-error hover:bg-error disabled:opacity-50 text-white rounded-lg text-body-sm font-semibold transition-colors cursor-pointer">
              {{ modal.saving ? 'Menghapus...' : 'Ya, Hapus' }}
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- Floating Toast Notification -->
    <transition enter-active-class="transform transition ease-out duration-300" enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2" enter-to-class="translate-y-0 opacity-100 sm:translate-x-0" leave-active-class="transition ease-in duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="toast.show" class="fixed bottom-5 right-5 z-50 flex items-center gap-2.5 px-4 py-3 rounded-xl shadow-2xl text-body-sm font-semibold backdrop-blur-xs border transition-all" :class="toast.isError ? 'bg-error text-white border-error' : 'bg-emerald-500 text-white border-emerald-500'">
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
  emits: ['update:selectedCurrency', 'filter-currency', 'update-row', 'update-item-field', 'delete-item'],
  props: {
    tableData: { type: Array, default: () => [] },
    selectedCurrency: { type: String, default: '' },
    exchangeRates: { type: Object, default: () => null },
    masterProjectId: { type: [Number, String], default: 1 },
    projectName: { type: String, default: '' },
  },
  data() {
    return {
      activeTab: 'All',
      searchQuery: '',
      statusFilter: '',  
      priorityFilter: '', 
      jenisFilter: '',    
      deliveryStatusFilter: '',
      showAdvancedFilters: false,
      currentPage: 1,
      rowsPerPage: 10,
      currencyFilter: this.selectedCurrency || '', 
      expandedRows: [],
      editingNo: null,
      priorityTabs: [{ name: 'All' }, { name: 'High' }, { name: 'Medium' }, { name: 'Low' }],
      sortKey: 'no',
      sortOrder: 'asc',
      showPaymentColumns: false,
      progressOptions: ['SPH', 'HPS', 'RKS', 'PR', 'RFQ', 'NEGOSIASI', 'PO', 'KONTRAK', 'PRODUCTION', 'DELIVERED'],
      priorityOptions: ['High', 'Medium', 'Low'],
      kontrakOptions: ['DONE', 'On Hold', 'In Progress', 'Draft'],
      paymentPriorityOptions: ['HIGH', 'MEDIUM', 'LOW'],
      deliveryStatusOptions2: ['NOT YET', 'DELIVERED'],
      statusOptions: ['In Progress', 'Done', 'Overdue', 'Warning'],
      modal: {
        show: false,
        mode: 'view',
        item: null,
        saving: false,
      },
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
    },
    searchQuery() { this.currentPage = 1; },
    activeTab() { this.currentPage = 1; },
    statusFilter() { this.currentPage = 1; },
    priorityFilter() { this.currentPage = 1; },
    jenisFilter() { this.currentPage = 1; },
    deliveryStatusFilter() { this.currentPage = 1; },
    sortKey() { this.currentPage = 1; },
    sortOrder() { this.currentPage = 1; },
    rowsPerPage() { this.currentPage = 1; },
    'tableData.length'() {
      if (this.currentPage > this.totalPages) this.currentPage = this.totalPages;
    }
  },
  computed: {
    activeCurrency() {
      return (this.currencyFilter || this.selectedCurrency || 'IDR').toUpperCase();
    },
    deliveryStatusOptions() {
      if (!Array.isArray(this.tableData)) return [];
      const set = new Set();
      this.tableData.forEach(i => {
        const v = String(i.deliveryStatus || '').trim();
        if (v && v !== '-') set.add(v);
        (i.children || []).forEach(c => {
          const cv = String(c.deliveryStatus || '').trim();
          if (cv && cv !== '-') set.add(cv);
        });
      });
      return [...set].sort();
    },
    totalPages() {
      return Math.max(1, Math.ceil(this.hierarchicalData.length / this.rowsPerPage));
    },
    pagedData() {
      const safePage = Math.min(this.currentPage, this.totalPages);
      const start = (safePage - 1) * this.rowsPerPage;
      return this.hierarchicalData.slice(start, start + this.rowsPerPage);
    },
    pageStartIndex() {
      if (this.hierarchicalData.length === 0) return 0;
      return (Math.min(this.currentPage, this.totalPages) - 1) * this.rowsPerPage + 1;
    },
    pageEndIndex() {
      return Math.min(Math.min(this.currentPage, this.totalPages) * this.rowsPerPage, this.hierarchicalData.length);
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
            item.noHps,
            item.tanggalHps,
            item.rks,
            item.tanggalRks,
            item.picOme,
            item.noPr,
            item.procOps,
            item.noPo,
            item.kontrakStatus,
            item.mataUang,
            item.efisiensi,
            item.dueDate,
            item.estimatedDelivery,
            item.deliveryStatus,
            item.deliveryStatus,
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
        const matchesDeliveryDropdown = !this.deliveryStatusFilter || String(item.deliveryStatus || '').toLowerCase() === this.deliveryStatusFilter.toLowerCase();
        
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
        return matchesTab && matchesSearch && matchesStatusDropdown && matchesPriorityDropdown && matchesJenisDropdown && matchesDeliveryDropdown;
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
    toggleEdit(no) {
      const key = String(no);
      this.editingNo = this.editingNo === key ? null : key;
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
      this.deliveryStatusFilter = '';
      this.currencyFilter = '';
      this.expandedRows = [];
      this.sortKey = 'no';
      this.sortOrder = 'asc';
      this.currentPage = 1;
      this.$emit('update:selectedCurrency', '');
      this.$emit('filter-currency', '');
    },
    onCurrencyChange() {
      this.$emit('update:selectedCurrency', this.currencyFilter);
      this.$emit('filter-currency', this.currencyFilter);
    },
    getCurrencyBadgeStyle(currency) {
      const c = String(currency || 'IDR').toUpperCase();
      if (c === 'USD') return 'bg-emerald-100 text-emerald-500 border border-emerald-300';
      if (c === 'EUR') return 'bg-purple-100 text-purple-800 border border-purple-300';
      if (c === 'SGD') return 'bg-amber-100 text-amber-800 border border-amber-300';
      return 'bg-blue-50 text-primary-600 border border-primary-400';
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
    openModal(item, mode) {
      this.modal.item = item;
      this.modal.mode = mode;
      this.modal.saving = false;
      this.modal.show = true;
    },
    goToDetail(no) {
      this.$router.push({
        path: `/procurement-monitoring/detail/${encodeURIComponent(String(no))}`,
        query: { id: this.masterProjectId || 1 },
      });
    },
    closeModal() {
      this.modal.show = false;
      this.modal.item = null;
      this.modal.saving = false;
    },
    async confirmDelete() {
      const item = this.modal.item;
      if (!item) return;
      this.modal.saving = true;
      try {
        const pId = this.masterProjectId || 1;
        await axios.delete('http://127.0.0.1:8000/api/procurements', {
          data: { master_project_id: pId, no: item.no },
        });
        this.$emit('delete-item', { no: item.no });
        if (this.editingNo === String(item.no)) this.editingNo = null;
        this.showToast(`No. ${item.no} dihapus dari Database ✓`);
        this.closeModal();
      } catch (err) {
        console.error('Failed to delete procurement:', err);
        this.showToast(`Gagal menghapus: ${err.response?.data?.message || err.message}`, true);
        this.modal.saving = false;
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
          ? 'bg-error text-white border-error shadow-sm' 
          : 'bg-white text-on-surface-variant border-outline-variant hover:bg-error hover:text-white hover:border-error hover:shadow-sm';
      }
      if (tabName === 'Medium') {
        return active 
          ? 'bg-yellow-400 text-white border-yellow-400 shadow-sm' 
          : 'bg-white text-on-surface-variant border-outline-variant hover:bg-yellow-400 hover:text-white hover:border-yellow-400 hover:shadow-sm';
      }
      if (tabName === 'Low' || tabName === 'Normal') {
        return active 
          ? 'bg-emerald-500 text-white border-emerald-500 shadow-sm' 
          : 'bg-white text-on-surface-variant border-outline-variant hover:bg-emerald-500 hover:text-white hover:border-emerald-500 hover:shadow-sm';
      }
      return active 
        ? 'bg-primary-500 text-white border-primary-500 shadow-sm' 
        : 'bg-white text-on-surface-variant border-outline-variant hover:bg-primary-500 hover:text-white hover:border-primary-500 hover:shadow-sm';
    },
    getStatusBadgeClass(s) {
      const st = String(s).toLowerCase();
      if (st.includes('completed') || st.includes('done') || st.includes('delivery')) return 'bg-emerald-100 text-emerald-700 border border-emerald-200';
      if (st.includes('overdue') || st.includes('delayed')) return 'bg-error-container text-error border border-error/30';
      if (st.includes('progress')) return 'bg-amber-100 text-amber-700 border border-amber-200';
      if (st.includes('warning') || st.includes('negosiasi') || st.includes('nego')) return 'bg-amber-100 text-amber-700 border border-amber-200';
      if (st.includes('kontrak') || st.includes('kontak') || st.includes('contract')) return 'bg-blue-50 text-primary-600 border border-primary-400/40';
      if (st.includes('review') || st.includes('service') || st.includes('rks')) return 'bg-sky-100 text-sky-700 border border-sky-200';
      if (st.includes('penyusunan') || st.includes('penyujaan') || st.includes('hps') || st.includes('draft')) return 'bg-[#737686]/15 text-on-surface-variant border border-[#737686]/40';
      return 'bg-[#737686]/15 text-on-surface-variant border border-[#737686]/40';
    },
    getProgressBadgeClass(p) {
      const pr = String(p || '').toUpperCase();
      if (pr === 'PRODUCTION') return 'bg-blue-100 text-blue-700 border border-blue-200';
      if (pr === 'DELIVERED') return 'bg-emerald-100 text-emerald-700 border border-emerald-200';
      if (pr === 'KONTRAK') return 'bg-blue-50 text-primary-600 border border-primary-400/40';
      if (pr === 'PO') return 'bg-sky-100 text-sky-700 border border-sky-200';
      if (pr === 'RKS') return 'bg-error-container text-error border border-error/30';
      if (pr === 'PR' || pr === 'RFQ' || pr === 'NEGOSIASI') return 'bg-amber-100 text-amber-700 border border-amber-200';
      if (pr === 'HPS' || pr === 'SPH') return 'bg-[#737686]/15 text-on-surface-variant border border-[#737686]/40';
      return 'bg-[#737686]/15 text-on-surface-variant border border-[#737686]/40';
    },
    progressTahapanLabel(p) {
      const val = String(p || '').trim();
      if (!val || val === '-') return '-';
      const idx = this.progressOptions.findIndex(o => String(o).toUpperCase() === val.toUpperCase());
      if (idx === -1) return val;
      return `${val} (${idx + 1}/${this.progressOptions.length})`;
    },
    getKontrakBadgeClass(k) {
      const st = String(k || '').toLowerCase();
      if (st.includes('done') || st.includes('selesai')) return 'bg-emerald-100 text-emerald-700 border border-emerald-200';
      if (st.includes('hold') || st.includes('batal')) return 'bg-error-container text-error border border-error/30';
      if (st.includes('progress') || st.includes('jalan')) return 'bg-blue-50 text-primary-600 border border-primary-400/40';
      if (st.includes('draft')) return 'bg-[#737686]/15 text-on-surface-variant border border-[#737686]/40';
      return 'bg-[#737686]/15 text-on-surface-variant border border-[#737686]/40';
    },
    getPriorityTextClass(p) {
      const pr = String(p || '').toLowerCase();
      if (pr.includes('high')) return 'text-error';
      if (pr.includes('medium')) return 'text-amber-600';
      if (pr.includes('normal') || pr.includes('low')) return 'text-emerald-500';
      return 'text-slate-500';
    },
    priorityMark(p) {
      const pr = String(p || '').toLowerCase();
      if (pr.includes('high')) return '↑';
      if (pr.includes('medium')) return '–';
      return '↓';
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
      if (st.includes('not paid')) return 'bg-error-container text-error';
      if (st.includes('paid')) return 'bg-emerald-100 text-emerald-500';
      return 'bg-[#737686]/15 text-on-surface-variant';
    },
    downloadData() { window.print(); }
  }
}
</script>

<style scoped>
.screen-table-view tbody td {
  text-align: center;
}
.screen-table-view tbody td.name-col {
  text-align: left;
}
.screen-table-view tbody td.num-col {
  text-align: right;
}
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
    zoom: 0.48 !important; /* Pas agar semua kolom dari NO sampai ACTION tampil utuh */
  }

  .screen-table-view th, 
  .screen-table-view td {
    padding: 3px 4px !important;
    white-space: nowrap !important;
  }
}
</style>