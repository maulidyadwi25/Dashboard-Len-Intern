import api from "./api";

export const DEFAULT_WBS_PROJECT_ID = 1;

function withWbsErrorHandling(request) {
  return request().catch((error) => {
    const status = error?.response?.status;

    if (![404, 409, 422, 429, 502].includes(status)) {
      throw error;
    }

    const responseData = error?.response?.data ?? {};
    const message =
      status === 409
        ? "Ringkasan AI sedang diproses. Coba lagi sebentar."
        : status === 429
          ? "Permintaan terlalu sering. Tunggu sebentar sebelum mencoba lagi."
          : status === 502
            ? "AI gagal membuat ringkasan. Ringkasan lama tetap tersedia."
            : responseData.message;
    const normalizedError = new Error(
      typeof message === "string"
        ? message
        : status === 404
          ? "WBS project or item was not found."
          : "The WBS request could not be processed.",
    );
    normalizedError.status = status;
    normalizedError.previousSummary = responseData.previous_summary ?? null;

    throw normalizedError;
  });
}

export const wbsService = {
  getWbs(id = DEFAULT_WBS_PROJECT_ID) {
    return withWbsErrorHandling(() => api.get(`/projects/${id}/wbs`));
  },

  getWbsWeekly(id = DEFAULT_WBS_PROJECT_ID, month = "all") {
    return withWbsErrorHandling(() =>
      api.get(`/projects/${id}/wbs/weekly`, { params: { month } }),
    );
  },

  getWeekSummary(id = DEFAULT_WBS_PROJECT_ID) {
    return withWbsErrorHandling(() =>
      api.get(`/projects/${id}/wbs/week-summary`),
    );
  },

  getAiSummary(id = DEFAULT_WBS_PROJECT_ID) {
    return withWbsErrorHandling(() =>
      api.get(`/projects/${id}/wbs/ai-summary`),
    );
  },

  generateAiSummary(id = DEFAULT_WBS_PROJECT_ID) {
    return withWbsErrorHandling(() =>
      api.post(`/projects/${id}/wbs/ai-summary`),
    );
  },

  saveAiSummary(id = DEFAULT_WBS_PROJECT_ID, content) {
    return withWbsErrorHandling(() =>
      api.put(`/projects/${id}/wbs/ai-summary`, { content }),
    );
  },

  syncWbs(id = DEFAULT_WBS_PROJECT_ID) {
    return withWbsErrorHandling(() => api.post(`/projects/${id}/wbs/sync`));
  },

  updateWbsProgress(id = DEFAULT_WBS_PROJECT_ID, wbsNo, percent) {
    return withWbsErrorHandling(() =>
      api.put(`/projects/${id}/wbs/${encodeURIComponent(wbsNo)}/progress`, {
        percent,
      }),
    );
  },
};
