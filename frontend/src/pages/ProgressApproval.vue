<script setup>
import { onMounted, ref, watch } from "vue";
import { CheckSquare, History, Shield } from "lucide-vue-next";
import ExecutiveHeader from "@/components/common/ExecutiveHeader.vue";
import Footer from "@/components/common/Footer.vue";
import WbsApprovalHistory from "@/components/progressrevenue/WbsApprovalHistory.vue";
import WbsProgressHistory from "@/components/progressrevenue/WbsProgressHistory.vue";
import WbsProgressRequests from "@/components/progressrevenue/WbsProgressRequests.vue";
import { useProject } from "@/composables/useProject";
import { wbsService } from "@/services/wbsService.js";

const { activeProjectId } = useProject();
const activeTab = ref("pending");

const counts = ref({
  pending: 0,
  approved: 0,
  rejected: 0,
  reviewed: 0,
  direct_logs: 0,
});

async function fetchCounts() {
  if (activeProjectId.value === null || activeProjectId.value === undefined) {
    return;
  }
  try {
    const res = await wbsService.getProgressChangeRequests(
      activeProjectId.value,
      1,
      "pending",
    );
    if (res.data?.meta?.counts) {
      counts.value = res.data.meta.counts;
    }
  } catch {
    // ignore
  }
}

function handleCountsUpdate(newCounts) {
  if (newCounts) {
    counts.value = { ...counts.value, ...newCounts };
  }
}

watch(
  () => activeProjectId.value,
  () => {
    void fetchCounts();
  },
);

onMounted(() => {
  void fetchCounts();
});
</script>

<template>
  <div
    class="flex min-h-screen flex-col bg-background font-sans text-on-surface antialiased"
  >
    <ExecutiveHeader />

    <main
      class="mx-auto w-full max-w-[1600px] flex-1 space-y-5 px-4 py-6 sm:px-6"
    >
      <section class="space-y-1">
        <div class="flex flex-wrap items-center gap-2">
          <h1
            class="text-xl font-bold tracking-tight text-on-surface sm:text-2xl"
          >
            Persetujuan Progres WBS
          </h1>
          <span
            class="rounded-full bg-primary/10 px-2.5 py-1 text-[11px] font-semibold text-primary"
          >
            Project Analyst → Vice President
          </span>
        </div>
        <p class="max-w-3xl text-sm text-on-surface-variant">
          Tinjau alasan dan perubahan progres yang diajukan PA. Progres hanya
          berubah setelah pengajuan disetujui.
        </p>
      </section>

      <!-- Navigation Tabs Styled exactly like design -->
      <nav
        aria-label="Bagian persetujuan progres"
        class="inline-flex max-w-full flex-wrap gap-2 rounded-2xl border border-slate-200/90 bg-[#f1f5f9] p-1.5"
      >
        <!-- Tab 1: Menunggu Persetujuan -->
        <button
          type="button"
          class="inline-flex h-10 items-center gap-2.5 rounded-xl px-4 text-xs font-bold transition"
          :class="
            activeTab === 'pending'
              ? 'bg-white text-[#0a4c78] shadow-xs'
              : 'text-slate-600 hover:bg-white/60 hover:text-slate-900'
          "
          :aria-current="activeTab === 'pending' ? 'page' : undefined"
          @click="activeTab = 'pending'"
        >
          <CheckSquare
            class="h-4 w-4"
            :class="activeTab === 'pending' ? 'text-[#0a4c78]' : 'text-slate-500'"
          />
          <span>Menunggu Persetujuan</span>
          <span
            class="rounded-full px-2 py-0.5 text-[11px] font-extrabold"
            :class="
              activeTab === 'pending'
                ? 'bg-[#0a4c78] text-white'
                : 'bg-slate-300/80 text-slate-700'
            "
          >
            {{ counts.pending }}
          </span>
        </button>

        <!-- Tab 2: Riwayat Keputusan -->
        <button
          type="button"
          class="inline-flex h-10 items-center gap-2.5 rounded-xl px-4 text-xs font-bold transition"
          :class="
            activeTab === 'history'
              ? 'bg-white text-[#0a4c78] shadow-xs'
              : 'text-slate-600 hover:bg-white/60 hover:text-slate-900'
          "
          :aria-current="activeTab === 'history' ? 'page' : undefined"
          @click="activeTab = 'history'"
        >
          <History
            class="h-4 w-4"
            :class="activeTab === 'history' ? 'text-[#0a4c78]' : 'text-slate-500'"
          />
          <span>Riwayat Keputusan</span>
          <span
            class="rounded-full px-2.5 py-0.5 text-[11px] font-bold"
            :class="
              activeTab === 'history'
                ? 'bg-blue-100 text-[#0a4c78]'
                : 'bg-slate-200 text-slate-700'
            "
          >
            {{ counts.reviewed }} Selesai
          </span>
        </button>

        <!-- Tab 3: Perubahan Langsung VP -->
        <button
          type="button"
          class="inline-flex h-10 items-center gap-2.5 rounded-xl px-4 text-xs font-bold transition"
          :class="
            activeTab === 'direct_logs'
              ? 'bg-white text-[#0a4c78] shadow-xs'
              : 'text-slate-600 hover:bg-white/60 hover:text-slate-900'
          "
          :aria-current="activeTab === 'direct_logs' ? 'page' : undefined"
          @click="activeTab = 'direct_logs'"
        >
          <Shield
            class="h-4 w-4"
            :class="activeTab === 'direct_logs' ? 'text-[#0a4c78]' : 'text-slate-500'"
          />
          <span>Perubahan Langsung VP</span>
          <span
            class="rounded-full px-2 py-0.5 text-[11px] font-bold"
            :class="
              activeTab === 'direct_logs'
                ? 'bg-blue-100 text-[#0a4c78]'
                : 'bg-slate-200 text-slate-700'
            "
          >
            {{ counts.direct_logs }} Log
          </span>
        </button>
      </nav>

      <!-- Tab Content -->
      <WbsProgressRequests
        v-if="activeTab === 'pending'"
        :project-id="activeProjectId"
        @update:counts="handleCountsUpdate"
      />
      <WbsApprovalHistory
        v-else-if="activeTab === 'history'"
        :project-id="activeProjectId"
        @update:counts="handleCountsUpdate"
      />
      <WbsProgressHistory
        v-else-if="activeTab === 'direct_logs'"
        :project-id="activeProjectId"
      />
    </main>

    <Footer />
  </div>
</template>
