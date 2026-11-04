<template>
  <div class="space-y-5">
    <!-- Down Payment Tracking -->
    <section class="bg-white rounded-2xl border border-outline-variant shadow-xs p-5 md:p-6">
      <div class="flex flex-wrap items-center gap-2.5">
        <span class="text-label-caps font-bold tracking-wider text-slate-500">TRACKING PENCAIRAN DP PENGADAAN VENDOR</span>
      </div>
      <h2 class="text-lg md:text-xl font-bold text-on-surface mt-1">Monitoring Down Payment (DP) &amp; Pembayaran</h2>

      <div class="mt-4 overflow-x-auto rounded-xl border border-outline-variant">
        <table class="w-full text-body-sm min-w-[860px]">
          <thead>
            <tr class="bg-primary-600 text-white text-label-caps tracking-wider">
              <th class="p-3 text-center w-14">NO</th>
              <th class="p-3 text-left">NAMA PAKET &amp; KATEGORI</th>
              <th class="p-3 text-left">VENDOR / PENYEDIA</th>
              <th class="p-3 text-left">NOMINAL &amp; TGL HPS</th>
              <th class="p-3 text-left">STATUS RKS &amp; NO. DOKUMEN</th>
              <th class="p-3 text-left">PROGRES NEGOSIASI</th>
              <th class="p-3 text-center w-36">STATUS</th>
              <th class="p-3 text-center w-14"></th>
            </tr>
          </thead>
          <tbody>
            <tr class="border-b border-outline-variant last:border-0 hover:bg-background/70 transition-colors">
              <td class="p-3 text-center font-bold text-on-surface">01</td>
              <td class="p-3 text-on-surface font-semibold">{{ item.name }}</td>
              <td class="p-3 text-on-surface-variant">{{ item.vendor }}</td>
              <td class="p-3">
                <span class="block font-bold text-primary-500">{{ formatCurrency(item.dp?.nilai, item.mataUang) }}</span>
                <span class="text-[11px] text-slate-500">HPS: {{ item.tanggalHps || '-' }}</span>
              </td>
              <td class="p-3 text-on-surface-variant">
                <span class="block">{{ item.dp?.grSes && item.dp.grSes !== '-' ? item.dp.grSes : 'RKS: ' + (item.rks || '-') }}</span>
                <span class="text-[11px] text-slate-500">No. Dok: {{ item.dp?.noSpp && item.dp.noSpp !== '-' ? item.dp.noSpp : (item.noPo !== '-' && item.noPo ? 'PO ' + item.noPo : '-') }}</span>
              </td>
              <td class="p-3 text-on-surface-variant">
                {{ item.dp?.tanggalSubmit && item.dp.tanggalSubmit !== '-' ? item.dp.tanggalSubmit : (item.progress || '-') }}
              </td>
              <td class="p-3 text-center">
                <select
                  :value="normStatus(paymentStatus.dp)"
                  @change="saveStatus('dp', 'payment_status', $event.target.value)"
                  class="text-[11px] font-bold px-2 py-1 rounded-full border cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary-400"
                  :class="statusBadgeClass(paymentStatus.dp)"
                >
                  <option v-for="opt in paymentStatusOptions" :key="opt" :value="opt">{{ opt }}</option>
                </select>
              </td>
              <td class="p-3 text-center">
                <button @click="scrollToTermin" title="Lihat detail termin" class="text-slate-500 hover:text-primary-500 transition-colors cursor-pointer">
                  <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Payment Milestones -->
    <section ref="terminSection" class="bg-white rounded-2xl border border-outline-variant shadow-xs p-5 md:p-6">
      <div class="flex flex-wrap items-center gap-2.5">
        <span class="text-label-caps font-bold tracking-wider text-slate-500">SCHEDULE &amp; PAYMENT MILESTONES</span>
        <span class="px-2 py-0.5 rounded-full bg-blue-50 text-primary-500 border border-primary-400/40 text-[10px] font-bold tracking-wider">{{ scheduledCount }} Termin Terjadwal</span>
      </div>
      <h2 class="text-lg md:text-xl font-bold text-on-surface mt-1">Jadwal &amp; Realisasi Termin Pembayaran Vendor</h2>
      <p class="text-body-sm text-slate-500 mt-0.5">Monitoring pengajuan termin, tanggal submit, kelengkapan berkas GR/SES, dan nomor SPP pengadaan.</p>

      <!-- Search & Filters -->
      <div class="mt-4 flex flex-wrap items-center gap-2.5">
        <div class="relative flex-1 min-w-[200px] max-w-xs">
          <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"></path></svg>
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Cari termin / SPP..."
            class="w-full pl-9 pr-3 py-2 text-body-sm bg-background border border-outline-variant rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-400 focus:border-primary-400"
          />
        </div>

        <label class="flex items-center gap-2 text-body-sm font-bold text-slate-500 uppercase tracking-wider">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
          Tanggal:
          <input
            v-model="dateFilter"
            type="date"
            class="font-normal text-on-surface normal-case tracking-normal bg-background border border-outline-variant rounded-lg px-2.5 py-1.5 focus:outline-none focus:ring-2 focus:ring-primary-400"
          />
        </label>

        <div class="flex items-center gap-2.5 ml-auto">
          <button
            @click="openNewTermin"
            class="flex items-center gap-1.5 px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg text-body-sm font-semibold transition-colors cursor-pointer shadow-xs"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            Pengajuan Termin Baru
          </button>
          <button
            @click="downloadCsv"
            class="flex items-center gap-1.5 px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg text-body-sm font-semibold transition-colors cursor-pointer shadow-xs"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
            Download
          </button>
        </div>
      </div>

      <!-- Termin Table -->
      <div class="mt-4 overflow-x-auto rounded-xl border border-outline-variant">
        <table class="w-full text-body-sm min-w-[760px]">
          <thead>
            <tr class="bg-primary-600 text-white text-label-caps tracking-wider">
              <th class="p-3 text-left w-56">TERMIN</th>
              <th class="p-3 text-left w-36">TANGGAL SUBMIT</th>
              <th class="p-3 text-left w-44">NILAI ({{ item.mataUang || 'IDR' }})</th>
              <th class="p-3 text-left w-40">GR / SES</th>
              <th class="p-3 text-left w-44">NO. SPP</th>
              <th class="p-3 text-center w-36">STATUS</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in filteredRows" :key="row.key" class="border-b border-outline-variant last:border-0 hover:bg-background/70 transition-colors">
              <td class="p-3">
                <span class="block font-semibold text-on-surface">{{ row.label }}</span>
                <span class="text-[11px] text-slate-500">{{ row.slotLabel }}</span>
              </td>
              <td class="p-3 text-on-surface-variant">
                <span v-if="row.tanggalSubmit && row.tanggalSubmit !== '-'" class="flex items-center gap-1.5">
                  <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                  {{ row.tanggalSubmit }}
                </span>
                <span v-else class="text-slate-400">-</span>
              </td>
              <td class="p-3">
                <span v-if="row.nilai" class="font-bold text-primary-500">{{ formatCurrency(row.nilai, item.mataUang) }}</span>
                <span v-else class="text-slate-400">-</span>
              </td>
              <td class="p-3" :class="row.grSes && row.grSes !== '-' ? 'text-on-surface-variant' : 'text-slate-400'">{{ row.grSes || '-' }}</td>
              <td class="p-3" :class="row.noSpp && row.noSpp !== '-' ? 'text-on-surface-variant' : 'text-slate-400'">{{ row.noSpp || '-' }}</td>

              <!-- Payment Status (editable) -->
              <td class="p-3 text-center">
                <select
                  :value="normStatus(row.paymentStatus)"
                  @change="saveStatus(row.key, 'payment_status', $event.target.value)"
                  class="text-[11px] font-bold px-2 py-1 rounded-full border cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary-400"
                  :class="statusBadgeClass(row.paymentStatus)"
                >
                  <option v-for="opt in paymentStatusOptions" :key="opt" :value="opt">{{ opt }}</option>
                </select>
              </td>
            </tr>
            <tr v-if="filteredRows.length === 0">
              <td colspan="6" class="p-8 text-center text-slate-500 font-medium">Tidak ada termin yang cocok dengan pencarian.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Payment Summary -->
      <div class="mt-4 flex flex-col md:flex-row md:items-center justify-between gap-2 text-body-sm">
        <p class="text-on-surface-variant">
          Total Komitmen Termin:
          <span class="font-bold text-on-surface">{{ scheduledCount }} Termin</span>
          <span class="text-slate-500"> (Total Nilai {{ formatCurrency(totals.commitment, item.mataUang) }})</span>
        </p>
        <div class="flex flex-wrap items-center gap-4 text-on-surface-variant">
          <span class="flex items-center gap-1.5">
            <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            Realisasi Terbayar: <span class="font-bold text-primary-500">{{ totals.paidPercent }}% ({{ formatCurrency(totals.paid, item.mataUang) }})</span>
          </span>
          <span>• Sisa Pembayaran: <span class="font-bold text-on-surface">{{ formatCurrency(totals.remaining, item.mataUang) }}</span></span>
        </div>
      </div>
    </section>

    <!-- New Payment Request Form -->
    <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="form.show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/50" @click="closeForm"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md max-h-[85vh] overflow-y-auto custom-scrollbar">
          <div class="flex items-center justify-between px-5 py-4 border-b border-outline-variant sticky top-0 bg-white rounded-t-2xl">
            <h3 class="text-sm font-bold text-on-surface">Pengajuan Termin Baru — {{ item.name }}</h3>
            <button @click="closeForm" class="w-7 h-7 rounded-full hover:bg-slate-100 text-slate-500 flex items-center justify-center cursor-pointer">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
          </div>

          <div class="px-5 py-4 space-y-3 text-body-sm">
            <p class="text-slate-500">Slot kosong berikutnya: <span class="font-bold text-on-surface">{{ form.slotLabel }}</span></p>

            <div>
              <label class="font-bold text-slate-500 uppercase tracking-wider text-label-caps">Tanggal Submit</label>
              <input v-model="form.tanggal_submit" type="date" class="mt-1 w-full px-3 py-2 bg-background border border-outline-variant rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-400" />
            </div>
            <div>
              <label class="font-bold text-slate-500 uppercase tracking-wider text-label-caps">Nilai ({{ item.mataUang || 'IDR' }})</label>
              <input v-model="form.nilai" type="text" placeholder="mis. 690.000.000" class="mt-1 w-full px-3 py-2 bg-background border border-outline-variant rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-400" />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="font-bold text-slate-500 uppercase tracking-wider text-label-caps">GR / SES</label>
                <input v-model="form.gr_ses" type="text" placeholder="SES-GCI-2026-0412" class="mt-1 w-full px-3 py-2 bg-background border border-outline-variant rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-400" />
              </div>
              <div>
                <label class="font-bold text-slate-500 uppercase tracking-wider text-label-caps">No. SPP</label>
                <input v-model="form.no_spp" type="text" placeholder="SPP/PROC-GCI/2026/088" class="mt-1 w-full px-3 py-2 bg-background border border-outline-variant rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-400" />
              </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="font-bold text-slate-500 uppercase tracking-wider text-label-caps">Status Termin</label>
                <select v-model="form.status_termin" class="mt-1 w-full px-3 py-2 bg-background border border-outline-variant rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-400 cursor-pointer">
                  <option v-for="opt in terminStatusOptions" :key="opt" :value="opt">{{ opt }}</option>
                </select>
              </div>
              <div>
                <label class="font-bold text-slate-500 uppercase tracking-wider text-label-caps">Payment Status</label>
                <select v-model="form.payment_status" class="mt-1 w-full px-3 py-2 bg-background border border-outline-variant rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-400 cursor-pointer">
                  <option v-for="opt in paymentStatusOptions" :key="opt" :value="opt">{{ opt }}</option>
                </select>
              </div>
            </div>
            <p v-if="form.error" class="text-error bg-error-container rounded-lg px-3 py-2">{{ form.error }}</p>
          </div>

          <div class="flex items-center justify-end gap-2 px-5 py-4 border-t border-outline-variant">
            <button @click="closeForm" class="px-4 py-1.5 bg-slate-100 hover:bg-slate-200 text-on-surface-variant rounded-lg text-body-sm font-semibold transition-colors cursor-pointer">Batal</button>
            <button @click="submitTermin" :disabled="form.saving" class="px-4 py-1.5 bg-primary-500 hover:bg-primary-600 disabled:opacity-50 text-white rounded-lg text-body-sm font-semibold transition-colors cursor-pointer">
              {{ form.saving ? 'Menyimpan...' : 'Ajukan Termin' }}
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- Notification -->
    <transition enter-active-class="transform transition ease-out duration-300" enter-from-class="translate-y-2 opacity-0" enter-to-class="translate-y-0 opacity-100" leave-active-class="transition ease-in duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="toast.show" class="fixed bottom-5 right-5 z-50 flex items-center gap-2.5 px-4 py-3 rounded-xl shadow-2xl text-body-sm font-semibold border transition-all" :class="toast.isError ? 'bg-error text-white border-error' : 'bg-emerald-500 text-white border-emerald-500'">
        <span>{{ toast.message }}</span>
      </div>
    </transition>
  </div>
