<!-- resources/js/components/common/Navbar.vue -->
<script setup>
import { Bell, Settings } from 'lucide-vue-next';
</script>

<template>
    <header class="sticky top-0 z-50 border-b border-slate-200 bg-white">
        <div
            class="mx-auto flex h-16 max-w-7xl items-center justify-between px-6"
        >
            <div class="flex items-center space-x-8">
                <div class="flex items-center space-x-2">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded bg-blue-600 text-sm font-bold text-white"
                    >
                        EI
                    </div>
                    <span
                        class="text-xl font-black tracking-tight text-blue-900"
                        >Executive Insights</span
                    >
                </div>
                <nav class="hidden space-x-6 md:flex">
                    <a
                        href="#"
                        class="py-5 text-sm font-medium text-slate-500 hover:text-slate-900"
                        >Summary</a
                    >
                    <a
                        href="#"
                        class="py-5 text-sm font-medium text-slate-500 hover:text-slate-900"
                        >Division Monitoring</a
                    >
                    <a
                        href="#"
                        class="border-b-2 border-blue-700 py-5 text-sm font-bold text-blue-700"
                        >Budget Monitoring</a
                    >
                </nav>
            </div>
            <div class="flex items-center space-x-4">
                <button
                    class="rounded-full p-2 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600"
                >
                    <Bell class="h-5 w-5" />
                </button>
                <button
                    class="rounded-full p-2 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600"
                >
                    <Settings class="h-5 w-5" />
                </button>
                <div
                    class="flex items-center space-x-2 border-l border-slate-200 pl-2"
                >
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-full border border-slate-300 bg-slate-200 text-xs font-bold text-slate-600"
                    >
                        AD
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>
