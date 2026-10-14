import jsPDF from "jspdf";
import autoTable from "jspdf-autotable";

export const generateBudgetPdf = ({
  selectedCurrency,
  metadata,
  summary,
  budgetList,
  formatCurrency,
}) => {
  const doc = new jsPDF("landscape", "pt", "a4");
  const curr = selectedCurrency;
  const meta = metadata;
  const sum = summary;
  const items = budgetList;

  const formatPdfCurrency = (value) => {
    const formatted = formatCurrency(value);
    return formatted.replace("€", "€ ").replace("S$", "S$ ").replace("$", "$ ");
  };

  doc.setFont("helvetica", "bold");
  doc.setFontSize(16);
  doc.setTextColor(15, 23, 42);
  doc.text(`EXECUTIVE BUDGET MONITORING REPORT (${curr})`, 40, 45);
  doc.setDrawColor(0, 82, 204);
  doc.setLineWidth(1.5);
  doc.line(40, 52, 802, 52);

  doc.setFont("helvetica", "normal");
  doc.setFontSize(9.5);
  doc.setTextColor(71, 85, 105);
  doc.text(`Nama Proyek : ${meta.projectName} [${meta.projectCode}]`, 40, 70);
  doc.text(`Last Update  : ${meta.lastUpdate}`, 40, 84);
  doc.text(`Currency     : ${curr}`, 40, 98);
  doc.text(`Tgl Cetak    : ${new Date().toLocaleDateString("id-ID")}`, 650, 70);

  autoTable(doc, {
    startY: 112,
    head: [
      [
        `Sisa SAP (${curr})`,
        `WBS Belum Input (${curr})`,
        `Total (${curr})`,
        `Estimasi Kebutuhan (${curr})`,
        "Selisih (Variance)",
      ],
    ],
    body: [
      [
        formatPdfCurrency(sum.sisa_anggaran_sap),
        formatPdfCurrency(sum.wbs_belum_input),
        formatPdfCurrency(sum.total),
        formatPdfCurrency(sum.estimasi_kebutuhan),
        (sum.selisih_variance >= 0 ? "+" : "") +
          formatPdfCurrency(sum.selisih_variance),
      ],
    ],
    theme: "grid",
    headStyles: {
      fillColor: [0, 82, 204],
      textColor: 255,
      fontStyle: "bold",
      halign: "center",
    },
    bodyStyles: {
      halign: "center",
      fontStyle: "bold",
      textColor: [30, 41, 59],
    },
    styles: { fontSize: 9, cellPadding: 6 },
  });

  autoTable(doc, {
    startY: doc.lastAutoTable.finalY + 20,
    head: [
      [
        "No.",
        "WBS Element",
        `Sisa SAP (${curr})`,
        `Belum Input (${curr})`,
        `Total (${curr})`,
        `Kebutuhan (${curr})`,
        "Variance",
      ],
    ],
    body: items.map((item) => [
      item.no,
      item.wbs_element,
      formatPdfCurrency(item.sisa_anggaran_sap),
      formatPdfCurrency(item.wbs_belum_input),
      formatPdfCurrency(item.total),
      formatPdfCurrency(item.estimasi_kebutuhan),
      (item.selisih_variance >= 0 ? "+" : "") +
        formatPdfCurrency(item.selisih_variance),
    ]),
    theme: "striped",
    headStyles: { fillColor: [30, 41, 59], textColor: 255, fontStyle: "bold" },
    columnStyles: {
      0: { halign: "center", cellWidth: 35 },
      1: { cellWidth: 170, fontStyle: "bold" },
      2: { halign: "right" },
      3: { halign: "right" },
      4: { halign: "right" },
      5: { halign: "right" },
      6: { halign: "right", fontStyle: "bold" },
    },
    styles: { fontSize: 8.5, cellPadding: 5 },
  });

  const safeProjectName = meta.projectName.replace(/[^a-zA-Z0-9_-]/g, "_");
  doc.save(
    `Budget_Report_${curr}_${safeProjectName}_${new Date().toISOString().slice(0, 10)}.pdf`,
  );
};

