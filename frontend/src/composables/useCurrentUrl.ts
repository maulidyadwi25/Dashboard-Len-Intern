import { computed, readonly, type ComputedRef, type DeepReadonly } from "vue";
import { useRoute, useRouter, type RouteLocationRaw } from "vue-router";

export type UrlTarget = string | RouteLocationRaw;

export type UseCurrentUrlReturn = {
  currentUrl: DeepReadonly<ComputedRef<string>>;
  isCurrentUrl: (
    urlToCheck: UrlTarget,
    currentUrl?: string,
    startsWith?: boolean,
  ) => boolean;
  isCurrentOrParentUrl: (urlToCheck: UrlTarget, currentUrl?: string) => boolean;
  whenCurrentUrl: <T, F = null>(
    urlToCheck: UrlTarget,
    ifTrue: T,
    ifFalse?: F,
  ) => T | F;
};

export function useCurrentUrl(): UseCurrentUrlReturn {
  const route = useRoute();
  const router = useRouter();

  // Mengambil path aktif secara reaktif (contoh: '/dashboard' atau '/users/1')
  const currentUrlReactive = computed(() => route.path);

  function resolvePath(target: UrlTarget): string {
    if (typeof target === "string") {
      if (target.startsWith("http://") || target.startsWith("https://")) {
        try {
          return new URL(target).pathname;
        } catch {
          return target;
        }
      }
      return target;
    }

    // Resolusi jika target berupa object route: { name: 'user.detail', params: { id: 1 } }
    try {
      return router.resolve(target).path;
    } catch {
      return "";
    }
  }

  function isCurrentUrl(
    urlToCheck: UrlTarget,
    currentUrl?: string,
    startsWith: boolean = false,
  ): boolean {
    const urlToCompare = currentUrl ?? currentUrlReactive.value;
    const targetPath = resolvePath(urlToCheck);

    if (!targetPath) return false;

    return startsWith
      ? urlToCompare.startsWith(targetPath)
      : urlToCompare === targetPath;
  }

  function isCurrentOrParentUrl(
    urlToCheck: UrlTarget,
    currentUrl?: string,
  ): boolean {
    return isCurrentUrl(urlToCheck, currentUrl, true);
  }

  function whenCurrentUrl<T, F = null>(
    urlToCheck: UrlTarget,
    ifTrue: T,
    ifFalse: F = null as unknown as F,
  ): T | F {
    return isCurrentUrl(urlToCheck) ? ifTrue : ifFalse;
  }

  return {
    currentUrl: readonly(currentUrlReactive),
    isCurrentUrl,
    isCurrentOrParentUrl,
    whenCurrentUrl,
  };
}
