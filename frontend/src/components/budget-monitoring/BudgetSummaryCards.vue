<script setup>
import { computed } from 'vue';
import {
    Wallet,
    FileClock,
    Sigma,
    Calculator,
    TrendingUp,
} from 'lucide-vue-next';
import { useCurrency } from '@/composables/useCurrency';

const props = defineProps({
    summary: { type: Object, required: true },
});

const { formatCurrency } = useCurrency();

const formattedSummary = computed(() => ({
    sisaAnggaran: formatCurrency(props.summary.sisa_anggaran_sap),
    wbsBelumInput: formatCurrency(props.summary.wbs_belum_input),
    total: formatCurrency(props.summary.total),
    estimasiKebutuhan: formatCurrency(props.summary.estimasi_kebutuhan),
    selisihVariance: formatCurrency(props.summary.selisih_variance),
}));
</script>

<template>
    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <div
            class="flex flex-col justify-between rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
        >
            <div class="flex items-center justify-between text-slate-400">
                <span class="text-[11px] font-bold tracking-wider uppercase"
                    >Sisa Anggaran di SAP</span
                >
                <Wallet class="h-4 w-4 text-slate-500" />
            </div>
            <div class="mt-4">
                <span class="text-xl font-black text-slate-900">{{
                    formattedSummary.sisaAnggaran
                }}</span>
            </div>
        </div>

        <div
            class="flex flex-col justify-between rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
        >
            <div class="flex items-center justify-between text-slate-400">
                <span class="text-[11px] font-bold tracking-wider uppercase"
                    >WBS Belum Input</span
                >
                <FileClock class="h-4 w-4 text-amber-500" />
            </div>
            <div class="mt-4">
                <span class="text-xl font-black text-slate-900">{{
                    formattedSummary.wbsBelumInput
                }}</span>
            </div>
        </div>

        <div
            class="flex flex-col justify-between rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
        >
            <div class="flex items-center justify-between text-slate-400">
                <span class="text-[11px] font-bold tracking-wider uppercase"
                    >Total</span
                >
                <Sigma class="h-4 w-4 text-blue-600" />
            </div>
            <div class="mt-4">
                <span class="text-xl font-black text-blue-700">{{
                    formattedSummary.total
                }}</span>
            </div>
        </div>

        <div
            class="flex flex-col justify-between rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
        >
            <div class="flex items-center justify-between text-slate-400">
                <span class="text-[11px] font-bold tracking-wider uppercase"
                    >Estimasi Kebutuhan</span
                >
                <Calculator class="h-4 w-4 text-slate-500" />
            </div>
            <div class="mt-4">
                <span class="text-xl font-black text-slate-900">{{
                    formattedSummary.estimasiKebutuhan
                }}</span>
            </div>
        </div>

        <div
            class="flex flex-col justify-between rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
        >
            <div class="flex items-center justify-between text-slate-400">
                <span class="text-[11px] font-bold tracking-wider uppercase"
                    >Selisih (Variance)</span
                >
                <TrendingUp
                    class="h-4 w-4"
                    :class="
                        summary.selisih_variance >= 0
                            ? 'text-emerald-500'
                            : 'text-rose-500'
                    "
                />
            </div>
            <div class="mt-4">
                <span
                    class="text-xl font-black"
                    :class="
                        summary.selisih_variance >= 0
                            ? 'text-emerald-600'
                            : 'text-rose-600'
                    "
                >
                    {{
                        (summary.selisih_variance >= 0 ? '+' : '') +
                        formattedSummary.selisihVariance
                    }}
                </span>
            </div>
        </div>
    </section>
</template>
