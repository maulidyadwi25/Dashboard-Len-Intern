<script setup lang="ts">
import { computed, ref, watch } from "vue";
import {
  Check,
  FileText,
  LoaderCircle,
  Pencil,
  RefreshCw,
  X,
} from "lucide-vue-next";
import { wbsService } from "../../services/wbsService.js";

interface Recommendation {
  priority: string;
  title: string;
  detail: string;
}

interface SummaryContent {
  executive_summary: string;
  recommendations: Recommendation[];
  risks: string[];
}

interface AiSummary {
  content: SummaryContent;
  source: "ai" | "manual" | string;
  summary_date: string;
  generated_at: string | null;
}

interface SummaryError extends Error {
  status?: number;
  previousSummary?: AiSummary | null;
}

const props = withDefaults(
  defineProps<{
    projectId?: number | string | null;
  }>(),
  { projectId: null },
);

const summary = ref<AiSummary | null>(null);
const loading = ref(true);
const generating = ref(false);
const saving = ref(false);
const editing = ref(false);
const errorMessage = ref("");
const syncWarning = ref("");
const confirmManualGenerate = ref(false);
const executiveSummaryDraft = ref("");
const recommendationsDraft = ref("");
const risksDraft = ref("");
const priorityLabels: Record<string, string> = {
  high: "Tinggi",
  medium: "Sedang",
  low: "Rendah",
};
const priorityClasses: Record<string, string> = {
  high: "bg-error-container text-error",
  medium: "bg-amber-100 text-amber-900",
  low: "bg-surface-container text-on-surface-variant",
};

const updatedAt = computed(() => {
  if (!summary.value) return "";
  const rawDate =
    summary.value.generated_at ?? `${summary.value.summary_date}T00:00:00`;
  const date = new Date(rawDate);
  return Number.isNaN(date.getTime())
    ? summary.value.summary_date
    : date.toLocaleString("id-ID", {
        dateStyle: "medium",
        timeStyle: "short",
      });
});

function errorMessageFor(error: unknown): string {
  const requestError = error as SummaryError;
  if (requestError.status === 404) return "Project WBS tidak ditemukan.";
  if (requestError.status === 409) {
    return "Ringkasan AI sedang diproses. Coba lagi sebentar.";
  }
  if (requestError.status === 429) {
    return "Permintaan terlalu sering. Tunggu sebentar sebelum mencoba lagi.";
  }
  if (requestError.status === 502) {
    return "AI gagal membuat ringkasan. Ringkasan lama tetap ditampilkan.";
  }
  return requestError.message || "Terjadi kesalahan saat memuat ringkasan WBS.";
}

async function loadSummary(projectId: number | string): Promise<void> {
  loading.value = true;
  errorMessage.value = "";

  try {
    const response = await wbsService.getAiSummary(projectId);
    summary.value = response.data?.data ?? null;
  } catch (error) {
    errorMessage.value = errorMessageFor(error);
  } finally {
    loading.value = false;
  }
}

function populateDrafts(content: SummaryContent): void {
  executiveSummaryDraft.value = content.executive_summary ?? "";
  recommendationsDraft.value = (content.recommendations ?? [])
    .map(
      (recommendation) =>
        `${recommendation.priority} | ${recommendation.title} | ${recommendation.detail}`,
    )
    .join("\n");
  risksDraft.value = (content.risks ?? []).join("\n");
}

function beginEdit(): void {
  if (!summary.value) return;
  populateDrafts(summary.value.content);
  errorMessage.value = "";
  editing.value = true;
}

function cancelEdit(): void {
  if (summary.value) populateDrafts(summary.value.content);
  editing.value = false;
  errorMessage.value = "";
}

function parseRecommendations(): Recommendation[] | null {
  const lines = recommendationsDraft.value
    .split("\n")
    .map((line) => line.trim())
    .filter(Boolean);
  const recommendations: Recommendation[] = [];

  for (const line of lines) {
    const [priority, title, ...detailParts] = line
      .split("|")
      .map((part) => part.trim());
    const detail = detailParts.join(" | ");
    if (!priority || !title || !detail) return null;
    recommendations.push({ priority, title, detail });
  }

  return recommendations;
}

async function saveEdit(): Promise<void> {
  errorMessage.value = "";
  const recommendations = parseRecommendations();
  if (recommendations === null) {
    errorMessage.value =
      "Setiap rekomendasi harus berisi prioritas, judul, dan detail.";
    return;
  }

  const content: SummaryContent = {
    executive_summary: executiveSummaryDraft.value.trim(),
    recommendations,
    risks: risksDraft.value
      .split("\n")
      .map((risk) => risk.trim())
      .filter(Boolean),
  };

  saving.value = true;
  try {
    const response = await wbsService.saveAiSummary(props.projectId, content);
    summary.value = response.data?.data ?? null;
    editing.value = false;
    syncWarning.value = "";
  } catch (error) {
    errorMessage.value = errorMessageFor(error);
  } finally {
    saving.value = false;
  }
}

function requestGenerate(): void {
  errorMessage.value = "";
  if (summary.value?.source === "manual") {
    confirmManualGenerate.value = true;
    return;
  }
  void generateSummary();
}

