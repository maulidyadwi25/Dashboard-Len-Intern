<template>
  <section class="bg-white rounded-2xl border border-outline-variant shadow-xs p-5 md:p-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div class="flex items-start gap-3.5">
        <span class="shrink-0 w-11 h-11 rounded-xl bg-primary-500 text-white flex items-center justify-center shadow-xs">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
        </span>
        <div>
          <div class="flex flex-wrap items-center gap-2.5">
            <span class="text-label-caps font-bold tracking-wider text-slate-500">PROCUREMENT INTELLIGENCE</span>
            <span class="px-2 py-0.5 rounded-full bg-blue-50 text-primary-500 border border-primary-400/40 text-[10px] font-bold tracking-wider">AI ENABLED</span>
            <span v-if="docSource && !dirty" class="px-2 py-0.5 rounded-full border text-[10px] font-bold tracking-wider" :class="sourceBadgeClass">{{ sourceBadgeLabel }}</span>
            <span v-if="dirty" class="px-2 py-0.5 rounded-full bg-amber-50 text-amber-600 border border-amber-300 text-[10px] font-bold tracking-wider">BELUM DISIMPAN</span>
            <span v-if="generatedAt" class="text-[11px] text-slate-400 whitespace-nowrap">{{ formatTime(generatedAt) }}</span>
          </div>
          <h2 class="text-lg md:text-xl font-bold text-on-surface mt-1">Saran &amp; Strategi Rekomendasi AI (Procurement Intelligence)</h2>
        </div>
      </div>

      <div class="flex flex-wrap items-center gap-2 shrink-0">
        <template v-if="mode === 'edit'">
          <button
            @click="addManual"
            class="flex items-center gap-1.5 px-3 py-2.5 rounded-lg border border-outline-variant bg-white hover:bg-background text-body-sm font-semibold text-on-surface transition-colors cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Tambah Kartu
          </button>
          <button
            @click="cancelEdit"
            :disabled="saving"
            class="px-3 py-2.5 rounded-lg border border-outline-variant bg-white hover:bg-background text-body-sm font-semibold text-on-surface transition-colors cursor-pointer disabled:opacity-60"
          >
            Batal
          </button>
          <button
            @click="save"
            :disabled="saving || (!summary && !recommendations.length)"
            class="flex items-center gap-2 px-4 py-2.5 bg-primary-500 hover:bg-primary-600 disabled:opacity-60 text-white rounded-lg text-body-sm font-semibold transition-colors cursor-pointer shadow-xs"
          >
            <svg v-if="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
            {{ saving ? 'Menyimpan...' : 'Simpan Hasil' }}
          </button>
        </template>

        <template v-else>
          <button
            v-if="dirty"
            @click="save"
            :disabled="saving"
            class="flex items-center gap-2 px-4 py-2.5 bg-primary-500 hover:bg-primary-600 disabled:opacity-60 text-white rounded-lg text-body-sm font-semibold transition-colors cursor-pointer shadow-xs"
          >
            <svg v-if="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
            {{ saving ? 'Menyimpan...' : 'Simpan Hasil' }}
          </button>

          <button
            v-if="hasData"
            @click="enterEdit"
            class="flex items-center gap-1.5 px-3 py-2.5 rounded-lg border border-outline-variant bg-white hover:bg-background text-body-sm font-semibold text-on-surface transition-colors cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
            Edit
          </button>

          <button
            v-else
            @click="startManual"
            class="flex items-center gap-1.5 px-3 py-2.5 rounded-lg border border-outline-variant bg-white hover:bg-background text-body-sm font-semibold text-on-surface transition-colors cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
            Input Manual
          </button>

          <button
            @click="generate"
            :disabled="generating"
            class="flex items-center gap-2 px-4 py-2.5 bg-primary-500 hover:bg-primary-600 disabled:opacity-60 text-white rounded-lg text-body-sm font-semibold transition-colors cursor-pointer shadow-xs"
          >
            <svg v-if="generating" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
            {{ generating ? 'Menganalisis Data...' : 'Generate Recommendation by AI' }}
          </button>
        </template>
      </div>
    </div>

    <!-- Error -->
    <div v-if="error && !generating && !loadingSaved" class="mt-4 rounded-xl border border-error/30 bg-error-container/40 px-4 py-3 text-body-sm text-error">
      {{ error }}
    </div>

    <!-- Initial Loading -->
    <div v-if="loadingSaved" class="mt-5 rounded-xl border border-outline-variant bg-background p-4 animate-pulse space-y-3">
      <div class="h-3 bg-slate-300 rounded w-1/4"></div>
      <div class="h-4 bg-slate-300 rounded w-3/4"></div>
    </div>

    <!-- Generating Skeleton -->
    <div v-else-if="generating" class="mt-5 space-y-4">
      <div class="rounded-xl border border-outline-variant bg-background p-4 animate-pulse space-y-3">
        <div class="h-3 bg-slate-300 rounded w-1/4"></div>
        <div class="h-4 bg-slate-300 rounded w-3/4"></div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div v-for="n in 3" :key="n" class="rounded-xl border border-outline-variant bg-background p-4 animate-pulse space-y-3">
          <div class="h-3 bg-slate-300 rounded w-1/2"></div>
          <div class="h-4 bg-slate-300 rounded w-4/5"></div>
          <div class="h-3 bg-slate-300 rounded w-full"></div>
          <div class="h-3 bg-slate-300 rounded w-3/4"></div>
        </div>
      </div>
    </div>

    <template v-else>
      <!-- Summary -->
      <div v-if="mode === 'edit'" class="mt-5 rounded-xl border border-primary-400/30 bg-primary-50/50 p-4">
        <label class="block text-label-caps font-bold tracking-wider text-primary-600 mb-2">RINGKASAN ANALISIS (BISA DIEDIT)</label>
        <textarea
          v-model="summary"
          @input="dirty = true"
          rows="3"
          class="w-full rounded-lg border border-outline-variant bg-white px-3 py-2 text-body-sm text-on-surface leading-relaxed focus:outline-none focus:ring-2 focus:ring-primary-400/50"
          placeholder="Tulis ringkasan analisis untuk paket ini..."
        ></textarea>
      </div>
      <div v-else-if="summary" class="mt-5 rounded-xl border border-primary-400/30 bg-primary-50/50 p-4">
        <div class="flex items-center gap-2 mb-2">
          <span class="text-label-caps font-bold tracking-wider text-primary-600">RINGKASAN ANALISIS</span>
        </div>
        <p class="text-body-sm text-on-surface leading-relaxed">{{ summary }}</p>
      </div>

      <!-- Empty State -->
      <div v-if="!hasData && !dirty" class="mt-5 rounded-xl border border-dashed border-outline-variant bg-background px-4 py-8 text-center">
        <p class="text-body-sm text-slate-500">
          Klik <span class="font-semibold text-on-surface">Generate Recommendation by AI</span> untuk menganalisis data paket ini (efisiensi, termin, delivery, payment), atau <span class="font-semibold text-on-surface">Input Manual</span> untuk menulis rekomendasi sendiri.
        </p>
      </div>

      <!-- Edit Mode: Cards -->
      <div v-if="mode === 'edit'" class="mt-5 grid grid-cols-1 md:grid-cols-3 gap-4">
        <article v-for="(rec, i) in recommendations" :key="'e' + i" class="rounded-xl border border-primary-400/40 bg-background p-4 flex flex-col gap-2.5">
          <div class="flex items-center justify-between gap-2">
            <span class="px-2 py-0.5 rounded-full border text-[10px] font-bold tracking-wider" :class="rec.source === 'manual' ? 'bg-slate-100 text-slate-600 border-slate-300' : 'bg-blue-50 text-primary-500 border-primary-400/40'">
              {{ rec.source === 'manual' ? 'MANUAL' : 'AI' }}
            </span>
            <button
              @click="removeCard(i)"
              class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-400 hover:text-error hover:bg-error-container/40 transition-colors cursor-pointer"
              title="Hapus kartu"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
            </button>
          </div>

          <div class="grid grid-cols-2 gap-2">
            <input
              v-model="rec.category"
              @input="dirty = true"
              class="col-span-2 rounded-lg border border-outline-variant bg-white px-2.5 py-1.5 text-[12px] font-bold uppercase tracking-wider text-on-surface focus:outline-none focus:ring-2 focus:ring-primary-400/50"
              placeholder="Kategori (mis. PAYMENT FOLLOW-UP)"
            />
            <select
              v-model="rec.tone"
              @change="dirty = true"
              class="rounded-lg border border-outline-variant bg-white px-2.5 py-1.5 text-[12px] text-on-surface focus:outline-none focus:ring-2 focus:ring-primary-400/50"
            >
              <option value="error">Merah (Urgent)</option>
              <option value="primary">Biru</option>
              <option value="slate">Abu-abu</option>
            </select>
            <input
              v-model="rec.metricValue"
              @input="dirty = true"
              class="rounded-lg border border-outline-variant bg-white px-2.5 py-1.5 text-[12px] text-on-surface focus:outline-none focus:ring-2 focus:ring-primary-400/50"
              placeholder="Nilai (mis. 3)"
            />
            <input
              v-model="rec.metricLabel"
              @input="dirty = true"
              class="col-span-2 rounded-lg border border-outline-variant bg-white px-2.5 py-1.5 text-[11px] text-slate-500 focus:outline-none focus:ring-2 focus:ring-primary-400/50"
              placeholder="Label metrik (mis. Termin Belum Dibayar)"
            />
            <input
              v-model="rec.title"
              @input="dirty = true"
              class="col-span-2 rounded-lg border border-outline-variant bg-white px-2.5 py-1.5 text-sm font-bold text-on-surface focus:outline-none focus:ring-2 focus:ring-primary-400/50"
              placeholder="Judul rekomendasi"
            />
            <textarea
              v-model="rec.body"
              @input="dirty = true"
              rows="4"
              class="col-span-2 rounded-lg border border-outline-variant bg-white px-2.5 py-1.5 text-body-sm text-on-surface-variant leading-relaxed focus:outline-none focus:ring-2 focus:ring-primary-400/50"
              placeholder="Isi rekomendasi"
            ></textarea>
          </div>
        </article>

        <button
          @click="addManual"
          class="rounded-xl border-2 border-dashed border-outline-variant bg-background hover:border-primary-400 hover:bg-primary-50/40 px-4 py-6 flex flex-col items-center justify-center gap-1.5 text-slate-500 hover:text-primary-600 transition-colors cursor-pointer"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
          <span class="text-body-sm font-semibold">Tambah Kartu Manual</span>
        </button>
      </div>

      <!-- View Mode: Cards -->
      <div v-else-if="recommendations.length" class="mt-5 grid grid-cols-1 md:grid-cols-3 gap-4">
        <article v-for="(rec, i) in recommendations" :key="'v' + i" class="rounded-xl border border-outline-variant bg-background p-4 flex flex-col gap-2">
          <div class="flex items-center justify-between gap-2">
            <span class="flex items-center gap-1.5 text-label-caps font-bold tracking-wider" :class="rec.tone">
              <span class="w-2 h-2 rounded-full bg-current"></span>{{ rec.category }}
            </span>
            <span v-if="rec.metricLabel" class="text-[11px] font-semibold text-slate-500 whitespace-nowrap">{{ rec.metricLabel }}{{ rec.metricValue ? ': ' + rec.metricValue : '' }}</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="px-1.5 py-0.5 rounded border text-[9px] font-bold tracking-wider" :class="cardSourceClass(rec)">{{ cardSourceLabel(rec) }}</span>
          </div>
          <h3 class="font-bold text-on-surface text-sm leading-snug">{{ rec.title }}</h3>
          <p class="text-body-sm text-on-surface-variant leading-relaxed">{{ rec.body }}</p>
        </article>
      </div>
    </template>
  </section>
