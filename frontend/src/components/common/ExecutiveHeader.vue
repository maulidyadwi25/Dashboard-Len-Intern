<script setup>
import { computed, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { LayoutDashboard, LogOut } from "lucide-vue-next";
import { useAuth } from "../../composables/useAuth";
import ProgressNotifications from "./ProgressNotifications.vue";

const route = useRoute();
const router = useRouter();
const { user, logout } = useAuth();

const activePath = computed(() => route.path);
const displayName = computed(() => user.value?.name ?? "User");
const displayRole = computed(() =>
  user.value?.role === "VP" ? "Vice President" : "Project Analyst",
);
const logoutError = ref(false);
const avatarInitials = computed(() =>
  displayName.value
    .split(/\s+/)
    .slice(0, 2)
    .map((part) => part.charAt(0))
    .join("")
    .toUpperCase(),
);

const linkClass = (path) =>
  activePath.value === path
    ? "px-4 py-1.5 text-xs font-semibold rounded-lg transition-all duration-150 bg-blue-600 text-white shadow-xs"
    : "px-4 py-1.5 text-xs font-semibold rounded-lg transition-all duration-150 text-slate-600 hover:text-slate-900 hover:bg-slate-200/60";

async function signOut() {
  logoutError.value = false;
  try {
    await logout();
    await router.replace({ name: "Login" });
  } catch {
    logoutError.value = true;
  }
}
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
        <router-link
          v-if="user?.role === 'VP'"
          to="/progress-approval"
          :class="linkClass('/progress-approval')"
        >
          Persetujuan Progres
        </router-link>
      </nav>

      <!-- User Profile -->
      <div class="flex items-center gap-3">
        <ProgressNotifications />
        <div
          class="w-9 h-9 rounded-full bg-slate-700 text-white font-bold text-xs flex items-center justify-center border-2 border-white shadow-xs"
        >
          {{ avatarInitials }}
        </div>
        <div class="text-right hidden sm:block">
          <div class="flex items-center gap-1">
            <span class="text-xs font-bold text-slate-800"
              >{{ displayName }}</span
            >
          </div>
          <p class="text-[11px] text-slate-500 font-medium">{{ displayRole }}</p>
        </div>
        <button
          type="button"
          class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-slate-200 px-2.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
          aria-label="Keluar"
          title="Keluar"
          @click="signOut"
        >
          <LogOut class="h-4 w-4" />
          <span class="hidden sm:inline">Keluar</span>
        </button>
      </div>
    </div>
    <p
      v-if="logoutError"
      role="alert"
      class="mx-auto mt-2 max-w-[1600px] text-right text-xs text-red-600"
    >
      Sesi tidak dapat ditutup dengan sempurna. Silakan masuk kembali.
    </p>
  </header>
</template>
