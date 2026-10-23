<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
const props = defineProps({ title: String, subtitle: String });
const page = usePage();
const open = ref(false);
const auth = computed(() => page.props.auth || {});
const links = computed(() => [
    { label: 'نظرة عامة', href: route('dashboard'), show: true, icon: '▦' },
    { label: 'الموظفون', href: route('employees.index'), show: true, icon: '♙' },
    { label: 'الإجازات', href: route('leaves.index'), show: true, icon: '◷' },
    { label: 'الحضور', href: route('attendance.index'), show: true, icon: '◫' },
    { label: 'الرواتب', href: route('payroll.index'), show: true, icon: '▤' },
    { label: 'التقييم', href: route('performance.index'), show: auth.value.isManager, icon: '◇' },
    { label: 'الأقسام', href: route('departments.index'), show: auth.value.isAdmin, icon: '▧' },
    { label: 'الإعدادات', href: route('settings.index'), show: auth.value.isAdmin, icon: '⚙' },
].filter(x => x.show));
const active = item => page.url.split('?')[0] === new URL(item.href).pathname;
</script>

<template>
    <div class="min-h-screen bg-[#F6F8FA] lg:flex">
        <div v-if="open" class="fixed inset-0 z-30 bg-slate-900/40 lg:hidden" @click="open=false"></div>
        <aside :class="open ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'" class="fixed inset-y-0 right-0 z-40 flex w-64 flex-col bg-[#1F3A5F] text-white transition-transform lg:sticky lg:top-0 lg:h-screen">
            <div class="flex h-20 items-center gap-3 border-b border-white/10 px-6">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg border border-white/30 bg-white/10 text-lg font-bold">H</div>
                <div><div class="text-lg font-bold tracking-tight">مدار</div><div class="text-xs text-blue-100">إدارة الموارد البشرية</div></div>
            </div>
            <nav aria-label="التنقل الرئيسي" class="flex-1 space-y-1 p-4">
                <Link v-for="item in links" :key="item.href" :href="item.href" @click="open=false" :class="active(item) ? 'bg-white text-[#1F3A5F]' : 'text-blue-50 hover:bg-white/10'" class="flex min-h-11 items-center gap-3 rounded-lg px-4 text-sm font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-white">
                    <span class="w-5 text-center text-lg" aria-hidden="true">{{ item.icon }}</span>{{ item.label }}
                </Link>
            </nav>
            <div class="border-t border-white/10 p-4 text-xs text-blue-100">نسخة تجريبية · {{ new Date().getFullYear() }}</div>
        </aside>
        <div class="min-w-0 flex-1">
            <header class="flex h-20 items-center justify-between border-b border-slate-200 bg-white px-4 sm:px-7">
                <button type="button" aria-label="فتح القائمة" class="rounded-lg border border-slate-200 p-2.5 lg:hidden" @click="open=true">☰</button>
                <div class="hidden text-sm text-slate-500 sm:block">مساحة عمل الموارد البشرية</div>
                <div class="flex items-center gap-3">
                    <div class="text-left"><div class="text-sm font-semibold text-slate-900">{{ auth.user?.name }}</div><div class="text-xs text-slate-500">{{ auth.roles?.[0] === 'super_admin' ? 'مدير النظام' : auth.roles?.[0] === 'hr_admin' ? 'مسؤول الموارد البشرية' : auth.roles?.[0] === 'department_manager' ? 'مدير قسم' : 'موظف' }}</div></div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-[#E9F3F4] font-bold text-teal-800">{{ auth.user?.name?.charAt(0) }}</div>
                    <Link :href="route('logout')" method="post" as="button" class="text-xs text-slate-500 underline hover:text-slate-900">خروج</Link>
                </div>
            </header>
            <main class="mx-auto max-w-7xl px-4 py-7 sm:px-7 lg:py-9">
                <div class="mb-7 flex flex-wrap items-end justify-between gap-4">
                    <div><p class="mb-1 text-sm font-medium text-teal-700">مدار / مساحة العمل</p><h1 class="text-3xl font-bold text-[#1F3A5F]">{{ title }}</h1><p v-if="subtitle" class="mt-2 text-sm text-slate-500">{{ subtitle }}</p></div>
                    <slot name="actions" />
                </div>
                <div v-if="page.props.flash?.success" role="status" class="mb-6 rounded-lg border border-teal-200 bg-teal-50 px-4 py-3 text-sm font-medium text-teal-900">{{ page.props.flash.success }}</div>
                <slot />
            </main>
        </div>
    </div>
</template>
