<script setup lang="ts">
import { ref } from "vue";
import { RefreshCw } from "lucide-vue-next";

type MonthFilter = number | "all";

const props = defineProps<{
  selectedMonth: MonthFilter;
  syncing: boolean;
  syncAction: () => Promise<boolean>;
}>();

const emit = defineEmits<{
  "update:selectedMonth": [value: MonthFilter];
}>();

const syncMessage = ref("");
const months = [
  "Januari",
  "Februari",
  "Maret",
  "April",
  "Mei",
  "Juni",
  "Juli",
  "Agustus",
  "September",
  "Oktober",
  "November",
  "Desember",
];

function updateMonth(event: Event): void {
  const value = (event.target as HTMLSelectElement).value;
  emit("update:selectedMonth", value === "all" ? "all" : Number(value));
}

async function runSync(): Promise<void> {
  syncMessage.value = "";
  const succeeded = await props.syncAction();
  syncMessage.value = succeeded
    ? "Sinkronisasi WBS berhasil."
    : "Sinkronisasi WBS gagal.";
}
</script>

<template>
  <section
    class="flex flex-wrap items-center justify-between gap-3 border-b border-outline-variant pb-4"
    aria-label="WBS controls"
  >
    <label
      class="hidden items-center gap-2 text-body-sm font-medium text-on-surface-variant"
    >
      Bulan
      <select
        :value="selectedMonth"
        class="h-9 min-w-36 rounded-lg border border-outline-variant bg-surface-lowest px-3 text-body-sm text-on-surface outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
        @change="updateMonth"
      >
        <option value="all">Semua bulan</option>
        <option
          v-for="(month, index) in months"
          :key="month"
          :value="index + 1"
        >
          {{ month }}
        </option>
      </select>
    </label>

    <div class="flex min-h-9 items-center gap-3">
      <p
        v-if="syncMessage"
        role="status"
        class="text-body-sm text-on-surface-variant"
      >
        {{ syncMessage }}
      </p>
      <button
        type="button"
        class="inline-flex h-9 items-center gap-2 rounded-lg bg-primary px-3 text-body-sm font-semibold text-on-primary transition hover:bg-primary-600 disabled:cursor-wait disabled:opacity-60"
        :disabled="syncing"
        @click="runSync"
      >
        <RefreshCw class="h-4 w-4" :class="syncing && 'animate-spin'" />
        {{ syncing ? "Menyinkronkan..." : "Sync WBS" }}
      </button>
    </div>
  </section>
</template>
