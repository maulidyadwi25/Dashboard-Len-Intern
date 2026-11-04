<template>
  <div class="min-h-screen bg-background text-on-surface font-sans pb-12 no-borders">
    <!-- Top Navigation -->
    <header class="bg-white border-b border-outline-variant sticky top-0 z-50">
      <div class="max-w-[1280px] mx-auto px-4 md:px-10 h-16 flex items-center justify-between">
        <div class="flex items-center space-x-6">
          <span class="text-2xl font-bold text-primary-500 tracking-tight">Executive Command</span>
          <nav class="flex items-center space-x-4 text-sm font-medium">
            <router-link :to="projectMonitoringLink" class="text-slate-500 hover:text-on-surface-variant pb-1">
              Project Monitoring
            </router-link>
            <router-link :to="procurementMonitoringLink" class="text-primary-500 font-semibold border-b-2 border-primary-500 pb-1">
              Procurement Monitoring
            </router-link>
          </nav>
        </div>
        <div class="flex items-center space-x-3">
          <div class="w-8 h-8 rounded-full bg-slate-200 border border-outline-variant overflow-hidden">
            <img src="https://ui-avatars.com/api/?name=User&background=1d4ed8&color=fff" alt="Profile" />
          </div>
        </div>
      </div>
    </header>

    <main class="max-w-[1280px] mx-auto px-4 md:px-10 pt-6 space-y-5">
      <!-- Breadcrumb & Title -->
      <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
        <div class="min-w-0">
          <div class="flex items-center space-x-1.5 text-label-caps text-slate-500 font-medium mb-1 tracking-wider">
            <router-link :to="procurementMonitoringLink" class="hover:underline text-primary-500">Procurement Monitoring</router-link>
            <span>&rsaquo;</span>
            <span class="text-on-surface-variant font-semibold">Detail Progress Pengadaan</span>
          </div>
          <h1 class="text-2xl md:text-3xl font-bold text-on-surface tracking-tight break-words">{{ item?.name || 'Memuat data...' }}</h1>
          <p v-if="item" class="text-body-sm text-slate-500 mt-1">
            No. {{ item.no }} • Vendor: {{ item.vendor }} • Jenis: {{ item.jenis }}
          </p>
        </div>
        <button
          @click="downloadData"
          class="shrink-0 self-start flex items-center gap-2 px-4 py-2.5 bg-primary-500 hover:bg-primary-600 text-white rounded-lg text-body-sm font-semibold transition-colors cursor-pointer shadow-xs"
          title="Download / Print halaman detail"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
          Download
        </button>
      </div>

      <!-- Error Message -->
      <div v-if="errorMessage" class="bg-error-container text-error p-4 rounded-xl border border-error/25 text-sm">
        {{ errorMessage }}
      </div>

      <!-- Loading State -->
      <div v-if="isLoading && !item" class="bg-white rounded-xl p-6 border border-outline-variant shadow-sm animate-pulse space-y-4">
        <div class="h-8 bg-slate-300 rounded w-1/3"></div>
        <div class="h-4 bg-slate-300 rounded w-1/2"></div>
        <div class="h-32 bg-slate-300 rounded"></div>
      </div>

      <!-- Not Found Message -->
      <div v-else-if="!isLoading && !item" class="bg-white rounded-xl p-10 border border-outline-variant shadow-sm text-center">
        <p class="font-bold text-on-surface">Data pengadaan No. {{ $route.params.no }} tidak ditemukan.</p>
        <router-link :to="procurementMonitoringLink" class="inline-block mt-3 text-primary-500 font-semibold text-body-sm hover:underline">
          &larr; Kembali ke Procurement Monitoring
        </router-link>
      </div>

      <template v-if="item">
        <!-- Key Status Highlights -->
        <div class="flex flex-wrap gap-3">
          <!-- Payment Priority -->
          <div class="flex items-center gap-3 bg-white border border-outline-variant rounded-xl px-4 py-3 shadow-xs min-w-[210px]">
            <span class="w-9 h-9 rounded-lg bg-error-container text-error flex items-center justify-center shrink-0">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86a2 2 0 001.74-3l-6.93-12a2 2 0 00-3.48 0l-6.93 12A2 2 0 005.07 19z"></path></svg>
            </span>
            <div class="min-w-0">
              <p class="text-[10px] font-bold tracking-wider text-slate-500 uppercase">Payment Priority</p>
              <p class="text-body-sm font-bold" :class="paymentPriorityClass">{{ displayPaymentPriority }}</p>
            </div>
          </div>

          <!-- Delivery -->
          <div class="flex items-center gap-3 bg-white border border-outline-variant rounded-xl px-4 py-3 shadow-xs min-w-[210px]">
            <span class="w-9 h-9 rounded-lg bg-blue-50 text-primary-500 flex items-center justify-center shrink-0">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            </span>
            <div class="min-w-0">
              <p class="text-[10px] font-bold tracking-wider text-slate-500 uppercase">Delivery</p>
              <p class="text-body-sm font-bold text-on-surface">{{ item.estimatedDelivery && item.estimatedDelivery !== '-' ? item.estimatedDelivery : (item.dueDate || '-') }}</p>
            </div>
          </div>

          <!-- Delivery Status -->
          <div class="flex items-center gap-3 bg-white border border-outline-variant rounded-xl px-4 py-3 shadow-xs min-w-[240px]">
            <span class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0" :class="deliveryStatusIconClass">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </span>
            <div class="min-w-0">
              <p class="text-[10px] font-bold tracking-wider text-slate-500 uppercase">Delivery Status</p>
              <p class="text-body-sm font-bold" :class="deliveryStatusClass">{{ displayDeliveryStatus }}</p>
            </div>
          </div>
        </div>

        <!-- Workflow Tracker -->
        <DetailWorkflowTracker :item="item" />

        <!-- Down Payment & Payment Milestones -->
        <DetailPaymentMilestones
          :item="item"
          :masterProjectId="masterProjectId"
          @termin-saved="onTerminSaved"
        />

        <!-- AI Recommendations -->
        <DetailAiInsights :item="item" :masterProjectId="masterProjectId" />
      </template>
    </main>

    <!-- Footer -->
    <footer class="max-w-[1280px] mx-auto px-4 md:px-10 mt-16 flex flex-col md:flex-row justify-between items-center text-label-caps text-slate-500 gap-2 tracking-wider">
      <span>© 2026 Corporate Performance Systems. All rights reserved.</span>
      <div class="flex items-center space-x-4">
        <a href="#" class="hover:underline">Privacy Policy</a>
        <a href="#" class="hover:underline">Terms of Service</a>
      </div>
    </footer>
  </div>
