export const formatCurrencyShort = (
  value: number | string | undefined | null,
  currency: string = "IDR",
  rates?: Record<string, number>,
): string => {
  if (value === undefined || value === null) return `${currency} 0`;

  const rawVal = Number(value);
  if (isNaN(rawVal)) return `${currency} 0`;

  const activeRate = rates?.[currency];

  if (activeRate === undefined || !Number.isFinite(activeRate)) {
    if (currency === "IDR") {
      return `IDR ${rawVal.toLocaleString("id-ID")}`;
    }

    return `${currency} unavailable`;
  }

  const val = rawVal * activeRate;
  const absVal = Math.abs(val);

  // Format IDR
  if (currency === "IDR") {
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

  // Format Valas (USD, EUR, SGD)
  const symbols: Record<string, string> = {
    USD: "$",
    EUR: "€",
    SGD: "S$",
  };
  const symbol = symbols[currency] || `${currency} `;

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
