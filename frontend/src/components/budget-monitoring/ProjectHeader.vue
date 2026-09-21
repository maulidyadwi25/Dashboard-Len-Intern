<script setup>
import { computed } from "vue";
import {
  Building2,
  UserCheck,
  Coins,
  Calendar,
  FileDown,
  Globe,
  ChevronDown,
} from "lucide-vue-next";
import { useCurrency } from "@/composables/useCurrency";

const props = defineProps({
  metadata: {
    type: Object,
    required: true,
    default: () => ({
      projectName: "-",
      projectCode: "-",
      lastUpdate: "-",
      namaPimpro: "-",
      namaClient: "-",
      netContractValue: 0,
    }),
  },
  estimasiKebutuhan: {
    type: Number,
    default: 0,
  },
});

defineEmits(["download"]);

const { formatCurrency, selectedCurrency } = useCurrency();
const currencies = ["IDR", "USD", "EUR", "SGD"];

const formattedNetContract = computed(() =>
  formatCurrency(
    props.metadata.netContractValue ?? props.metadata.net_contract_value,
  ),
);
</script>

<template>
  <header
    class="space-y-6 rounded-2xl border border-slate-200/80 bg-white p-7 shadow-xs transition-all"
  >
    <!-- TOP TIER: Project Title & Primary Action Bar -->
    <div
      class="flex flex-col justify-between gap-4 border-b border-slate-100 pb-5 sm:flex-row sm:items-center"
    >
      <!-- Title & Project Code -->
      <div class="flex flex-wrap items-center gap-3">
        <h1
          class="text-2xl font-black tracking-tight text-slate-900 sm:text-3xl"
        >
          {{ metadata.projectName || metadata.nama_proyek }}
        </h1>
        <span
          class="rounded-md bg-blue-50 px-2.5 py-1 font-mono text-xs font-bold text-blue-700 ring-1 ring-blue-700/10 ring-inset self-center"
        >
          {{ metadata.projectCode || metadata.kode_proyek }}
        </span>
      </div>

      <!-- Action Controls (Currency Switcher + PDF Button) -->
      <div class="flex items-center gap-3 self-start sm:self-auto">
        <!-- Currency Dropdown -->
        <div class="relative flex items-center">
          <Globe
            class="pointer-events-none absolute left-3 h-4 w-4 text-slate-400"
          />
          <select
            v-model="selectedCurrency"
            class="cursor-pointer appearance-none rounded-xl border border-slate-200 bg-slate-50 py-2.5 pr-8 pl-9 text-xs font-bold text-slate-700 transition hover:bg-slate-100 focus:border-blue-500 focus:bg-white focus:outline-none"
          >
            <option v-for="curr in currencies" :key="curr" :value="curr">
              {{ curr }}
            </option>
          </select>
          <ChevronDown
            class="pointer-events-none absolute right-2.5 h-3.5 w-3.5 text-slate-400"
          />
        </div>

        <!-- Download Button -->
        <button
          @click="$emit('download')"
          class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-blue-700 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-slate-800 active:scale-95"
        >
          <FileDown class="h-4 w-4" />
          <span>Download PDF</span>
        </button>
      </div>
    </div>

    <!-- BOTTOM TIER: Metadata Strip & Net Contract Highlight -->
    <div
      class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center"
    >
      <!-- Meta Information Badges Grid -->
      <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 sm:gap-6">
        <!-- Client -->
        <div class="flex items-center gap-3">
          <div
            class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-600"
          >
            <Building2 class="h-4 w-4" />
          </div>
          <div>
            <p
              class="text-[10px] font-bold tracking-wider text-slate-400 uppercase"
            >
              Client
            </p>
            <p class="text-sm font-semibold text-slate-800">
              {{ metadata.namaClient || metadata.nama_client }}
            </p>
          </div>
        </div>

        <!-- Pimpro / Project Manager -->
        <div class="flex items-center gap-3">
          <div
            class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-50 text-amber-700"
          >
            <UserCheck class="h-4 w-4" />
          </div>
          <div>
            <p
              class="text-[10px] font-bold tracking-wider text-slate-400 uppercase"
            >
              Pimpro
            </p>
            <p class="text-sm font-semibold text-slate-800">
              {{ metadata.namaPimpro || metadata.nama_pimpro }}
            </p>
          </div>
        </div>

        <!-- Last Updated -->
        <div class="flex items-center gap-3">
          <div
            class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-600"
          >
            <Calendar class="h-4 w-4" />
          </div>
          <div>
            <p
              class="text-[10px] font-bold tracking-wider text-slate-400 uppercase"
            >
              Last Update
            </p>
            <p class="text-sm font-semibold text-slate-800">
              {{ metadata.lastUpdate || metadata.tanggal_update }}
            </p>
          </div>
        </div>
      </div>

      <!-- Hero Metric: Net Contract Value -->
      <div
        class="flex items-center gap-3.5 rounded-xl border border-emerald-200/70 bg-gradient-to-r from-emerald-50/70 to-teal-50/40 px-5 py-3"
      >
        <div
          class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-xs"
        >
          <Coins class="h-5 w-5" />
        </div>
        <div>
          <span
            class="text-[10px] font-black tracking-wider text-emerald-800/80 uppercase"
          >
            Net Contract Value ({{ selectedCurrency }})
          </span>
          <p class="text-lg font-black tracking-tight text-emerald-950">
            {{ formattedNetContract }}
          </p>
        </div>
      </div>
    </div>
  </header>
</template>
