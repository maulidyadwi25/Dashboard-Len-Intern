/**
 * Helper Parser untuk Header dan Gantt Chart (Full Dynamic Google Sheet)
 */

// 1. Parser Header Proyek
export function parseProjectHeader(rows = [], activities = []) {
  const projectInfo = {
    projectName: 'REFURBISHMENT KRI',
    projectCode: '1AB-12345678',
    projectValue: 'Rp 0',
    overallProgress: '0%',
    progressTrend: '0%',
    spiValue: '0.00%',
    spiStatus: 'On Track'
  };

  if (!Array.isArray(rows) || rows.length === 0) return projectInfo;

  rows.forEach(row => {
    const key = String(row[0] || '').trim().toLowerCase();
    const val = String(row[2] || row[3] || row[1] || '').trim();

    if (!key) return;

    // 1. Nama Proyek
    if (key.includes('nama proyek') && val) {
      projectInfo.projectName = val;
    }

    // 2. Kode Proyek
    if (key.includes('kode proyek') && val) {
      projectInfo.projectCode = val;
    }

    // 3. Nilai Kontrak
    if (key.includes('nilai kontrak') && val) {
      const cleanNum = val.replace(/[^0-9,-]/g, '');
      projectInfo.projectValue = !isNaN(cleanNum) && cleanNum !== '' 
        ? `Rp ${Number(cleanNum).toLocaleString('id-ID')}` 
        : val;
    }

    // 6. Trend
    if (key.includes('trend') && val) {
      projectInfo.progressTrend = val.includes('%') ? val : `${val}%`;
    }
  });

  // 4. Hitung Overall Progress sebagai Grand Total dari SEMUA nilai Actual (A) yang ada di sheet
  let totalActual = 0;
  rows.forEach(row => {
    const typeAP = String(row[7] || '').trim().toUpperCase();
    if (typeAP === 'A') {
      for (let i = 8; i < row.length; i++) {
        const cellValue = String(row[i] || '').trim();
        if (
          cellValue && 
          cellValue !== '0' && 
          cellValue !== '0%' && 
          cellValue !== '0.00%' && 
          cellValue !== '-'
        ) {
          const num = parseFloat(cellValue.replace('%', ''));
          if (!isNaN(num)) {
            totalActual += num;
          }
        }
      }
    }
  });

  if (totalActual > 0) {
    projectInfo.overallProgress = `${parseFloat(totalActual.toFixed(3))}%`;
  }

  // 5. Hitung SPI sebagai Grand Total Selisih Khusus Aktivitas yang OVERDUE
  const monthNames = [
    'JANUARI', 'FEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI',
    'JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOVEMBER', 'DESEMBER'
  ];
  const flatWeeks = [];
  monthNames.forEach(m => {
    ['W1', 'W2', 'W3', 'W4'].forEach(w => {
      flatWeeks.push(`${m}_${w}`);
    });
  });

  const now = new Date();
  let currentWeekIdx = flatWeeks.indexOf('AGUSTUS_W4'); // default fallback
  if (now.getFullYear() === 2026) {
    const currentMonth = now.getMonth();
    const currentDay = now.getDate();
    let weekNum = 'W4';
    if (currentDay <= 7) weekNum = 'W1';
    else if (currentDay <= 14) weekNum = 'W2';
    else if (currentDay <= 21) weekNum = 'W3';

    const currentWeekKey = `${monthNames[currentMonth]}_${weekNum}`;
    const idx = flatWeeks.indexOf(currentWeekKey);
    if (idx !== -1) currentWeekIdx = idx;
  }

  let totalOverdueGap = 0;
  let overdueCount = 0;

  if (activities && activities.length > 0) {
    activities.forEach(act => {
      if (!act.planWeeks || !act.actualWeeks) return;
      const planKeys = Object.keys(act.planWeeks);
      const actualKeys = Object.keys(act.actualWeeks);
      if (planKeys.length === 0 || actualKeys.length === 0) return;

      const planIndices = planKeys.map(k => flatWeeks.indexOf(k)).filter(i => i !== -1);
      const actualIndices = actualKeys.map(k => flatWeeks.indexOf(k)).filter(i => i !== -1);
      if (planIndices.length === 0 || actualIndices.length === 0) return;

      const maxPlanIdx = Math.max(...planIndices);
      const lastPlanKey = flatWeeks[maxPlanIdx];
      const planTarget = parseFloat(String(act.planWeeks[lastPlanKey] || '0').replace('%', '')) || 0;

      let isOverdue = false;
      const actualVals = Object.values(act.actualWeeks).map(v => parseFloat(String(v || '0').replace('%', '')) || 0);
      const maxActual = Math.max(...actualVals);

      for (const aIdx of actualIndices) {
        const precedingPlans = planIndices.filter(p => p <= aIdx);
        if (precedingPlans.length > 0) {
          const closestPlanIdx = Math.max(...precedingPlans);
          if (aIdx > closestPlanIdx) {
            isOverdue = true;
            break;
          }
        }
      }

      if (isOverdue) {
        const gap = maxActual - planTarget;
        totalOverdueGap += gap;
        overdueCount++;
      }
    });
  }

  if (overdueCount > 0) {
    projectInfo.spiValue = `${parseFloat(totalOverdueGap.toFixed(3))}%`;
    projectInfo.spiStatus = 'Delay';
  } else {
    projectInfo.spiValue = '0.00%';
    projectInfo.spiStatus = 'On Track';
  }

  return projectInfo;
}

