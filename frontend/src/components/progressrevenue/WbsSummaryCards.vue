<script setup lang="ts">
import { Download, Info, Pencil } from "lucide-vue-next";

interface WbsSummary {
  total_on_track: number;
  total_overdue: number;
  total_critical: number;
}

defineProps<{
  summary: WbsSummary;
  canDownload: boolean;
}>();

const emit = defineEmits<{
  download: [];
}>();

const legends = [
  { label: "On Track", className: "bg-emerald-500" },
  { label: "Overdue", className: "bg-yellow-500" },
  { label: "Critical", className: "bg-error" },
];

const bars = [
  {
    label: "Bar Atas: Rencana (Baseline Plan)",
    className: "bg-blue-200",
  },
  { label: "Bar Bawah: Realisasi Aktual", className: "bg-blue-600" },
  {
    label: "Bar Merah: Overdue lebih dari satu minggu",
    className: "bg-red-700",
  },
];
</script>

<template>
  <section aria-label="Ringkasan WBS" class="flex flex-col gap-4">
    <!-- Header (Tanpa background pembungkus & tanpa border luar) -->
    <header class="flex items-center justify-between px-1">
      <h2 class="text-title-md font-semibold text-on-surface">Ringkasan WBS</h2>
    </header>

    <!-- Grid Ringkasan (Card kecil yang memiliki background & border) -->
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
      <article
        class="rounded-2xl border border-outline-variant/60 bg-surface p-4 shadow-sm transition-all hover:border-primary/40 hover:shadow-md"
      >
        <p
          class="flex items-center justify-between text-xs font-medium text-on-surface-variant"
        >
          On Track
          <span
            class="h-2.5 w-2.5 shrink-0 rounded-full bg-emerald-500 shadow-xs shadow-emerald-500/20"
          />
        </p>
        <p class="mt-1.5 text-2xl font-bold tabular-nums text-emerald-700">
          {{ summary.total_on_track }}
          <span class="text-xs font-semibold text-on-surface-variant">WBS</span>
        </p>
      </article>

      <article
        class="rounded-2xl border border-outline-variant/60 bg-surface p-4 shadow-sm transition-all hover:border-primary/40 hover:shadow-md"
      >
        <p
          class="flex items-center justify-between text-xs font-medium text-on-surface-variant"
        >
          Overdue
          <span
            class="h-2.5 w-2.5 shrink-0 rounded-full bg-yellow-500 shadow-xs shadow-yellow-500/20"
          />
        </p>
        <p class="mt-1.5 text-2xl font-bold tabular-nums text-amber-700">
          {{ summary.total_overdue }}
          <span class="text-xs font-semibold text-on-surface-variant">WBS</span>
        </p>
      </article>

      <article
        class="rounded-2xl border border-outline-variant/60 bg-surface p-4 shadow-sm transition-all hover:border-primary/40 hover:shadow-md"
      >
        <p
          class="flex items-center justify-between text-xs font-medium text-on-surface-variant"
        >
          Critical
          <span
            class="h-2.5 w-2.5 shrink-0 rounded-full bg-rose-500 shadow-xs shadow-rose-500/20"
          />
        </p>
        <p class="mt-1.5 text-2xl font-bold tabular-nums text-rose-600">
          {{ summary.total_critical }}
          <span class="text-xs font-semibold text-on-surface-variant">WBS</span>
        </p>
      </article>
    </div>

    <!-- Keterangan & Legenda (Card bawah yang memiliki background & border) -->
    <div
      class="rounded-2xl border border-outline-variant/60 bg-surface p-4 shadow-sm"
    >
      <div
        class="flex flex-col lg:flex-row lg:items-center justify-between gap-4"
      >
        <!-- Bagian Keterangan & Legenda -->
        <div class="space-y-3 flex-1">
          <h3
            class="flex items-center gap-2 text-xs font-bold text-on-surface uppercase tracking-wider"
          >
            <Info class="h-4 w-4 shrink-0 text-primary" />
            <span
              >Keterangan Indikator &amp; Legenda Gantt (Konsolidasi Plan vs
              Aktual)</span
            >
          </h3>

          <div
            class="flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-on-surface-variant"
          >
            <span
              v-for="bar in bars"
              :key="bar.label"
              class="inline-flex items-center gap-2"
            >
              <span
                class="h-2 w-4 shrink-0 rounded-xs"
                :class="bar.className"
              />
              {{ bar.label }}
            </span>
            <span
              aria-hidden="true"
              class="h-4 border-l border-outline-variant/40"
            />
            <span
              v-for="legend in legends"
              :key="legend.label"
              class="inline-flex items-center gap-2"
            >
              <span
                class="h-2 w-2 shrink-0 rounded-full"
                :class="legend.className"
              />
              {{ legend.label }}
            </span>
          </div>
        </div>

        <!-- Tombol Aksi -->
        <div
          class="flex shrink-0 items-center gap-2 pt-2 lg:pt-0 lg:border-l lg:border-outline-variant/40 lg:pl-4"
        >
          <button
            type="button"
            class="inline-flex h-9 items-center gap-2 rounded-xl bg-primary px-3.5 text-xs font-medium text-on-primary transition hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-50 shadow-xs"
            :disabled="!canDownload"
            @click="emit('download')"
          >
            <Download class="h-3.5 w-3.5" />
            Download Gantt
          </button>
        </div>
      </div>
    </div>
  </section>
</template>
