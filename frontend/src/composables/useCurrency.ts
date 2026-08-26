import { ref } from "vue";

const FALLBACK_RATES: Record<string, number> = {
  IDR: 1,
  USD: 1 / 16250,
  EUR: 1 / 17600,
  SGD: 1 / 12200,
};

export const selectedCurrency = ref<string>("IDR");

export const exchangeRates = ref<Record<string, number>>({
  IDR: 1,
  USD: FALLBACK_RATES.USD,
  EUR: FALLBACK_RATES.EUR,
  SGD: FALLBACK_RATES.SGD,
});

export function useCurrency() {
  const getEffectiveRate = (currency: string): number => {
    return exchangeRates.value[currency] ?? FALLBACK_RATES[currency] ?? 1;
  };

  const formatCurrency = (
    value: number | string | undefined | null,
  ): string => {
    if (value === undefined || value === null)
      return `${selectedCurrency.value} 0`;

    const rawVal = Number(value);
    if (isNaN(rawVal)) return `${selectedCurrency.value} 0`;

    const curr = selectedCurrency.value;
    const rate = getEffectiveRate(curr);
    const val = rawVal * rate;
    const absVal = Math.abs(val);

    if (curr === "IDR") {
      let formatted = "";
      if (absVal >= 1_000_000_000_000) {
        formatted = (absVal / 1_000_000_000_000).toFixed(1) + "T";
      } else if (absVal >= 1_000_000_000) {
        formatted = (absVal / 1_000_000_000).toFixed(1) + "M";
      } else if (absVal >= 1_000_000) {
        formatted = (absVal / 1_000_000).toFixed(1) + "Jt";
      } else {
        formatted = absVal.toLocaleString("id-ID");
      }
      return (val < 0 ? "-" : "") + "IDR " + formatted;
    }

    const symbols: Record<string, string> = {
      USD: "$",
      EUR: "€",
      SGD: "S$",
    };
    const symbol = symbols[curr] || `${curr} `;

    let formatted = "";
    if (absVal >= 1_000_000_000) {
      formatted = (absVal / 1_000_000_000).toFixed(2) + "B";
    } else if (absVal >= 1_000_000) {
      formatted = (absVal / 1_000_000).toFixed(2) + "M";
    } else if (absVal >= 1_000) {
      formatted = (absVal / 1_000).toFixed(1) + "K";
    } else {
      formatted = absVal.toFixed(2);
    }

    return (val < 0 ? "-" : "") + symbol + formatted;
  };

  return {
    selectedCurrency,
    exchangeRates,
    formatCurrency,
  };
}
