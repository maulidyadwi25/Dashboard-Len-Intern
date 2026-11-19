import { createRouter, createWebHistory } from "vue-router";
import Dashboard from "../pages/BudgetMonitoring.vue";
import Login from "../pages/Login.vue";
import Procurement from "../pages/Procurement.vue";
import ProgressRevenue from "../pages/ProgressRevenue.vue";
import ProgressApproval from "../pages/ProgressApproval.vue";
import { useAuth } from "../composables/useAuth";

const routes = [
  {
    path: "/login",
    name: "Login",
    component: Login,
    meta: { guestOnly: true },
  },
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
  {
    path: "/progress-approval",
    name: "ProgressApproval",
    component: ProgressApproval,
    meta: { role: "VP" },
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach(async (to) => {
  const { user, loadUser } = useAuth();

  try {
    await loadUser();
  } catch {
    return {
      name: "Login",
      query: { redirect: to.fullPath, error: "session" },
    };
  }

  if (!user.value && to.name !== "Login") {
    return { name: "Login", query: { redirect: to.fullPath } };
  }

  if (to.meta.role && user.value?.role !== to.meta.role) {
    return { name: "ProgressRevenue" };
  }

  if (user.value && to.meta.guestOnly) {
    const redirect =
      typeof to.query.redirect === "string" &&
      to.query.redirect.startsWith("/")
        ? to.query.redirect
        : "/";
    return redirect;
  }

  return true;
});

export default router;