</template>

<script>
import axios from 'axios';
import DetailWorkflowTracker from '@/components/Monitoring/DetailWorkflowTracker.vue';
import DetailPaymentMilestones from '@/components/Monitoring/DetailPaymentMilestones.vue';
import DetailAiInsights from '@/components/Monitoring/DetailAiInsights.vue';
import { parseProcurementData } from '@/utils/procurementParser.js';

const API_BASE = 'http://127.0.0.1:8000/api';

export default {
  name: 'ProcurementDetailView',
  components: { DetailWorkflowTracker, DetailPaymentMilestones, DetailAiInsights },
  data() {
    return {
      isLoading: false,
      errorMessage: '',
      pollingTimer: null,
      projects: [],
      items: [],
      rawExchangeRates: null,
    };
  },
  computed: {
    routeNo() {
      return String(this.$route.params.no || '');
    },
    projectIdParam() {
      return this.$route.query.id || 'all';
    },
    masterProjectId() {
      if (this.projectIdParam === 'all') {
        return this.projects.length > 0 ? this.projects[0].id : 1;
      }
      const found = this.projects.find(p => String(p.id) === String(this.projectIdParam) || p.spreadsheet_id === this.projectIdParam);
      return found ? found.id : (Number(this.projectIdParam) || 1);
    },
    item() {
      const flat = [];
      const walk = (list) => {
        list.forEach(it => {
          flat.push(it);
          if (it.children && it.children.length > 0) walk(it.children);
        });
      };
      walk(this.items);
      return flat.find(it => String(it.no) === this.routeNo) || null;
    },
    procurementMonitoringLink() {
      return this.projectIdParam !== 'all' ? { path: '/procurement-monitoring', query: { id: this.projectIdParam } } : '/procurement-monitoring';
    },
    projectMonitoringLink() {
      return this.projectIdParam !== 'all' ? { path: '/project-monitoring', query: { id: this.projectIdParam } } : '/project-monitoring';
    },
    displayPaymentPriority() {
      const v = String(this.item?.paymentPriority || '-').trim();
      if (v === '-' || v === '') return 'No Data';
      const low = v.toLowerCase();
      if (low === 'high') return 'High Priority';
      if (low === 'medium') return 'Medium Priority';
      if (low === 'low') return 'Low Priority';
      return v;
    },
    paymentPriorityClass() {
      const low = String(this.item?.paymentPriority || '').toLowerCase();
      if (low === 'high') return 'text-error';
      if (low === 'medium') return 'text-amber-600';
      if (low === 'low') return 'text-emerald-600';
      return 'text-slate-500';
    },
    displayDeliveryStatus() {
      const v = String(this.item?.deliveryStatus || '-').trim();
      if (v === '-' || v === '') return 'No Data';
      const low = v.toLowerCase();
      if (low.includes('deliver')) return 'On Track (Sesuai Jadwal)';
      if (low.includes('not')) return 'Not Yet (Belum Terkirim)';
      return v;
    },
    deliveryStatusClass() {
      const low = String(this.item?.deliveryStatus || '').toLowerCase();
      if (low.includes('deliver')) return 'text-emerald-600';
      if (low.includes('not')) return 'text-slate-500';
      return 'text-amber-600';
    },
    deliveryStatusIconClass() {
      const low = String(this.item?.deliveryStatus || '').toLowerCase();
      if (low.includes('deliver')) return 'bg-emerald-50 text-emerald-500';
      if (low.includes('not')) return 'bg-slate-100 text-slate-500';
      return 'bg-amber-50 text-amber-500';
    },
  },
  async created() {
    await this.fetchProjects();
    await this.fetchData();
    this.pollingTimer = setInterval(() => this.fetchData(true), 10000);
  },
  beforeUnmount() {
    if (this.pollingTimer) clearInterval(this.pollingTimer);
  },
  methods: {
    async fetchProjects() {
      try {
        const res = await axios.get(`${API_BASE}/projects?t=${Date.now()}`);
        this.projects = res.data?.data || [];
      } catch {
        this.projects = [];
      }
    },
    async fetchData(isSilent = false) {
      if (!isSilent) this.isLoading = true;
      this.errorMessage = '';
      try {
        const res = await axios.get(`${API_BASE}/procurement-monitoring?id=${this.masterProjectId}&t=${Date.now()}`);
        const rows = res.data?.data || [];
        if (rows.length > 0) {
          this.items = parseProcurementData(rows).items;
        }
        this.rawExchangeRates = res.data?.exchange_rates || null;
        if (!isSilent && this.items.length > 0 && !this.item) {
          this.errorMessage = `Pengadaan No. ${this.routeNo} tidak ada di proyek ini. Coba pilih proyek lain di menu Procurement Monitoring.`;
        }
      } catch (error) {
        if (!isSilent) {
          this.errorMessage = error.response?.data?.message || 'Gagal mengambil data Procurement dari API.';
        }
      } finally {
        if (!isSilent) this.isLoading = false;
      }
    },
    onTerminSaved() {
      // refresh data dari server (cache ProjectLink sudah diupdate endpoint)
      this.fetchData(true);
    },
    downloadData() {
      window.print();
    },
  },
};
</script>

<style>
@media print {
  @page {
    size: landscape;
    margin: 8mm;
  }

  html, body, #app {
    max-width: none !important;
    width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
    background: white !important;
  }

  header, footer {
    display: none !important;
  }
}
</style>
