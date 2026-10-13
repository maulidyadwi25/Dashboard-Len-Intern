<script setup>
import { computed } from "vue";

const props = defineProps({
  items: {
    type: Array,
    default: () => [],
  },
});

// 1. Helper: Cek apakah paket Delay
const isDelayed = (dateStr, status) => {
  if ((status || "").toUpperCase() === "DONE") return false;
  if (!dateStr || dateStr === "-") return false;

  const parts = dateStr.split("/");
  if (parts.length === 3) {
    const targetDate = new Date(parts[2], parts[1] - 1, parts[0]);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return targetDate < today;
  }
  return false;
};

// 2. Helper: Kategori Status
const getCategorizedStatus = (item) => {
  const status = (item.status || "").toUpperCase();
  if (status === "DONE") return "ON_TRACK";
  if (isDelayed(item.target_tgl_pengadaan, status)) return "DELAY";
  return "ON_GOING";
};

// 3. Filter Hanya Paket Utama
const mainPackages = computed(() => {
  if (!props.items.length) return [];
  return props.items.filter((i) => !i.is_sub_item);
});

const totalPackages = computed(() => {
  return mainPackages.value.length || 120;
});

// 4. Kalkulasi Data Donut Chart
const statusCounts = computed(() => {
  const items = mainPackages.value.length ? mainPackages.value : [];

  let onTrack = 0,
    onGoing = 0,
    delay = 0;

  items.forEach((i) => {
    const cat = getCategorizedStatus(i);
    if (cat === "ON_TRACK") onTrack++;
    else if (cat === "DELAY") delay++;
    else onGoing++;
  });

  if (!items.length) {
    onTrack = 68;
    onGoing = 34;
    delay = 18;
  }

  const total = onTrack + onGoing + delay;

  return {
    onTrack,
    onGoing,
    delay,
    total,
    onTrackPct: total ? ((onTrack / total) * 100).toFixed(1) : 0,
    onGoingPct: total ? ((onGoing / total) * 100).toFixed(1) : 0,
    delayPct: total ? ((delay / total) * 100).toFixed(1) : 0,
  };
});

// Warna config dikembalikan ke asalnya (Blue, Slate/Black, Rose/Red)
const colorConfig = {
  onTrack: {
    stroke: "#2563eb",
    bg: "bg-blue-600",
    text: "text-blue-600",
    border: "border-blue-600",
    shadow: "shadow-blue-100/50",
  },
  ongoing: {
    stroke: "#334155",
    bg: "bg-slate-700",
    text: "text-slate-700",
    border: "border-slate-700",
    shadow: "shadow-slate-200/50",
  },
  delay: {
    stroke: "#e11d48",
    bg: "bg-rose-600",
    text: "text-rose-600",
    border: "border-rose-600",
    shadow: "shadow-rose-100/50",
  },
};

// 5. Kalkulasi Data Matrix
const matrixData = computed(() => {
  const items = mainPackages.value.length ? mainPackages.value : [];

  if (!items.length) {
    return [
      {
        category: "Barang (Hardware & Satellite)",
        total: 8,
        onTrack: 5,
        ongoing: 2,
        delay: 1,
      },
      {
        category: "Jasa (Consulting & Maintenance)",
        total: 10,
        onTrack: 7,
        ongoing: 2,
        delay: 1,
      },
      { category: "Barang & Jasa", total: 9, onTrack: 1, ongoing: 8, delay: 0 },
    ];
  }

  const calc = (list, name) => {
    let onTrack = 0,
      ongoing = 0,
      delay = 0;
    list.forEach((i) => {
      const cat = getCategorizedStatus(i);
      if (cat === "ON_TRACK") onTrack++;
      else if (cat === "DELAY") delay++;
      else ongoing++;
    });
    return { category: name, total: list.length, onTrack, ongoing, delay };
  };

  const barang = items.filter(
    (i) => (i.jenis || "").toUpperCase().trim() === "BARANG",
  );
  const jasa = items.filter(
    (i) => (i.jenis || "").toUpperCase().trim() === "JASA",
  );
  const barangJasa = items.filter(
    (i) => (i.jenis || "").toUpperCase().trim() === "BARANG & JASA",
  );
  const lainnya = items.filter(
    (i) =>
      !["BARANG", "JASA", "BARANG & JASA"].includes(
        (i.jenis || "").toUpperCase().trim(),
      ),
  );

  return [
    calc(barang, "Pengadaan Barang"),
    calc(jasa, "Pengadaan Jasa"),
    calc(barangJasa, "Pengadaan Barang & Jasa"),
    calc(lainnya, "Lainnya"),
  ].filter((g) => g.total > 0);
});
</script>

