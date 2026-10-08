<template>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
    <!-- 1. Total Pengadaan -->
    <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-xs flex flex-col justify-between">
      <div class="flex items-center space-x-3 mb-2">
        <div class="bg-[#004ac6]/10 text-[#004ac6] rounded-lg p-2 w-10 h-10 flex items-center justify-center">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
        </div>
        <h3 class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider">Total Pengadaan</h3>
      </div>
      <div class="text-2xl font-extrabold text-[#191c1e] mt-2">{{ metrics.totalPengadaan || 0 }}</div>
    </div>

    <!-- 2. Total Nilai (Dinamis sesuai Currency yang dipilih) -->
    <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-xs flex flex-col justify-between">
      <div class="flex items-center space-x-3 mb-2">
        <div class="bg-amber-50 text-amber-600 rounded-lg p-2 w-10 h-10 flex items-center justify-center">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <h3 class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider">Total Nilai ({{ activeCurrencyLabel }})</h3>
      </div>
      <div class="text-2xl font-extrabold text-[#191c1e] mt-2">{{ formatNilai(metrics.totalNilai) }}</div>
    </div>

    <!-- 3. Rata-rata Efisiensi (Dinamis, menyesuaikan 0% dari sheet) -->
    <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-xs flex flex-col justify-between">
      <div class="flex items-center space-x-3 mb-2">
        <div class="bg-emerald-50 text-[#10B981] rounded-lg p-2 w-10 h-10 flex items-center justify-center">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
        </div>
        <h3 class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider">Rata-rata Efisiensi</h3>
      </div>
      <!-- Otomatis merah kalau minus, hijau kalau plus, abu kalau 0 -->
      <div class="text-2xl font-extrabold mt-2" :class="getEfisiensiColor(metrics.rataEfisiensi)">
        {{ formatEfisiensi(metrics.rataEfisiensi) }}
      </div>
    </div>

    <!-- 4. On-Track Status (Dinamis dari parser) -->
    <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-xs flex flex-col justify-between relative">
      <div class="absolute top-4 right-4 bg-[#737686]/15 text-[#64748B] px-2 py-0.5 rounded text-[10px] font-bold tracking-wider">
        STATUS
      </div>
      <div class="flex items-center space-x-3 mb-2">
        <div class="bg-blue-50 text-[#004ac6] rounded-lg p-2 w-10 h-10 flex items-center justify-center">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <h3 class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider">On-Track Status</h3>
      </div>
      <div class="text-2xl font-extrabold text-[#004ac6] mt-2">{{ metrics.onTrackStatus || 0 }}%</div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ProcurementMetrics1',
  props: {
    metrics: {
      type: Object,
      default: () => ({
        totalPengadaan: 0,
        totalNilai: 0,
        rataEfisiensi: 0,
        onTrackStatus: 0
      })
    },
    selectedCurrency: {
      type: String,
      default: ''
    },
    exchangeRates: {
      type: Object,
      default: () => null
    }
  },
  computed: {
    activeCurrencyLabel() {
      return this.selectedCurrency ? this.selectedCurrency.toUpperCase() : 'IDR';
    }
  },
  methods: {
    formatNilai(val) {
      const num = Number(val) || 0;
      if (num === 0) return '0';
      const curr = (this.selectedCurrency || 'IDR').toUpperCase();

      if (curr === 'USD') {
        if (num >= 1000000000) {
          return '$ ' + (num / 1000000000).toFixed(2) + ' B';
        }
        if (num >= 1000000) {
          return '$ ' + (num / 1000000).toFixed(2) + ' M';
        }
        return '$ ' + new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(Math.round(num));
      }

      if (curr === 'EUR') {
        if (num >= 1000000000) {
          return '€ ' + (num / 1000000000).toFixed(2) + ' B';
        }
        if (num >= 1000000) {
          return '€ ' + (num / 1000000).toFixed(2) + ' M';
        }
        return '€ ' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Math.round(num));
      }

      if (curr === 'SGD') {
        if (num >= 1000000000) {
          return 'S$ ' + (num / 1000000000).toFixed(2) + ' B';
        }
        if (num >= 1000000) {
          return 'S$ ' + (num / 1000000).toFixed(2) + ' M';
        }
        return 'S$ ' + new Intl.NumberFormat('en-SG', { maximumFractionDigits: 0 }).format(Math.round(num));
      }

      // Default IDR
      if (num >= 1000000000000) {
        return 'Rp ' + (num / 1000000000000).toFixed(2) + ' T';
      }
      if (num >= 1000000000) {
        return 'Rp ' + (num / 1000000000).toFixed(2) + ' M';
      }
      if (num >= 1000000) {
        return 'Rp ' + (num / 1000000).toFixed(1) + ' Jt';
      }
      return 'Rp ' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Math.round(num));
    },
    formatEfisiensi(val) {
      const num = Number(val) || 0;
      if (num > 0) return `+${num}%`;
      if (num < 0) return `${num}%`;
      return '0%';
    },
    getEfisiensiColor(val) {
      const num = Number(val) || 0;
      if (num > 0) return 'text-[#10B981]';
      if (num < 0) return 'text-[#ba1a1a]';
      return 'text-[#191c1e]';
    }
  }
}
</script>