<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import HrmsLayout from '@/Layouts/HrmsLayout.vue';
import HrmsStatus from '@/Components/HrmsStatus.vue';
const props = defineProps({ requests:Array, types:Array, balances:Array, canRequest:Boolean, canApprove:Boolean });
const page = usePage();
const showForm = ref(false);
const selected = ref(props.requests[0] || null);
const form = useForm({ leave_type_id: props.types[0]?.id || '', starts_on:'', ends_on:'', reason:'' });
const decision = useForm({ decision:'approved', comment:'' });
const formatDate = v => v ? new Date(v).toLocaleDateString('ar-SA', { year:'numeric', month:'short', day:'numeric' }) : '—';
const canAct = computed(() => {
    const item = selected.value, auth = page.props.auth;
    if (!item || item.status !== 'pending' || !item.current_step || auth.employee?.id === item.employee_id) return false;
    if (item.current_step.approver_scope === 'department_manager') return auth.isAdmin || (auth.roles.includes('department_manager') && auth.employee?.id === item.department?.manager_employee_id);
    return item.current_step.approver_role?.code === 'super_admin' ? auth.roles.includes('super_admin') : auth.isAdmin;
});
const submit = () => form.post(route('leaves.store'), { onSuccess: () => { showForm.value=false; form.reset(); } });
const decide = value => { decision.decision=value; decision.post(route('leaves.decide', selected.value.id), { onSuccess: () => { decision.reset(); selected.value=null; } }); };
const cancel = item => { if (confirm('هل تريد إلغاء هذا الطلب؟')) useForm({}).post(route('leaves.cancel', item.id)); };
</script>
<template>
    <Head title="الإجازات" />
    <HrmsLayout title="الإجازات" subtitle="تابع رصيدك وطلباتك ومسار الموافقة في صفحة واحدة.">
        <template #actions><button v-if="canRequest" class="btn-primary" @click="showForm=!showForm">{{ showForm ? 'إغلاق النموذج' : 'طلب إجازة جديد' }}</button></template>
        <section v-if="balances.length" class="mb-6 grid gap-3 sm:grid-cols-3">
            <div v-for="balance in balances" :key="balance.id" class="panel flex items-center justify-between p-5"><div><p class="text-sm text-slate-500">{{ balance.leave_type?.name }}</p><p class="mt-1 text-xs text-slate-400">الرصيد المتاح</p></div><div class="text-3xl font-bold text-[#1F3A5F]">{{ balance.available_days }} <span class="text-sm font-normal text-slate-500">يوم</span></div></div>
        </section>
        <section v-if="showForm" class="panel mb-6 p-5 sm:p-6" aria-label="طلب إجازة جديد">
            <div class="mb-5 flex items-center justify-between"><h2 class="text-xl font-bold text-[#1F3A5F]">طلب إجازة جديد</h2><p class="text-sm text-slate-500">تُحسب أيام العمل من الاثنين إلى الجمعة</p></div>
            <form @submit.prevent="submit" class="grid gap-4 sm:grid-cols-2">
                <div><label class="label" for="leave_type">نوع الإجازة</label><select id="leave_type" v-model="form.leave_type_id" class="field" required><option v-for="type in types" :key="type.id" :value="type.id">{{ type.name }}</option></select><p v-if="form.errors.leave_type_id" class="error">{{ form.errors.leave_type_id }}</p></div>
                <div></div>
                <div><label class="label" for="starts_on">من تاريخ</label><input id="starts_on" v-model="form.starts_on" type="date" class="field" required><p v-if="form.errors.starts_on" class="error">{{ form.errors.starts_on }}</p></div>
                <div><label class="label" for="ends_on">إلى تاريخ</label><input id="ends_on" v-model="form.ends_on" type="date" class="field" required><p v-if="form.errors.ends_on" class="error">{{ form.errors.ends_on }}</p></div>
                <div class="sm:col-span-2"><label class="label" for="reason">سبب الطلب <span class="font-normal text-slate-400">(اختياري)</span></label><textarea id="reason" v-model.trim="form.reason" rows="3" class="field" placeholder="اكتب تفاصيل تساعد في مراجعة الطلب"></textarea><p v-if="form.errors.reason" class="error">{{ form.errors.reason }}</p></div>
                <div class="sm:col-span-2 flex justify-end"><button class="btn-primary" :disabled="form.processing">{{ form.processing ? 'جارٍ الإرسال…' : 'إرسال طلب الإجازة' }}</button></div>
            </form>
        </section>
        <div class="grid gap-6 xl:grid-cols-[1.4fr_1fr]">
            <section class="panel overflow-hidden"><div class="border-b border-slate-100 px-5 py-4"><h2 class="text-lg font-bold text-[#1F3A5F]">الطلبات</h2><p class="mt-1 text-sm text-slate-500">اختر طلبًا لعرض خطوات المراجعة</p></div>
                <div v-if="!requests.length" class="p-8 text-sm text-slate-500">لا توجد طلبات إجازة بعد. ابدأ بطلب جديد.</div>
                <button v-for="item in requests" :key="item.id" type="button" @click="selected=item" :class="selected?.id===item.id ? 'bg-[#E9F3F4]' : 'hover:bg-slate-50'" class="flex w-full items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 text-right last:border-0 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-teal-700">
                    <div><div class="font-semibold text-[#1F3A5F]">{{ item.type?.name }} <span class="text-xs font-normal text-slate-400">#{{ item.id }}</span></div><div class="mt-1 text-xs text-slate-500">{{ item.employee?.first_name }} {{ item.employee?.last_name }} · {{ formatDate(item.starts_on) }} — {{ formatDate(item.ends_on) }} · {{ item.requested_days }} يوم</div></div><HrmsStatus :value="item.status" />
                </button>
            </section>
            <section class="panel p-5 sm:p-6"><template v-if="selected"><div class="flex items-start justify-between gap-3"><div><p class="text-sm text-slate-500">تفاصيل الطلب #{{ selected.id }}</p><h2 class="mt-1 text-xl font-bold text-[#1F3A5F]">{{ selected.type?.name }}</h2></div><HrmsStatus :value="selected.status" /></div>
                <p class="mt-4 text-sm leading-7 text-slate-600">{{ selected.reason || 'لم يضف الموظف سببًا لهذا الطلب.' }}</p>
                <div class="mt-6 border-t border-slate-100 pt-5"><h3 class="mb-4 text-sm font-bold text-[#1F3A5F]">مسار الموافقة</h3><ol class="space-y-4"><li v-for="(approval,index) in selected.approvals" :key="approval.id" class="flex gap-3"><div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border text-xs font-bold" :class="approval.status==='approved' ? 'border-teal-700 bg-teal-700 text-white' : 'border-slate-300 text-slate-600'">{{ index+1 }}</div><div><div class="text-sm font-semibold text-slate-800">{{ approval.step?.approver_role?.name }}</div><div class="mt-1 text-xs text-slate-500">{{ approval.status==='approved' ? 'تمت الموافقة' : approval.status==='rejected' ? 'تم الرفض' : 'بانتظار القرار' }}</div><p v-if="approval.comment" class="mt-1 text-xs text-slate-600">{{ approval.comment }}</p></div></li></ol></div>
                <div v-if="canAct" class="mt-6 border-t border-slate-100 pt-5"><label class="label" for="decision_comment">تعليق القرار</label><textarea id="decision_comment" v-model="decision.comment" rows="2" class="field" placeholder="سبب أو ملاحظة للمراجعة"></textarea><div class="mt-3 flex gap-2"><button class="btn-primary flex-1" :disabled="decision.processing" @click="decide('approved')">موافقة</button><button class="btn-danger flex-1" :disabled="decision.processing" @click="decide('rejected')">رفض</button></div></div>
                <button v-if="selected.employee_id===page.props.auth.employee?.id && selected.status==='pending'" class="mt-5 text-sm font-semibold text-red-700 underline" @click="cancel(selected)">إلغاء الطلب</button>
                </template><div v-else class="text-sm text-slate-500">اختر طلبًا من القائمة لعرض تفاصيله.</div></section>
        </div>
    </HrmsLayout>
</template>
