<script setup lang="ts">
import { Download, Info, Pencil } from "lucide-vue-next";

interface WbsSummary {
  total_on_track: number;
  total_overdue: number;
  total_critical: number;
}

defineProps<{
  summary: WbsSummary;
  editing: boolean;
  canDownload: boolean;
}>();

const emit = defineEmits<{
  "toggle-edit": [];
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
  <section aria-label="Ringkasan WBS" class="space-y-3">
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
      <article
        class="rounded-2xl border-2 border-outline-variant bg-surface-lowest p-3 shadow-sm"
      >
        <p
          class="flex items-center justify-between text-xs font-medium text-on-surface-variant"
        >
          On Track
          <span class="h-2.5 w-2.5 shrink-0 rounded-full bg-emerald-500" />
        </p>
        <p class="mt-1 text-xl font-semibold tabular-nums text-emerald-700">
          {{ summary.total_on_track }} WBS
        </p>
      </article>
      <article
        class="rounded-2xl border-2 border-outline-variant bg-surface-lowest p-3 shadow-sm"
      >
        <p
          class="flex items-center justify-between text-xs font-medium text-on-surface-variant"
        >
          Overdue
          <span class="h-2.5 w-2.5 shrink-0 rounded-full bg-yellow-500" />
        </p>
        <p class="mt-1 text-xl font-semibold tabular-nums text-amber-800">
          {{ summary.total_overdue }} WBS
        </p>
      </article>
      <article
        class="rounded-2xl border-2 border-outline-variant bg-surface-lowest p-3 shadow-sm"
      >
        <p
          class="flex items-center justify-between text-xs font-medium text-on-surface-variant"
        >
          Critical
          <span class="h-2.5 w-2.5 shrink-0 rounded-full bg-error" />
        </p>
        <p class="mt-1 text-xl font-semibold tabular-nums text-error">
          {{ summary.total_critical }} WBS
        </p>
      </article>
    </div>

    <div
      class="space-y-3 rounded-2xl border-2 border-outline-variant bg-surface-container p-3"
    >
      <h3
        class="flex items-start gap-2 text-label-caps text-on-surface uppercase"
      >
        <Info class="mt-0.5 h-3.5 w-3.5 shrink-0 text-primary" />
        <span
          >Keterangan Indikator &amp; Legenda Gantt (Konsolidasi Plan vs
          Aktual)</span
        >
      </h3>

      <div
        class="flex flex-wrap items-center gap-x-5 gap-y-2 text-body-sm text-on-surface-variant"
      >
        <span
          v-for="bar in bars"
          :key="bar.label"
          class="inline-flex items-center gap-2"
        >
          <span class="h-2 w-4 shrink-0 rounded-sm" :class="bar.className" />
          {{ bar.label }}
        </span>
        <span aria-hidden="true" class="h-5 border-l border-outline-variant" />
        <span
          v-for="legend in legends"
          :key="legend.label"
          class="inline-flex items-center gap-2"
        >
          <span
            class="h-2.5 w-2.5 shrink-0 rounded-full"
            :class="legend.className"
          />
          {{ legend.label }}
        </span>
        <div
          class="ml-auto flex shrink-0 items-center gap-2 border-l border-outline-variant pl-3"
        >
          <button
            v-if="!editing"
            type="button"
            class="inline-flex h-9 items-center justify-center gap-2 rounded-lg bg-primary px-3 text-body-sm font-medium text-on-primary transition hover:bg-primary-600 disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="!canDownload"
            aria-label="Edit progres WBS"
            title="Edit progres WBS"
            @click="emit('toggle-edit')"
          >
            <Pencil class="h-4 w-4" />
            Edit
          </button>
          <button
            type="button"
            class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-primary text-on-primary transition hover:bg-primary-600 disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="!canDownload"
            aria-label="Download data WBS sebagai PDF"
            title="Download PDF"
            @click="emit('download')"
          >
            <Download class="h-4 w-4" />
          </button>
        </div>
      </div>
    </div>
  </section>
</template>
