<template>
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 items-stretch">
    <!-- Chart 1: Total by Status Pengadaan (Dinamis sesuai Currency) -->
    <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-xs flex flex-col justify-between print-chart-box">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-bold text-gray-800">Total by Status Pengadaan</h3>
        <span class="text-[10px] font-extrabold px-2 py-0.5 bg-blue-50 text-blue-700 rounded-md border border-blue-200 uppercase">
          {{ selectedCurrency || 'IDR' }}
        </span>
      </div>
      
      <div class="space-y-4 my-auto">
        <!-- In Progress -->
        <div class="flex items-center justify-between text-xs">
          <span class="w-24 text-gray-600 font-medium">In Progress</span>
          <div class="flex-1 mx-4 bg-gray-100 h-3 rounded-full overflow-hidden">
            <div class="bg-blue-600 h-full rounded-full transition-all duration-500" :style="{ width: getStatusWidth(chartsData?.statusBreakdown?.inProgress) }"></div>
          </div>
          <span class="min-w-[80px] text-right font-bold text-gray-700 whitespace-nowrap">{{ formatValue(chartsData?.statusBreakdown?.inProgress) }}</span>
        </div>

        <!-- Completed -->
        <div class="flex items-center justify-between text-xs">
          <span class="w-24 text-gray-600 font-medium">Completed</span>
          <div class="flex-1 mx-4 bg-gray-100 h-3 rounded-full overflow-hidden">
            <div class="bg-blue-500 h-full rounded-full transition-all duration-500" :style="{ width: getStatusWidth(chartsData?.statusBreakdown?.completed) }"></div>
          </div>
          <span class="min-w-[80px] text-right font-bold text-gray-700 whitespace-nowrap">{{ formatValue(chartsData?.statusBreakdown?.completed) }}</span>
        </div>

        <!-- Overdue -->
        <div class="flex items-center justify-between text-xs">
          <span class="w-24 text-gray-600 font-medium">Overdue</span>
          <div class="flex-1 mx-4 bg-gray-100 h-3 rounded-full overflow-hidden">
            <div class="bg-indigo-400 h-full rounded-full transition-all duration-500" :style="{ width: getStatusWidth(chartsData?.statusBreakdown?.overdue) }"></div>
          </div>
          <span class="min-w-[80px] text-right font-bold text-gray-700 whitespace-nowrap">{{ formatValue(chartsData?.statusBreakdown?.overdue) }}</span>
        </div>
      </div>
    </div>

    <!-- Chart 2: Distribution by Jenis Pengadaan (Donut + Label Persentase Melayang) -->
    <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-xs flex flex-col justify-between print-chart-box">
      <h3 class="text-sm font-bold text-gray-800 mb-4">Distribution by Jenis Pengadaan</h3>
      
      <div class="flex items-center justify-around my-auto py-2">
        <!-- Container Donut dengan Label Eksternal Melayang -->
        <div class="relative w-36 h-32 flex items-center justify-center">
          
          <!-- Label Eksternal Barang (Kanan Atas) -->
          <div class="absolute top-0 right-1 bg-white px-1.5 py-0.5 rounded shadow-xs border border-gray-200 text-[10px] font-bold text-gray-700 flex items-center space-x-1 z-10">
            <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
            <span>{{ persentaseBarang }}%</span>
          </div>

          <!-- Label Eksternal Jasa (Kiri Atas) -->
          <div class="absolute top-0 left-1 bg-white px-1.5 py-0.5 rounded shadow-xs border border-gray-200 text-[10px] font-bold text-gray-700 flex items-center space-x-1 z-10">
            <span class="w-1.5 h-1.5 rounded-full bg-blue-300"></span>
            <span>{{ persentaseJasa }}%</span>
          </div>

          <!-- Donut Ring -->
          <div 
            class="relative w-28 h-28 rounded-full flex items-center justify-center print-exact mt-3 shadow-xs"
            :style="{ background: `conic-gradient(#2563eb ${persentaseBarang}%, #93c5fd ${persentaseBarang}% 100%)` }"
          >
            <!-- Lingkaran Putih Tengah dengan Teks di Dalamnya -->
            <div class="absolute inset-[15px] bg-white rounded-full print-exact flex flex-col items-center justify-center">
              <span class="text-xs text-gray-400 font-medium leading-none mb-0.5">Total</span>
              <span class="text-base font-bold text-gray-700 leading-none">100%</span>
            </div>
          </div>
        </div>

        <!-- Legend Bersih (Tanpa Persentase di Sampingnya) -->
        <div class="space-y-3 text-xs w-24">
          <div class="flex items-center space-x-2">
            <span class="w-3 h-3 rounded-full bg-blue-600 inline-block print-exact"></span>
            <span class="font-medium text-gray-700">Barang</span>
          </div>
          <div class="flex items-center space-x-2">
            <span class="w-3 h-3 rounded-full bg-blue-300 inline-block print-exact"></span>
            <span class="font-medium text-gray-700">Jasa</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ProcurementCharts2',
  props: {
    chartsData: {
      type: Object,
      default: () => ({
        statusBreakdown: { inProgress: 0, completed: 0, overdue: 0 },
        jenisBreakdown: { barang: 0, jasa: 0 }
      })
    },
    selectedCurrency: {
      type: String,
      default: 'IDR'
    }
  },
  computed: {
    totalStatusValue() {
      const data = this.chartsData?.statusBreakdown || {};
      const total = (Number(data.inProgress) || 0) + (Number(data.completed) || 0) + (Number(data.overdue) || 0);
      return total > 0 ? total : 1; 
    },
    totalJenis() {
      const data = this.chartsData?.jenisBreakdown || {};
      return (Number(data.barang) || 0) + (Number(data.jasa) || 0);
    },
    persentaseBarang() {
      if (this.totalJenis === 0) return 0;
      return Math.round(((this.chartsData.jenisBreakdown?.barang || 0) / this.totalJenis) * 100);
    },
    persentaseJasa() {
      if (this.totalJenis === 0) return 0;
      return Math.round(((this.chartsData.jenisBreakdown?.jasa || 0) / this.totalJenis) * 100);
    }
  },
  methods: {
    formatValue(val) {
      if (!val && val !== 0) return '0';
      const num = Number(val) || 0;
      if (num === 0) return '0';
      const curr = (this.selectedCurrency || 'IDR').toUpperCase();

      if (curr === 'EUR') {
        if (num >= 1000000000) {
          return '€ ' + (num / 1000000000).toFixed(2) + ' B';
        }
        if (num >= 1000000) {
          return '€ ' + (num / 1000000).toFixed(2) + ' M';
        }
        if (num >= 1000) {
          return '€ ' + (num / 1000).toFixed(1) + ' K';
        }
        return '€ ' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Math.round(num));
      }

      if (curr === 'USD') {
        if (num >= 1000000000) {
          return '$ ' + (num / 1000000000).toFixed(2) + ' B';
        }
        if (num >= 1000000) {
          return '$ ' + (num / 1000000).toFixed(2) + ' M';
        }
        if (num >= 1000) {
          return '$ ' + (num / 1000).toFixed(1) + ' K';
        }
        return '$ ' + new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(Math.round(num));
      }

      if (curr === 'SGD') {
        if (num >= 1000000000) {
          return 'S$ ' + (num / 1000000000).toFixed(2) + ' B';
        }
        if (num >= 1000000) {
          return 'S$ ' + (num / 1000000).toFixed(2) + ' M';
        }
        if (num >= 1000) {
          return 'S$ ' + (num / 1000).toFixed(1) + ' K';
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
      if (num >= 1000) {
        return 'Rp ' + (num / 1000).toFixed(0) + ' Rb';
      }
      return 'Rp ' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Math.round(num));
    },
    getStatusWidth(val) {
      if (!val) return '0%';
      const percent = (Number(val) / this.totalStatusValue) * 100;
      return Math.round(percent) + '%';
    }
  }
}
</script>

<style scoped>
@media print {
  .print-chart-box {
    break-inside: avoid;
    border: 1px solid #e5e7eb !important;
  }
  
  .print-exact {
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }

  .bg-blue-600, .bg-blue-500, .bg-blue-300, .bg-indigo-400 {
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }
}
</style>