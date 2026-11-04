<template>
  <div class="min-h-screen bg-background text-on-surface font-sans pb-12 no-borders">
    <!-- Top Navigation -->
    <header class="bg-white border-b border-outline-variant sticky top-0 z-50 hide-on-print">
      <div class="max-w-[1280px] mx-auto px-4 md:px-10 h-16 flex items-center justify-between">
        <!-- Brand Logo & Top Nav Tabs -->
        <div class="flex items-center space-x-6">
          <div class="flex items-center space-x-2">
            <span class="text-2xl font-bold text-primary-500 tracking-tight">Executive Command</span>
          </div>

          <!-- Project Dropdown Selector -->
          <div v-if="projects.length > 0" class="flex items-center space-x-2 bg-background px-3 py-1.5 rounded-lg border border-outline-variant shadow-xs">
            <span class="text-body-sm font-bold text-slate-500 uppercase tracking-wider">Project:</span>
            <select 
              v-model="selectedProjectId" 
              @change="onProjectChange"
              class="bg-transparent text-body-sm font-bold text-on-surface focus:outline-none cursor-pointer"
            >
              <option value="all">All Projects</option>
              <option v-for="p in projects" :key="p.id" :value="p.id">
                {{ p.type ? '[' + p.type + '] ' : '' }}{{ p.project_name }} ({{ p.project_code }})
              </option>
            </select>
          </div>

          <nav class="flex items-center space-x-4 text-sm font-medium">
            <router-link :to="projectMonitoringLink" class="text-slate-500 hover:text-on-surface-variant pb-1">
              Project Monitoring
            </router-link>
            <router-link :to="procurementMonitoringLink" class="text-primary-500 font-semibold border-b-2 border-primary-500 pb-1">
              Procurement Monitoring
            </router-link>
          </nav>
        </div>

        <!-- Right Icons & Profile Avatar -->
        <div class="flex items-center space-x-3">
          <!-- Sync Database Button -->
          <button 
            @click="triggerSync" 
            :disabled="isSyncing"
            class="flex items-center space-x-1.5 px-3 py-1.5 bg-blue-50 text-primary-600 hover:bg-primary-400/20 rounded-lg text-body-sm font-semibold border border-primary-400 transition-colors cursor-pointer disabled:opacity-50"
            title="Sinkronisasi data dari Google Sheets ke Database SQLite"
          >
            <svg :class="{ 'animate-spin': isSyncing }" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            <span>{{ isSyncing ? 'Syncing...' : 'Sync Sheet' }}</span>
          </button>

          <button class="text-slate-500 hover:text-on-surface-variant transition-colors p-1.5 rounded-full hover:bg-[#737686]/15">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
          </button>
          <button class="text-slate-500 hover:text-on-surface-variant transition-colors p-1.5 rounded-full hover:bg-[#737686]/15">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path></svg>
          </button>
          <div class="w-8 h-8 rounded-full bg-slate-200 border border-outline-variant overflow-hidden">
            <img src="https://ui-avatars.com/api/?name=User&background=1d4ed8&color=fff" alt="Profile" />
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-[1280px] mx-auto px-4 md:px-10 pt-6 space-y-5">
      <!-- Breadcrumb & Title Section -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 hide-on-print">
        <div>
          <div class="flex items-center space-x-1.5 text-label-caps text-slate-500 font-medium mb-1 tracking-wider">
            <router-link :to="projectMonitoringLink" class="hover:underline">Project Monitoring</router-link>
            <span>&rsaquo;</span>
            <span class="text-on-surface-variant font-semibold">Procurement Monitoring</span>
          </div>
          <h1 class="text-2xl font-bold text-on-surface tracking-tight">Procurement Monitoring</h1>
          <p class="text-body-sm text-slate-500 mt-0.5">
            Monitoring and analysis of enterprise procurement activities, vendor performance, and efficiency metrics.
          </p>
        </div>

        <!-- Currency Filter -->
        <div class="flex flex-wrap items-center gap-3 self-end md:self-auto">
          <!-- Currency Selector -->
          <div class="flex items-center space-x-1.5">
            <label class="text-body-sm text-on-surface-variant font-bold">Currency View:</label>
            <select 
              v-model="selectedCurrency" 
              class="bg-blue-50 border border-primary-400 text-primary-500 font-bold rounded-lg px-3 py-1.5 text-body-sm focus:outline-none focus:ring-2 focus:ring-primary-400 shadow-xs cursor-pointer"
            >
              <option value="IDR">IDR - Rupiah (Rp)</option>
              <option value="EUR">EUR - Euro (€)</option>
              <option value="USD">USD - US Dollar ($)</option>
              <option value="SGD">SGD - SG Dollar (S$)</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Error Message -->
      <div v-if="errorMessage" class="bg-error-container text-error p-4 rounded-xl border border-error/25 mb-6 text-sm">
        {{ errorMessage }}
      </div>

      <!-- Loading State -->
      <div v-if="isLoading && procurementData.items.length === 0" class="bg-white rounded-xl p-6 border shadow-sm animate-pulse space-y-4 hide-on-print">
        <div class="h-8 bg-[#737686] rounded w-1/3"></div>
        <div class="h-4 bg-[#737686] rounded w-1/2"></div>
      </div>

      <template v-else>
        <!-- Project Profile (currently hidden)
        <ProjectProfileCard v-if="showProfile" :profile="projectProfile" />
        -->

        <!-- Key Metrics & Charts -->
        <ProcurementMetrics1 
          :metrics="convertedProcurementData.metrics" 
          :selectedCurrency="selectedCurrency" 
          :exchangeRates="exchangeRates" 
        />
        <ProcurementCharts2 
          :chartsData="convertedProcurementData.charts" 
          :selectedCurrency="selectedCurrency" 
        />
        <ProcurementTable3 
          :tableData="convertedProcurementData.items" 
          :selectedCurrency="selectedCurrency" 
          :exchangeRates="exchangeRates"
          :masterProjectId="effectiveMasterProjectId"
          :projectName="selectedProject ? selectedProject.project_name : ''"
          @update:selectedCurrency="selectedCurrency = $event"
          @update-item-field="onItemFieldUpdated"
          @delete-item="onItemDeleted"
        />
      </template>
    </main>

    <!-- Footer -->
    <footer class="max-w-[1280px] mx-auto px-4 md:px-10 mt-16 flex flex-col md:flex-row justify-between items-center text-label-caps text-slate-500 gap-2 hide-on-print tracking-wider">
      <span>© 2026 Corporate Performance Systems. All rights reserved.</span>
      <div class="flex items-center space-x-4">
        <a href="#" class="hover:underline">Privacy Policy</a>
        <a href="#" class="hover:underline">Terms of Service</a>
        <a href="#" class="hover:underline">Support</a>
      </div>
    </footer>
  </div>
