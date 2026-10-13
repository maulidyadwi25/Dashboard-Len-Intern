import { budgetService } from "../services/budgetServices";
import { ref, computed, type Ref, onMounted, onBeforeUnmount } from "vue";

// Helper to compute today's date at midnight.
const computeToday = (): Date => {
  const d = new Date();
  d.setHours(0, 0, 0, 0);
  return d;
};

// Reactive today date updated periodically (every minute).
const todayRef = ref(computeToday());
let todayInterval: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
  // Update todayRef every minute to catch day changes.
  todayInterval = setInterval(() => {
    todayRef.value = computeToday();
  }, 60 * 1000);
});

onBeforeUnmount(() => {
  if (todayInterval) clearInterval(todayInterval);
});

// Exported helper returning the reactive today date.
export const getToday = (): Date => {
  return todayRef.value;
};

// 1. Buat Interface untuk struktur data
export interface ProcurementItem {
  id?: string | number;
  no?: string | number;
  nomor?: string | number;
  main_no: string | number;
  is_sub_item?: boolean;
  nama_pengadaan?: string;
  nama_vendor?: string;
  priority?: string;
  status?: string;
  start_date?: string;
  tgl_mulai?: string;
  target_tgl_pengadaan?: string;
  target_date?: string;
  tgl_selesai?: string;
  finish_date?: string;
  startCol?: number;
  spanCol?: number;
  subItems?: ProcurementItem[];
}

// 2. Beri tipe eksplisit pada parameter Composable
export function parseDate(
  dateStr: string | Date | null | undefined,
): Date | null {
  if (!dateStr || dateStr === "dd/mm/yyyy") return null;
  if (dateStr instanceof Date) return isNaN(dateStr.getTime()) ? null : dateStr;

  if (typeof dateStr === "string" && dateStr.includes("/")) {
    const [day, month, year] = dateStr.split("/").map(Number);
    if (day && month && year) {
      return new Date(year, month - 1, day);
    }
  }

  const parsed = new Date(dateStr);
  return isNaN(parsed.getTime()) ? null : parsed;
}