async function generateSummary(): Promise<void> {
  confirmManualGenerate.value = false;
  generating.value = true;
  errorMessage.value = "";
  syncWarning.value = "";

  try {
    const response = await wbsService.generateAiSummary(props.projectId);
    const result = response.data?.data;
    if (result) {
      summary.value = result;
      syncWarning.value = result.sync_warning ?? "";
    }
    editing.value = false;
  } catch (error) {
    const requestError = error as SummaryError;
    errorMessage.value = errorMessageFor(error);
    if (requestError.previousSummary) {
      summary.value = requestError.previousSummary;
    }
  } finally {
    generating.value = false;
  }
}

watch(
  () => props.projectId,
  (projectId) => {
    summary.value = null;
    errorMessage.value = "";
    syncWarning.value = "";
    if (projectId === null || projectId === undefined) {
      loading.value = false;
      return;
    }
    void loadSummary(projectId);
  },
  { immediate: true },
);
</script>

<template>
  <section
    aria-label="Ringkasan dan rekomendasi AI"
    class="flex min-h-[30rem] max-h-[70vh] flex-col overflow-hidden rounded-xl border-2 border-outline-variant bg-surface-lowest shadow-sm"
  >
    <header
      class="flex flex-wrap items-center justify-between gap-3 border-b border-outline-variant bg-primary-50 px-4 py-3 text-on-surface"
    >
      <div>
        <h3 class="text-title-md">Ringkasan &amp; Rekomendasi AI</h3>
      </div>

      <div v-if="!editing" class="flex items-center gap-2">
        <button
          type="button"
          class="inline-flex h-8 items-center gap-2 rounded border border-primary bg-primary px-3 text-sm font-semibold text-on-primary hover:bg-primary-600 disabled:cursor-wait disabled:opacity-50"
          :disabled="generating || loading"
          aria-label="Generate ulang ringkasan AI"
          title="Generate ulang"
          @click="requestGenerate"
        >
          <LoaderCircle v-if="generating" class="h-4 w-4 animate-spin" />
          <RefreshCw v-else class="h-4 w-4" />
          Generate ulang
        </button>
        <button
          v-if="summary"
          type="button"
          class="inline-flex h-8 items-center gap-2 rounded border border-outline-variant bg-surface-lowest px-3 text-sm font-medium text-on-surface-variant hover:bg-surface disabled:cursor-not-allowed disabled:opacity-50"
          :disabled="generating || loading"
          aria-label="Edit ringkasan AI"
          title="Edit ringkasan"
          @click="beginEdit"
        >
          <Pencil class="h-4 w-4" />
          Edit
        </button>
      </div>

      <div v-else class="flex items-center gap-2">
        <button
          type="button"
          class="inline-flex h-8 w-8 items-center justify-center rounded bg-primary text-on-primary hover:bg-primary-600 disabled:opacity-50"
          :disabled="saving"
          aria-label="Simpan ringkasan manual"
          title="Simpan"
          @click="saveEdit"
        >
          <LoaderCircle v-if="saving" class="h-4 w-4 animate-spin" />
          <Check v-else class="h-4 w-4" />
        </button>
        <button
          type="button"
          class="inline-flex h-8 w-8 items-center justify-center rounded border border-outline-variant text-on-surface-variant hover:bg-surface disabled:opacity-50"
          :disabled="saving"
          aria-label="Batalkan edit ringkasan"
          title="Batal"
          @click="cancelEdit"
        >
          <X class="h-4 w-4" />
        </button>
      </div>
    </header>

    <div class="min-h-0 flex-1 space-y-4 overflow-y-auto p-4">
      <p
        v-if="errorMessage"
        role="alert"
        class="rounded border border-error bg-error-container px-3 py-2 text-body-sm text-error"
      >
        {{ errorMessage }}
        <span v-if="errorMessage.includes('AI gagal') && summary">
          Diperbarui: {{ updatedAt }}.
        </span>
      </p>
      <p
        v-if="syncWarning"
        role="status"
        class="rounded border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-950"
      >
        {{ syncWarning }}
      </p>
      <p
        v-if="generating"
        role="status"
        class="text-sm text-on-surface-variant"
      >
        <LoaderCircle class="mr-2 inline h-4 w-4 animate-spin" />
        Menyinkronkan data dan membuat rekomendasi...
      </p>
      <p
        v-if="loading"
        role="status"
        class="inline-flex items-center gap-2 text-sm text-on-surface-variant"
      >
        <LoaderCircle class="h-4 w-4 animate-spin text-primary" />
        Memuat ringkasan...
      </p>
      <p
        v-else-if="!summary && !editing"
        class="text-sm text-on-surface-variant"
      >
        Belum ada ringkasan
      </p>

      <template v-if="editing">
        <div class="space-y-1.5">
          <label
            for="wbs-ai-executive"
            class="text-sm font-medium text-on-surface-variant"
            >Ringkasan</label
          >
          <textarea
            id="wbs-ai-executive"
            v-model="executiveSummaryDraft"
            rows="4"
            class="w-full rounded border border-outline-variant bg-surface-lowest px-3 py-2 text-body-base text-on-surface outline-none focus:border-primary focus:ring-1 focus:ring-primary/20"
          />
        </div>
        <div class="space-y-1.5">
          <label
            for="wbs-ai-recommendations"
            class="text-sm font-medium text-on-surface-variant"
            >Rekomendasi</label
          >
          <textarea
            id="wbs-ai-recommendations"
            v-model="recommendationsDraft"
            rows="5"
            class="w-full rounded border border-outline-variant bg-surface-lowest px-3 py-2 text-body-base text-on-surface outline-none focus:border-primary focus:ring-1 focus:ring-primary/20"
          />
        </div>
        <div class="space-y-1.5">
          <label
            for="wbs-ai-risks"
            class="text-sm font-medium text-on-surface-variant"
            >Risiko</label
          >
          <textarea
            id="wbs-ai-risks"
            v-model="risksDraft"
            rows="4"
            class="w-full rounded border border-outline-variant bg-surface-lowest px-3 py-2 text-body-base text-on-surface outline-none focus:border-primary focus:ring-1 focus:ring-primary/20"
          />
        </div>
      </template>

      <template v-else-if="summary">
        <section>
          <h4
            class="flex items-center gap-2 text-xs font-semibold uppercase text-on-surface-variant"
          >
            <FileText class="h-4 w-4 text-primary" />
            Ringkasan Eksekutif
          </h4>
          <p class="mt-2 whitespace-pre-wrap text-sm leading-6 text-on-surface">
            {{ summary.content.executive_summary }}
          </p>
        </section>
        <section>
          <h4 class="text-xs font-semibold uppercase text-on-surface-variant">
            Rekomendasi Tindakan
          </h4>
          <ol
            v-if="summary.content.recommendations.length"
            class="mt-2 list-decimal space-y-2 pl-5 marker:font-semibold marker:text-primary"
          >
            <li
              v-for="(recommendation, index) in summary.content.recommendations"
              :key="`${recommendation.title}-${index}`"
              class="rounded-2xl border-2 border-outline-variant bg-surface-lowest p-3"
            >
              <p class="text-sm font-semibold text-on-surface">
                <span
                  class="mr-2 rounded px-1.5 py-0.5 text-xs font-semibold"
                  :class="
                    priorityClasses[recommendation.priority] ??
                    'bg-surface-container text-on-surface-variant'
                  "
                  >{{
                    priorityLabels[recommendation.priority] ??
                    recommendation.priority
                  }}</span
                >
                {{ recommendation.title }}
              </p>
              <p class="mt-1 text-sm leading-5 text-on-surface-variant">
                {{ recommendation.detail }}
              </p>
            </li>
          </ol>
          <p v-else class="mt-1 text-sm text-on-surface-variant">
            Tidak ada rekomendasi.
          </p>
        </section>
        <section>
          <h4 class="text-xs font-semibold uppercase text-on-surface-variant">
            Risiko
          </h4>
          <ul
            v-if="summary.content.risks.length"
            class="mt-2 list-disc space-y-1 pl-5 text-sm text-on-surface"
          >
            <li v-for="(risk, index) in summary.content.risks" :key="index">
              {{ risk }}
            </li>
          </ul>
          <p v-else class="mt-1 text-sm text-on-surface-variant">
            Tidak ada risiko tercatat.
          </p>
        </section>
      </template>
    </div>

    <footer
      v-if="summary"
      class="flex flex-wrap items-center justify-between gap-2 border-t border-outline-variant bg-surface-lowest px-4 py-2 text-body-sm text-on-surface-variant"
    >
      <span
        class="rounded bg-surface-container px-2 py-1 font-medium text-on-surface-variant"
      >
        {{ summary.source === "ai" ? "AI" : "Manual" }}
      </span>
      <span>Diperbarui: {{ updatedAt }}</span>
    </footer>

    <div
      v-if="confirmManualGenerate"
      class="fixed inset-0 z-[110] flex items-center justify-center bg-on-surface/40 p-4"
      role="alertdialog"
      aria-modal="true"
      aria-labelledby="wbs-ai-confirm-title"
    >
      <section
        class="w-full max-w-md rounded-xl border border-outline-variant bg-surface-lowest p-5 shadow-xl"
      >
        <h4
          id="wbs-ai-confirm-title"
          class="text-base font-semibold text-on-surface"
        >
          Generate ulang akan menimpa koreksi manual. Lanjutkan?
        </h4>
        <div class="mt-5 flex justify-end gap-2">
          <button
            type="button"
            class="h-9 rounded border border-outline-variant px-4 text-body-sm font-medium text-on-surface-variant hover:bg-surface"
            @click="confirmManualGenerate = false"
          >
            Batal
          </button>
          <button
            type="button"
            class="inline-flex h-9 items-center gap-2 rounded bg-primary px-4 text-body-sm font-medium text-on-primary hover:bg-primary-600"
            @click="generateSummary"
          >
            <RefreshCw class="h-4 w-4" />
            Generate ulang
          </button>
        </div>
      </section>
    </div>
  </section>
</template>
