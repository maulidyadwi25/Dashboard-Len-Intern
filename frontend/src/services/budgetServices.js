import api from "./api";

export const budgetService = {
  getProjects() {
    return api.get("/projects");
  },

  getDashboardData(projectId) {
    if (!projectId) {
      return Promise.reject(new Error("Project ID is required."));
    }
    return api.get(`/projects/${projectId}/dashboard`);
  },

  updateDashboardData(payload) {
    return api.post("/budget-dashboard", payload);
  },

  downloadPdfReport(projectId) {
    if (!projectId) {
      return Promise.reject(new Error("Project ID is required."));
    }
    return api.get("/budget-dashboard/pdf", {
      params: { project_id: projectId },
      responseType: "blob",
    });
  },

  getGanttTasks(projectId) {
    return api.get("/gantt-tasks", {
      params: projectId ? { project_id: projectId } : {},
    });
  },

  getProcurementData(projectId) {
    return api.get("/procurement", {
      params: projectId ? { project_id: projectId } : {},
    });
  },

  updateProcurementStartDate(itemId, newDate) {
    return api.patch(`/procurement/${itemId}/start-date`, {
      start_date: newDate,
    });
  },

  getProgressRevenueData(projectId, month = "all") {
    if (!projectId) {
      return Promise.reject(new Error("Project ID is required."));
    }
    return api.get(`/projects/${projectId}/progress-revenue`, {
      params: { month },
    });
  },
};