export const generateProcurementPdf = ({
  items = [],
  projectName = "",
}) => {
  const doc = new jsPDF("landscape", "pt", "a4");

  doc.setFont("helvetica", "bold");
  doc.setFontSize(16);
  doc.setTextColor(15, 23, 42);
  doc.text("Procurement Monitoring Report", 40, 45);

  doc.setDrawColor(0, 82, 204);
  doc.setLineWidth(1.5);
  doc.line(40, 52, 802, 52);

  doc.setFont("helvetica", "normal");
  doc.setFontSize(9.5);
  doc.setTextColor(71, 85, 105);

  if (projectName) {
    doc.text(`Proyek: ${projectName}`, 40, 70);
  }

  const now = new Date();
  const dateStr = now.toLocaleDateString("id-ID", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  });
  const timeStr = now.toLocaleTimeString("id-ID", {
    hour: "2-digit",
    minute: "2-digit",
  });
  doc.text(`Tgl Cetak: ${dateStr} ${timeStr}`, 650, 70);

  const getStatus = (item) => {
    const status = (item.status || "").toLowerCase();
    if (status === "done" || status === "selesai" || status === "completed") {
      return "DONE";
    }
    if (item.tgl_selesai || item.finish_date) {
      return "DONE";
    }

    const dateStr = item.target_tgl_pengadaan || item.target_date;
    if (dateStr && typeof dateStr === "string" && dateStr.includes("/")) {
      const [day, month, year] = dateStr.split("/").map(Number);
      if (day && month && year) {
        const targetDate = new Date(year, month - 1, day);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        if (today > targetDate) {
          return "OVERDUE";
        }
      }
    }
    return "IN PROGRESS";
  };

  const tableBody = items.map((item, index) => {
    const noVal = item.no || item.nomor || index + 1;
    const nameVal = item.nama_pengadaan || "-";
    const vendorVal = item.nama_vendor || "-";
    const startVal = item.tgl_mulai || item.start_date || "-";
    const targetVal = item.target_tgl_pengadaan || item.target_date || "-";
    const finishVal = item.tgl_selesai || item.finish_date || "-";
    const statusVal = getStatus(item);

    return [noVal, nameVal, vendorVal, startVal, targetVal, finishVal, statusVal];
  });

  autoTable(doc, {
    startY: projectName ? 85 : 70,
    head: [
      [
        "No",
        "Nama Pengadaan",
        "Vendor",
        "Start Date",
        "Target",
        "Finish",
        "Status",
      ],
    ],
    body: tableBody,
    theme: "striped",
    headStyles: {
      fillColor: [30, 41, 59],
      textColor: 255,
      fontStyle: "bold",
      halign: "center",
      fontSize: 9,
    },
    bodyStyles: {
      textColor: [30, 41, 59],
      fontSize: 8.5,
    },
    columnStyles: {
      0: { halign: "center", cellWidth: 35 },
      1: { cellWidth: 230, fontStyle: "bold" },
      2: { cellWidth: 160 },
      3: { halign: "center", cellWidth: 80 },
      4: { halign: "center", cellWidth: 80 },
      5: { halign: "center", cellWidth: 80 },
      6: { halign: "center", cellWidth: 70, fontStyle: "bold" },
    },
    styles: {
      cellPadding: 6,
      overflow: "linebreak",
    },
    margin: { top: 40, left: 40, right: 40, bottom: 40 },
  });

  const safeProjectName = projectName
    ? projectName.replace(/[^a-zA-Z0-9_-]/g, "_")
    : "All_Packages";
  const dateStamp = now.toISOString().slice(0, 10);
  doc.save(`Procurement_Report_${safeProjectName}_${dateStamp}.pdf`);
};

