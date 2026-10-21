import { ref, computed } from "vue";
import api from "../services/api";
import router from "../routes";

export interface ProjectOption {
  id: number;
  project_code: string;
  project_name: string;
  client_name?: string | null;
  pimpro?: string | null;
  analyst?: string | null;
}

const STORAGE_KEY = "activeProjectId";

function getInitialProjectId(): string | number | null {
  if (typeof window !== "undefined") {
    const urlParams = new URLSearchParams(window.location.search);
    const queryProj = urlParams.get("project");
    if (queryProj) {
      return queryProj;
    }

    const storedProj = localStorage.getItem(STORAGE_KEY);
    if (storedProj) {
      return storedProj;
    }
  }
  return null;
}

const activeProjectId = ref<number | string | null>(getInitialProjectId());
const projects = ref<ProjectOption[]>([]);
const isLoadingProjects = ref(false);

function syncUrlAndStorage(id: number | string | null) {
  if (typeof window === "undefined") return;

  if (id !== null && id !== undefined && id !== "") {
    localStorage.setItem(STORAGE_KEY, String(id));
    if (router.currentRoute.value) {
      const currentQuery = { ...router.currentRoute.value.query };
      if (String(currentQuery.project) !== String(id)) {
        router.replace({
          query: {
            ...currentQuery,
            project: String(id),
          },
        }).catch(() => {});
      }
    }
  } else {
    localStorage.removeItem(STORAGE_KEY);
    if (router.currentRoute.value) {
      const currentQuery = { ...router.currentRoute.value.query };
      if (currentQuery.project) {
        delete currentQuery.project;
        router.replace({ query: currentQuery }).catch(() => {});
      }
    }
  }
}

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
      const list: ProjectOption[] = res.data?.data ?? [];
      projects.value = list;

      if (list.length === 0) {
        activeProjectId.value = null;
        syncUrlAndStorage(null);
        return;
      }

      // Check priority: query ?project= -> localStorage -> list[0]
      let candidateId: string | number | null = null;
      if (typeof window !== "undefined") {
        const urlParams = new URLSearchParams(window.location.search);
        candidateId = urlParams.get("project");
        if (!candidateId && router.currentRoute.value?.query?.project) {
          candidateId = String(router.currentRoute.value.query.project);
        }
        if (!candidateId) {
          candidateId = localStorage.getItem(STORAGE_KEY);
        }
      }

      const match = list.find((p) => String(p.id) === String(candidateId));
      if (match) {
        activeProjectId.value = match.id;
        syncUrlAndStorage(match.id);
      } else {
        // Fallback: If saved/query ID is not in list, fallback to first item
        const fallbackId = list[0].id;
        activeProjectId.value = fallbackId;
        syncUrlAndStorage(fallbackId);
      }
    } catch (err) {
      console.error("Failed to load projects list:", err);
    } finally {
      isLoadingProjects.value = false;
    }
  };

  const setActiveProjectId = (id: number | string | null) => {
    activeProjectId.value = id;
    syncUrlAndStorage(id);
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
