import { ref } from "vue";
import { authService } from "../services/authService";

const user = ref(null);
const initialized = ref(false);
let currentUserRequest;

export function useAuth() {
  async function loadUser({ force = false } = {}) {
    if (initialized.value && !force) return user.value;
    if (currentUserRequest && !force) return currentUserRequest;

    currentUserRequest = authService
      .getCurrentUser()
      .then((authenticatedUser) => {
        user.value = authenticatedUser;
        initialized.value = true;
        return user.value;
      })
      .catch((error) => {
        if (error?.response?.status === 401) {
          user.value = null;
          initialized.value = true;
          return null;
        }

        throw error;
      })
      .finally(() => {
        currentUserRequest = undefined;
      });

    return currentUserRequest;
  }

  async function login(credentials) {
    user.value = await authService.login(credentials);
    initialized.value = true;
    return user.value;
  }

  async function logout() {
    await authService.logout();
    user.value = null;
    initialized.value = true;
  }

  return {
    user,
    initialized,
    loadUser,
    login,
    logout,
  };
}
