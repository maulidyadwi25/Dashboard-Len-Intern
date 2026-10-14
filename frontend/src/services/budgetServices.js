import api from "./api";

export const budgetService = {
  getProjects() {
    return api.get("/projects");
  },

  getDashboardData(projectId) {
    return api.get(`/projects/${projectId}/dashboard`);
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

  getProgressRevenueData(projectId, month = "all") {
    return api.get(`/projects/${projectId}/progress-revenue`, {
      params: { month },
    });
  },
};
