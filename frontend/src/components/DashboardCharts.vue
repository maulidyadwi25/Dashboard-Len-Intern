<!-- src/components/DashboardCharts.vue -->
<script setup>
import { onMounted, ref, watch } from "vue";
import Chart from "chart.js/auto";

const props = defineProps({
  summary: {
    type: Object,
    default: () => ({
      sisa_anggaran_sap: 0,
      wbs_belum_input: 0,
      total: 0,
      estimasi_kebutuhan: 0,
      selisih_variance: 0,
    }),
  },
});

const chartCanvas = ref(null);
let chartInstance = null;

const renderChart = () => {
  if (!chartCanvas.value) return;
  if (chartInstance) chartInstance.destroy();

  const ctx = chartCanvas.value.getContext("2d");
  chartInstance = new Chart(ctx, {
    type: "bar",
    data: {
      labels: [
        "Sisa SAP",
        "WBS Belum Input",
        "Total Anggaran",
        "Estimasi Kebutuhan",
      ],
      datasets: [
        {
          label: "Nilai Anggaran",
          data: [
            props.summary.sisa_anggaran_sap || 0,
            props.summary.wbs_belum_input || 0,
            props.summary.total || 0,
            props.summary.estimasi_kebutuhan || 0,
          ],
          backgroundColor: ["#3b82f6", "#f59e0b", "#10b981", "#6366f1"],
          borderRadius: 6,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: (ctx) => ` Rp ${ctx.raw.toLocaleString("id-ID")}`,
          },
        },
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: (val) => `Rp ${val / 1e6}Jt`,
          },
        },
      },
    },
  });
};

onMounted(renderChart);
watch(() => props.summary, renderChart, { deep: true });
</script>

<template>
  <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <h3 class="mb-4 text-base font-bold text-slate-800">
      Ringkasan Grafik Anggaran Proyek
    </h3>
    <div class="h-72 w-full">
      <canvas ref="chartCanvas"></canvas>
    </div>
  </div>
</template>
