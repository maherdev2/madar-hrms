<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
defineProps({ canResetPassword:Boolean, status:String, demoEnabled:Boolean });
const form = useForm({ email:'',password:'',remember:false });
const submit = () => form.post(route('login'),{onFinish:()=>form.reset('password')});
const fill = email => { form.email=email; form.password='Demo1234!'; };
</script>
<template>
    <Head title="تسجيل الدخول" />
    <div class="grid min-h-screen bg-[#F6F8FA] lg:grid-cols-2">
        <div class="flex min-h-screen flex-col justify-between bg-[#1F3A5F] px-8 py-8 text-white sm:px-14 lg:px-20">
            <div class="flex items-center gap-3"><div class="flex h-11 w-11 items-center justify-center rounded-lg border border-white/40 bg-white/10 text-xl font-bold">H</div><div><div class="text-xl font-bold">مدار</div><div class="text-xs text-blue-100">إدارة الموارد البشرية</div></div></div>
            <div class="max-w-xl py-14"><div class="mb-8 h-1 w-16 bg-[#4DB9AD]"></div><h1 class="text-4xl font-bold leading-tight sm:text-5xl">كل تفاصيل فريقك،<br>في مسار واضح.</h1><p class="mt-7 max-w-md text-lg leading-9 text-blue-100">نظّم ملفات الموظفين، تابع الإجازات والحضور، واعتمد الرواتب بثقة من مساحة عمل واحدة.</p><div class="mt-12 flex gap-3"><div class="h-2 w-20 rounded bg-[#4DB9AD]"></div><div class="h-2 w-8 rounded bg-white/25"></div><div class="h-2 w-8 rounded bg-white/25"></div></div></div>
            <div class="text-xs text-blue-200">مدار HRMS · تجربة عمل أكثر وضوحًا</div>
        </div>
        <div class="flex items-center justify-center px-5 py-12 sm:px-10"><div class="w-full max-w-md"><p class="text-sm font-semibold text-teal-700">مرحبًا بعودتك</p><h2 class="mt-2 text-3xl font-bold text-[#1F3A5F]">تسجيل الدخول</h2><p class="mt-3 text-sm leading-7 text-slate-500">أدخل بيانات حسابك للوصول إلى مساحة العمل.</p>
            <div v-if="status" role="status" class="mt-5 rounded-lg bg-teal-50 p-3 text-sm text-teal-800">{{ status }}</div>
            <form class="mt-8 space-y-5" @submit.prevent="submit"><div><label for="email" class="label">البريد الإلكتروني</label><input id="email" v-model.trim="form.email" type="email" autocomplete="username" class="field" placeholder="name@company.com" required autofocus><p v-if="form.errors.email" class="error">{{ form.errors.email }}</p></div><div><div class="flex justify-between"><label for="password" class="label">كلمة المرور</label><Link v-if="canResetPassword" :href="route('password.request')" class="text-xs font-semibold text-teal-700 hover:underline">نسيت كلمة المرور؟</Link></div><input id="password" v-model="form.password" type="password" autocomplete="current-password" class="field" required><p v-if="form.errors.password" class="error">{{ form.errors.password }}</p></div><label class="flex items-center gap-2 text-sm text-slate-600"><input v-model="form.remember" type="checkbox" class="rounded border-slate-300 text-teal-700 focus:ring-teal-700"> تذكرني</label><button class="btn-primary w-full" :disabled="form.processing">{{ form.processing ? 'جارٍ الدخول…' : 'الدخول إلى مدار' }}</button></form>
            <div v-if="demoEnabled" class="mt-9 rounded-xl border border-slate-200 bg-white p-5"><p class="font-semibold text-[#1F3A5F]">حسابات التجربة المحلية</p><p class="mt-1 text-xs text-slate-500">اختر دورًا لتعبئة البيانات. كلمة المرور: Demo1234!</p><div class="mt-4 grid grid-cols-2 gap-2"><button type="button" class="btn-secondary text-xs" @click="fill('admin@madar.test')">مدير النظام</button><button type="button" class="btn-secondary text-xs" @click="fill('hr@madar.test')">الموارد البشرية</button><button type="button" class="btn-secondary text-xs" @click="fill('manager@madar.test')">مدير قسم</button><button type="button" class="btn-secondary text-xs" @click="fill('employee@madar.test')">موظف</button></div></div>
        </div></div>
    </div>
</template>
