<script setup>
import { Head, Link } from '@inertiajs/vue3';
import HrmsLayout from '@/Layouts/HrmsLayout.vue';
import HrmsStatus from '@/Components/HrmsStatus.vue';
defineProps({ stats:Object, recentLeaves:Array });
const date = value => value ? new Date(value).toLocaleDateString('ar-SA') : '—';
</script>
<template>
    <Head title="نظرة عامة" />
    <HrmsLayout title="نظرة عامة" subtitle="كل ما تحتاج متابعته اليوم في مكان واحد.">
        <template #actions><Link :href="route('leaves.index')" class="btn-primary">طلب إجازة <span aria-hidden="true">←</span></Link></template>
        <div class="mb-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="panel p-5"><p class="text-sm text-slate-500">الموظفون في نطاقك</p><p class="mt-4 text-4xl font-bold text-[#1F3A5F]">{{ stats.employees }}</p><div class="mt-4 h-1 w-12 rounded bg-teal-700"></div></div>
            <div class="panel p-5"><p class="text-sm text-slate-500">طلبات قيد المراجعة</p><p class="mt-4 text-4xl font-bold text-[#1F3A5F]">{{ stats.pendingLeaves }}</p><div class="mt-4 h-1 w-12 rounded bg-amber-600"></div></div>
            <div class="panel p-5"><p class="text-sm text-slate-500">حاضرون اليوم</p><p class="mt-4 text-4xl font-bold text-[#1F3A5F]">{{ stats.presentToday }}</p><div class="mt-4 h-1 w-12 rounded bg-[#0F766E]"></div></div>
            <div class="panel p-5"><p class="text-sm text-slate-500">قسائم راتبي المنشورة</p><p class="mt-4 text-4xl font-bold text-[#1F3A5F]">{{ stats.myPayslips }}</p><div class="mt-4 h-1 w-12 rounded bg-slate-400"></div></div>
        </div>
        <div class="grid gap-6 xl:grid-cols-[1.7fr_1fr]">
            <section class="panel overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4"><h2 class="text-lg font-bold text-[#1F3A5F]">آخر طلبات الإجازة</h2><Link :href="route('leaves.index')" class="text-sm font-semibold text-teal-700 hover:underline">عرض الكل</Link></div>
                <div v-if="!recentLeaves.length" class="p-8 text-sm text-slate-500">لا توجد طلبات حتى الآن. يمكنك إنشاء أول طلب من صفحة الإجازات.</div>
                <div v-for="item in recentLeaves" :key="item.id" class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 px-5 py-4 last:border-0">
                    <div><div class="font-semibold text-slate-800">{{ item.employee?.first_name }} {{ item.employee?.last_name }}</div><div class="mt-1 text-xs text-slate-500">{{ item.type?.name }} · {{ date(item.starts_on) }} إلى {{ date(item.ends_on) }}</div></div><HrmsStatus :value="item.status" />
                </div>
            </section>
            <section class="panel p-6"><p class="text-sm font-semibold text-teal-700">خطوة تالية</p><h2 class="mt-3 text-xl font-bold text-[#1F3A5F]">رتّب يوم العمل بهدوء</h2><p class="mt-3 text-sm leading-7 text-slate-600">تابع الحضور، وراجع رصيد الإجازة، ثم انتقل إلى طلبات فريقك التي تحتاج قرارًا.</p><div class="mt-6 space-y-2"><Link :href="route('attendance.index')" class="btn-secondary w-full">عرض سجلات الحضور</Link><Link :href="route('leaves.index')" class="btn-secondary w-full">مراجعة الإجازات</Link></div></section>
        </div>
    </HrmsLayout>
</template>
