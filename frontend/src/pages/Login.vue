<script setup>
import { ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { LayoutDashboard, LoaderCircle } from "lucide-vue-next";
import { useAuth } from "../composables/useAuth";

const route = useRoute();
const router = useRouter();
const { login } = useAuth();
const email = ref("");
const password = ref("");
const errorMessage = ref(
  route.query.error === "session"
    ? "Sesi tidak dapat diverifikasi. Coba lagi."
    : "",
);
const submitting = ref(false);

async function submitLogin() {
  if (submitting.value) return;
  submitting.value = true;
  errorMessage.value = "";

  try {
    await login({ email: email.value, password: password.value });
    const redirect =
      typeof route.query.redirect === "string" &&
      route.query.redirect.startsWith("/")
        ? route.query.redirect
        : "/";
    await router.replace(redirect);
  } catch (error) {
    const status = error?.response?.status;
    errorMessage.value =
      status === 422
        ? "Email atau password tidak valid."
        : "Login gagal. Periksa koneksi dan coba lagi.";
  } finally {
    submitting.value = false;
  }
}
</script>

<template>
  <main
    class="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-10"
  >
    <section
      class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-sm"
    >
      <div class="mb-8 flex items-center gap-3">
        <div
          class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-600 text-white"
        >
          <LayoutDashboard class="h-6 w-6" />
        </div>
        <div>
          <h1 class="text-lg font-bold text-slate-900">Project Monitoring</h1>
          <p class="text-sm text-slate-500">Masuk dengan akun PA atau VP</p>
        </div>
      </div>

      <form class="space-y-5" @submit.prevent="submitLogin">
        <label class="block space-y-1.5">
          <span class="text-sm font-medium text-slate-700">Email</span>
          <input
            v-model.trim="email"
            type="email"
            autocomplete="username"
            required
            class="h-11 w-full rounded-lg border border-slate-300 px-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
          />
        </label>

        <label class="block space-y-1.5">
          <span class="text-sm font-medium text-slate-700">Password</span>
          <input
            v-model="password"
            type="password"
            autocomplete="current-password"
            required
            class="h-11 w-full rounded-lg border border-slate-300 px-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
          />
        </label>

        <p
          v-if="errorMessage"
          role="alert"
          class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
        >
          {{ errorMessage }}
        </p>

        <button
          type="submit"
          :disabled="submitting"
          class="flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
        >
          <LoaderCircle v-if="submitting" class="h-4 w-4 animate-spin" />
          {{ submitting ? "Memproses..." : "Masuk" }}
        </button>
      </form>
    </section>
  </main>
</template>
