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