export const generateProgressRevenuePdf = ({
  projectCode = "",
  periodLabel = "Semua Bulan (Jan - Des)",
  monthGroups = [],
  indicators = [],
  formatPercentage = (v) => (v === null || v === undefined ? "-" : v),
}) => {
  const doc = new jsPDF("landscape", "pt", "a4");

  doc.setFont("helvetica", "bold");
  doc.setFontSize(15);
  doc.setTextColor(15, 23, 42);
  doc.text("Laporan Progress Pekerjaan Mingguan & Akumulasi RKAP", 40, 45);

  doc.setDrawColor(0, 82, 204);
  doc.setLineWidth(1.5);
  doc.line(40, 52, 802, 52);

  doc.setFont("helvetica", "normal");
  doc.setFontSize(9.5);
  doc.setTextColor(71, 85, 105);
  doc.text(`Kode Proyek: ${projectCode}`, 40, 70);
  doc.text(`Periode    : ${periodLabel}`, 40, 84);
  doc.text(
    `Tgl Cetak  : ${new Date().toLocaleString("id-ID", {
      dateStyle: "short",
      timeStyle: "short",
    })}`,
    650,
    70,
  );

  const head = [
    [
      "INDIKATOR",
      ...monthGroups.flatMap((group) =>
        Array(group.weeks.length).fill(group.label),
      ),
    ],
    [
      "",
      ...monthGroups.flatMap((group) => group.weeks.map((w) => w.label)),
    ],
  ];

  const body = indicators.map((indicator) => [
    indicator.label,
    ...monthGroups.flatMap((group) =>
      group.weeks.map(
        (week) => formatPercentage(indicator.values?.[week.index]),
      ),
    ),
  ]);

if (monthGroups.length === 0) {
      doc.text('Tidak ada data progress & revenue untuk periode ini.', 40, 120);
    } else {
      const columnStyles = { 0: { halign: 'left', cellWidth: 150, fontStyle: 'bold' } };
      // assign small width for week columns (indexes >=1)
      monthGroups.forEach((g, gi) => {
        g.weeks.forEach((w, wi) => {
          const colIdx = 1 + monthGroups.slice(0, gi).reduce((sum, gr) => sum + gr.weeks.length, 0) + wi;
          columnStyles[colIdx] = { halign: 'center', cellWidth: 30 };
        });
      });
      autoTable(doc, {
        startY: 100,
        head,
        body,
        theme: 'grid',
        headStyles: {
          fillColor: [0, 82, 204],
          textColor: 255,
          fontStyle: 'bold',
          halign: 'center',
          fontSize: 7,
        },
        bodyStyles: { textColor: [30, 41, 59], fontSize: 7, halign: 'center' },
        columnStyles,
        styles: { cellPadding: 3, overflow: 'linebreak' },
        margin: { left: 40, right: 40 },
      });
    }

  const safeCode = (projectCode || "PROJECT").replace(/[^a-zA-Z0-9_-]/g, "_");
  doc.save(
    `Progress_Revenue_${safeCode}_${new Date().toISOString().slice(0, 10)}.pdf`,
  );
};

