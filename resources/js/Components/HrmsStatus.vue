<script setup>
import { computed } from 'vue';
const props = defineProps({ value: String });
const labels = { pending:'قيد المراجعة', approved:'معتمد', rejected:'مرفوض', cancelled:'ملغى', draft:'مسودة', present:'حاضر', absent:'غائب', leave:'إجازة', holiday:'عطلة', calculated:'محسوب', open:'مفتوح', locked:'مقفل', submitted:'مقدم', finalized:'نهائي' };
const tone = computed(() => ({ approved:'bg-teal-50 text-teal-800 border-teal-200', present:'bg-teal-50 text-teal-800 border-teal-200', rejected:'bg-red-50 text-red-800 border-red-200', absent:'bg-red-50 text-red-800 border-red-200', pending:'bg-amber-50 text-amber-800 border-amber-200', calculated:'bg-amber-50 text-amber-800 border-amber-200' })[props.value] || 'bg-slate-50 text-slate-700 border-slate-200');
</script>
<template><span :class="tone" class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold">{{ labels[value] || value }}</span></template>
