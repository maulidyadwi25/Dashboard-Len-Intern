import api from "./api";

export const budgetService = {
  getDashboardData() {
    return api.get("/projects/1/dashboard");
  },

  updateDashboardData(payload) {
    return api.post("/budget-dashboard", payload);
  },

  downloadPdfReport() {
    return api.get("/budget-dashboard/pdf", { responseType: "blob" });
  },

  getGanttTasks() {
    return api.get("/gantt-tasks");
  },

  getProcurementData() {
    return api.get("/procurement");
  },

  updateProcurementStartDate(itemId, newDate) {
    return api.patch(`/procurement/${itemId}/start-date`, {
      start_date: newDate,
    });
  },

  getProgressRevenueData(month = "all") {
    console.log("[API CALL] getProgressRevenueData with month:", month, typeof month);
    return api.get("/projects/1/progress-revenue", {
      params: { month },
    });
  },
};
