import axios from "axios";
import api from "./api";

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || "/api";
const apiUrl = new URL(apiBaseUrl, window.location.origin);
const apiPath = apiUrl.pathname.replace(/\/api\/?$/, "");
const authApi = axios.create({
  baseURL: `${apiUrl.origin}${apiPath}`,
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    Accept: "application/json",
  },
});

export const authService = {
  async login(credentials) {
    await authApi.get("/sanctum/csrf-cookie");
    await authApi.post("/login", credentials);
    return this.getCurrentUser();
  },

  async logout() {
    await api.post("/logout");
  },

  async getCurrentUser() {
    const response = await api.get("/user");
    return response.data?.data ?? null;
  },
};
