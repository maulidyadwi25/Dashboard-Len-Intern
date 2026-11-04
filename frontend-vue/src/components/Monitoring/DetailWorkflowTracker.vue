<template>
  <section class="bg-white rounded-2xl border border-outline-variant shadow-xs p-5 md:p-6">
    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
      <div>
        <div class="flex flex-wrap items-center gap-2.5">
          <span class="text-label-caps font-bold tracking-wider text-slate-500">PROCUREMENT WORKFLOW &amp; DEPENDENCY TRACKER</span>
          <span class="px-2 py-0.5 rounded-full bg-blue-50 text-primary-500 border border-primary-400/40 text-[10px] font-bold tracking-wider">10 TAHAPAN UTAMA</span>
        </div>
        <h2 class="text-lg md:text-xl font-bold text-on-surface mt-1">Matriks Dependensi Alur Pengadaan Terintegrasi</h2>
      </div>

      <!-- Legend -->
      <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-body-sm text-on-surface-variant">
        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-primary-500"></span>Approved</span>
        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>In Progress</span>
        <span class="flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
          Terkunci / Waiting
        </span>
        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-slate-400"></span>Amandemen</span>
        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-red-600"></span>Dibatalkan (Cancel)</span>
      </div>
    </div>

    <!-- Gating Rule -->
    <div class="mt-4 flex flex-col md:flex-row md:items-center justify-between gap-3 bg-background border border-outline-variant rounded-xl px-4 py-3">
      <div class="flex items-start gap-3">
        <span class="shrink-0 w-8 h-8 rounded-lg bg-blue-50 border border-primary-400/40 flex items-center justify-center text-primary-500">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
        </span>
        <div>
          <p class="text-label-caps font-bold tracking-wider text-slate-500">ATURAN DEPENDENSI ALUR (GATING RULE):</p>
          <p class="text-body-sm text-on-surface-variant mt-0.5">{{ gatingText }}</p>
        </div>
      </div>
      <span class="shrink-0 self-start md:self-auto flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-body-sm font-semibold border" :class="gatingReady ? 'bg-emerald-50 text-emerald-600 border-emerald-200' : 'bg-amber-50 text-amber-600 border-amber-200'">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"></path></svg>
        {{ gatingReady ? 'Syarat Terpenuhi (SPH & HPS Done)' : 'Menunggu Syarat (SPH & HPS)' }}
      </span>
    </div>

    <!-- Procurement Stages -->
    <div class="mt-5 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 xl:grid-cols-10 gap-3">
      <div
        v-for="stage in stageStates"
        :key="stage.no"
        class="rounded-xl border px-3 py-3 flex flex-col gap-1 transition-colors"
        :class="stageClass(stage.state)"
      >
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-bold tracking-wider" :class="stage.state === 'approved' ? 'text-white/80' : stage.state === 'inprogress' ? 'text-amber-600' : 'text-slate-400'">{{ stage.no }}</span>
          <!-- Stage Completed Icon -->
          <svg v-if="stage.state === 'approved'" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4"></path></svg>
          <!-- Active Stage Indicator -->
          <span v-else-if="stage.state === 'inprogress'" class="w-2.5 h-2.5 rounded-full bg-amber-400 border-2 border-amber-200"></span>
          <!-- Locked Stage Icon -->
          <svg v-else class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
        </div>
        <p class="font-bold text-sm leading-tight" :class="stage.state === 'approved' ? 'text-white' : stage.state === 'inprogress' ? 'text-amber-700' : 'text-slate-400'">{{ stage.name }}</p>
        <p class="text-[11px] leading-tight" :class="stage.state === 'approved' ? 'text-white/75' : 'text-slate-400'">{{ stage.sub }}</p>
        <span class="mt-1.5 text-[10px] font-bold tracking-wider px-1.5 py-0.5 rounded text-center" :class="badgeClass(stage.state)">{{ stateLabel(stage.state) }}</span>
      </div>
    </div>

    <!-- Exception Paths -->
    <div class="mt-5 flex flex-col md:flex-row md:items-center gap-3">
      <p class="text-label-caps font-bold tracking-wider text-slate-500 md:w-56 shrink-0">JALUR PENGECUALIAN &amp; STATUS KHUSUS:</p>
      <div class="flex-1 space-y-2">
        <div class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-lg border text-body-sm" :class="amandemenActive ? 'bg-slate-100 border-slate-300' : 'bg-slate-50 border-outline-variant opacity-70'">
          <span class="w-2.5 h-2.5 rounded-full bg-slate-500 shrink-0"></span>
          <p class="text-on-surface-variant"><span class="font-bold text-on-surface">Amandemen (Addendum Kontrak):</span> {{ amandemenActive ? 'Status saat ini — aktif karena ada perubahan scope/nilai pasca PO/Kontrak.' : 'Aktif bila ada perubahan scope/nilai pasca PO/Kontrak' }}</p>
        </div>
        <div class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-lg border text-body-sm" :class="cancelActive ? 'bg-red-50 border-red-200' : 'bg-slate-50 border-outline-variant opacity-70'">
          <span class="w-2.5 h-2.5 rounded-full bg-red-600 shrink-0"></span>
          <p class="text-on-surface-variant"><span class="font-bold text-on-surface">Cancel (Pembatalan Pengadaan):</span> {{ cancelActive ? 'Status saat ini — pengadaan dibatalkan.' : 'Terminasi pengadaan oleh KPA / Komite Audit' }}</p>
        </div>
      </div>
    </div>
  </section>