</template>

<script>
import axios from 'axios'
import ProcurementMetrics1 from '@/components/Monitoring/ProcurementMetrics1.vue'
import ProcurementCharts2 from '@/components/Monitoring/ProcurementCharts2.vue'
import ProcurementTable3 from '@/components/Monitoring/ProcurementTable3.vue'
import ProjectProfileCard from '@/components/Header/ProjectProfileCard.vue'
import { parseProcurementData } from '@/utils/procurementParser.js'
import { toBaseIdr, convertCurrency } from '@/utils/currencyConverter.js'

export default {
  name: 'ProcurementMonitoringView',
  components: {
    ProcurementMetrics1,
    ProcurementCharts2,
    ProcurementTable3,
    ProjectProfileCard
  },
  data() {
    return {
      isLoading: false,
      isSyncing: false,
      errorMessage: '',
      pollingTimer: null,
      projects: [],
      selectedProjectId: null,
      selectedCurrency: 'IDR',
      exchangeRates: null,
      procurementData: parseProcurementData([]),
      projectProfile: null
    }
  },
  computed: {
    isAllProjects() {
      return (this.$route.query.id || this.selectedProjectId) === 'all';
    },
    showProfile() {
      return !this.isAllProjects && !!this.projectProfile;
    },
    selectedProject() {
      if (!this.projects || this.projects.length === 0) return null;
      const currentId = this.$route.query.id || this.selectedProjectId;
      if (!currentId || currentId === 'all') return currentId === 'all' ? null : this.projects[0];
      return this.projects.find(p => String(p.id) === String(currentId) || p.spreadsheet_id === currentId) || this.projects[0];
    },
    effectiveMasterProjectId() {
      if (this.isAllProjects) return this.projects.length > 0 ? this.projects[0].id : 1;
      return this.selectedProjectId || (this.selectedProject ? this.selectedProject.id : 1);
    },
    projectMonitoringLink() {
      const id = this.resolveEffectiveId();
      return id ? { path: '/project-monitoring', query: { id: id } } : '/project-monitoring';
    },
    procurementMonitoringLink() {
      const id = this.resolveEffectiveId();
      return id ? { path: '/procurement-monitoring', query: { id: id } } : '/procurement-monitoring';
    },
    convertedProcurementData() {
      const targetCurr = (this.selectedCurrency || 'IDR').toUpperCase();
      const rawItems = this.procurementData.items || [];
      const rates = this.exchangeRates;

      const convertRow = (item) => {
        const originCurr = item.mataUang || 'IDR';
        const baseRab = toBaseIdr(item.nilaiRab, originCurr, rates);
        const baseReal = toBaseIdr(item.realisasi, originCurr, rates);

        const convertedItem = {
          ...item,
          nilaiRabOriginal: item.nilaiRab,
          realisasiOriginal: item.realisasi,
          nilaiRab: convertCurrency(baseRab, targetCurr, rates),
          realisasi: convertCurrency(baseReal, targetCurr, rates),
          mataUang: targetCurr,
        };

        ['dp', 'termin1', 'termin2', 'termin3', 'termin4', 'termin5', 'termin6'].forEach(k => {
          if (item[k]) {
            const baseT = toBaseIdr(item[k].nilai, originCurr, rates);
            convertedItem[k] = {
              ...item[k],
              nilai: convertCurrency(baseT, targetCurr, rates)
            };
          }
        });

        if (item.children && item.children.length > 0) {
          convertedItem.children = item.children.map(convertRow);
        }

        return convertedItem;
      };

      const convertedItems = rawItems.map(convertRow);

      const totalPengadaan = convertedItems.length;
      const totalNilai = convertedItems.reduce((acc, curr) => acc + (Number(curr.nilaiRab) || Number(curr.realisasi) || 0), 0);
      const completedCount = convertedItems.filter(i => {
        const st = String(i.status || '').toLowerCase();
        return st.includes('completed') || st.includes('done');
      }).length;
      const inProgressCount = convertedItems.filter(i => {
        const st = String(i.status || '').toLowerCase();
        return st.includes('progress');
      }).length;
      const onTrackPercentage = totalPengadaan > 0 
        ? Math.round(((completedCount + inProgressCount) / totalPengadaan) * 100) 
        : 0;

      return {
        items: convertedItems,
        metrics: {
          totalPengadaan,
          totalNilai,
          rataEfisiensi: this.procurementData.metrics?.rataEfisiensi ?? 12.4,
          onTrackStatus: onTrackPercentage
        },
        charts: {
          statusBreakdown: {
            inProgress: convertedItems.filter(i => String(i.status || '').toLowerCase().includes('progress')).reduce((a, c) => a + (Number(c.nilaiRab) || Number(c.realisasi) || 0), 0),
            completed: convertedItems.filter(i => {
              const st = String(i.status || '').toLowerCase();
              return st.includes('completed') || st.includes('done');
            }).reduce((a, c) => a + (Number(c.nilaiRab) || Number(c.realisasi) || 0), 0),
            overdue: convertedItems.filter(i => String(i.status || '').toLowerCase().includes('overdue')).reduce((a, c) => a + (Number(c.nilaiRab) || Number(c.realisasi) || 0), 0)
          },
          jenisBreakdown: (() => {
            const norm = (v) => String(v || '').toLowerCase();
            const isCombo = (j) => j.includes('&') || (j.includes('barang') && j.includes('jasa'));
            return {
              barang: convertedItems.filter(i => { const j = norm(i.jenis); return j.includes('barang') && !isCombo(j); }).length,
              jasa: convertedItems.filter(i => { const j = norm(i.jenis); return j.includes('jasa') && !isCombo(j); }).length,
              barangJasa: convertedItems.filter(i => isCombo(norm(i.jenis))).length
            };
          })()
        }
      };
    }
  },
  watch: {
    '$route.query.id'(newId) {
      if (newId) {
        this.selectedProjectId = isNaN(newId) ? newId : Number(newId);
        this.fetchData();
      }
    }
  },
  async mounted() {
    await this.fetchProjects();
    await this.fetchData();
    this.pollingTimer = setInterval(() => {
      this.fetchData(true);
    }, 10000);
  },
  beforeUnmount() {
    if (this.pollingTimer) clearInterval(this.pollingTimer);
  },
  methods: {
    resolveEffectiveId() {
      // Ada selectedProject.id?
      //   ↓ YA → gunakan ID tersebut
      //   ↓ TIDAK → Ada project.spreadsheet_id?
      //     ↓ YA → gunakan spreadsheet_id
      //     ↓ TIDAK → id = ''
      const p = this.selectedProject;
      if (p?.id !== null && p?.id !== undefined && p?.id !== '') return p.id;
      if (p?.spreadsheet_id) return p.spreadsheet_id;
      return '';
    },
    formatRate(val) {
      if (!val) return '0';
      return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(val);
    },
    async fetchProjects() {
      try {
        const res = await axios.get('http://127.0.0.1:8000/api/projects');
        this.projects = res.data?.data || [];
        const queryId = this.$route.query.id;
        if (queryId === 'all') {
          this.selectedProjectId = 'all';
        } else if (queryId) {
          const match = this.projects.find(p => String(p.id) === String(queryId) || p.spreadsheet_id === queryId);
          if (match) this.selectedProjectId = match.id;
        } else if (this.projects.length > 0) {
          this.selectedProjectId = this.projects[0].id;
        }
      } catch (err) {
        console.error('Failed to fetch projects:', err);
      }
    },
    onProjectChange() {
      this.$router.push({
        query: { ...this.$route.query, id: this.selectedProjectId }
      });
      this.fetchData();
    },
    async triggerSync() {
      this.isSyncing = true;
      try {
        await axios.post('http://127.0.0.1:8000/api/sheets/sync', { 
          sheet: 'procurement',
          id: this.resolveEffectiveId()
        });
        await this.fetchData();
      } catch (err) {
        console.error('Failed to sync procurement sheet:', err);
      } finally {
        this.isSyncing = false;
      }
    },
    onItemFieldUpdated({ no, field, value }) {
      if (this.procurementData && Array.isArray(this.procurementData.items)) {
        const updateRecursive = (items) => {
          for (const it of items) {
            if (String(it.no) === String(no)) {
              it[field] = value;
              return true;
            }
            if (it.children && it.children.length > 0) {
              if (updateRecursive(it.children)) return true;
            }
          }
          return false;
        };
        updateRecursive(this.procurementData.items);
      }
    },
    onItemDeleted({ no }) {
      if (!this.procurementData || !Array.isArray(this.procurementData.items)) return;
      const target = String(no);
      const removeRecursive = (items) => {
        for (let i = items.length - 1; i >= 0; i--) {
          const itNo = String(items[i].no);
          if (itNo === target || itNo.startsWith(target + '.')) {
            items.splice(i, 1);
          } else if (items[i].children && items[i].children.length > 0) {
            removeRecursive(items[i].children);
          }
        }
      };
      removeRecursive(this.procurementData.items);
    },
    async fetchData(isSilent = false) {
      if (!isSilent) this.isLoading = true;
      this.errorMessage = '';
      try {
        const idParam = this.resolveEffectiveId();
        const response = await axios.get(`http://127.0.0.1:8000/api/procurement-monitoring?id=${idParam}&t=${Date.now()}`);
        const rows = response.data?.data || [];
        const prof = response.data?.profile || null;
        this.projectProfile = prof ? { ...prof, type: response.data?.project?.type || null } : null;
        if (rows.length > 0) {
          this.procurementData = parseProcurementData(rows);
        }
        if (response.data?.exchange_rates) {
          this.exchangeRates = response.data.exchange_rates;
        }
      } catch (error) {
        console.error('Error fetching procurement data:', error);
        if (!isSilent) {
          this.errorMessage = error.response?.data?.message || 'Gagal mengambil data Procurement dari API.';
        }
      } finally {
        if (!isSilent) this.isLoading = false;
      }
    }
  }
}
</script>

<style>
@media print {
  @page {
    size: landscape;
    margin: 4mm;
  }

  html, body, #app, main, .max-w-\[1400px\] {
    max-width: none !important;
    width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
    background: white !important;
  }

  .hide-on-print {
    display: none !important;
  }
}
</style>
