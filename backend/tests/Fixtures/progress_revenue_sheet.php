<?php

/*
 * Fixture sheet for the "2. Progress & Revenue" tab, shaped like the real
 * Google Sheets response with valueRenderOption=UNFORMATTED_VALUE:
 * - percentages arrive as fractions (5% => 0.05)
 * - blank cells arrive as ''
 *
 * Layout:
 *   rows 5-15  project profile block (label col A, value col C, D8 must be ignored)
 *   row  17    weekly header ("Week", "Des 2025", "Jan W1".."Des W4")
 *   rows 18-24 indicator rows, deliberately NOT in canonical order
 *   row  22+  WBS block, must never be parsed
 */
return [
    // 1
    ['2. PROGRESS & REVENUE'],
    // 2
    [],
    // 3
    [],
    // 4
    [],
    // 5
    ['Nama Client', '', 'PT LEN Industri'],
    // 6
    ['Kode Proyek', '', '  1LC-522UD019  '],
    // 7
    ['  nama   proyek  ', '', 'Refurbishment KRI Sigma'],
    // 8
    ['Nilai Kontrak', '', '125800000000', 'SHOULD BE IGNORED D8'],
    // 9
    ['% LKP Des 2025', '', 0.1234],
    // 10
    ['Progress Tercapai', '', 0.1500],
    // 11
    ['Anual Deviasi Target', '', -0.0021],
    // 12
    ['Target Progress 2026', '', 0.9800],
    // 13
    ['Target % LKP Des 2026', '', 0.9750],
    // 14
    ['Nama Pimpro', '', 'SETIAJI'],
    // 15
    ['Penanggungjawab Report', '', 'KURNIAWAN'],
    // 16
    [],
    // 17  weekly header
    [
        'Week',
        'Des 2025', 'Jan W1', 'Jan W2', 'Jan W3', 'Jan W4',
        'Feb W1', 'Feb W2', 'Feb W3', 'Feb W4',
    ],
    // 18  Deviasi first on purpose: row order must not matter
    ['Deviasi Proyek vs RKAP', 0.0100, 0.0195, 0.0225, '', 0.0, -0.0030, '', '', '', 0.0100, 0.0110, 0.0115, 0.0120],
    // 19
    ['Rencana Mingguan RKAP', 0.0050, 0.0050, 0.0052, 0.0051, 0.0049, 0.0055, '', 0.0050, 0.0050, 0.0050, 0.0051, 0.0051, 0.0051],
    // 20
    ['Akumulasi Mingguan RKAP', 0.5000, 0.5050, 0.5102, 0.5153, 0.5202, 0.5257, '', 0.5362, 0.5412, 0.5417, 0.5427, 0.5447, 0.5463],
    // 21
    ['Rencana Mingguan Proyek', 0.0051, 0.0051, 0.0053, 0.0052, 0.0050, 0.0056, '', 0.0051, 0.0051, 0.0051, 0.0052, 0.0052, 0.0052],
    // 22  Akumulasi / Realisasi before the WBS sentinel so the weekly-summary
    //     block stays the full 7 indicators without sneaking past row 22.
    ['Akumulasi Mingguan Proyek', 0.5100, 0.5151, 0.5204, 0.5256, 0.5306, 0.5362, '', 0.5468, 0.5519, 0.5524, 0.5534, 0.5544, 0.5554],
    ['Realisasi Mingguan Proyek', 0.0048, 0.0048, 0.0050, 0.0050, 0.0048, 0.0052, '', 0.0049, 0.0050, 0.0050, 0.0050, 0.0050, 0.0050],
    ['Akumulasi Realisasi Proyek', 0.4800, 0.4848, 0.4898, '', 0.4996, 0.5048, '', 0.5146, 0.5196, 0.5201, 0.5211, 0.5221, 0.5231],
    // WBS block starts here
    ['WBS', 'Nama Pekerjaan', 'Bobot', 0.5],
    ['1.1', 'Pekerjaan Pondasi', 0.2, 0.5],
];
