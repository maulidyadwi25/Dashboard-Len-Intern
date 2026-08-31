import api from "./api";

export const budgetService = {
  getDashboardData() {
    return api.get("/budget-dashboard");
  },

  downloadPdfReport() {
    return api.get("/budget-dashboard/pdf", { responseType: "blob" });
  },

  getGanttTasks() {
    return api.get("/gantt-tasks");
  },
};
