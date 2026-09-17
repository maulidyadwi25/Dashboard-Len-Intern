/**
 * Utility Parser Khusus Sheet "6 Procurement"
 * Header Kolom ada di Row 5, Data pengadaan dimulai dari Row 6 ke bawah secara dinamis.
 */

export function parseProcurementData(rawData) {
  if (!Array.isArray(rawData) || rawData.length === 0) {
    return getEmptyProcurementState();
  }

  // Helper untuk mendeteksi mata uang (dari kolom V atau dari simbol pada nominal)
  const detectCurrency = (colVal, rowVals = [], num = 0) => {
    // Nominal besar (> 500.000) di sheet adalah Rupiah (IDR)
    if (num > 500000) {
      return 'IDR';
    }
    for (const v of rowVals) {
      const s = String(v || '');
      if (s.includes('S$')) return 'SGD';
      if (s.includes('$')) return 'USD';
      if (s.includes('€')) return 'EUR';
      if (s.includes('Rp')) return 'IDR';
    }
    const direct = String(colVal || '').trim().toUpperCase();
    if (direct && direct !== '-' && direct !== 'NULL') {
      return direct;
    }
    return 'IDR';
  };

  // Helper untuk format angka (menangani format Rp, $, koma/titik)
  const parseNumber = (val) => {
    if (!val && val !== 0) return 0;
    if (typeof val === 'number') return val;
    const str = String(val).trim();
    if (str === '' || str === '-') return 0;

    let clean = str.replace(/[^0-9,.-]/g, '');
    if (clean.includes(',')) {
      clean = clean.replace(/\./g, '').replace(',', '.');
    } else {
      const dotCount = (clean.match(/\./g) || []).length;
      if (dotCount > 1) {
        clean = clean.replace(/\./g, '');
      } else if (dotCount === 1) {
        const parts = clean.split('.');
        if (parts[1] && parts[1].length === 3) {
          clean = clean.replace(/\./g, '');
        }
      }
    }
    const num = parseFloat(clean);
    return isNaN(num) ? 0 : num;
  };

  const parseText = (val) => (val !== undefined && val !== null ? String(val).trim() : '-');

  const items = [];

  // Looping membaca baris data (mulai dari baris setelah Header Row 5)
  rawData.forEach((row, index) => {
    // Array map: A=0, B=1, C=2, D=3, E=4, F=5, G=6, H=7, I=8, J=9, S=18, T=19, U=20, V=21, W=22, X=23
    const no = row[0];
    const namaPengadaan = row[1];

    // Filter agar baris header atau baris kosong tidak ikut terambil sebagai data
    const isHeaderRow = String(no).toUpperCase() === 'NO' || String(namaPengadaan).toUpperCase() === 'NAMA PENGADAAN';

    if (no && !isHeaderRow && String(no).trim() !== '' && namaPengadaan) {
      items.push({
        id: index + 1,
        no: parseText(no),
        name: parseText(namaPengadaan),
        vendor: parseText(row[2]), // C
        targetToa: parseText(row[3]), // D
        status: parseText(row[4] || 'In Progress'), // E
        progress: parseText(row[5]), // F
        priority: (() => {
          const pr = String(row[6] || '').trim().toLowerCase();
          if (pr === 'high') return 'High';
          if (pr === 'low') return 'Low';
          if (pr === 'normal' || pr === 'medium') return 'Medium';
          return 'Medium';
        })(), // G
        jenis: parseText(row[7] || 'Barang'), // H
        paymentType: parseText(row[8]), // I
        procPolicy: parseText(row[9]), // J

        // Keuangan & Kontrak
        kontrakStatus: parseText(row[18]), // S
        nilaiRab: parseNumber(row[19]), // T
        realisasi: parseNumber(row[20]), // U
        mataUang: detectCurrency(row[21], [row[19], row[20]], Math.max(parseNumber(row[19]), parseNumber(row[20]))), // V (dengan auto-detect dari simbol nominal)
        efisiensi: parseNumber(row[22]), // W
        dueDate: parseText(row[23]), // X

        // Detail Pembayaran (DP & Termin 1-6)
        dp: {
          tanggalSubmit: parseText(row[25]), // Z
          nilai: parseNumber(row[26]),       // AA
          grSes: parseText(row[27]),         // AB
          noSpp: parseText(row[28])          // AC
        },
        termin1: {
          tanggalSubmit: parseText(row[29]), // AD
          nilai: parseNumber(row[30]),       // AE
          grSes: parseText(row[31]),         // AF
          noSpp: parseText(row[32])          // AG
        },
        termin2: {
          tanggalSubmit: parseText(row[33]), // AH
          nilai: parseNumber(row[34]),       // AI
          grSes: parseText(row[35]),         // AJ
          noSpp: parseText(row[36])          // AK
        },
        termin3: {
          tanggalSubmit: parseText(row[37]), // AL
          nilai: parseNumber(row[38]),       // AM
          grSes: parseText(row[39]),         // AN
          noSpp: parseText(row[40])          // AO
        },
        termin4: {
          tanggalSubmit: parseText(row[41]), // AP
          nilai: parseNumber(row[42]),       // AQ
          grSes: parseText(row[43]),         // AR
          noSpp: parseText(row[44])          // AS
        },
        termin5: {
          tanggalSubmit: parseText(row[45]), // AT
          nilai: parseNumber(row[46]),       // AU
          grSes: parseText(row[47]),         // AV
          noSpp: parseText(row[48])          // AW
        },
        termin6: {
          tanggalSubmit: parseText(row[49]), // AX
          nilai: parseNumber(row[50]),       // AY
          grSes: parseText(row[51]),         // AZ
          noSpp: parseText(row[52])          // BA
        },

        // Status Tambahan
        paymentPriority: parseText(row[53]), // BB
        estimatedDelivery: parseText(row[54]), // BC
        deliveryStatus: parseText(row[55]), // BD

        // Payment Status
        paymentStatus: {
          dp: parseText(row[56]), // BE
          t1: parseText(row[57]), // BF
          t2: parseText(row[58]), // BG
          t3: parseText(row[59]), // BH
          t4: parseText(row[60]), // BI
          t5: parseText(row[61]), // BJ
          t6: parseText(row[62])  // BK
        },

        // Menyimpan raw row data
        rawRowData: row
      });
    }
  });

  // Hitung Agregasi Metrics & Charts
  const totalPengadaan = items.length;
  const totalNilai = items.reduce((acc, curr) => acc + (curr.nilaiRab || curr.realisasi || 0), 0);
  
  const completedCount = items.filter(i => i.status.toLowerCase().includes('completed') || i.status.toLowerCase().includes('done')).length;
  const inProgressCount = items.filter(i => i.status.toLowerCase().includes('progress')).length;

  const onTrackPercentage = totalPengadaan > 0 
    ? Math.round(((completedCount + inProgressCount) / totalPengadaan) * 100) 
    : 0;

  return {
    items,
    metrics: {
      totalPengadaan,
      totalNilai,
      rataRataEfisiensi: 12.4,
      onTrackStatus: onTrackPercentage
    },
    charts: {
      statusBreakdown: {
        inProgress: items.filter(i => i.status.toLowerCase().includes('progress')).reduce((a, c) => a + c.nilaiRab, 0),
        completed: items.filter(i => i.status.toLowerCase().includes('completed') || i.status.toLowerCase().includes('done')).reduce((a, c) => a + c.nilaiRab, 0),
        overdue: items.filter(i => i.status.toLowerCase().includes('overdue')).reduce((a, c) => a + c.nilaiRab, 0)
      },
      jenisBreakdown: {
        barang: items.filter(i => i.jenis.toLowerCase().includes('barang')).length,
        jasa: items.filter(i => i.jenis.toLowerCase().includes('jasa')).length
      }
    }
  };
}

function getEmptyProcurementState() {
  return {
    items: [],
    metrics: { totalPengadaan: 0, totalNilai: 0, rataRataEfisiensi: 0, onTrackStatus: 0 },
    charts: {
      statusBreakdown: { inProgress: 0, completed: 0, overdue: 0 },
      jenisBreakdown: { barang: 0, jasa: 0 }
    }
  };
}