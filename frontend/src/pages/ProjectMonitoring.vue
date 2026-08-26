<script setup>
import { computed } from "vue";
import jsPDF from "jspdf";
import autoTable from "jspdf-autotable";
import { useCurrency } from "@/composables/useCurrency";

import Navbar from "@/components/common/Navbar.vue";
import Footer from "@/components/common/Footer.vue";
import ProjectHeader from "@/components/budget/ProjectHeader.vue";
import BudgetSummaryCards from "@/components/budget/BudgetSummaryCards.vue";
import BudgetBreakdownTable from "@/components/budget/BudgetBreakdownTable.vue";

const props = defineProps({
  sheetData: {
    type: Object,
    default: () => ({
      metadata: {},
      budget_summary: {},
      budget_breakdown: [],
    }),
  },
  exchangeRates: {
    type: Object,
    default: () => ({}),
  },
});

const { selectedCurrency, exchangeRates, formatCurrency } = useCurrency();

// Update exchangeRates jika ada data dari controller
if (props.exchangeRates && Object.keys(props.exchangeRates).length > 0) {
  exchangeRates.value = { ...exchangeRates.value, ...props.exchangeRates };
}

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
    props.sheetData?.budget_summary || {
      sisa_anggaran_sap: 0,
      wbs_belum_input: 0,
      total: 0,
      estimasi_kebutuhan: 0,
      selisih_variance: 0,
    },
);

const budgetList = computed(() => props.sheetData?.budget_breakdown || []);

// Download PDF mengikuti selectedCurrency aktif
const handleDownloadPdf = () => {
  const doc = new jsPDF("landscape", "pt", "a4");
  const curr = selectedCurrency.value;
  const meta = metadata.value;
  const sum = summary.value;
  const items = budgetList.value;

  doc.setFont("helvetica", "bold");
  doc.setFontSize(16);
  doc.setTextColor(15, 23, 42);
  doc.text(`EXECUTIVE BUDGET MONITORING REPORT (${curr})`, 40, 45);

  doc.setDrawColor(0, 82, 204);
  doc.setLineWidth(1.5);
  doc.line(40, 52, 802, 52);

  doc.setFont("helvetica", "normal");
  doc.setFontSize(9.5);
  doc.setTextColor(71, 85, 105);
  doc.text(`Nama Proyek : ${meta.projectName} [${meta.projectCode}]`, 40, 70);
  doc.text(`Pimpro / Klien : ${meta.namaPimpro} | ${meta.namaClient}`, 40, 84);
  doc.text(`Net Contract : ${formatCurrency(meta.netContractValue)}`, 40, 98);
  doc.text(`Last Update  : ${meta.lastUpdate}`, 40, 84);
  doc.text(`Currency     : ${curr}`, 40, 98);
  doc.text(`Tgl Cetak    : ${new Date().toLocaleDateString("id-ID")}`, 650, 70);

  autoTable(doc, {
    startY: 112,
    head: [
      [
        `Sisa SAP (${curr})`,
        `WBS Belum Input (${curr})`,
        `Total (${curr})`,
        `Estimasi Kebutuhan (${curr})`,
        `Selisih (Variance)`,
      ],
    ],
    body: [
      [
        formatCurrency(sum.sisa_anggaran_sap),
        formatCurrency(sum.wbs_belum_input),
        formatCurrency(sum.total),
        formatCurrency(sum.estimasi_kebutuhan),
        (sum.selisih_variance >= 0 ? "+" : "") +
          formatCurrency(sum.selisih_variance),
      ],
    ],
    theme: "grid",
    headStyles: {
      fillColor: [0, 82, 204],
      textColor: 255,
      fontStyle: "bold",
      halign: "center",
    },
    bodyStyles: {
      halign: "center",
      fontStyle: "bold",
      textColor: [30, 41, 59],
    },
    styles: { fontSize: 9, cellPadding: 6 },
  });

  const tableBody = items.map((item) => [
    item.no,
    item.wbs_element,
    formatCurrency(item.sisa_anggaran_sap),
    formatCurrency(item.wbs_belum_input),
    formatCurrency(item.total),
    formatCurrency(item.estimasi_kebutuhan),
    (item.selisih_variance >= 0 ? "+" : "") +
      formatCurrency(item.selisih_variance),
  ]);

  autoTable(doc, {
    startY: doc.lastAutoTable.finalY + 20,
    head: [
      [
        "No.",
        "WBS Element",
        `Sisa SAP (${curr})`,
        `Belum Input (${curr})`,
        `Total (${curr})`,
        `Kebutuhan (${curr})`,
        `Variance`,
      ],
    ],
    body: tableBody,
    theme: "striped",
    headStyles: {
      fillColor: [30, 41, 59],
      textColor: 255,
      fontStyle: "bold",
    },
    columnStyles: {
      0: { halign: "center", cellWidth: 35 },
      1: { cellWidth: 170, fontStyle: "bold" },
      2: { halign: "right" },
      3: { halign: "right" },
      4: { halign: "right" },
      5: { halign: "right" },
      6: { halign: "right", fontStyle: "bold" },
    },
    styles: { fontSize: 8.5, cellPadding: 5 },
  });

  const safeProjectName = meta.projectName.replace(/[^a-zA-Z0-9_-]/g, "_");
  doc.save(
    `Budget_Report_${curr}_${safeProjectName}_${new Date().toISOString().slice(0, 10)}.pdf`,
  );
};
</script>

<template>
  <div class="flex min-h-screen flex-col bg-[#f8fafc] font-sans text-slate-800">
    <Navbar />
    <main class="mx-auto w-full max-w-7xl flex-1 space-y-6 px-6 py-8">
      <!-- Tidak butuh oper-oper props currency lagi! -->
      <ProjectHeader
        :metadata="metadata"
        :estimasi-kebutuhan="summary.estimasi_kebutuhan"
        @download="handleDownloadPdf"
      />
      <BudgetSummaryCards :summary="summary" />
      <BudgetBreakdownTable :items="budgetList" :items-per-page="4" />
    </main>
    <Footer />
  </div>
</template>
