import { createRouter, createWebHistory } from "vue-router";
import Dashboard from "../pages/BudgetMonitoring.vue";
import Procurement from "../pages/Procurement.vue";

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
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;
