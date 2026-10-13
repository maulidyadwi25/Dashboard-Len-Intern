<script setup>
import { computed } from "vue";
import { useRoute } from "vue-router";
import { LayoutDashboard, ChevronDown } from "lucide-vue-next";

const route = useRoute();

const activePath = computed(() => route.path);

const linkClass = (path) =>
  activePath.value === path
    ? "px-4 py-1.5 text-xs font-semibold rounded-lg transition-all duration-150 bg-blue-600 text-white shadow-xs"
    : "px-4 py-1.5 text-xs font-semibold rounded-lg transition-all duration-150 text-slate-600 hover:text-slate-900 hover:bg-slate-200/60";
</script>

<template>
  <header class="bg-white border-b border-slate-200 px-6 py-3 shadow-xs">
    <div
      class="max-w-[1600px] mx-auto flex flex-col md:flex-row items-center justify-between gap-4"
    >
      <!-- Logo & Title -->
      <div class="flex items-center gap-3">
        <div
          class="w-9 h-9 rounded-lg bg-blue-600 flex items-center justify-center text-white shadow-xs"
        >
          <LayoutDashboard class="w-5 h-5" />
        </div>
        <div>
          <h1
            class="text-sm font-bold tracking-wider text-slate-900 uppercase leading-none"
          >
            Executive Command
          </h1>
          <p
            class="text-[10px] tracking-widest text-slate-500 font-medium uppercase mt-0.5"
          >
            Governance & Oversight
          </p>
        </div>
      </div>

      <!-- Navigation Tabs -->
      <nav
        class="flex items-center bg-slate-100/80 p-1 rounded-xl border border-slate-200/60"
      >
        <router-link
          to="/budget-monitoring"
          :class="linkClass('/budget-monitoring')"
        >
          Budget Monitoring
        </router-link>
        <router-link to="/procurement" :class="linkClass('/procurement')">
          Procurement
        </router-link>
        <router-link
          to="/progress-revenue"
          :class="linkClass('/progress-revenue')"
        >
          Progress &amp; Revenue
        </router-link>
      </nav>

      <!-- User Profile -->
      <div class="flex items-center gap-3">
        <div
          class="w-9 h-9 rounded-full bg-slate-700 text-white font-bold text-xs flex items-center justify-center border-2 border-white shadow-xs"
        >
          MS
        </div>
        <div class="text-right hidden sm:block">
          <div class="flex items-center gap-1">
            <span class="text-xs font-bold text-slate-800"
              >Marcus Sterling</span
            >
            <ChevronDown class="w-3.5 h-3.5 text-slate-400" />
          </div>
          <p class="text-[11px] text-slate-500 font-medium">Project Director</p>
        </div>
      </div>
    </div>
  </header>
</template>
