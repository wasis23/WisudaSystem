<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    wisudawan: Object,
});

const form = useForm({
    tracer_status_pekerjaan: props.wisudawan?.tracer_status_pekerjaan || 'Bekerja',
    tracer_nama_instansi: props.wisudawan?.tracer_nama_instansi || '',
    tracer_jabatan: props.wisudawan?.tracer_jabatan || '',
    tracer_pendapatan: props.wisudawan?.tracer_pendapatan || '< Rp 3.000.000',
    tracer_kesesuaian_prodi: props.wisudawan?.tracer_kesesuaian_prodi || 'Sangat Sesuai',
});

const submit = () => {
    form.post(route('wisudawan.tracer.store'));
};
</script>

<template>
    <Head title="Formulir Data Tracer Study Wisudawan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white">
                        Formulir Data Tracer Study Alumni
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Pengisian kuesioner pelacakan alumni wajib diisi sebelum melengkapi Biodata Wisuda.
                    </p>
                </div>
                <Link
                    :href="route('wisudawan.dashboard')"
                    class="px-4 py-2 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-semibold text-xs rounded-xl hover:bg-slate-300 transition"
                >
                    ← Kembali ke Dashboard
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 sm:p-8 border border-slate-200 dark:border-slate-700 shadow-xl space-y-6">
                    
                    <div class="p-4 rounded-2xl bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-700 flex items-start gap-3">
                        <span class="text-2xl">📋</span>
                        <div class="space-y-1">
                            <h3 class="text-sm font-bold text-blue-950 dark:text-blue-200">Syarat Kelengkapan Dokumen Wisuda</h3>
                            <p class="text-xs text-blue-900/80 dark:text-blue-300">
                                Data Tracer Study digunakan oleh Politeknik Indonusa Surakarta untuk akreditasi institusi dan peningkatan kualitas lulusan. Setelah menyimpan data ini, menu <strong class="font-bold text-blue-950 dark:text-white">Biodata & Live Preview Layar Wisuda</strong> akan otomatis terbuka.
                            </p>
                        </div>
                    </div>

                    <form @submit.prevent="submit" class="space-y-5">
                        
                        <!-- Status Pekerjaan -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                Status Karir / Pekerjaan Saat Ini
                            </label>
                            <select
                                v-model="form.tracer_status_pekerjaan"
                                class="w-full text-sm border-slate-300 dark:border-slate-700 rounded-xl focus:ring-blue-600 focus:border-blue-600 dark:bg-slate-900 dark:text-white"
                                required
                            >
                                <option value="Bekerja">Bekerja (Full Time / Part Time)</option>
                                <option value="Wirausaha">Wirausaha / Usaha Mandiri</option>
                                <option value="Bekerja & Wirausaha">Bekerja & Berwirausaha</option>
                                <option value="Lanjut Studi">Melanjutkan Studi (S1 / D4)</option>
                                <option value="Mencari Kerja">Belum Bekerja / Sedang Mencari Kerja</option>
                            </select>
                        </div>

                        <!-- Nama Instansi / Perusahaan -->
                        <div v-if="form.tracer_status_pekerjaan !== 'Mencari Kerja' && form.tracer_status_pekerjaan !== 'Lanjut Studi'" class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                Nama Perusahaan / Tempat Usaha / Instansi
                            </label>
                            <input
                                type="text"
                                v-model="form.tracer_nama_instansi"
                                placeholder="Contoh: PT Indonusa Media / RSUD Surakarta / Mandiri"
                                class="w-full text-sm border-slate-300 dark:border-slate-700 rounded-xl focus:ring-blue-600 focus:border-blue-600 dark:bg-slate-900 dark:text-white"
                            />
                        </div>

                        <!-- Jabatan / Posisi -->
                        <div v-if="form.tracer_status_pekerjaan !== 'Mencari Kerja' && form.tracer_status_pekerjaan !== 'Lanjut Studi'" class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                Jabatan / Posisi Pekerjaan
                            </label>
                            <input
                                type="text"
                                v-model="form.tracer_jabatan"
                                placeholder="Contoh: Software Engineer / Staff Farmasi / Owner"
                                class="w-full text-sm border-slate-300 dark:border-slate-700 rounded-xl focus:ring-blue-600 focus:border-blue-600 dark:bg-slate-900 dark:text-white"
                            />
                        </div>

                        <!-- Range Pendapatan -->
                        <div v-if="form.tracer_status_pekerjaan !== 'Mencari Kerja' && form.tracer_status_pekerjaan !== 'Lanjut Studi'" class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                Range Rata-Rata Pendapatan per Bulan
                            </label>
                            <select
                                v-model="form.tracer_pendapatan"
                                class="w-full text-sm border-slate-300 dark:border-slate-700 rounded-xl focus:ring-blue-600 focus:border-blue-600 dark:bg-slate-900 dark:text-white"
                            >
                                <option value="< Rp 3.000.000">&lt; Rp 3.000.000</option>
                                <option value="Rp 3.000.000 - Rp 5.000.000">Rp 3.000.000 - Rp 5.000.000</option>
                                <option value="Rp 5.000.000 - Rp 10.000.000">Rp 5.000.000 - Rp 10.000.000</option>
                                <option value="> Rp 10.000.000">&gt; Rp 10.000.000</option>
                            </select>
                        </div>

                        <!-- Kesesuaian Prodi -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                Kesesuaian Kurikulum Prodi dengan Bidang Pekerjaan / Dunia Kerja
                            </label>
                            <select
                                v-model="form.tracer_kesesuaian_prodi"
                                class="w-full text-sm border-slate-300 dark:border-slate-700 rounded-xl focus:ring-blue-600 focus:border-blue-600 dark:bg-slate-900 dark:text-white"
                                required
                            >
                                <option value="Sangat Sesuai">Sangat Sesuai</option>
                                <option value="Sesuai">Sesuai</option>
                                <option value="Cukup Sesuai">Cukup Sesuai</option>
                                <option value="Kurang Sesuai">Kurang Sesuai</option>
                            </select>
                        </div>

                        <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-200 dark:border-slate-700">
                            <Link
                                :href="route('wisudawan.dashboard')"
                                class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl transition"
                            >
                                Batal
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-6 py-2.5 bg-blue-700 hover:bg-blue-800 text-white text-xs font-bold rounded-xl shadow-md transition disabled:opacity-50"
                            >
                                <span v-if="form.processing">Menyimpan...</span>
                                <span v-else>Simpan Data Tracer Study →</span>
                            </button>
                        </div>

                    </form>

                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
