<script setup>
import { ref, onMounted } from 'vue'
import { FolderKanban, ChevronDown } from 'lucide-vue-next'
import { useProject } from '@/composables/useProject'

const {
  activeProjectId,
  projects,
  currentProject,
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
</script>

<template>
  <div class="flex justify-end mb-4 relative">
    <div class="inline-flex items-center">
      <div 
        @click="isOpen = !isOpen"
        class="bg-white border border-slate-200/90 rounded-full px-4 py-2 flex items-center gap-2.5 shadow-xs cursor-pointer hover:border-blue-300 transition-colors"
      >
        <FolderKanban class="w-4 h-4 text-blue-600" />
        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide">PROYEK:</span>
        <span class="text-xs font-bold text-slate-800">
          {{ currentProject ? `${currentProject.project_name} (${currentProject.project_code})` : 'Memuat Proyek...' }}
        </span>
        <ChevronDown class="w-4 h-4 text-slate-400 ml-1" />
      </div>

      <!-- Dropdown Menu -->
      <div 
        v-if="isOpen" 
        class="absolute right-0 top-11 z-30 w-96 bg-white border border-slate-200 rounded-xl shadow-lg py-2 text-xs"
      >
        <div 
          v-for="p in projects" 
          :key="p.id" 
          @click="selectProject(p)"
          :class="[
            'px-4 py-2.5 cursor-pointer font-medium hover:bg-blue-50 transition-colors flex items-center justify-between',
            p.id === activeProjectId ? 'text-blue-600 font-bold bg-blue-50/50' : 'text-slate-700'
          ]"
        >
          <span>{{ p.project_name }} ({{ p.project_code }})</span>
          <span v-if="p.id === activeProjectId" class="w-2 h-2 rounded-full bg-blue-600"></span>
        </div>
      </div>
    </div>
  </div>
</template>