</template>

<script>
import axios from 'axios';

const API_BASE = 'http://127.0.0.1:8000/api';

export default {
  name: 'DetailAiInsights',
  props: {
    item: { type: Object, required: true },
    masterProjectId: { type: [Number, String], default: 1 },
  },
  data() {
    return {
      mode: 'view',
      loadingSaved: true,
      generating: false,
      saving: false,
      dirty: false,
      summary: '',
      recommendations: [],
      generatedAt: null,
      docSource: null,
      error: '',
      snapshot: '',
    };
  },
  computed: {
    hasData() {
      return Boolean(this.summary || this.recommendations.length);
    },
    sourceBadgeLabel() {
      if (this.docSource === 'mixed') return 'AI + MANUAL';
      if (this.docSource === 'manual') return 'INPUT MANUAL';
      return 'HASIL AI';
    },
    sourceBadgeClass() {
      if (this.docSource === 'mixed') return 'bg-violet-50 text-violet-600 border-violet-300';
      if (this.docSource === 'manual') return 'bg-slate-100 text-slate-600 border-slate-300';
      return 'bg-blue-50 text-primary-500 border-primary-400/40';
    },
  },
  mounted() {
    this.loadSaved();
  },
  methods: {
    takeSnapshot() {
      this.snapshot = JSON.stringify({
        summary: this.summary,
        recommendations: this.recommendations,
        generatedAt: this.generatedAt,
        docSource: this.docSource,
      });
    },
    async loadSaved() {
      this.loadingSaved = true;
      this.error = '';
      try {
        const res = await axios.get(`${API_BASE}/recommendations`, {
          params: { id: this.masterProjectId, no: this.item.no },
        });
        const rec = res.data && res.data.data;
        if (rec) {
          this.summary = rec.summary || '';
          this.recommendations = Array.isArray(rec.recommendations) ? rec.recommendations : [];
          this.generatedAt = rec.updated_at;
          this.docSource = rec.source;
          this.dirty = false;
        }
        this.takeSnapshot();
      } catch (e) {
        this.error = (e.response && e.response.data && e.response.data.message)
          || 'Gagal memuat recommendation tersimpan.';
      } finally {
        this.loadingSaved = false;
      }
    },
    async generate() {
      if (this.generating) return;
      this.generating = true;
      this.error = '';
      try {
        const res = await axios.post(`${API_BASE}/ai/recommendation`, {
          id: this.masterProjectId,
          no: this.item.no,
        }, { headers: { 'Cache-Control': 'no-cache' } });
        this.summary = res.data.summary || '';
        const recs = Array.isArray(res.data.recommendations) ? res.data.recommendations : [];
        this.recommendations = recs.map((r) => ({
          category: r.category || 'REKOMENDASI',
          tone: ['error', 'primary', 'slate'].includes(r.tone) ? r.tone : 'slate',
          metricLabel: r.metricLabel || null,
          metricValue: r.metricValue || null,
          title: r.title || '',
          body: r.body || '',
          source: 'ai',
          edited: false,
        }));
        this.generatedAt = res.data.generated_at || new Date().toISOString();
        this.docSource = null;
        this.dirty = true;
        this.mode = 'view';
        if (!this.summary && !this.recommendations.length) {
          this.error = 'AI tidak menghasilkan rekomendasi, coba lagi.';
        }
      } catch (e) {
        this.error = (e.response && e.response.data && e.response.data.message)
          || 'Gagal menghubungi layanan AI. Pastikan backend berjalan dan GROQ_API_KEY sudah diisi.';
      } finally {
        this.generating = false;
      }
    },
    enterEdit() {
      this.takeSnapshot();
      this.mode = 'edit';
    },
    startManual() {
      this.takeSnapshot();
      this.recommendations.push({
        category: 'REKOMENDASI',
        tone: 'slate',
        metricLabel: null,
        metricValue: null,
        title: '',
        body: '',
        source: 'manual',
        edited: false,
      });
      this.dirty = true;
      this.mode = 'edit';
    },
    addManual() {
      this.recommendations.push({
        category: 'REKOMENDASI',
        tone: 'slate',
        metricLabel: null,
        metricValue: null,
        title: '',
        body: '',
        source: 'manual',
        edited: false,
      });
      this.dirty = true;
    },
    removeCard(i) {
      this.recommendations.splice(i, 1);
      this.dirty = true;
    },
    cancelEdit() {
      if (this.snapshot) {
        const prev = JSON.parse(this.snapshot);
        this.summary = prev.summary;
        this.recommendations = prev.recommendations;
        this.generatedAt = prev.generatedAt;
        this.docSource = prev.docSource;
      }
      this.dirty = !this.docSource && (this.summary || this.recommendations.length);
      this.mode = 'view';
      this.error = '';
    },
    computeEditedFlags() {
      let original = [];
      try {
        original = JSON.parse(this.snapshot).recommendations || [];
      } catch (e) {
        original = [];
      }
      this.recommendations.forEach((rec) => {
        if (rec.source !== 'ai') return;
        const orig = original.find((o) => o && o.source === 'ai'
          && o.title === rec.title && o.category === rec.category && o.body === rec.body);
        if (orig) {
          const same = orig.tone === rec.tone
            && orig.metricLabel === rec.metricLabel
            && orig.metricValue === rec.metricValue;
          rec.edited = !same;
        } else {
          rec.edited = true;
        }
      });
    },
    async save() {
      if (this.saving) return;
      this.error = '';
      this.computeEditedFlags();
      this.saving = true;
      try {
        const res = await axios.post(`${API_BASE}/recommendations`, {
          id: this.masterProjectId,
          no: this.item.no,
          summary: this.summary || null,
          recommendations: this.recommendations.map((r) => ({
            category: r.category || 'REKOMENDASI',
            tone: ['error', 'primary', 'slate'].includes(r.tone) ? r.tone : 'slate',
            metricLabel: r.metricLabel || null,
            metricValue: r.metricValue || null,
            title: r.title || '',
            body: r.body || '',
            source: r.source === 'manual' ? 'manual' : 'ai',
            edited: Boolean(r.edited),
          })),
        });
        const saved = res.data && res.data.data;
        if (saved) {
          this.recommendations = Array.isArray(saved.recommendations) ? saved.recommendations : this.recommendations;
          this.generatedAt = saved.updated_at;
          this.docSource = saved.source;
        }
        this.dirty = false;
        this.mode = 'view';
        this.takeSnapshot();
      } catch (e) {
        const errors = e.response && e.response.data && e.response.data.errors;
        if (errors) {
          this.error = Object.values(errors).flat().join(' ');
        } else {
          this.error = (e.response && e.response.data && e.response.data.message)
            || 'Gagal menyimpan recommendation.';
        }
      } finally {
        this.saving = false;
      }
    },
    cardSourceClass(rec) {
      if (rec.source === 'manual') return 'bg-slate-100 text-slate-600 border-slate-300';
      if (rec.edited) return 'bg-amber-50 text-amber-600 border-amber-300';
      return 'bg-blue-50 text-primary-500 border-primary-400/40';
    },
    cardSourceLabel(rec) {
      if (rec.source === 'manual') return 'MANUAL';
      if (rec.edited) return 'AI · DIEDIT';
      return 'AI';
    },
    formatTime(d) {
      const date = d instanceof Date ? d : new Date(d);
      if (Number.isNaN(date.getTime())) return '';
      return date.toLocaleString('id-ID', {
        timeZone: 'Asia/Jakarta',
        hour: '2-digit',
        minute: '2-digit',
      }) + ' WIB';
    },
  },
};
</script>
