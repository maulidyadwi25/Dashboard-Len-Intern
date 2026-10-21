<script setup>
import { ref, onMounted } from 'vue'
import { ArrowLeftRight, ChevronDown, RefreshCw } from 'lucide-vue-next'
import { useProject } from '@/composables/useProject'

const {
  activeProjectId,
  projects,
  currentProject,
  isLoadingProjects,
  fetchProjects,
  setActiveProjectId,
} = useProject()

const isOpen = ref(false)

onMounted(() => {
  fetchProjects()
})

const selectProject = (proj) => {
  setActiveProjectId(proj.id)
  isOpen.value = false
}

const handleRefresh = async () => {
  await fetchProjects()
}
</script>

<template>
  <div class="bg-surface-container-lowest bg-white rounded-xl p-5 shadow-sm flex flex-col gap-4">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <!-- Left: Label PILIH PROJECT -->
      <span class="text-xs font-bold text-slate-600 tracking-wider uppercase">
        PILIH PROJECT
      </span>

      <!-- Right: Selector pill + Refresh button -->
      <div class="flex items-center gap-3">
        <!-- Project dropdown selector -->
        <div class="relative">
          <button
            type="button"
            @click="isOpen = !isOpen"
            class="bg-slate-100 hover:bg-slate-200/80 text-slate-800 text-xs font-bold px-4 py-2.5 rounded-lg flex items-center gap-2.5 transition-colors cursor-pointer select-none"
          >
            <ArrowLeftRight class="w-3.5 h-3.5 text-slate-600 shrink-0" />
            <span class="truncate max-w-[280px] sm:max-w-[420px] md:max-w-[500px]">
              {{ currentProject ? `${currentProject.project_code} - ${currentProject.project_name}` : 'Memuat Proyek...' }}
            </span>
            <ChevronDown class="w-3.5 h-3.5 text-slate-500 shrink-0 ml-1 transition-transform" :class="{ 'rotate-180': isOpen }" />
          </button>

          <!-- Dropdown List -->
          <div
            v-if="isOpen"
            class="absolute right-0 top-full mt-1.5 z-40 w-80 sm:w-96 bg-white border border-slate-200 rounded-xl shadow-lg py-1.5 text-xs max-h-72 overflow-y-auto"
          >
            <div
              v-for="p in projects"
              :key="p.id"
              @click="selectProject(p)"
              :class="[
                'px-4 py-2.5 cursor-pointer font-medium hover:bg-blue-50 transition-colors flex items-center justify-between gap-2',
                p.id === activeProjectId ? 'text-blue-600 font-bold bg-blue-50/50' : 'text-slate-700'
              ]"
            >
              <span class="truncate">{{ p.project_code }} - {{ p.project_name }}</span>
              <span v-if="p.id === activeProjectId" class="w-2 h-2 rounded-full bg-blue-600 shrink-0"></span>
            </div>
          </div>
        </div>

        <!-- Refresh Button -->
        <button
          type="button"
          @click="handleRefresh"
          :disabled="isLoadingProjects"
          class="bg-blue-600 hover:bg-blue-700 active:bg-blue-800 disabled:opacity-60 text-white text-xs font-semibold px-4 py-2.5 rounded-lg flex items-center gap-2 shadow-xs transition-colors cursor-pointer select-none"
        >
          <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': isLoadingProjects }" />
          <span>Refresh</span>
        </button>
      </div>
    </div>
  </div>
</template>
