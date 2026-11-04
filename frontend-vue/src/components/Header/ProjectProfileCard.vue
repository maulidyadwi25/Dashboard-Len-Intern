<template>
  <div v-if="profile" class="bg-white rounded-xl border border-outline-variant shadow-xs p-5">
    <div class="flex items-center gap-2.5 mb-4">
      <span class="w-8 h-8 rounded-lg bg-primary-500/10 text-primary-500 flex items-center justify-center shrink-0">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5h1v5m-1 0h1m4 0v-5h1v5m-1 0h1"></path>
        </svg>
      </span>
      <div>
        <h3 class="text-sm font-bold text-on-surface">Project Profile</h3>
        <p class="text-label-caps text-slate-500 tracking-wider">Informasi umum proyek dari header spreadsheet</p>
      </div>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-x-6 gap-y-3 text-body-sm">
      <div v-for="f in fields" :key="f.key">
        <p class="font-bold text-slate-500 uppercase tracking-wider text-label-caps">{{ f.label }}</p>
        <p class="text-on-surface font-semibold mt-0.5 truncate" :title="f.value">{{ f.value }}</p>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ProjectProfileCard',
  props: {
    profile: { type: Object, default: null }
  },
  computed: {
    val() {
      return (v) => (v !== null && v !== undefined && String(v).trim() !== '' ? String(v) : '-');
    },
    fields() {
      const p = this.profile || {};
      return [
        { key: 'type', label: 'Type', value: this.val(p.type) },
        { key: 'project_name', label: 'Nama Proyek', value: this.val(p.project_name) },
        { key: 'pimpro_name', label: 'Nama Pimpro', value: this.val(p.pimpro_name) },
        { key: 'report_pic', label: 'Report PIC', value: this.val(p.report_pic) },
        { key: 'contract_value', label: 'Nilai Kontrak', value: this.val(p.contract_value) },
      ];
    }
  }
};
</script>