<template>
  <div class="mb-8 font-sans">
    <div class="mb-6">
      <h3 class="text-lg font-extrabold text-slate-900">
        Procurement Status & Monitoring
      </h3>
      <p class="text-sm text-slate-500 font-medium">
        Analitik sebaran pengadaan, rasio komitmen, dan deviasi jadwal real-time
      </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      <!-- KIRI: Status Procurement Card -->
      <div
        class="lg:col-span-6 xl:col-span-5 bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex flex-col"
      >
        <h4
          class="text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-8 text-center sm:text-left"
        >
          Status Procurement
        </h4>

        <!-- Wrapper diperkecil max-width nya -->
        <div
          class="relative w-full max-w-[280px] mx-auto mt-2 mb-8 aspect-square flex items-center justify-center"
        >
          <!-- Base Donut SVG (diperkecil ukurannya menjadi w-36 sm:w-40) -->
          <div
            class="relative w-36 h-36 sm:w-40 sm:h-40 flex items-center justify-center z-0"
          >
            <svg
              class="w-full h-full transform -rotate-90 drop-shadow-sm"
              viewBox="-4 -4 44 44"
            >
              <!-- Background Ring -->
              <path
                class="text-slate-100"
                stroke-width="5"
                stroke="currentColor"
                fill="none"
                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
              />

              <!-- On Track Segment (Blue) -->
              <path
                v-if="statusCounts.onTrackPct > 0"
                :stroke="colorConfig.onTrack.stroke"
                stroke-width="5"
                :stroke-dasharray="`${statusCounts.onTrackPct}, 100`"
                stroke-linecap="round"
                fill="none"
                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
              />

              <!-- Ongoing Segment (Slate/Black) -->
              <path
                v-if="statusCounts.onGoingPct > 0"
                :stroke="colorConfig.ongoing.stroke"
                stroke-width="5"
                :stroke-dasharray="`${statusCounts.onGoingPct}, 100`"
                :stroke-dashoffset="`-${statusCounts.onTrackPct}`"
                stroke-linecap="round"
                fill="none"
                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
              />

              <!-- Delayed Segment (Rose/Red) -->
              <path
                v-if="statusCounts.delayPct > 0"
                :stroke="colorConfig.delay.stroke"
                stroke-width="5"
                :stroke-dasharray="`${statusCounts.delayPct}, 100`"
                :stroke-dashoffset="`-${parseFloat(statusCounts.onTrackPct) + parseFloat(statusCounts.onGoingPct)}`"
                stroke-linecap="round"
                fill="none"
                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
              />
            </svg>

            <!-- Teks Tengah (TOTAL 120 PAKET) - Ukuran font sedikit dikecilkan -->
            <div
              class="absolute inset-0 flex flex-col items-center justify-center text-center mt-1"
            >
              <span
                class="text-[10px] font-bold text-slate-400 uppercase tracking-widest"
                >TOTAL</span
              >
              <span
                class="text-2xl font-black text-slate-900 leading-none my-1"
                >{{ totalPackages }}</span
              >
              <span
                class="bg-slate-100/80 text-slate-500 px-3 py-0.5 mt-0.5 rounded-full text-[9px] font-bold tracking-widest"
              >
                PAKET
              </span>
            </div>
          </div>

          <!-- FLOATING LABELS (Posisi disesuaikan dengan lingkaran yang lebih kecil) -->

          <!-- On Track (Kanan Atas) -->
          <div
            :class="[
              'absolute -top-1 -right-2 sm:top-2 sm:-right-6 z-10 flex items-center gap-2 bg-white border-2 px-3 py-1.5 rounded-xl shadow-lg',
              colorConfig.onTrack.border,
              colorConfig.onTrack.shadow,
            ]"
          >
            <div
              :class="['w-2 h-2 rounded-full', colorConfig.onTrack.bg]"
            ></div>
            <div class="flex flex-col text-left leading-tight">
              <span class="font-bold text-slate-800 text-sm"
                >{{ statusCounts.onTrack }}
                <span
                  :class="[colorConfig.onTrack.text, 'font-semibold text-xs']"
                  >({{ statusCounts.onTrackPct }}%)</span
                ></span
              >
              <span
                :class="[
                  colorConfig.onTrack.text,
                  'text-[9px] font-bold tracking-wider uppercase',
                ]"
                >ON TRACK</span
              >
            </div>
          </div>

          <!-- Delayed (Kiri Tengah) -->
          <div
            :class="[
              'absolute top-[35%] -left-6 sm:-left-10 z-10 flex items-center gap-2 bg-white border-2 px-3 py-1.5 rounded-xl shadow-lg',
              colorConfig.delay.border,
              colorConfig.delay.shadow,
            ]"
          >
            <div :class="['w-2 h-2 rounded-full', colorConfig.delay.bg]"></div>
            <div class="flex flex-col text-left leading-tight">
              <span class="font-bold text-slate-800 text-sm"
                >{{ statusCounts.delay }}
                <span :class="[colorConfig.delay.text, 'font-semibold text-xs']"
                  >({{ statusCounts.delayPct }}%)</span
                ></span
              >
              <span
                :class="[
                  colorConfig.delay.text,
                  'text-[9px] font-bold tracking-wider uppercase',
                ]"
                >DELAYED</span
              >
            </div>
          </div>

          <!-- On Going (Kanan Bawah) -->
          <div
            :class="[
              'absolute -bottom-2 -right-2 sm:bottom-2 sm:-right-6 z-10 flex items-center gap-2 bg-white border-2 px-3 py-1.5 rounded-xl shadow-lg',
              colorConfig.ongoing.border,
              colorConfig.ongoing.shadow,
            ]"
          >
            <div
              :class="['w-2 h-2 rounded-full', colorConfig.ongoing.bg]"
            ></div>
            <div class="flex flex-col text-left leading-tight">
              <span class="font-bold text-slate-800 text-sm"
                >{{ statusCounts.onGoing }}
                <span
                  :class="[colorConfig.ongoing.text, 'font-semibold text-xs']"
                  >({{ statusCounts.onGoingPct }}%)</span
                ></span
              >
              <span
                :class="[
                  colorConfig.ongoing.text,
                  'text-[9px] font-bold tracking-wider uppercase',
                ]"
                >ON GOING</span
              >
            </div>
          </div>
        </div>
      </div>

      <!-- KANAN: Procurement Matrix Card (Menggunakan warna original) -->
      <div
        class="lg:col-span-6 xl:col-span-7 bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex flex-col"
      >
        <h4
          class="text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-6"
        >
          Procurement Matrix Status
        </h4>

        <div class="space-y-6">
          <div
            v-for="item in matrixData"
            :key="item.category"
            class="space-y-2.5"
          >
            <div
              class="flex flex-col sm:flex-row sm:items-center justify-between text-xs gap-2"
            >
              <span class="font-bold text-slate-800 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-slate-300"></span>
                {{ item.category }}
                <span class="text-slate-400 font-normal"
                  >({{ item.total }} Paket)</span
                >
              </span>
              <span class="text-[11px] font-semibold text-slate-500">
                <span :class="[colorConfig.onTrack.text, 'font-bold']">{{
                  item.onTrack
                }}</span>
                On Track &bull;
                <span :class="[colorConfig.ongoing.text, 'font-bold']">{{
                  item.ongoing
                }}</span>
                Ongoing &bull;
                <span :class="[colorConfig.delay.text, 'font-bold']">{{
                  item.delay
                }}</span>
                Delay
              </span>
            </div>

            <!-- Segmented Progress Bar -->
            <div
              class="h-3 w-full bg-slate-100 rounded-full overflow-hidden flex p-0.5 border border-slate-200/60"
            >
              <div
                :class="[
                  'h-full rounded-l-full transition-all duration-300',
                  colorConfig.onTrack.bg,
                ]"
                :style="{ width: `${(item.onTrack / item.total) * 100}%` }"
                title="On Track"
              ></div>
              <div
                :class="[
                  'h-full transition-all duration-300',
                  colorConfig.ongoing.bg,
                ]"
                :style="{ width: `${(item.ongoing / item.total) * 100}%` }"
                title="Ongoing"
              ></div>
              <div
                :class="[
                  'h-full rounded-r-full transition-all duration-300',
                  colorConfig.delay.bg,
                ]"
                :style="{ width: `${(item.delay / item.total) * 100}%` }"
                title="Delay"
              ></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