export const generateWbsPdf = ({
  projectCode = "",
  projectName = "",
  items = [],
  formatWeekOrdinal = (v) => "",
}) => {
  const doc = new jsPDF("landscape", "pt", "a4");

  // ─── Header ────────────────────────────────────────────────────────
  doc.setFont("helvetica", "bold");
  doc.setFontSize(15);
  doc.setTextColor(15, 23, 42);
  doc.text("Laporan WBS Progress & Revenue", 40, 45);

  doc.setDrawColor(0, 82, 204);
  doc.setLineWidth(1.5);
  doc.line(40, 52, 802, 52);

  doc.setFont("helvetica", "normal");
  doc.setFontSize(9.5);
  doc.setTextColor(71, 85, 105);
  doc.text(`Kode Proyek : ${projectCode}`, 40, 70);
  if (projectName) {
    doc.text(`Nama Proyek : ${projectName}`, 40, 84);
  }
  doc.text(
    `Tgl Cetak   : ${new Date().toLocaleString("id-ID", {
      dateStyle: "short",
      timeStyle: "short",
    })}`,
    650,
    70,
  );

  // ─── Determine parent WBS numbers for progress calculation ─────
  const parentWbsNumbers = new Set(
    items.map((item) => item.parent_wbs_no).filter(Boolean),
  );

  // ─── Status color helper ───────────────────────────────────────
  const statusColor = (status) => {
    switch ((status || "").toLowerCase()) {
      case "on_track":
        return [5, 150, 105]; // emerald
      case "overdue":
        return [202, 138, 4]; // yellow
      case "critical":
        return [185, 28, 28]; // red
      default:
        return [100, 116, 139]; // slate
    }
  };

  const statusLabel = (status) => {
    switch ((status || "").toLowerCase()) {
      case "on_track":
        return "On Track";
      case "overdue":
        return "Overdue";
      case "critical":
        return "Critical";
      case "not_started":
        return "Not Started";
      default:
        return status || "-";
    }
  };

  // ─── Format bobot as percentage (stored in 0-100 scale) ───────
  const fmtBobot = (v) => {
    const num = Number(v) || 0;
    return `${num.toLocaleString("id-ID", { maximumFractionDigits: 2 })}%`;
  };

  // ─── Format progress ──────────────────────────────────────────
  const fmtProgress = (val) => {
    if (val === null || val === undefined) return "-";
    return `${Number(val).toLocaleString("id-ID", { maximumFractionDigits: 2 })}%`;
  };

  // ─── Build table ──────────────────────────────────────────────
  const tableBody = items.map((item) => {
    const progress = parentWbsNumbers.has(item.wbs_no)
      ? item.progress_sum
      : item.latest_progress_percent;

    return [
      item.wbs_no || "-",
      item.activity_name || "-",
      item.pic || "-",
      item.divisi || "-",
      fmtBobot(item.bobot_plan),
      fmtBobot(item.bobot_real),
      item.plan_start_period || "-",
      item.plan_end_period || "-",
      formatWeekOrdinal(item.actual_start_week) || "-",
      formatWeekOrdinal(item.actual_finish_week) || "-",
      item.durasi || "-",
      fmtProgress(progress),
      statusLabel(item.status),
    ];
  });

  if (items.length === 0) {
    doc.text("Tidak ada data WBS untuk dicetak.", 40, 110);
  } else {
    autoTable(doc, {
      startY: projectName ? 100 : 86,
      head: [
        [
          "WBS",
          "Aktivitas",
          "PIC",
          "Divisi",
          "Bobot Plan",
          "Bobot Real",
          "Plan Mulai",
          "Plan Selesai",
          "Act. Mulai",
          "Act. Selesai",
          "Durasi",
          "Progress",
          "Status",
        ],
      ],
      body: tableBody,
      theme: "striped",
      headStyles: {
        fillColor: [30, 41, 59],
        textColor: 255,
        fontStyle: "bold",
        halign: "center",
        fontSize: 7,
      },
      bodyStyles: {
        textColor: [30, 41, 59],
        fontSize: 7,
      },
      columnStyles: {
        0: { cellWidth: 50, fontStyle: "bold" },
        1: { cellWidth: 120 },
        2: { cellWidth: 55, halign: "center" },
        3: { cellWidth: 55, halign: "center" },
        4: { cellWidth: 42, halign: "center" },
        5: { cellWidth: 42, halign: "center" },
        6: { cellWidth: 50, halign: "center" },
        7: { cellWidth: 50, halign: "center" },
        8: { cellWidth: 50, halign: "center" },
        9: { cellWidth: 50, halign: "center" },
        10: { cellWidth: 35, halign: "center" },
        11: { cellWidth: 42, halign: "center", fontStyle: "bold" },
        12: { cellWidth: 48, halign: "center", fontStyle: "bold" },
      },
      styles: {
        cellPadding: 3,
        overflow: "linebreak",
      },
      margin: { left: 40, right: 40 },
      didParseCell: (data) => {
        // Color the Status column based on value
        if (data.section === "body" && data.column.index === 12) {
          const raw = items[data.row.index]?.status;
          data.cell.styles.textColor = statusColor(raw);
        }
      },
    });
  }

  // ─── Summary footer ────────────────────────────────────────────
  const finalY = doc.lastAutoTable?.finalY ?? 120;
  const onTrack = items.filter(
    (i) => (i.status || "").toLowerCase() === "on_track",
  ).length;
  const overdue = items.filter(
    (i) => (i.status || "").toLowerCase() === "overdue",
  ).length;
  const critical = items.filter(
    (i) => (i.status || "").toLowerCase() === "critical",
  ).length;

  if (items.length > 0) {
    doc.setFont("helvetica", "bold");
    doc.setFontSize(8);
    doc.setTextColor(71, 85, 105);
    doc.text(
      `Ringkasan: ${items.length} WBS   |   On Track: ${onTrack}   |   Overdue: ${overdue}   |   Critical: ${critical}`,
      40,
      finalY + 20,
    );
  }

  // ─── Save ──────────────────────────────────────────────────────
  const safeCode = (projectCode || "PROJECT").replace(/[^a-zA-Z0-9_-]/g, "_");
  doc.save(
    `WBS_Progress_Revenue_${safeCode}_${new Date().toISOString().slice(0, 10)}.pdf`,
  );
};
