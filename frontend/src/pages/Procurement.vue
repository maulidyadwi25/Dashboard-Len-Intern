<script setup>
import { ref, computed, onMounted, watch } from "vue";
import ExecutiveHeader from "@/components/common/ExecutiveHeader.vue";
import ProjectSelectorBar from "@/components/common/ProjectSelectorBar.vue";
import ProjectProfileCard from "@/components/common/ProjectProfileCard.vue";
import ProcurementStatusMonitoring from "@/components/procurement/ProcurementStatusMonitoring.vue";
import ProcurementMetrics from "@/components/procurement/ProcurementMetrics.vue";
import WbsGanttTimeline from "@/components/procurement/gantt/Index.vue";
import Footer from "@/components/common/Footer.vue";
import { budgetService } from "@/services/budgetServices";
import { useProject } from "@/composables/useProject";
import { generateProcurementPdf } from "@/utils/pdfExport";

const { activeProjectId } = useProject();

const procurementItems = ref([]);
const activeFilter = ref("All Packages");
const projectMeta = ref({
  title: "-",
  code: "-",
  client: "-",
  nilaiKontrak: "-",
  pimpinanProyek: "-",
  projectAnalyst: "-",
});

const loadProcurementData = async () => {
  if (!activeProjectId.value) return;

  try {
    const res = await budgetService.getProcurementData(activeProjectId.value);
    const payload = res.data?.data ?? res.data ?? {};

    const items = payload.procurement_items ?? payload.items ?? [];
    procurementItems.value = items;

    if (payload.project) {
      projectMeta.value = {
        title: payload.project.name || payload.project.project_name || "-",
        code: payload.project.code || payload.project.project_code || "-",
        client: payload.project.client_name || "-",
        nilaiKontrak:
          payload.project.contract_value ??
          payload.project.net_contract_value ??
          0,
        pimpinanProyek: payload.project.leader_name || "-",
        projectAnalyst: payload.project.analyst_name || "-",
      };
    }
  } catch (err) {
    console.warn("Failed to load real procurement data from API:", err);
  }
};

watch(activeProjectId, () => {
  void loadProcurementData();
});

onMounted(() => {
  void loadProcurementData();
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
