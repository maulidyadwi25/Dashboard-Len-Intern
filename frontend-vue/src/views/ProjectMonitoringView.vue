<template>
  <div class="min-h-screen bg-gray-50 p-6">
    <!-- Navbar / Header Atas -->
    <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-start md:items-center justify-between mb-6 gap-4">
      <div class="flex flex-wrap items-center gap-6">
        <h1 class="text-2xl font-bold text-blue-900">Executive Command</h1>

        <!-- Project Dropdown Selector -->
        <div v-if="projects.length > 0" class="flex items-center space-x-2 bg-white px-3 py-1.5 rounded-lg border border-gray-200 shadow-xs">
          <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Project:</span>
          <select 
            v-model="selectedProjectId" 
            @change="onProjectChange"
            class="bg-transparent text-xs font-bold text-gray-800 focus:outline-none cursor-pointer"
          >
            <option v-for="p in projects" :key="p.id" :value="p.id">
              {{ p.project_name }} ({{ p.project_code }})
            </option>
          </select>
        </div>

        <nav class="flex items-center space-x-6 text-sm font-medium">
          <!-- Navigasi Aktif Ke Project Monitoring -->
          <router-link :to="projectMonitoringLink" class="text-blue-600 font-semibold border-b-2 border-blue-600 pb-1">
            Project Monitoring
          </router-link>
          <!-- Procurement Monitoring -->
          <router-link :to="procurementMonitoringLink" class="text-gray-500 hover:text-gray-700 pb-1">
            Procurement Monitoring
          </router-link>
        </nav>
      </div>

      <!-- Icon Profile & Notif -->
      <div class="flex items-center space-x-3">
        <!-- Sync Database Button -->
        <button 
          @click="triggerSync" 
          :disabled="isSyncing"
          class="flex items-center space-x-1.5 px-3 py-1.5 bg-blue-50 text-blue-700 hover:bg-blue-100 rounded-lg text-xs font-semibold border border-blue-200 transition-colors cursor-pointer disabled:opacity-50"
          title="Sinkronisasi data dari Google Sheets ke Database SQLite"
        >
          <svg :class="{ 'animate-spin': isSyncing }" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
          </svg>
          <span>{{ isSyncing ? 'Syncing...' : 'Sync Sheet' }}</span>
        </button>

        <button class="text-gray-400 hover:text-gray-600">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
        </button>
        <button class="text-gray-400 hover:text-gray-600">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path></svg>
        </button>
        <div class="w-8 h-8 rounded-full bg-slate-300 overflow-hidden border">
          <img src="https://ui-avatars.com/api/?name=User&background=0D8ABC&color=fff" alt="Profile" />
        </div>
      </div>
    </div>

    <!-- Main Content Container -->
    <div class="max-w-7xl mx-auto space-y-6">
      <div v-if="errorMessage" class="bg-red-50 text-red-600 p-4 rounded-xl border border-red-200 mb-6 text-sm">
        {{ errorMessage }}
      </div>

      <div v-if="isLoading && !projectInfo.projectName" class="bg-white rounded-xl p-6 border shadow-sm animate-pulse space-y-4">
        <div class="h-8 bg-gray-200 rounded w-1/3"></div>
        <div class="h-4 bg-gray-200 rounded w-1/2"></div>
      </div>

      <template v-else>
        <ProjectHeader 
          :project-info="projectInfo" 
          :project-id="projectId"
          :description="projectDescription"
          @update:description="projectDescription = $event"
          :key="(projectInfo.overallProgress || '') + '-' + (projectInfo.spiValue || '')" 
        />
        <GanttChart :gantt-data="ganttData" />
      </template>
    </div>

    <!-- Footer -->
    <div class="max-w-7xl mx-auto mt-12 flex justify-between text-xs text-gray-400">
      <span>© 2026 Executive Project Monitoring Platform. All rights reserved.</span>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { parseProjectHeader, parseGanttChartData } from '../utils/projectParser';
import ProjectHeader from '../components/Header/ProjectHeader.vue';
import GanttChart from '../components/Monitoring/GanttChart.vue';

export default {
  name: 'ProjectMonitoringView',
  components: {
    ProjectHeader,
    GanttChart
  },
  data() {
    return {
      isLoading: false,
      isSyncing: false,
      errorMessage: '',
      pollingTimer: null,
      projects: [],
      selectedProjectId: null,
      projectInfo: {},
      ganttData: { months: [], activities: [] },
      projectId: null,
      projectDescription: '',
    };
  },
  computed: {
    selectedProject() {
      if (!this.projects || this.projects.length === 0) return null;
      const currentId = this.$route.query.id || this.selectedProjectId;
      if (!currentId) return this.projects[0];
      return this.projects.find(p => String(p.id) === String(currentId) || p.spreadsheet_id === currentId) || this.projects[0];
    },
    projectMonitoringLink() {
      const id = this.resolveEffectiveId();
      return id ? { path: '/project-monitoring', query: { id: id } } : '/project-monitoring';
    },
    procurementMonitoringLink() {
      const id = this.resolveEffectiveId();
      return id ? { path: '/procurement-monitoring', query: { id: id } } : '/procurement-monitoring';
    }
  },
  watch: {
    '$route.query.id'(newId) {
      if (newId) {
        this.selectedProjectId = isNaN(newId) ? newId : Number(newId);
        this.fetchDashboardData();
      }
    }
  },
  async mounted() {
    await this.fetchProjects();
    await this.fetchDashboardData();
    this.pollingTimer = setInterval(() => {
      this.fetchDashboardData(true);
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
    async fetchProjects() {
      try {
        const res = await axios.get('http://127.0.0.1:8000/api/projects');
        this.projects = res.data?.data || [];
        const queryId = this.$route.query.id;
        if (queryId) {
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
      this.fetchDashboardData();
    },
    async triggerSync() {
      this.isSyncing = true;
      try {
        await axios.post('http://127.0.0.1:8000/api/sheets/sync', { 
          sheet: 'progress_revenue',
          id: this.resolveEffectiveId()
        });
        await this.fetchDashboardData();
      } catch (err) {
        console.error('Failed to sync sheet:', err);
      } finally {
        this.isSyncing = false;
      }
    },
    async fetchDashboardData(isSilent = false) {
      if (!isSilent) this.isLoading = true;
      this.errorMessage = '';
      try {
        const idParam = this.resolveEffectiveId();
        const response = await axios.get(`http://127.0.0.1:8000/api/project-monitoring?id=${idParam}&t=${Date.now()}`, {
          headers: { 'Cache-Control': 'no-cache, no-store, must-revalidate' }
        });
        const rows = response.data?.data || [];
        const project = response.data?.project || null;
        if (rows.length > 0) {
          const parsedGantt = parseGanttChartData(rows);
          this.ganttData = { ...parsedGantt };
          const parsedHeader = parseProjectHeader(rows, parsedGantt.activities);
          this.projectInfo = { ...parsedHeader };
        }
        if (project) {
          this.projectId = project.id;
          this.projectDescription = project.description || '';
        }
      } catch (error) {
        console.error('Error fetching data:', error);
        if (!isSilent) {
          this.errorMessage = error.response?.data?.message || 'Gagal mengambil data dari API Laravel.';
        }
      } finally {
        if (!isSilent) this.isLoading = false;
      }
    }
  }
};
</script>
