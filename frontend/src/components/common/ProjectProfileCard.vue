<script setup>
defineProps({
  projectInfo: {
    type: Object,
    default: () => ({
      title: "Title Default",
      code: "Kode Default",
      sumberDana: "PDN",
      nilaiKontrak: 0,
      pimpinanProyek: "Pimpro Default",
      projectAnalyst: "PA Default",
    }),
  },
});

const formatNilaiKontrak = (nilai) => {
  if (nilai === null || nilai === undefined || nilai === "" || nilai === "-")
    return "-";

  let num;

  if (typeof nilai === "number") {
    num = nilai;
  } else if (typeof nilai === "string") {
    let s = nilai.replace(/^Rp\s*/i, "").trim();
    s = s.replace(/\s*[TtMmJj][a-zA-Z]*\s*$/, "").trim();

    const hasDot = s.includes(".");
    const hasComma = s.includes(",");

    if (hasDot && hasComma) {
      s = s.replace(/\./g, "").replace(",", ".");
    } else if (hasComma && !hasDot) {
      s = s.replace(",", ".");
    } else if (hasDot && !hasComma) {
      const parts = s.split(".");
      const isThousands = parts.length > 2 || parts[0].length > 3;
      if (isThousands) {
        s = s.replace(/\./g, "");
      }
    }

    num = Number(s);
  }

  if (!Number.isFinite(num) || num === 0) return "Rp 0";

  const fmt = (v) =>
    v % 1 === 0
      ? v.toFixed(0)
      : v
          .toFixed(2)
          .replace(/\.?0+$/, "")
          .replace(".", ",");

  if (num >= 1_000_000_000_000) return `Rp ${fmt(num / 1_000_000_000_000)} T`;
  if (num >= 1_000_000_000) return `Rp ${fmt(num / 1_000_000_000)} M`;
  if (num >= 1_000_000) return `Rp ${fmt(num / 1_000_000)} Jt`;

  return `Rp ${num.toLocaleString("id-ID")}`;
};
</script>

<template>
  <header
    class="bg-surface-container-lowest rounded-xl p-5 shadow-sm flex flex-col gap-4 mb-element-gap transition-all"
  >
    <!-- TOP TIER: Project Title & Project Code -->
    <div class="flex flex-wrap items-center gap-3">
      <h1 class="text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">
        {{ projectInfo.title }}
      </h1>
      <span
        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[12px] font-semibold bg-primary/10 text-primary border border-primary/20 tracking-wider font-label-caps"
      >
        {{ projectInfo.code }}
      </span>
    </div>

    <!-- BOTTOM TIER: Metadata Grid -->
    <div
      class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 sm:gap-4 pt-4"
    >
      <!-- Pimpinan Proyek -->
      <div class="flex items-center gap-3">
        <div
          class="w-9 h-9 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center shrink-0"
        >
          <span class="material-symbols-outlined text-[20px]">person</span>
        </div>
        <div>
          <p
            class="font-label-caps text-label-caps text-on-surface-variant uppercase"
          >
            Pimpinan Proyek
          </p>
          <p class="font-title-md text-body-base font-semibold text-on-surface">
            {{ projectInfo.pimpinanProyek }}
          </p>
        </div>
      </div>

      <!-- Project Analyst (PA) -->
      <div class="flex items-center gap-3">
        <div
          class="w-9 h-9 rounded-lg bg-secondary-container text-primary flex items-center justify-center shrink-0"
        >
          <span class="material-symbols-outlined text-[20px]"
            >supervisor_account</span
          >
        </div>
        <div>
          <p
            class="font-label-caps text-label-caps text-on-surface-variant uppercase"
          >
            Project Analyst
          </p>
          <p class="font-title-md text-body-base font-semibold text-on-surface">
            {{ projectInfo.projectAnalyst }}
          </p>
        </div>
      </div>

      <!-- Sumber Dana -->
      <div class="flex items-center gap-3">
        <div
          class="w-9 h-9 rounded-lg bg-lime-100 text-lime-700 flex items-center justify-center shrink-0"
        >
          <span class="material-symbols-outlined text-[20px]"
            >account_balance</span
          >
        </div>
        <div>
          <p
            class="font-label-caps text-label-caps text-on-surface-variant uppercase"
          >
            Sumber Dana
          </p>
          <p class="font-title-md text-body-base font-semibold text-on-surface">
            {{ projectInfo.sumberDana || "PDN" }}
          </p>
        </div>
      </div>

      <!-- Net Contract Value -->
      <div
        class="flex items-center gap-3 rounded-xl bg-gradient-to-r from-emerald-50/70 to-teal-50/40 px-4 py-2"
      >
        <div
          class="w-9 h-9 rounded-lg bg-emerald-700 text-white flex items-center justify-center shrink-0"
        >
          <span class="material-symbols-outlined text-[20px]">payments</span>
        </div>
        <div>
          <p
            class="font-label-caps text-label-caps text-emerald-800/80 uppercase"
          >
            Net Contract Value
          </p>
          <p
            class="font-title-md text-body-base font-semibold text-emerald-950"
          >
            {{ formatNilaiKontrak(projectInfo.nilaiKontrak) }}
          </p>
        </div>
      </div>
    </div>
  </header>
</template>
