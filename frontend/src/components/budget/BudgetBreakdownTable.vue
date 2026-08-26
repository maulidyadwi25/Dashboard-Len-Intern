<script setup>
import { ref, computed, watch } from "vue";
import { Search, X, ArrowUpDown, ArrowUp, ArrowDown } from "lucide-vue-next";
import { useCurrency } from "@/composables/useCurrency";

const props = defineProps({
  items: { type: Array, default: () => [] },
  itemsPerPage: { type: Number, default: 4 },
});

const { selectedCurrency, formatCurrency } = useCurrency();

const formattedTableData = computed(() =>
  props.items.map((item) => ({
    ...item,
    formattedSisaAnggaranSap: formatCurrency(item.sisa_anggaran_sap),
    formattedWbsBelumInput: formatCurrency(item.wbs_belum_input),
    formattedTotal: formatCurrency(item.total),
    formattedEstimasiKebutuhan: formatCurrency(item.estimasi_kebutuhan),
    formattedSelisihVariance:
      (item.selisih_variance >= 0 ? "+" : "") +
      formatCurrency(item.selisih_variance),
  })),
);

const searchTerm = ref("");
const currentPage = ref(1);
const perPage = ref(props.itemsPerPage);

const sortKey = ref("no");
const sortOrder = ref("asc");

const handleSort = (key) => {
  if (sortKey.value === key) {
    sortOrder.value = sortOrder.value === "asc" ? "desc" : "asc";
  } else {
    sortKey.value = key;
    sortOrder.value = "asc";
  }
  currentPage.value = 1;
};

const filteredData = computed(() => {
  const query = searchTerm.value.trim().toLowerCase();
  const items = formattedTableData.value;
  if (!query) return items;

  return items.filter((item) => {
    const searchableText = [
      item.no,
      item.wbs_element,
      item.formattedSisaAnggaranSap,
      item.formattedWbsBelumInput,
      item.formattedTotal,
      item.formattedEstimasiKebutuhan,
      item.formattedSelisihVariance,
    ]
      .filter(Boolean)
      .join(" ")
      .toLowerCase();

    return searchableText.includes(query);
  });
});

const sortedData = computed(() => {
  const data = [...filteredData.value];
  if (!sortKey.value) return data;

  return data.sort((a, b) => {
    const valA = a[sortKey.value];
    const valB = b[sortKey.value];

    if (typeof valA === "string" || typeof valB === "string") {
      const strA = (valA ?? "").toString().toLowerCase();
      const strB = (valB ?? "").toString().toLowerCase();
      return sortOrder.value === "asc"
        ? strA.localeCompare(strB)
        : strB.localeCompare(strA);
    }

    const numA = Number(valA) || 0;
    const numB = Number(valB) || 0;
    return sortOrder.value === "asc" ? numA - numB : numB - numA;
  });
});

watch([perPage, searchTerm], () => {
  currentPage.value = 1;
});

const totalPages = computed(
  () => Math.ceil(sortedData.value.length / perPage.value) || 1,
);

const paginatedData = computed(() => {
  const start = (currentPage.value - 1) * perPage.value;
  return sortedData.value.slice(start, start + perPage.value);
});

const clearSearch = () => {
  searchTerm.value = "";
  currentPage.value = 1;
};
</script>

