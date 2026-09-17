import { createRouter, createWebHistory } from 'vue-router'
import ProjectMonitoringView from '../views/ProjectMonitoringView.vue'
import ProcurementMonitoringView from '../views/ProcurementMonitoringView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: '/project-monitoring'
    },
    {
      path: '/project-monitoring',
      name: 'project-monitoring',
      component: ProjectMonitoringView
    },
    {
      path: '/procurement-monitoring',
      name: 'procurement-monitoring',
      component: ProcurementMonitoringView
    }
  ]
})

export default router