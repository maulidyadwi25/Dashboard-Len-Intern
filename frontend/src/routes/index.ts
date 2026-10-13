import { createRouter, createWebHistory } from "vue-router";
import Dashboard from "../pages/BudgetMonitoring.vue";
import Procurement from "../pages/Procurement.vue";
import ProgressRevenue from "../pages/ProgressRevenue.vue";

const routes = [
  {
    path: "/",
    name: "ProjectMonitoringMain",
    component: Procurement,
  },
  {
    path: "/budget-monitoring",
    name: "BudgetMonitoring",
    component: Dashboard,
  },
  {
    path: "/budget",
    redirect: "/budget-monitoring",
  },
  {
    path: "/procurement",
    name: "Procurement",
    component: Procurement,
  },
  {
    path: "/progress-revenue",
    name: "ProgressRevenue",
    component: ProgressRevenue,
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;