</template>

<script>
const STAGES = [
  { no: '01', name: 'SPH', sub: 'Penawaran' },
  { no: '02', name: 'HPS', sub: 'Owner Estimate' },
  { no: '03', name: 'RKS', sub: 'Syarat & Spek' },
  { no: '04', name: 'PR', sub: 'Requisition' },
  { no: '05', name: 'RFQ', sub: 'Quotation' },
  { no: '06', name: 'NEGOSIASI', sub: 'BAFO & BA' },
  { no: '07', name: 'PO', sub: 'Purchase Order' },
  { no: '08', name: 'KONTRAK', sub: 'Legal Signing' },
  { no: '09', name: 'PRODUCTION', sub: 'Fabrikasi QC' },
  { no: '10', name: 'DELIVERED', sub: 'Uji BAST' },
];

export default {
  name: 'DetailWorkflowTracker',
  props: {
    item: { type: Object, required: true },
  },
  computed: {
    currentIdx() {
      const cur = String(this.item.progress || '').trim().toUpperCase();
      return STAGES.findIndex(s => s.name === cur);
    },
    stageStates() {
      const idx = this.currentIdx;
      const st = String(this.item.status || '').toLowerCase();
      const cancelled = st.includes('cancel') || st.includes('batal');
      const amended = st.includes('amend') || st.includes('amandemen');
      return STAGES.map((s, i) => {
        let state = 'locked';
        if (cancelled) state = 'cancelled';
        else if (amended && i <= idx) state = 'amended';
        else if (idx >= 0 && i < idx) state = 'approved';
        else if (idx >= 0 && i === idx) state = 'inprogress';
        return { ...s, state };
      });
    },
    gatingText() {
      const idx = this.currentIdx;
      const active = idx >= 0 ? STAGES[idx] : null;
      if (active) {
        return `Tahap ${active.name} sedang berjalan; tahap berikutnya baru dapat dimulai setelah tahap SPH dan HPS tuntas (100% Selesai & Disetujui).`;
      }
      return 'Tahap berikutnya baru dapat dimulai setelah tahap SPH dan HPS tuntas (100% Selesai & Disetujui).';
    },
    gatingReady() {
      const idx = this.currentIdx;
      return idx >= 2; // SPH & HPS sudah dilalui
    },
    amandemenActive() {
      const st = String(this.item.status || '').toLowerCase();
      return st.includes('amend') || st.includes('amandemen');
    },
    cancelActive() {
      const st = String(this.item.status || '').toLowerCase();
      return st.includes('cancel') || st.includes('batal');
    },
  },
  methods: {
    stageClass(state) {
      if (state === 'approved') return 'bg-primary-500 border-primary-600 shadow-xs';
      if (state === 'inprogress') return 'bg-amber-50 border-2 border-amber-400';
      if (state === 'cancelled') return 'bg-red-50 border border-red-200';
      if (state === 'amended') return 'bg-slate-200 border border-slate-300';
      return 'bg-slate-50 border border-outline-variant';
    },
    badgeClass(state) {
      if (state === 'approved') return 'bg-white/20 text-white';
      if (state === 'inprogress') return 'bg-amber-100 text-amber-700';
      if (state === 'cancelled') return 'bg-red-100 text-red-600';
      if (state === 'amended') return 'bg-slate-300 text-slate-700';
      return 'bg-slate-200 text-slate-500';
    },
    stateLabel(state) {
      if (state === 'approved') return 'APPROVED';
      if (state === 'inprogress') return 'IN PROGRESS';
      if (state === 'cancelled') return 'CANCEL';
      if (state === 'amended') return 'AMANDEMEN';
      return 'TERKUNCI';
    },
  },
};
</script>
