<script setup>
import { FileText, CreditCard, User, UserCheck } from "lucide-vue-next";

defineProps({
  projectInfo: {
    type: Object,
    default: () => ({
      title: "Radar GCI Infrastructure Phase II",
      code: "1LC-522UD019",
      nilaiKontrak: "Rp 125,8 M",
      pimpinanProyek: "Marcus S. / Syahr...",
      projectAnalyst: "Lutfhi Nur Amali",
    }),
  },
});
</script>

<template>
  <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs mb-6">
    <div
      class="flex flex-col lg:flex-row lg:items-center justify-between gap-6"
    >
      <!-- Left: Title & Code -->
      <div class="space-y-2">
        <div
          class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 text-[11px] font-bold uppercase tracking-wider"
        >
          <FileText class="w-3.5 h-3.5" />
          Project Profile
        </div>
        <div class="flex flex-wrap items-center gap-3">
          <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">
            {{ projectInfo.title }}
          </h2>
          <span
            class="px-2.5 py-1 rounded-md bg-blue-50 text-blue-600 font-mono text-xs font-bold border border-blue-200/80"
          >
            # {{ projectInfo.code }}
          </span>
        </div>
      </div>

      <!-- Right: Metadata Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <!-- Nilai Kontrak -->
        <div
          class="bg-slate-50/80 border border-slate-100 rounded-xl p-3 flex items-center gap-3"
        >
          <div
            class="w-10 h-10 rounded-lg bg-blue-100/70 text-blue-600 flex items-center justify-center shrink-0"
          >
            <CreditCard class="w-5 h-5" />
          </div>
          <div>
            <p
              class="text-[10px] font-bold text-slate-500 uppercase tracking-wider"
            >
              Nilai Kontrak
            </p>
            <p class="text-sm font-extrabold text-slate-900 mt-0.5">
              {{ projectInfo.nilaiKontrak }}
            </p>
          </div>
        </div>

        <!-- Pimpinan Proyek -->
        <div
          class="bg-slate-50/80 border border-slate-100 rounded-xl p-3 flex items-center gap-3"
        >
          <div
            class="w-10 h-10 rounded-lg bg-slate-200/70 text-slate-700 flex items-center justify-center shrink-0"
          >
            <User class="w-5 h-5" />
          </div>
          <div>
            <p
              class="text-[10px] font-bold text-slate-500 uppercase tracking-wider"
            >
              Pimpinan Proyek
            </p>
            <p
              class="text-sm font-bold text-slate-900 mt-0.5 truncate max-w-[130px]"
            >
              {{ projectInfo.pimpinanProyek }}
            </p>
          </div>
        </div>

        <!-- Project Analyst -->
        <div
          class="bg-slate-50/80 border border-slate-100 rounded-xl p-3 flex items-center gap-3"
        >
          <div
            class="w-10 h-10 rounded-lg bg-slate-200/70 text-slate-700 flex items-center justify-center shrink-0"
          >
            <UserCheck class="w-5 h-5" />
          </div>
          <div>
            <p
              class="text-[10px] font-bold text-slate-500 uppercase tracking-wider"
            >
              Project Analyst
            </p>
            <p
              class="text-sm font-bold text-slate-900 mt-0.5 truncate max-w-[130px]"
            >
              {{ projectInfo.projectAnalyst }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