</template>

<script>
import axios from 'axios';

const API_BASE = 'http://127.0.0.1:8000/api';

const TERMIN_SLOTS = [
  { key: 'dp', block: 'dp', label: 'DP - Uang Muka', slotLabel: 'Down Payment' },
  { key: 't1', block: 'termin1', label: 'Termin I', slotLabel: 'Termin 1' },
  { key: 't2', block: 'termin2', label: 'Termin II', slotLabel: 'Termin 2' },
  { key: 't3', block: 'termin3', label: 'Termin III', slotLabel: 'Termin 3' },
  { key: 't4', block: 'termin4', label: 'Termin IV', slotLabel: 'Termin 4' },
  { key: 't5', block: 'termin5', label: 'Termin V', slotLabel: 'Termin 5' },
  { key: 't6', block: 'termin6', label: 'Termin VI', slotLabel: 'Termin 6' },
];

const ROMAN = { t1: 'I', t2: 'II', t3: 'III', t4: 'IV', t5: 'V', t6: 'VI' };

export default {
  name: 'DetailPaymentMilestones',
  emits: ['termin-saved'],
  props: {
    item: { type: Object, required: true },
    masterProjectId: { type: [Number, String], default: 1 },
  },
  data() {
    return {
      searchQuery: '',
      dateFilter: '',
      terminStatusOptions: ['Paid', 'In Progress', 'Unpaid', 'N/A'],
      paymentStatusOptions: ['Paid', 'Not Paid', 'In Progress', 'N/A'],
      savingKeys: {},
      form: {
        show: false,
        saving: false,
        error: '',
        terminKey: '',
        slotLabel: '',
        tanggal_submit: '',
        nilai: '',
        gr_ses: '',
        no_spp: '',
        status_termin: 'In Progress',
        payment_status: 'Not Paid',
      },
      toast: { show: false, message: '', isError: false, timer: null },
    };
  },
  computed: {
    rows() {
      return TERMIN_SLOTS.map(slot => {
        const blk = this.item[slot.block] || {};
        const statusTermin = this.item.terminStatus?.[slot.key] || '';
        const paymentStatus = this.item.paymentStatus?.[slot.key] || '';
        return {
          key: slot.key,
          label: slot.label,
          slotLabel: slot.slotLabel,
          tanggalSubmit: blk.tanggalSubmit || '-',
          nilai: Number(blk.nilai) || 0,
          grSes: blk.grSes || '-',
          noSpp: blk.noSpp || '-',
          statusTermin,
          paymentStatus,
          hasData: (Number(blk.nilai) || 0) > 0 || (blk.tanggalSubmit && blk.tanggalSubmit !== '-'),
        };
      });
    },
    scheduledCount() {
      return this.rows.filter(r => r.hasData).length;
    },
    filteredRows() {
      const q = this.searchQuery.trim().toLowerCase();
      const d = this.dateFilter;
      return this.rows.filter(r => {
        if (!r.hasData) return false;
        if (q) {
          const hay = `${r.label} ${r.slotLabel} ${r.noSpp} ${r.grSes} ${r.statusTermin} ${r.paymentStatus}`.toLowerCase();
          if (!hay.includes(q)) return false;
        }
        if (d) {
          // cocokkan dd/mm/yyyy atau dd-Mon-yyyy dengan input yyyy-mm-dd
          const t = r.tanggalSubmit || '';
          if (!this.dateMatches(t, d)) return false;
        }
        return true;
      });
    },
    totals() {
      const dataRows = this.rows.filter(r => r.hasData);
      const commitment = dataRows.reduce((a, r) => a + r.nilai, 0);
      const paid = dataRows.filter(r => this.normStatus(r.paymentStatus) === 'Paid' || this.normStatus(r.statusTermin) === 'Paid')
        .reduce((a, r) => a + r.nilai, 0);
      return {
        commitment,
        paid,
        remaining: Math.max(commitment - paid, 0),
        paidPercent: commitment > 0 ? Math.round((paid / commitment) * 100) : 0,
      };
    },
    paymentStatus() {
      return this.item.paymentStatus || {};
    },
  },
  methods: {
    normStatus(v) {
      const s = String(v || '').trim();
      if (!s || s === '-') return 'N/A';
      const low = s.toLowerCase();
      if (low === 'paid' || low === 'not paid' || low === 'in progress' || low === 'n/a') {
        return s.charAt(0).toUpperCase() + s.slice(1).toLowerCase();
      }
      return s;
    },
    statusBadgeClass(v) {
      const s = String(v || '').toLowerCase();
      if (s.includes('not paid') || s === 'unpaid') return 'bg-error-container text-error border-error/30';
      if (s.includes('paid') && !s.includes('not')) return 'bg-emerald-100 text-emerald-600 border-emerald-200';
      if (s.includes('progress')) return 'bg-blue-50 text-primary-500 border-primary-400/40';
      return 'bg-slate-100 text-slate-500 border-outline-variant';
    },
    dateMatches(sheetDate, isoDate) {
      if (!sheetDate || sheetDate === '-' || !isoDate) return false;
      const [y, m, dd] = isoDate.split('-');
      const months = ['jan', 'feb', 'mar', 'apr', 'mei', 'jun', 'jul', 'aug', 'agu', 'sep', 'okt', 'nov', 'des'];
      const low = String(sheetDate).toLowerCase();
      const day = String(parseInt(dd, 10));
      const mon = months[parseInt(m, 10) - 1];
      // format "18 Okt 2026" atau "18/10/2026"
      if (low.includes(mon) && low.includes(y)) return true;
      if (low.includes(`${day}/${parseInt(m, 10)}/${y}`)) return true;
      if (low.includes(`${day}/${m}/${y}`)) return true;
      return false;
    },
    formatCurrency(val, currency = '') {
      if (!val && val !== 0) return '-';
      const num = Number(val);
      if (isNaN(num)) return '-';
      const curr = (currency || 'IDR').toUpperCase();
      if (curr === 'EUR') return '€ ' + new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num);
      if (curr === 'USD') return '$ ' + new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num);
      if (curr === 'SGD') return 'S$ ' + new Intl.NumberFormat('en-SG', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num);
      return 'Rp ' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(num);
    },
    async saveStatus(terminKey, field, value) {
      if (this.savingKeys[terminKey + field]) return;
      this.savingKeys[terminKey + field] = true;
      try {
        const res = await axios.post(`${API_BASE}/procurements/termin`, {
          master_project_id: this.masterProjectId,
          no: this.item.no,
          termin_key: terminKey,
          [field]: value,
        });
        if (res.data?.status === 'success') {
          this.$emit('termin-saved', { terminKey, field, value });
          this.showToast('Tersimpan ke Google Sheet');
        } else {
          this.showToast(res.data?.message || 'Gagal menyimpan', true);
        }
      } catch (err) {
        this.showToast(err.response?.data?.message || 'Gagal menyimpan ke server', true);
      } finally {
        this.savingKeys[terminKey + field] = false;
      }
    },
    openNewTermin() {
      const empty = this.rows.find(r => !r.hasData && r.key !== 'dp')
        ?? (!this.rows[0].hasData ? this.rows[0] : null);
      if (!empty) {
        this.showToast('Semua slot termin sudah terisi', true);
        return;
      }
      this.form = {
        show: true,
        saving: false,
        error: '',
        terminKey: empty.key,
        slotLabel: empty.label + ' (' + empty.slotLabel + ')',
        tanggal_submit: '',
        nilai: '',
        gr_ses: '',
        no_spp: '',
        status_termin: 'In Progress',
        payment_status: 'Not Paid',
      };
    },
    closeForm() {
      this.form.show = false;
    },
    async submitTermin() {
      const f = this.form;
      f.error = '';
      if (!f.tanggal_submit) { f.error = 'Tanggal submit wajib diisi.'; return; }
      if (!f.nilai || !String(f.nilai).trim()) { f.error = 'Nilai termin wajib diisi.'; return; }
      f.saving = true;
      try {
        const res = await axios.post(`${API_BASE}/procurements/termin`, {
          master_project_id: this.masterProjectId,
          no: this.item.no,
          termin_key: f.terminKey,
          tanggal_submit: this.formatDateForSheet(f.tanggal_submit),
          nilai: String(f.nilai).trim(),
          gr_ses: f.gr_ses || '-',
          no_spp: f.no_spp || '-',
          status_termin: f.status_termin,
          payment_status: f.payment_status,
        });
        if (res.data?.status === 'success') {
          f.show = false;
          this.$emit('termin-saved', { terminKey: f.terminKey, created: true });
          this.showToast('Termin baru tersimpan ke Google Sheet');
        } else {
          f.error = res.data?.message || 'Gagal menyimpan termin.';
        }
      } catch (err) {
        f.error = err.response?.data?.message || 'Gagal menyimpan ke server.';
      } finally {
        f.saving = false;
      }
    },
    formatDateForSheet(iso) {
      const [y, m, dd] = iso.split('-');
      const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
      return `${dd} ${months[parseInt(m, 10) - 1]} ${y}`;
    },
    downloadCsv() {
      const headers = ['TERMIN', 'TANGGAL SUBMIT', `NILAI (${this.item.mataUang || 'IDR'})`, 'GR / SES', 'NO. SPP', 'PAYMENT STATUS'];
      const lines = [headers.join(';')];
      this.filteredRows.forEach(r => {
        lines.push([
          r.label,
          r.tanggalSubmit,
          r.nilai || '',
          r.grSes,
          r.noSpp,
          this.normStatus(r.paymentStatus),
        ].map(v => `"${String(v).replace(/"/g, '""')}"`).join(';'));
      });
      const blob = new Blob(['\uFEFF' + lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `termin-${String(this.item.no).replace(/\./g, '-')}-${this.item.name || 'pengadaan'}.csv`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
    },
    scrollToTermin() {
      this.$refs.terminSection?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    },
    showToast(message, isError = false) {
      if (this.toast.timer) clearTimeout(this.toast.timer);
      this.toast = { show: true, message, isError, timer: setTimeout(() => { this.toast.show = false; }, 3000) };
    },
  },
};
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #c3c6d7; border-radius: 3px; }
</style>
