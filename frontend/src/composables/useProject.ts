import { ref, computed } from "vue";
import api from "../services/api";

export interface ProjectOption {
  id: number;
  project_code: string;
  project_name: string;
}

const activeProjectId = ref<number | string | null>(null);
const projects = ref<ProjectOption[]>([]);
const isLoadingProjects = ref(false);

export function useProject() {
  const currentProject = computed(() => {
    return (
      projects.value.find(
        (p) => String(p.id) === String(activeProjectId.value),
      ) || null
    );
  });

  const fetchProjects = async () => {
    if (isLoadingProjects.value) return;
    isLoadingProjects.value = true;
    try {
      const res = await api.get("/projects");
      const list = res.data?.data ?? [];
      projects.value = list;
      if (
        list.length > 0 &&
        !list.some(
          (p: ProjectOption) => String(p.id) === String(activeProjectId.value),
        )
      ) {
        activeProjectId.value = list[0].id;
      }
    } catch (err) {
      console.error("Failed to load projects list:", err);
    } finally {
      isLoadingProjects.value = false;
    }
  };

  const setActiveProjectId = (id: number | string | null) => {
    activeProjectId.value = id;
  };

  return {
    activeProjectId,
    projects,
    currentProject,
    isLoadingProjects,
    fetchProjects,
    setActiveProjectId,
  };
}
