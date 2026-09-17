/**
 * Utility Konversi Kurs Mata Uang (IDR, EUR, USD, SGD)
 * Menggunakan data kurs realtime dari CDN API
 */

export const DEFAULT_RATES = {
  IDR: 1,
  USD: 17607.8,
  EUR: 20468.7,
  SGD: 13921.45
};

/**
 * Konversi nilai nominal dari data mentah ke IDR (Rupiah) sebagai basis konversi
 */
export function toBaseIdr(val, originCurrency = 'IDR', exchangeRates = null) {
  const num = Number(val) || 0;
  if (num === 0) return 0;

  // Nilai nominal di sheet yang berupa jutaan (> 500.000) adalah dalam satuan Rupiah (IDR),
  // misalnya baris 1 (92.300.123), baris 2 (88.724.647), baris 3 (10.010.000), dst.
  if (num > 500000) {
    return num;
  }

  const rates = exchangeRates?.to_idr || DEFAULT_RATES;
  const curr = String(originCurrency || '').toUpperCase();

  if (curr === 'USD') {
    return num * (rates.USD || DEFAULT_RATES.USD);
  }
  if (curr === 'EUR') {
    return num * (rates.EUR || DEFAULT_RATES.EUR);
  }
  if (curr === 'SGD') {
    return num * (rates.SGD || DEFAULT_RATES.SGD);
  }

  return num; // Default IDR
}

/**
 * Konversi dari IDR ke mata uang target (EUR, USD, SGD, atau IDR)
 */
export function convertCurrency(valInIdr, targetCurrency = 'IDR', exchangeRates = null) {
  const num = Number(valInIdr) || 0;
  if (num === 0) return 0;

  const target = String(targetCurrency || 'IDR').toUpperCase();
  const rates = exchangeRates?.to_idr || DEFAULT_RATES;

  if (target === 'EUR') {
    const rate = rates.EUR || DEFAULT_RATES.EUR;
    return num / rate;
  }
  if (target === 'USD') {
    const rate = rates.USD || DEFAULT_RATES.USD;
    return num / rate;
  }
  if (target === 'SGD') {
    const rate = rates.SGD || DEFAULT_RATES.SGD;
    return num / rate;
  }

  return num; // Default IDR
}

/**
 * Format angka hasil konversi sesuai standar mata uang
 */
export function formatCurrencyValue(val, targetCurrency = 'IDR') {
  if (!val && val !== 0) return '-';
  const num = Number(val);
  if (isNaN(num)) return '-';

  const target = String(targetCurrency || 'IDR').toUpperCase();

  if (target === 'EUR') {
    return '€ ' + new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num);
  }
  if (target === 'USD') {
    return '$ ' + new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num);
  }
  if (target === 'SGD') {
    return 'S$ ' + new Intl.NumberFormat('en-SG', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num);
  }

  // Default IDR
  return 'Rp ' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(num);
}
