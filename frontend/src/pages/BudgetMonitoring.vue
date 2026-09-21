<script setup>
import { ref, onMounted, computed } from "vue";
import { budgetService } from "@/services/budgetServices";
import { generateBudgetPdf } from "@/utils/pdfExport";
import { useCurrency } from "@/composables/useCurrency";

import Navbar from "@/components/budget-monitoring/Navbar.vue";
import Footer from "@/components/common/Footer.vue";
import ProjectHeader from "@/components/budget-monitoring/ProjectHeader.vue";
import BudgetSummaryCards from "@/components/budget-monitoring/BudgetSummaryCards.vue";
import BudgetBreakdownTable from "@/components/budget-monitoring/BudgetBreakdownTable.vue";

const { selectedCurrency, exchangeRates, formatCurrency } = useCurrency();

const isLoading = ref(true);
const errorMessage = ref("");
const sheetData = ref({
  metadata: {},
  budget_summary: {},
  budget_breakdown: [],
});

const fetchDashboardData = async () => {
  try {
    isLoading.value = true;
    errorMessage.value = "";

    const response = await budgetService.getDashboardData();
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

onMounted(fetchDashboardData);

const metadata = computed(() => ({
  projectName: sheetData.value?.metadata?.nama_proyek || "ABCDE PROJECT NAME",
  projectCode: sheetData.value?.metadata?.kode_proyek || "-",
  lastUpdate: sheetData.value?.metadata?.tanggal_update || "-",
  namaPimpro: sheetData.value?.metadata?.nama_pimpro || "-",
  namaClient: sheetData.value?.metadata?.nama_client || "-",
  netContractValue: Number(sheetData.value?.metadata?.net_contract_value || 0),
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
  <div class="flex min-h-screen flex-col bg-[#f8fafc] font-sans text-slate-800">
    <Navbar />
    <main class="mx-auto w-full max-w-7xl flex-1 space-y-6 px-6 py-8">
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
        <ProjectHeader
          :metadata="metadata"
          :estimasi-kebutuhan="summary.estimasi_kebutuhan"
          @download="handleDownloadPdf"
        />
        <BudgetSummaryCards :summary="summary" />
        <BudgetBreakdownTable :items="budgetList" :items-per-page="4" />
      </template>
    </main>
    <Footer />
  </div>
</template>
