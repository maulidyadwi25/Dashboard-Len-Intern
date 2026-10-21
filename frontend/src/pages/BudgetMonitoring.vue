<script setup>
import { ref, onMounted, computed, watch } from "vue";
import { Globe, ChevronDown, FileDown } from "lucide-vue-next";
import { budgetService } from "@/services/budgetServices";
import { generateBudgetPdf } from "@/utils/pdfExport";
import { useCurrency } from "@/composables/useCurrency";
import { useProject } from "@/composables/useProject";

import ExecutiveHeader from "@/components/common/ExecutiveHeader.vue";
import Footer from "@/components/common/Footer.vue";
import ProjectSelectorBar from "@/components/common/ProjectSelectorBar.vue";
import ProjectProfileCard from "@/components/common/ProjectProfileCard.vue";
import BudgetSummaryCards from "@/components/budget-monitoring/BudgetSummaryCards.vue";
import BudgetBreakdownTable from "@/components/budget-monitoring/BudgetBreakdownTable.vue";

const { selectedCurrency, exchangeRates, formatCurrency } = useCurrency();
const { activeProjectId } = useProject();
const currencies = ["IDR", "USD", "EUR", "SGD"];

const isLoading = ref(true);
const errorMessage = ref("");
const sheetData = ref({
  metadata: {},
  budget_summary: {},
  budget_breakdown: [],
});

const fetchDashboardData = async () => {
  if (activeProjectId.value === null) {
    isLoading.value = false;
    return;
  }

  try {
    isLoading.value = true;
    errorMessage.value = "";

    const response = await budgetService.getDashboardData(
      activeProjectId.value,
    );
    const payload = response.data?.data ?? response.data ?? {};

    const normalizedSheetData = payload.sheetData ?? {
      metadata: payload.metadata ?? {},
      budget_summary: payload.budget_summary ?? {
        sisa_anggaran_sap: payload.aggregates?.total_sap_remaining ?? 0,
        wbs_belum_input: payload.aggregates?.total_wbs_uninput ?? 0,
        total: payload.aggregates?.total_budget ?? 0,
        estimasi_kebutuhan: payload.aggregates?.total_estimated_need ?? 0,
        selisih_variance: payload.aggregates?.total_variance ?? 0,
      },
      budget_breakdown:
        payload.budget_breakdown ?? payload.budget_breakdowns ?? [],
    };

    const contractValue = [
      normalizedSheetData.metadata?.net_contract_value,
      payload.profiles?.find((profile) => profile.type === "progress_revenue")
        ?.nilai_kontrak,
      payload.procurement_profile?.nilai_kontrak,
      payload.net_contract_value,
    ]
      .map(Number)
      .find((value) => Number.isFinite(value) && value > 0);

    const progressRevenueProfile = payload.profiles?.find(
      (profile) => profile.type === "progress_revenue",
    );

    normalizedSheetData.metadata = {
      ...normalizedSheetData.metadata,
      nama_client:
        progressRevenueProfile?.client_name ||
        payload.client_name ||
        normalizedSheetData.metadata?.nama_client ||
        "-",
      kode_proyek:
        progressRevenueProfile?.project_code ||
        payload.project_code ||
        normalizedSheetData.metadata?.kode_proyek ||
        "-",
      nama_pimpro:
        progressRevenueProfile?.nama_pimpro ||
        payload.pimpro ||
        normalizedSheetData.metadata?.nama_pimpro ||
        "-",
      nama_pa:
        progressRevenueProfile?.nama_PA ||
        payload.analyst ||
        normalizedSheetData.metadata?.nama_pa ||
        "-",
      net_contract_value:
        contractValue ??
        Number(normalizedSheetData.metadata?.net_contract_value ?? 0),
    };

    sheetData.value = normalizedSheetData;

    if (payload.exchangeRates) {
      exchangeRates.value = {
        ...exchangeRates.value,
        ...payload.exchangeRates,
      };
    }
  } catch (error) {
    console.error("Error fetching API:", error);
    errorMessage.value =
      error.response?.data?.message ||
      "Gagal menghubungi backend Laravel. Pastikan server backend aktif.";
  } finally {
    isLoading.value = false;
  }
};

watch(activeProjectId, () => {
  void fetchDashboardData();
});

onMounted(fetchDashboardData);