export function useProcurementGantt(
  itemsRef: Ref<ProcurementItem[]>,
  ganttContainerRef: Ref<HTMLElement | null>,
) {
  // Beri tipe pada ref yang awalnya berupa objek kosong
  const expandedGroups = ref<Record<string | number, boolean>>({});

  const activeFilter = ref<string>("All Priority");
  const filterOptions: string[] = ["All Priority", "High", "Medium", "Low"];

  const selectedStatus = ref<string>("");
  const statusOptions: string[] = [
    "In Progress",
    "Warning",
    "Overdue",
    "Open",
    "Done",
  ];

  const searchQuery = ref<string>("");
  const sortKey = ref<string>("no");
  const sortOrder = ref<string>("asc");

  const isFilterActive = computed<boolean>(() => {
    return (
      searchQuery.value !== "" ||
      activeFilter.value !== "All Priority" ||
      selectedStatus.value !== ""
    );
  });

  const resetFilters = (): void => {
    searchQuery.value = "";
    activeFilter.value = "All Priority";
    selectedStatus.value = "";
  };

  const handleSort = (key: string): void => {
    if (sortKey.value === key) {
      sortOrder.value = sortOrder.value === "asc" ? "desc" : "asc";
    } else {
      sortKey.value = key;
      sortOrder.value = "asc";
    }
  };

  const isSortActive = computed<boolean>(
    () => sortKey.value !== "no" || sortOrder.value !== "asc",
  );

  const resetSort = (): void => {
    sortKey.value = "no";
    sortOrder.value = "asc";
  };

  // Beri tipe parameter
  const toggleGroup = (mainNo: string | number): void => {
    expandedGroups.value[mainNo] = !expandedGroups.value[mainNo];
  };

  // Batasi opsi string (Literal Types) agar tidak bisa diisi string sembarangan
  const scrollGantt = (direction: "left" | "right"): void => {
    if (ganttContainerRef.value) {
      const amount = direction === "left" ? -350 : 350;
      ganttContainerRef.value.scrollBy({ left: amount, behavior: "smooth" });
    }
  };

  const ganttYear = computed<number>(() => {
    const firstItem = itemsRef.value?.find((i) => i.target_tgl_pengadaan);
    if (firstItem?.target_tgl_pengadaan) {
      const parts = firstItem.target_tgl_pengadaan.split("/");
      if (parts.length === 3) return parseInt(parts[2], 10);
    }
    return new Date().getFullYear();
  });

  const timelineMonths = computed(() => {
    const monthData = [
      { short: "Jan", full: "Januari" },
      { short: "Feb", full: "Februari" },
      { short: "Mar", full: "Maret" },
      { short: "Apr", full: "April" },
      { short: "Mei", full: "Mei" },
      { short: "Jun", full: "Juni" },
      { short: "Jul", full: "Juli" },
      { short: "Ags", full: "Agustus" },
      { short: "Sep", full: "September" },
      { short: "Okt", full: "Oktober" },
      { short: "Nov", full: "November" },
      { short: "Des", full: "Desember" },
    ];
    const currentMonth = new Date().getMonth();
    const year = ganttYear.value;

    return monthData.map((m, index) => ({
      name: m.short,
      fullName: m.full,
      monthNumber: String(index + 1).padStart(2, "0"),
      label: `${m.full} ${year}`,
      current: index === currentMonth,
      weeks: [
        { label: "W1" },
        { label: "W2" },
        { label: "W3" },
        { label: "W4" },
      ],
    }));
  });

  const getColFromDate = (dateStr?: string): number => {
    if (!dateStr) return 15;
    let parts: string[] | number[];
    if (dateStr.includes("-")) {
      parts = dateStr.split("-").map((p) => parseInt(p, 10));
      if (parts.length === 3) {
        const [, month, day] = parts as number[];
        let weekInMonth: number;
        if (day >= 1 && day <= 10) weekInMonth = 1;
        else if (day >= 11 && day <= 17) weekInMonth = 2;
        else if (day >= 18 && day <= 24) weekInMonth = 3;
        else weekInMonth = 4;
        return (month - 1) * 4 + weekInMonth;
      }
    } else if (dateStr.includes("/")) {
      parts = dateStr.split("/");
      if (parts.length === 3) {
        const month = parseInt(parts[1], 10);
        const day = parseInt(parts[0], 10);
        if (!isNaN(month) && month >= 1 && month <= 12) {
          let weekInMonth: number;
          if (day >= 1 && day <= 10) weekInMonth = 1;
          else if (day >= 11 && day <= 17) weekInMonth = 2;
          else if (day >= 18 && day <= 24) weekInMonth = 3;
          else weekInMonth = 4;
          return (month - 1) * 4 + weekInMonth;
        }
      }
    }
    return 15;
  };

  const formatToNativeDate = (dateStr?: string): string => {
    if (!dateStr) return "";
    if (dateStr.includes("/")) {
      const parts = dateStr.split("/");
      if (parts.length === 3) return `${parts[2]}-${parts[1]}-${parts[0]}`;
    }
    return dateStr;
  };

  const formatToAppDate = (nativeDate?: string): string => {
    if (!nativeDate) return "";
    if (nativeDate.includes("-")) {
      const parts = nativeDate.split("-");
      if (parts.length === 3) return `${parts[2]}/${parts[1]}/${parts[0]}`;
    }
    return nativeDate;
  };

  const parseDateStr = parseDate;

  const todayCol = computed<number>(() => {
    const today = getToday();
    const day = today.getDate();
    const month = today.getMonth() + 1;
    return getColFromDate(
      `${String(day).padStart(2, "0")}/${String(month).padStart(2, "0")}/${today.getFullYear()}`,
    );
  });

  const todayFraction = computed<number>(() => {
    const today = getToday();
    const day = today.getDate();
    if (day >= 1 && day <= 10) return (day - 1) / 10;
    if (day >= 11 && day <= 17) return (day - 11) / 7;
    if (day >= 18 && day <= 24) return (day - 18) / 7;
    return (day - 25) / 7;
  });

  const groupedProcurements = computed<ProcurementItem[]>(() => {
    const groups: ProcurementItem[] = [];
    const groupMap: Record<string | number, ProcurementItem> = {};

    if (!itemsRef.value || itemsRef.value.length === 0) {
      return [];
    }

    // Filter di level item mentah agar tidak ada item dengan priority/status lain yang bocor
    let rawItems = itemsRef.value;

    if (activeFilter.value !== "All Priority") {
      const p = activeFilter.value.toLowerCase();
      rawItems = rawItems.filter((i) => (i.priority || "").toLowerCase() === p);
    }

    if (selectedStatus.value) {
      const s = selectedStatus.value.toLowerCase();
      rawItems = rawItems.filter((i) => (i.status || "").toLowerCase() === s);
    }

    if (searchQuery.value) {
      const q = searchQuery.value.toLowerCase();
      rawItems = rawItems.filter(
        (i) =>
          (i.nama_pengadaan || "").toLowerCase().includes(q) ||
          (i.no || "").toString().toLowerCase().includes(q) ||
          (i.nama_vendor || "").toLowerCase().includes(q) ||
          (i.status || "").toLowerCase().includes(q) ||
          (i.priority || "").toLowerCase().includes(q),
      );
    }

    rawItems.forEach((item) => {
      const targetCol = getColFromDate(item.target_tgl_pengadaan);
      const startColParsed = getColFromDate(item.start_date);

      if (!item.is_sub_item) {
        if (expandedGroups.value[item.main_no] === undefined) {
          expandedGroups.value[item.main_no] = true;
        }

        groupMap[item.main_no] = {
          ...item,
          startCol: Math.max(1, startColParsed),
          spanCol: Math.max(1, targetCol - startColParsed),
          subItems: [],
          priority: item.priority,
        };
        groups.push(groupMap[item.main_no]);
      } else {
        const subObj = {
          ...item,
          startCol: startColParsed,
          spanCol: Math.max(1, targetCol - startColParsed),
          priority: item.priority,
        };
        if (groupMap[item.main_no]) {
          const parent = groupMap[item.main_no];
          parent.subItems!.push(subObj);

          // Parent bar wraps the union of every sub-item bar, not just the last
          // one seen. Columns are absolute, so the parent may also need to grow
          // leftwards when a sub-item starts before it.
          const parentStart = parent.startCol ?? 1;
          const parentEnd = parentStart + (parent.spanCol ?? 1);
          const subStart = startColParsed;
          const subEnd = startColParsed + (subObj.spanCol ?? 1);

          const unionStart = Math.min(parentStart, subStart);
          const unionEnd = Math.max(parentEnd, subEnd);

          parent.startCol = Math.max(1, unionStart);
          parent.spanCol = Math.max(1, unionEnd - parent.startCol);
        } else {
          groups.push({
            ...subObj,
            subItems: [],
          });
        }
      }
    });

    let result = groups;

    const dateFields = ["start_date", "target_tgl_pengadaan", "tgl_selesai"];

    result.sort((a, b) => {
      let aVal: any = a[sortKey.value as keyof ProcurementItem];
      let bVal: any = b[sortKey.value as keyof ProcurementItem];

      if (sortKey.value === "no" || sortKey.value === "nomor") {
        const valA = a.no || a.nomor;
        const valB = b.no || b.nomor;
        const numA = parseFloat(String(valA ?? 0));
        const numB = parseFloat(String(valB ?? 0));

        if (numA !== numB) {
          return sortOrder.value === "asc" ? numA - numB : numB - numA;
        }

        const res = String(valA ?? "").localeCompare(
          String(valB ?? ""),
          undefined,
          {
            numeric: true,
          },
        );
        return sortOrder.value === "asc" ? res : -res;
      }

      if (sortKey.value === "start_date") {
        aVal = a.start_date || a.tgl_mulai;
        bVal = b.start_date || b.tgl_mulai;
      } else if (sortKey.value === "tgl_selesai") {
        aVal = a.tgl_selesai || a.finish_date;
        bVal = b.tgl_selesai || b.finish_date;
      }

      if (dateFields.includes(sortKey.value)) {
        const dateA = parseDate(aVal as string);
        const dateB = parseDate(bVal as string);
        if (dateA && dateB)
          return sortOrder.value === "asc" ? dateA - dateB : dateB - dateA;
        if (dateA && !dateB) return sortOrder.value === "asc" ? -1 : 1;
        if (!dateA && dateB) return sortOrder.value === "asc" ? 1 : -1;
      }

      const aStr = String(aVal ?? "").toLowerCase();
      const bStr = String(bVal ?? "").toLowerCase();

      if (aStr < bStr) return sortOrder.value === "asc" ? -1 : 1;
      if (aStr > bStr) return sortOrder.value === "asc" ? 1 : -1;
      return 0;
    });

    return result;
  });

  const getStatusBadgeClass = (status?: string): string => {
    switch ((status || "").toLowerCase()) {
      case "done":
      case "selesai":
      case "completed":
        return "bg-emerald-50 text-emerald-700 border-emerald-200";
      case "overdue":
        return "bg-rose-50 text-rose-700 border-rose-200";
      case "warning":
        return "bg-amber-50 text-amber-700 border-amber-200";
      case "in progress":
      case "in_progress":
        return "bg-blue-50 text-blue-700 border-blue-200";
      case "open":
        return "bg-slate-50 text-slate-700 border-slate-200";
      default:
        return "bg-slate-50 text-slate-700 border-slate-200";
    }
  };

  const getPriorityBadgeClass = (priority?: string): string => {
    switch ((priority || "").toLowerCase()) {
      case "high":
        return "bg-rose-50 text-rose-700 border-rose-200";
      case "medium":
        return "bg-amber-50 text-amber-700 border-amber-200";
      case "low":
        return "bg-blue-50 text-blue-700 border-blue-200";
      default:
        return "bg-slate-50 text-slate-700 border-slate-200";
    }
  };

  const isDone = (item) => {
    const status = (item.status || "").toLowerCase();
    if (status === "done" || status === "selesai" || status === "completed")
      return true;
    return !!(item.tgl_selesai || item.finish_date);
  };

  const computedStatus = (item) => {
    const status = (item.status || "").toLowerCase();

    // Derived DONE — tetap diprioritaskan karena berdasarkan logika aplikasi
    if (isDone(item)) return "DONE";

    // Pertahankan status asli dari data source (sudah dinormalisasi backend)
    if (status === "warning") return "Warning";
    if (status === "open") return "Open";
    if (status === "overdue") return "Overdue";
    if (status === "in progress" || status === "in_progress")
      return "In Progress";

    // Fallback derived statuses
    const targetDate = parseDate(item.target_tgl_pengadaan);
    if (targetDate && getToday() > targetDate) return "OVERDUE";
    return "IN PROGRESS";
  };

  const barStartDate = (item) => {
    const startDate = parseDate(item.start_date);
    if (startDate) return startDate;
    const targetDate = parseDate(item.target_tgl_pengadaan);
    if (targetDate) return targetDate;
    return getToday();
  };

  const barEndDate = (item) => {
    const finishDate = parseDate(item.finish_date || item.tgl_selesai);
    const targetDate = parseDate(item.target_tgl_pengadaan || item.target_date);

    // If already DONE, use finishDate or fallback to targetDate or today
    if (isDone(item)) {
      return finishDate || targetDate || getToday();
    }

    // Not DONE (IN PROGRESS / OVERDUE) -> end at today
    return getToday();
  };

  const getGanttBarClass = (item) => {
    const status = computedStatus(item);
    if (status === "DONE")
      return "bg-emerald-500 border-emerald-600 text-white shadow-sm";
    if (status === "OVERDUE")
      return "bg-rose-500 border-rose-600 text-white animate-pulse";
    if (getToday() < barStartDate(item))
      return "bg-slate-300 border-slate-400 text-slate-700";
    return "bg-sky-500 border-sky-600 text-white";
  };

  // Type event sebagai Event bawaan DOM, lalu di-cast menjadi HTMLInputElement
  const updateItemStartDate = async (
    item: ProcurementItem,
    event: Event,
  ): Promise<void> => {
    const target = event.target as HTMLInputElement;
    const newDateVal = target.value;
    const appDateStr = formatToAppDate(newDateVal);

    const index = itemsRef.value.findIndex(
      (i) => i.id === item.id || i.no === item.no,
    );

    if (index !== -1) {
      const originalDate = itemsRef.value[index].start_date;
      itemsRef.value[index].start_date = appDateStr;

      try {
        await budgetService.updateProcurementStartDate(item.id, newDateVal);
      } catch (error) {
        itemsRef.value[index].start_date = originalDate;
        alert("Gagal menyimpan start date. Perubahan dibatalkan.");
      }
    }
  };

  return {
    expandedGroups,
    activeFilter,
    filterOptions,
    selectedStatus,
    statusOptions,
    searchQuery,
    isFilterActive,
    resetFilters,
    toggleGroup,
    scrollGantt,
    ganttYear,
    timelineMonths,
    todayCol,
    todayFraction,
    groupedProcurements,
    getStatusBadgeClass,
    getPriorityBadgeClass,
    computedStatus,
    isDone,
    barStartDate,
    barEndDate,
    getGanttBarClass,
    updateItemStartDate,
    formatToNativeDate,
    sortKey,
    sortOrder,
    handleSort,
    isSortActive,
    resetSort,
  };
}
