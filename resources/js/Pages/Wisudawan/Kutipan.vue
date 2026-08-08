<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    wisudawan: Object,
    kutipan: Object,
});

const form = useForm({
    kesan_pesan: props.kutipan?.kesan_pesan || '',
    cita_cita: props.kutipan?.cita_cita || '',
    motto_hidup: props.kutipan?.motto_hidup || '',
    instagram: props.kutipan?.social_media_handles?.instagram || '',
    linkedin: props.kutipan?.social_media_handles?.linkedin || '',
});

const submitForm = () => {
    form.post(route('wisudawan.kutipan.store'), {
        onSuccess: () => {
            // Handled by controller redirect
        },
    });
};
</script>

<template>
    <Head title="Buku Kenangan - Kesan, Pesan & Motto" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Formulir Buku Kenangan Wisuda</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Tuliskan pesan, kesan, motto, serta cita-cita untuk dicetak pada Buku Kenangan Wisuda.</p>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <form @submit.prevent="submitForm" class="space-y-6 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/60 shadow-sm p-6 sm:p-8">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">
                            Kesan & Pesan Selama Kuliah <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            v-model="form.kesan_pesan"
                            rows="4"
                            placeholder="Bagikan pengalaman terbaik, ucapan terima kasih kepada dosen, sahabat, atau almamater..."
                            class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 text-sm"
                            required
                        ></textarea>
                        <p class="text-[11px] text-slate-400 mt-1">Maksimal 1.000 karakter.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">
                            Motto Hidup
                        </label>
                        <input
                            v-model="form.motto_hidup"
                            type="text"
                            placeholder="Contoh: Man Jadda Wajada / Consistency is the key to mastery."
                            class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 text-sm"
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">
                                Cita-cita / Impian Karir
                            </label>
                            <input
                                v-model="form.cita_cita"
                                type="text"
                                placeholder="Contoh: Senior AI Engineer / Founder Startup"
                                class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 text-sm"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">
                                Instagram Handle
                            </label>
                            <input
                                v-model="form.instagram"
                                type="text"
                                placeholder="@username"
                                class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 text-sm"
                            />
                        </div>
                    </div>

                    <div class="pt-6 border-t border-slate-100 dark:border-slate-700 flex justify-end">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-bold text-sm rounded-xl transition shadow-lg shadow-indigo-500/25"
                        >
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Untuk Buku Kenangan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
