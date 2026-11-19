import api from "./api";

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
  getWbs(id) {
    if (!id) return Promise.reject(new Error("Project ID is required."));
    return withWbsErrorHandling(() => api.get(`/projects/${id}/wbs`));
  },

  getProgressChanges(id, page = 1) {
    if (!id) return Promise.reject(new Error("Project ID is required."));
    return withWbsErrorHandling(() =>
      api.get(`/projects/${id}/wbs/progress-changes`, { params: { page } }),
    );
  },

  getProgressChangeRequests(id, page = 1, status = "pending") {
    if (!id) return Promise.reject(new Error("Project ID is required."));
    return withWbsErrorHandling(() =>
      api.get(`/projects/${id}/wbs/progress-change-requests`, {
        params: { page, status },
      }),
    );
  },

  getMyProgressChangeRequests(id, page = 1, status = "all") {
    if (!id) return Promise.reject(new Error("Project ID is required."));
    return withWbsErrorHandling(() =>
      api.get(`/projects/${id}/wbs/my-progress-change-requests`, {
        params: { page, status },
      }),
    );
  },

  reviewProgressChangeRequest(id, requestId, decision, note = "") {
    if (!id) return Promise.reject(new Error("Project ID is required."));
    const body = { decision };
    if (note && note.trim()) body.note = note.trim();
    return withWbsErrorHandling(() =>
      api.patch(
        `/projects/${id}/wbs/progress-change-requests/${requestId}`,
        body,
      ),
    );
  },

  requestProgressDecrease(id, wbsNo, percent, comment) {
    if (!id) return Promise.reject(new Error("Project ID is required."));
    return withWbsErrorHandling(() =>
      api.post(
        `/projects/${id}/wbs/${encodeURIComponent(wbsNo)}/progress-change-requests`,
        { percent, comment },
      ),
    );
  },

  getWbsWeekly(id, month = "all") {
    if (!id) return Promise.reject(new Error("Project ID is required."));
    return withWbsErrorHandling(() =>
      api.get(`/projects/${id}/wbs/weekly`, { params: { month } }),
    );
  },

  getWeekSummary(id) {
    if (!id) return Promise.reject(new Error("Project ID is required."));
    return withWbsErrorHandling(() =>
      api.get(`/projects/${id}/wbs/week-summary`),
    );
  },

  getAiSummary(id) {
    if (!id) return Promise.reject(new Error("Project ID is required."));
    return withWbsErrorHandling(() =>
      api.get(`/projects/${id}/wbs/ai-summary`),
    );
  },

  generateAiSummary(id) {
    if (!id) return Promise.reject(new Error("Project ID is required."));
    return withWbsErrorHandling(() =>
      api.post(`/projects/${id}/wbs/ai-summary`),
    );
  },

  saveAiSummary(id, content) {
    if (!id) return Promise.reject(new Error("Project ID is required."));
    return withWbsErrorHandling(() =>
      api.put(`/projects/${id}/wbs/ai-summary`, { content }),
    );
  },

  syncWbs(id) {
    if (!id) return Promise.reject(new Error("Project ID is required."));
    return withWbsErrorHandling(() => api.post(`/projects/${id}/wbs/sync`));
  },

  updateWbsProgress(id, wbsNo, percent, comment) {
    if (!id) return Promise.reject(new Error("Project ID is required."));
    return withWbsErrorHandling(() =>
      api.put(`/projects/${id}/wbs/${encodeURIComponent(wbsNo)}/progress`, {
        percent,
        ...(comment === undefined ? {} : { comment }),
      }),
    );
  },
};
