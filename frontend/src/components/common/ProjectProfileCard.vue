<script setup>
import { FileText, CreditCard, User, UserCheck } from "lucide-vue-next";

defineProps({
  projectInfo: {
    type: Object,
    default: () => ({
      title: "Title Default",
      code: "Kode Default",
      nilaiKontrak: 0, // angka mentah (IDR) atau string format Indonesia "Rp 1.500.000.000"
      pimpinanProyek: "Pimpro Default",
      projectAnalyst: "PA Default",
    }),
  },
});

// Format nilai kontrak → T / M / Jt, tanpa desimal nol ekor
const formatNilaiKontrak = (nilai) => {
  if (nilai === null || nilai === undefined || nilai === "" || nilai === "-") return "-";

  let num;

  if (typeof nilai === "number") {
    num = nilai;
  } else if (typeof nilai === "string") {
    // Hapus prefix "Rp" dan spasi
    let s = nilai.replace(/^Rp\s*/i, "").trim();

    // Lepas sufiks satuan (T, M, Jt, Miliar, Triliun, dst.)
    s = s.replace(/\s*[TtMmJj][a-zA-Z]*\s*$/, "").trim();

    const hasDot   = s.includes(".");
    const hasComma = s.includes(",");

    if (hasDot && hasComma) {
      // "1.500.000.000,50" → titik=ribuan, koma=desimal
      s = s.replace(/\./g, "").replace(",", ".");
    } else if (hasComma && !hasDot) {
      // "7,78" → koma=desimal Indonesia
      s = s.replace(",", ".");
    } else if (hasDot && !hasComma) {
      // "150.000.000.000" dari toLocaleString("id-ID") → titik=ribuan
      // BUKAN JS float (JS float tidak punya banyak titik)
      // Heuristik: jika ada lebih dari 1 titik, atau titik setelah >3 digit → ribuan
      const parts = s.split(".");
      const isThousands = parts.length > 2 || (parts[0].length > 3);
      if (isThousands) {
        s = s.replace(/\./g, "");
      }
      // else: 1 titik dengan ≤3 digit sebelumnya → JS float, parse langsung
    }

    num = Number(s);
  }

  if (!Number.isFinite(num) || num === 0) return "Rp 0";

  const fmt = (v) =>
    v % 1 === 0 ? v.toFixed(0) : v.toFixed(2).replace(/\.?0+$/, "").replace(".", ",");

  if (num >= 1_000_000_000_000) return `Rp ${fmt(num / 1_000_000_000_000)} T`;
  if (num >= 1_000_000_000)     return `Rp ${fmt(num / 1_000_000_000)} M`;
  if (num >= 1_000_000)         return `Rp ${fmt(num / 1_000_000)} Jt`;

  return `Rp ${num.toLocaleString("id-ID")}`;
};
</script>

<template>
  <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs mb-6">
    <div
      class="flex flex-col lg:flex-row lg:items-center justify-between gap-6"
    >
      <!-- Left: Title & Code -->
      <div class="space-y-2">
        <div
          class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 text-[11px] font-bold uppercase tracking-wider"
        >
          <FileText class="w-3.5 h-3.5" />
          Project Profile
        </div>
        <div class="flex flex-wrap items-center gap-3">
          <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">
            {{ projectInfo.title }}
          </h2>
          <span
            class="px-2.5 py-1 rounded-md bg-blue-50 text-blue-600 font-mono text-xs font-bold border border-blue-200/80"
          >
            # {{ projectInfo.code }}
          </span>
        </div>
      </div>

      <!-- Right: Metadata Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <!-- Nilai Kontrak -->
        <div
          class="bg-slate-50/80 border border-slate-100 rounded-xl p-3 flex items-center gap-3"
        >
          <div
            class="w-10 h-10 rounded-lg bg-emerald-100/70 text-emerald-600 flex items-center justify-center shrink-0"
          >
            <CreditCard class="w-5 h-5" />
          </div>
          <div>
            <p
              class="text-[10px] font-bold text-slate-500 uppercase tracking-wider"
            >
              Nilai Kontrak
            </p>
            <p class="text-sm font-extrabold text-slate-900 mt-0.5">
              {{ formatNilaiKontrak(projectInfo.nilaiKontrak) }}
            </p>
          </div>
        </div>

        <!-- Pimpinan Proyek -->
        <div
          class="bg-slate-50/80 border border-slate-100 rounded-xl p-3 flex items-center gap-3"
        >
          <div
            class="w-10 h-10 rounded-lg bg-slate-200/70 text-slate-700 flex items-center justify-center shrink-0"
          >
            <User class="w-5 h-5" />
          </div>
          <div>
            <p
              class="text-[10px] font-bold text-slate-500 uppercase tracking-wider"
            >
              Pimpinan Proyek
            </p>
            <p
              class="text-sm font-bold text-slate-900 mt-0.5 truncate max-w-[130px]"
            >
              {{ projectInfo.pimpinanProyek }}
            </p>
          </div>
        </div>

        <!-- Project Analyst -->
        <div
          class="bg-slate-50/80 border border-slate-100 rounded-xl p-3 flex items-center gap-3"
        >
          <div
            class="w-10 h-10 rounded-lg bg-slate-200/70 text-slate-700 flex items-center justify-center shrink-0"
          >
            <UserCheck class="w-5 h-5" />
          </div>
          <div>
            <p
              class="text-[10px] font-bold text-slate-500 uppercase tracking-wider"
            >
              Project Analyst
            </p>
            <p
              class="text-sm font-bold text-slate-900 mt-0.5 truncate max-w-[130px]"
            >
              {{ projectInfo.projectAnalyst }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
