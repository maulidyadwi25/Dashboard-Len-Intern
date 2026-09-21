<script setup>
import { ref, computed, onMounted } from "vue";
import ExecutiveHeader from "@/components/project-monitoring/ExecutiveHeader.vue";
import ProjectSelectorBar from "@/components/project-monitoring/ProjectSelectorBar.vue";
import ProjectProfileCard from "@/components/project-monitoring/ProjectProfileCard.vue";
import ProcurementStatusMonitoring from "@/components/project-monitoring/ProcurementStatusMonitoring.vue";
import ProcurementMetrics from "@/components/project-monitoring/ProcurementMetrics.vue";
import WbsGanttTimeline from "@/components/project-monitoring/gantt/Index.vue";
import Footer from "@/components/common/Footer.vue";
import { budgetService } from "@/services/budgetServices";
import { generateProcurementPdf } from "@/utils/pdfExport";

const procurementItems = ref([]);
const activeFilter = ref("All Packages");
const projectMeta = ref({
  title: "PROCUREMENT",
  code: "B-1234567",
  nilaiKontrak: "Rp 7,78 T",
  pimpinanProyek: "SETIAJI",
  projectAnalyst: "KURNIAWAN",
});

onMounted(async () => {
  try {
    const res = await budgetService.getProcurementData();
    const payload = res.data?.data ?? res.data ?? {};

    const items = payload.procurement_items ?? payload.items ?? [];
    procurementItems.value = items;

    if (payload.project) {
      projectMeta.value = {
        title:
          payload.project.name ||
          payload.project.project_name ||
          "REFURBISHMENT KRI SIGMA",
        code:
          payload.project.code || payload.project.project_code || "B-1234567",
        nilaiKontrak:
          payload.project.contract_value || payload.project.net_contract_value
            ? `Rp ${((payload.project.contract_value ?? payload.project.net_contract_value) / 1e12).toFixed(2)} T`
            : "Rp 7,78 T",
        pimpinanProyek: payload.project.leader_name || "SETIAJI",
        projectAnalyst: payload.project.analyst_name || "KURNIAWAN",
      };
    }
  } catch (err) {
    console.warn("Failed to load real procurement data from API:", err);
  }
});

const handleDownload = () => {
  generateProcurementPdf({
    items: filteredItems.value,
    projectName: projectMeta.value?.title || "Procurement Report",
  });
};

const filteredItems = computed(() => {
  if (activeFilter.value === "All Packages") return procurementItems.value;
  return procurementItems.value.filter(
    (i) =>
      (i.priority || "").trim().toLowerCase() ===
      activeFilter.value.toLowerCase(),
  );
});

// Helper function to convert YYYY-MM-DD to DD/MM/YYYY for backend compatibility
const formatDateForBackend = (dateStr) => {
  if (!dateStr) return "";
  const parts = dateStr.split("-");
  if (parts.length === 3) {
    return `${parts[2]}/${parts[1]}/${parts[0]}`;
  }
  return dateStr;
};

// Update start date for a specific item
const updateStartDate = (itemId, newDateStr) => {
  const index = procurementItems.value.findIndex((i) => i.id === itemId);
  if (index !== -1) {
    const formattedDate = formatDateForBackend(newDateStr);
    procurementItems.value[index] = {
      ...procurementItems.value[index],
      tgl_mulai: formattedDate,
      start_date: formattedDate,
    };
  }
};
</script>

<template>
  <div
    class="min-h-screen flex flex-col bg-[#f4f7fa] font-sans text-slate-800 antialiased"
  >
    <!-- Executive Top Bar / Header -->
    <ExecutiveHeader />

    <!-- Main Container -->
    <main
      class="flex-1 max-w-[1600px] w-full mx-auto px-4 sm:px-6 py-6 space-y-2"
    >
      <!-- Project Selector Bar -->
      <ProjectSelectorBar />

      <!-- Project Profile Card -->
      <ProjectProfileCard :projectInfo="projectMeta" />

      <!-- Procurement Status & Monitoring -->
      <ProcurementStatusMonitoring :items="filteredItems" />

      <!-- Package Filters & Metric Cards -->
      <ProcurementMetrics
        :items="procurementItems"
        :filter="activeFilter"
        @update:filter="activeFilter = $event"
        @download="handleDownload"
      />

      <!-- WBS Sheet & Baseline Gantt Timeline (Powered by Procurement Data) -->
      <WbsGanttTimeline
        :items="filteredItems"
        :filter="activeFilter"
        @update:filter="activeFilter = $event"
        @download="handleDownload"
      />
    </main>

    <!-- Footer -->
    <Footer />
  </div>
</template>
