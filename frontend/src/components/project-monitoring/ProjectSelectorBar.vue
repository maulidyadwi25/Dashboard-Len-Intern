<script setup>
import { ref } from 'vue'
import { FolderKanban, ChevronDown } from 'lucide-vue-next'

const selectedProject = ref('Radar GCI Infrastructure Phase II (1LC-522UD019)')
const projects = [
  'Radar GCI Infrastructure Phase II (1LC-522UD019)',
  'Naval Radar Modernization Program (2LC-881AB002)',
  'Cyber Enterprise Security Upgrade (3LC-104XY088)'
]
const isOpen = ref(false)

const selectProject = (proj) => {
  selectedProject.value = proj
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
        <span class="text-xs font-bold text-slate-800">{{ selectedProject }}</span>
        <ChevronDown class="w-4 h-4 text-slate-400 ml-1" />
      </div>

      <!-- Dropdown Menu -->
      <div 
        v-if="isOpen" 
        class="absolute right-0 top-11 z-30 w-96 bg-white border border-slate-200 rounded-xl shadow-lg py-2 text-xs"
      >
        <div 
          v-for="p in projects" 
          :key="p" 
          @click="selectProject(p)"
          :class="[
            'px-4 py-2.5 cursor-pointer font-medium hover:bg-blue-50 transition-colors flex items-center justify-between',
            p === selectedProject ? 'text-blue-600 font-bold bg-blue-50/50' : 'text-slate-700'
          ]"
        >
          <span>{{ p }}</span>
          <span v-if="p === selectedProject" class="w-2 h-2 rounded-full bg-blue-600"></span>
        </div>
      </div>
    </div>
  </div>
</template>
