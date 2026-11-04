import { createRouter, createWebHistory } from 'vue-router'
import ProjectMonitoringView from '../views/ProjectMonitoringView.vue'
import ProcurementMonitoringView from '../views/ProcurementMonitoringView.vue'
import ProcurementDetailView from '../views/ProcurementDetailView.vue'

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
    },
    {
      path: '/procurement-monitoring/detail/:no',
      name: 'procurement-detail',
      component: ProcurementDetailView
    }
  ]
})

export default router