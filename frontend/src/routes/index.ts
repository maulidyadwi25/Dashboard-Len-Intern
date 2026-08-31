import { createRouter, createWebHistory } from "vue-router";
import Dashboard from "../pages/Dashboard.vue";
import DashboardGantt from "../pages/DashboardGantt.vue";

const routes = [
  {
    path: "/",
    name: "DashboardMain",
    component: Dashboard,
  },
  {
    path: "/gantt",
    name: "DashboardGantt",
    component: DashboardGantt,
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;
