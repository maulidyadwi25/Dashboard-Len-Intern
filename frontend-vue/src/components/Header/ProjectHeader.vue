<template>
  <div class="bg-white rounded-xl p-6 border border-outline-variant shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
    <!-- Project Information -->
    <div class="flex-1 min-w-0">
      <h2 class="text-2xl font-bold text-gray-900">
        {{ projectInfo.projectName }}
      </h2>
      <p class="text-body-sm text-gray-500 mt-1.5 font-normal">
        Project Code: <span class="font-medium text-gray-700">{{ projectInfo.projectCode }}</span> 
        <span class="mx-2 text-gray-300">|</span> 
        Project Value: <span class="font-medium text-gray-700">{{ projectInfo.projectValue }}</span>
      </p>

      <!-- Project Description -->
      <div class="mt-3">
        <div v-if="!isEditingDesc" 
          @click="startEditDesc" 
          class="text-sm text-gray-400 bg-white border border-outline-variant rounded-full px-4 py-2 cursor-text hover:border-gray-400 transition-colors"
          :class="{ 'text-gray-700': description }"
        >
          {{ description || 'Tambah catatan' }}
        </div>
        <div v-else class="flex items-center gap-2">
          <input 
            ref="descInput"
            v-model="editDescValue"
            type="text"
            maxlength="1000"
            class="flex-1 text-sm text-gray-700 border border-outline-variant rounded-full px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            placeholder="Tambah catatan"
            @blur="saveDesc"
            @keydown.enter="saveDesc"
            @keydown.esc="cancelEditDesc"
          />
          <button 
            @click="saveDesc" 
            class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-full text-body-sm font-semibold transition-colors cursor-pointer"
          >
            Simpan
          </button>
        </div>
        <p v-if="descStatus" class="text-label-caps mt-1 tracking-wider" :class="descStatus === 'saved' ? 'text-emerald-600' : 'text-red-500'">
          {{ descStatus === 'saved' ? 'Tersimpan' : descStatus }}
        </p>
      </div>
    </div> 

    <!-- Progress & SPI Metrics -->
    <div class="flex items-center space-x-6 divide-x divide-gray-200">
      <div class="pr-2">
        <span class="text-label-caps font-bold text-gray-400 tracking-wider uppercase block">
          OVERALL PROGRESS
        </span>
        <div class="flex items-baseline space-x-2 mt-1">
          <span class="text-2xl font-extrabold text-gray-900">
            {{ calculatedProgress }}
          </span>
          <span v-if="projectInfo.progressTrend" class="text-body-sm font-semibold text-emerald-600 flex items-center">
            ↑ {{ projectInfo.progressTrend }}
          </span>
        </div>
      </div>

      <div class="pl-6">
        <span class="text-label-caps font-bold text-gray-400 tracking-wider uppercase block">
          SPI
        </span>
        <div class="flex items-baseline space-x-2 mt-1">
          <span class="text-2xl font-extrabold text-gray-900">
            {{ projectInfo.spiValue }}
          </span>
          <span 
            v-if="projectInfo.spiStatus" 
            class="text-body-sm font-semibold"
            :class="projectInfo.spiStatus === 'Delay' ? 'text-amber-600' : 'text-emerald-600'"
          >
            {{ projectInfo.spiStatus }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'ProjectHeader',
  emits: ['update:description'],
  props: {
    projectInfo: {
      type: Object,
      required: true,
      default: () => ({})
    },
    projectId: {
      type: [Number, String],
      default: null,
    },
    description: {
      type: String,
      default: '',
    },
    activities: {
      type: Array,
      default: () => []
    }
  },
  data() {
    return {
      isEditingDesc: false,
      editDescValue: '',
      descStatus: '',
    };
  },
  watch: {
    description(newVal) {
      if (!this.isEditingDesc) {
        this.projectInfo.description = newVal;
      }
    }
  },
  methods: {
    startEditDesc() {
      this.editDescValue = this.description || '';
      this.isEditingDesc = true;
      this.descStatus = '';
      this.$nextTick(() => {
        this.$refs.descInput?.focus();
      });
    },
    cancelEditDesc() {
      this.isEditingDesc = false;
      this.editDescValue = '';
      this.descStatus = '';
    },
    async saveDesc() {
      const newVal = this.editDescValue.trim();
      if (newVal === (this.description || '')) {
        this.isEditingDesc = false;
        return;
      }
      try {
        const res = await axios.post('http://127.0.0.1:8000/api/description', {
          id: this.projectId,
          description: newVal || null,
        });
        if (res.data.status === 'success') {
          this.$emit('update:description', newVal);
          this.projectInfo.description = newVal;
          this.descStatus = 'saved';
          setTimeout(() => { this.descStatus = ''; }, 2000);
        }
      } catch (err) {
        this.descStatus = 'Gagal menyimpan';
        console.error('Failed to save description:', err);
      }
      this.isEditingDesc = false;
    },
  },
  computed: {
    calculatedProgress() {
      if (this.projectInfo.overallProgress) {
        return this.projectInfo.overallProgress;
      }
      if (!this.activities || this.activities.length === 0) {
        return '0%';
      }
      let totalActual = 0;
      let totalGrandTarget = 0;
      this.activities.forEach(item => {
        if (item.actualWeeks) {
          Object.values(item.actualWeeks).forEach(val => {
            totalActual += Number(val) || 0;
          });
        }
        if (item.grandTotal) {
          totalGrandTarget += Number(item.grandTotal) || 0;
        } else if (item.planWeeks) {
          Object.values(item.planWeeks).forEach(val => {
            totalGrandTarget += Number(val) || 0;
          });
        }
      });
      if (totalGrandTarget === 0) return '0%';
      const percentage = (totalActual / totalGrandTarget) * 100;
      return `${percentage.toFixed(2)}%`;
    }
  }
};
</script>