<template>
  <section
    class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
  >
    <div
      class="flex flex-col justify-between gap-4 p-6 sm:flex-row sm:items-center"
    >
      <h2 class="text-lg font-bold text-slate-900">
        Detailed Budget Breakdown
      </h2>
      <div class="relative w-full sm:w-72">
        <input
          type="text"
          placeholder="Search by WBS or No..."
          v-model="searchTerm"
          class="w-full rounded-lg border border-slate-300 bg-white py-2 pr-9 pl-9 text-sm placeholder-slate-400 focus:border-transparent focus:ring-2 focus:ring-blue-600 focus:outline-none"
        />
        <Search
          class="pointer-events-none absolute top-2.5 left-3 h-4 w-4 text-slate-400"
        />
        <button
          v-if="searchTerm"
          @click="clearSearch"
          type="button"
          class="absolute top-2.5 right-2.5 rounded-full p-0.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
        >
          <X class="h-4 w-4" />
        </button>
      </div>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full border-collapse text-left text-sm">
        <thead>
          <tr
            class="bg-[#0052cc] text-xs font-semibold tracking-wider text-white select-none"
          >
            <th
              class="w-20 cursor-pointer px-4 py-3.5 text-center hover:bg-blue-700"
            >
              <div class="flex items-center justify-center space-x-1">
                <span>No.</span>
              </div>
            </th>

            <th
              @click="handleSort('wbs_element')"
              class="cursor-pointer px-4 py-3.5 hover:bg-blue-700"
            >
              <div class="flex items-center space-x-1">
                <span>WBS Element</span>
                <ArrowUp
                  v-if="sortKey === 'wbs_element' && sortOrder === 'asc'"
                  class="h-3.5 w-3.5"
                />
                <ArrowDown
                  v-else-if="sortKey === 'wbs_element' && sortOrder === 'desc'"
                  class="h-3.5 w-3.5"
                />
                <ArrowUpDown v-else class="h-3.5 w-3.5 opacity-40" />
              </div>
            </th>

            <!-- Header Dinamis Otomatis -->
            <th
              @click="handleSort('sisa_anggaran_sap')"
              class="cursor-pointer px-4 py-3.5 text-right hover:bg-blue-700"
            >
              <div class="flex items-center justify-end space-x-1">
                <span>Sisa Anggaran di SAP ({{ selectedCurrency }})</span>
                <ArrowUp
                  v-if="sortKey === 'sisa_anggaran_sap' && sortOrder === 'asc'"
                  class="h-3.5 w-3.5"
                />
                <ArrowDown
                  v-else-if="
                    sortKey === 'sisa_anggaran_sap' && sortOrder === 'desc'
                  "
                  class="h-3.5 w-3.5"
                />
                <ArrowUpDown v-else class="h-3.5 w-3.5 opacity-40" />
              </div>
            </th>

            <th
              @click="handleSort('wbs_belum_input')"
              class="cursor-pointer px-4 py-3.5 text-right hover:bg-blue-700"
            >
              <div class="flex items-center justify-end space-x-1">
                <span>WBS Belum Input ({{ selectedCurrency }})</span>
                <ArrowUp
                  v-if="sortKey === 'wbs_belum_input' && sortOrder === 'asc'"
                  class="h-3.5 w-3.5"
                />
                <ArrowDown
                  v-else-if="
                    sortKey === 'wbs_belum_input' && sortOrder === 'desc'
                  "
                  class="h-3.5 w-3.5"
                />
                <ArrowUpDown v-else class="h-3.5 w-3.5 opacity-40" />
              </div>
            </th>

            <th
              @click="handleSort('total')"
              class="cursor-pointer px-4 py-3.5 text-right hover:bg-blue-700"
            >
              <div class="flex items-center justify-end space-x-1">
                <span>Total ({{ selectedCurrency }})</span>
                <ArrowUp
                  v-if="sortKey === 'total' && sortOrder === 'asc'"
                  class="h-3.5 w-3.5"
                />
                <ArrowDown
                  v-else-if="sortKey === 'total' && sortOrder === 'desc'"
                  class="h-3.5 w-3.5"
                />
                <ArrowUpDown v-else class="h-3.5 w-3.5 opacity-40" />
              </div>
            </th>

            <th
              @click="handleSort('estimasi_kebutuhan')"
              class="cursor-pointer px-4 py-3.5 text-right hover:bg-blue-700"
            >
              <div class="flex items-center justify-end space-x-1">
                <span>Estimasi Kebutuhan ({{ selectedCurrency }})</span>
                <ArrowUp
                  v-if="sortKey === 'estimasi_kebutuhan' && sortOrder === 'asc'"
                  class="h-3.5 w-3.5"
                />
                <ArrowDown
                  v-else-if="
                    sortKey === 'estimasi_kebutuhan' && sortOrder === 'desc'
                  "
                  class="h-3.5 w-3.5"
                />
                <ArrowUpDown v-else class="h-3.5 w-3.5 opacity-40" />
              </div>
            </th>

            <th
              @click="handleSort('selisih_variance')"
              class="cursor-pointer px-4 py-3.5 text-right hover:bg-blue-700"
            >
              <div class="flex items-center justify-end space-x-1">
                <span>Selisih (Variance)</span>
                <ArrowUp
                  v-if="sortKey === 'selisih_variance' && sortOrder === 'asc'"
                  class="h-3.5 w-3.5"
                />
                <ArrowDown
                  v-else-if="
                    sortKey === 'selisih_variance' && sortOrder === 'desc'
                  "
                  class="h-3.5 w-3.5"
                />
                <ArrowUpDown v-else class="h-3.5 w-3.5 opacity-40" />
              </div>
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-slate-700">
          <tr
            v-for="item in paginatedData"
            :key="item.no"
            class="transition-colors hover:bg-slate-50"
          >
            <td class="px-4 py-3.5 text-center font-medium text-slate-400">
              {{ item.no }}
            </td>
            <td class="px-4 py-3.5 font-semibold text-slate-800">
              {{ item.wbs_element }}
            </td>
            <td class="px-4 py-3.5 text-right font-medium">
              {{ item.formattedSisaAnggaranSap }}
            </td>
            <td class="px-4 py-3.5 text-right font-medium">
              {{ item.formattedWbsBelumInput }}
            </td>
            <td class="px-4 py-3.5 text-right font-medium">
              {{ item.formattedTotal }}
            </td>
            <td class="px-4 py-3.5 text-right font-medium">
              {{ item.formattedEstimasiKebutuhan }}
            </td>
            <td
              class="px-4 py-3.5 text-right font-bold"
              :class="
                item.selisih_variance >= 0
                  ? 'text-emerald-600'
                  : 'text-rose-600'
              "
            >
              {{ item.formattedSelisihVariance }}
            </td>
          </tr>
          <tr v-if="paginatedData.length === 0">
            <td colSpan="7" class="py-8 text-center text-slate-400">
              Tidak ada rincian anggaran yang sesuai.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div
      class="flex flex-col items-center justify-between gap-4 border-t border-slate-100 p-4 text-sm text-slate-500 sm:flex-row"
    >
      <div class="flex items-center space-x-3">
        <p>
          Showing
          <span class="font-semibold text-slate-700">{{
            sortedData.length > 0 ? (currentPage - 1) * perPage + 1 : 0
          }}</span>
          to
          <span class="font-semibold text-slate-700">{{
            Math.min(currentPage * perPage, sortedData.length)
          }}</span>
          of
          <span class="font-semibold text-slate-700">{{
            sortedData.length
          }}</span>
          entries
        </p>

        <div class="flex items-center space-x-1 border-l border-slate-200 pl-3">
          <span class="text-xs text-slate-400">Show:</span>
          <select
            v-model="perPage"
            class="cursor-pointer rounded border border-slate-200 bg-slate-50 px-2 py-0.5 text-xs font-semibold text-slate-700 focus:ring-1 focus:ring-blue-600 focus:outline-none"
          >
            <option :value="4">4</option>
            <option :value="8">8</option>
            <option :value="12">12</option>
            <option :value="100">All</option>
          </select>
        </div>
      </div>

      <div class="flex items-center space-x-1">
        <button
          type="button"
          @click="currentPage = Math.max(currentPage - 1, 1)"
          :disabled="currentPage === 1"
          class="rounded-md border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-100 disabled:opacity-50"
        >
          Previous
        </button>
        <button
          v-for="page in totalPages"
          :key="page"
          type="button"
          @click="currentPage = page"
          class="rounded-md px-3 py-1.5 text-xs font-semibold"
          :class="
            currentPage === page
              ? 'bg-blue-700 text-white'
              : 'border border-slate-200 text-slate-600 hover:bg-slate-100'
          "
        >
          {{ page }}
        </button>
        <button
          type="button"
          @click="currentPage = Math.min(currentPage + 1, totalPages)"
          :disabled="currentPage === totalPages || totalPages === 0"
          class="rounded-md border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-100 disabled:opacity-50"
        >
          Next
        </button>
      </div>
    </div>
  </section>
</template>
