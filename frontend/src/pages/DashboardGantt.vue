<!-- src/pages/DashboardGantt.vue -->
<script setup>
import { RouterLink } from "vue-router";
import Navbar from "@/components/common/Navbar.vue";
import Footer from "@/components/common/Footer.vue";
import GanttTrial from "@/components/gantt/GanttTrial.vue";
import GanttDhtmlx from "@/components/gantt/GanttDhtmlx.vue";
</script>

<template>
  <div class="flex min-h-screen flex-col bg-[#f8fafc] font-sans text-slate-800">
    <Navbar />

    <main class="mx-auto w-full max-w-7xl flex-1 space-y-6 px-6 py-8">
      <!-- Tombol Navigasi Kembali -->
      <div class="mb-4">
        <RouterLink
          to="/"
          class="inline-flex items-center text-sm font-medium text-slate-500 transition hover:text-indigo-600"
        >
          &larr; Kembali ke Dashboard Utama
        </RouterLink>
      </div>

      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-slate-900">
            Work Breakdown Structure (WBS) Schedule
          </h1>
          <p class="text-sm text-slate-500">
            Visualisasi linimasa proyek dan progres pengerjaan task.
          </p>
        </div>
      </div>

      <!-- Container Gantt Chart 1 -->
      <div
        class="w-full rounded-xl border border-slate-200 bg-white p-6 shadow-sm"
      >
        <h2 class="mb-4 text-lg font-semibold text-slate-800">
          Visualisasi Gantt Trial
        </h2>
        <div class="min-h-100 w-full overflow-x-auto">
          <GanttTrial />
        </div>
      </div>

      <!-- Container Gantt Chart 2 -->
      <div
        class="w-full rounded-xl border border-slate-200 bg-white p-6 shadow-sm"
      >
        <h2 class="mb-4 text-lg font-semibold text-slate-800">
          Visualisasi DHTMLX Gantt
        </h2>
        <div class="min-h-100 w-full overflow-x-auto">
          <GanttDhtmlx />
        </div>
      </div>
    </main>

    <Footer />
  </div>
</template>
