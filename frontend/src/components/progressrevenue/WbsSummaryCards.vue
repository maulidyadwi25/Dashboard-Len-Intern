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
  { label: "On Track", className: "bg-emerald-700" },
  { label: "Overdue", className: "bg-yellow-500" },
  { label: "Critical", className: "bg-red-700" },
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
    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 xl:grid-cols-3">
      <article class="rounded-md border border-slate-200 bg-white p-3">
        <p
          class="flex items-center justify-between text-xs font-medium text-slate-600"
        >
          On Track
          <span class="h-2.5 w-2.5 shrink-0 rounded-full bg-emerald-700" />
        </p>
        <p class="mt-1 text-xl font-semibold tabular-nums text-emerald-700">
          {{ summary.total_on_track }} WBS
        </p>
      </article>
      <article class="rounded-md border border-slate-200 bg-white p-3">
        <p
          class="flex items-center justify-between text-xs font-medium text-slate-600"
        >
          Overdue
          <span class="h-2.5 w-2.5 shrink-0 rounded-full bg-yellow-500" />
        </p>
        <p class="mt-1 text-xl font-semibold tabular-nums text-yellow-600">
          {{ summary.total_overdue }} WBS
        </p>
      </article>
      <article class="rounded-md border border-slate-200 bg-white p-3">
        <p
          class="flex items-center justify-between text-xs font-medium text-slate-600"
        >
          Critical
          <span class="h-2.5 w-2.5 shrink-0 rounded-full bg-red-700" />
        </p>
        <p class="mt-1 text-xl font-semibold tabular-nums text-red-700">
          {{ summary.total_critical }} WBS
        </p>
      </article>
    </div>

    <div class="space-y-3 rounded-md bg-slate-100 p-3">
      <h3
        class="flex items-start gap-2 text-xs font-bold uppercase text-slate-800"
      >
        <Info class="mt-0.5 h-3.5 w-3.5 shrink-0 text-blue-700" />
        <span
          >Keterangan Indikator &amp; Legenda Gantt (Konsolidasi Plan vs
          Aktual)</span
        >
      </h3>

      <div
        class="flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-slate-600"
      >
        <span
          v-for="bar in bars"
          :key="bar.label"
          class="inline-flex items-center gap-2"
        >
          <span class="h-2 w-4 shrink-0 rounded-sm" :class="bar.className" />
          {{ bar.label }}
        </span>
        <span aria-hidden="true" class="h-5 border-l border-slate-300" />
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
          class="ml-auto flex shrink-0 items-center gap-2 border-l border-slate-300 pl-3"
        >
          <button
            v-if="!editing"
            type="button"
            class="inline-flex h-9 items-center justify-center gap-2 rounded-md bg-amber-500 px-3 text-sm font-medium text-white transition hover:bg-amber-600 disabled:cursor-not-allowed disabled:opacity-50"
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
            class="inline-flex h-9 w-9 items-center justify-center rounded-md bg-blue-700 text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-50"
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