// 2. Parser Data Gantt Chart (Januari - Desember)
export function parseGanttChartData(rows = []) {
  if (!Array.isArray(rows) || rows.length === 0) return { months: [], activities: [] };

  const months = [
    { name: 'JANUARI', weeks: ['W1', 'W2', 'W3', 'W4'] },
    { name: 'FEBRUARI', weeks: ['W1', 'W2', 'W3', 'W4'] },
    { name: 'MARET', weeks: ['W1', 'W2', 'W3', 'W4'] },
    { name: 'APRIL', weeks: ['W1', 'W2', 'W3', 'W4'] },
    { name: 'MEI', weeks: ['W1', 'W2', 'W3', 'W4'] },
    { name: 'JUNI', weeks: ['W1', 'W2', 'W3', 'W4'] },
    { name: 'JULI', weeks: ['W1', 'W2', 'W3', 'W4'] },
    { name: 'AGUSTUS', weeks: ['W1', 'W2', 'W3', 'W4'] },
    { name: 'SEPTEMBER', weeks: ['W1', 'W2', 'W3', 'W4'] },
    { name: 'OKTOBER', weeks: ['W1', 'W2', 'W3', 'W4'] },
    { name: 'NOVEMBER', weeks: ['W1', 'W2', 'W3', 'W4'] },
    { name: 'DESEMBER', weeks: ['W1', 'W2', 'W3', 'W4'] }
  ];

  const groupedMap = new Map();

  rows.forEach((row) => {
    // Gabungkan kolom A, B, C untuk pengecekan teks menyeluruh
    const colA = String(row[0] || '').trim();
    const colB = String(row[1] || '').trim();
    const colC = String(row[2] || '').trim();

    const no = colA;
    const wbsName = colC || colB || colA;
    const typeAP = String(row[7] || '').trim().toUpperCase();

    if (!wbsName) return;

    const fullText = `${colA} ${colB} ${colC}`.toLowerCase();

    // 🔴 FILTER BLOKIR TOTAL UNTUK SEMUA HEADER / METADATA
    if (
      fullText.includes('kode proyek') ||
      fullText.includes('nama proyek') ||
      fullText.includes('nilai kontrak') ||
      fullText.includes('% lkp') ||
      fullText.includes('progress tercapai') ||
      fullText.includes('anual deviasi') ||
      fullText.includes('target progress') ||
      fullText.includes('nama pimpro') ||
      fullText.includes('penanggungjawab') ||
      fullText.includes('rincian kegiatan') ||
      fullText.includes('penting') ||
      fullText.includes('data (p) planing') ||
      fullText.includes('dilarang mengubah') ||
      colA.toLowerCase().startsWith('a.') ||
      colA.toLowerCase().startsWith('b.') ||
      colA.toLowerCase().startsWith('c.') ||
      colA.toLowerCase() === 'no'
    ) {
      return; // Lewati baris ini dari tabel
    }

    const isHeader = !typeAP || (typeAP !== 'P' && typeAP !== 'A');
    
    // Penentuan level WBS berdasarkan jumlah titik pada penomoran (Level 1: 4, Level 2: 4.1/4.3/4.4, Level 3: 4.1.1)
    let level = 3;
    if (!no) {
      level = isHeader ? 1 : 3;
    } else {
      const cleanNo = String(no).trim().replace(/\.$/, '');
      const dotCount = (cleanNo.match(/\./g) || []).length;
      if (dotCount === 0) level = 1;
      else if (dotCount === 1) level = 2;
      else level = 3;
    }

    // Uniqueness Key: menggabungkan No WBS dan Nama Aktivitas agar tidak bentrok jika ada nama yang duplikat
    const uniqueKey = `${no}_${wbsName}`;

    if (!groupedMap.has(uniqueKey)) {
      groupedMap.set(uniqueKey, {
        no: no,
        name: wbsName,
        level: level,
        isHeader: isHeader,
        planWeeks: {},
        actualWeeks: {},
        startWeekKey: null,
        endWeekKey: null
      });
    }

    const item = groupedMap.get(uniqueKey);

    if (typeAP === 'P' || typeAP === 'A') {
      item.isHeader = false;
      let colIndex = 8;
      months.forEach(month => {
        month.weeks.forEach(week => {
          const weekKey = `${month.name}_${week}`;
          const cellValue = String(row[colIndex] || '').trim();

          if (
            cellValue && 
            cellValue !== '0' && 
            cellValue !== '0%' && 
            cellValue !== '0.00%' && 
            cellValue !== '-'
          ) {
            if (typeAP === 'P') item.planWeeks[weekKey] = cellValue;
            if (typeAP === 'A') item.actualWeeks[weekKey] = cellValue;

            if (!item.startWeekKey) item.startWeekKey = weekKey;
            item.endWeekKey = weekKey;
          }

          colIndex++;
        });
      });
    }
  });

  return {
    months,
    activities: Array.from(groupedMap.values())
  };
}