const metadata = computed(() => ({
  projectName: sheetData.value?.metadata?.nama_proyek || "ABCDE PROJECT NAME",
  projectCode: sheetData.value?.metadata?.kode_proyek || "-",
  lastUpdate: sheetData.value?.metadata?.tanggal_update || "-",
  namaPimpro: sheetData.value?.metadata?.nama_pimpro || "-",
  namaClient: sheetData.value?.metadata?.nama_client || "-",
  namaPA: sheetData.value?.metadata?.nama_pa || "-",
  netContractValue: Number(sheetData.value?.metadata?.net_contract_value || 0),
}));

const projectMeta = computed(() => ({
  title: metadata.value.projectName,
  code: metadata.value.projectCode,
  nilaiKontrak: metadata.value.netContractValue,
  pimpinanProyek: metadata.value.namaPimpro,
  projectAnalyst: metadata.value.namaPA,
}));

const summary = computed(
  () =>
    sheetData.value?.budget_summary || {
      sisa_anggaran_sap: 0,
      wbs_belum_input: 0,
      total: 0,
      estimasi_kebutuhan: 0,
      selisih_variance: 0,
    },
);

const budgetList = computed(() => sheetData.value?.budget_breakdown || []);

const handleDownloadPdf = () => {
  generateBudgetPdf({
    selectedCurrency: selectedCurrency.value,
    metadata: metadata.value,
    summary: summary.value,
    budgetList: budgetList.value,
    formatCurrency,
  });
};
</script>

<template>
  <div
    class="flex min-h-screen flex-col bg-[#f8fafc] font-sans text-slate-800 antialiased"
  >
    <ExecutiveHeader />
    <main
      class="flex-1 max-w-[1600px] w-full mx-auto px-4 sm:px-6 py-6 space-y-2"
    >
      <ProjectSelectorBar />

      <!-- State Loading -->
      <div v-if="isLoading" class="flex h-64 items-center justify-center">
        <div
          class="h-8 w-8 animate-spin rounded-full border-4 border-indigo-600 border-t-transparent"
        ></div>
        <span class="ml-3 font-semibold text-slate-600"
          >Menghubungkan ke Backend Laravel...</span
        >
      </div>

      <!-- State Error -->
      <div
        v-else-if="errorMessage"
        class="rounded-xl border border-rose-200 bg-rose-50 p-6 text-center text-rose-700"
      >
        <p class="font-bold">{{ errorMessage }}</p>
        <button
          @click="fetchDashboardData"
          class="mt-3 rounded-lg bg-rose-600 px-4 py-2 text-xs font-semibold text-white hover:bg-rose-700"
        >
          Coba Refresh Lagi
        </button>
      </div>

      <!-- State Data Loaded -->
      <template v-else>
        <!-- Project Profile Card (consistent with Progress & Revenue and Procurement) -->
        <ProjectProfileCard :projectInfo="projectMeta" />

        <!-- Controls Toolbar (Currency Switcher & Download PDF) -->
        <div
          class="flex flex-col sm:flex-row items-center justify-between gap-4 rounded-xl border border-slate-200/80 bg-white px-5 py-3 shadow-xs"
        >
          <div class="text-xs text-slate-500 font-medium">
            Terakhir diperbarui:
            <span class="font-semibold text-slate-700">{{
              metadata.lastUpdate
            }}</span>
          </div>

          <div class="flex items-center gap-3 self-end sm:self-auto">
            <!-- Currency Dropdown -->
            <div class="relative flex items-center">
              <Globe
                class="pointer-events-none absolute left-3 h-4 w-4 text-slate-400"
              />
              <select
                v-model="selectedCurrency"
                class="cursor-pointer appearance-none rounded-xl border border-slate-200 bg-slate-50 py-2 pr-8 pl-9 text-xs font-bold text-slate-700 transition hover:bg-slate-100 focus:border-blue-500 focus:bg-white focus:outline-none"
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
              @click="handleDownloadPdf"
              class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-blue-700 px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-slate-800 active:scale-95"
            >
              <FileDown class="h-4 w-4" />
              <span>Download PDF</span>
            </button>
          </div>
        </div>

        <BudgetSummaryCards :summary="summary" />
        <BudgetBreakdownTable :items="budgetList" :items-per-page="4" />
      </template>
    </main>
    <Footer />
  </div>
</template>
