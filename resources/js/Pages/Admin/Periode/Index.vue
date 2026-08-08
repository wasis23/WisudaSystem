<script setup>
import AuthenticatedLayout from '@/Layouts/AdminLayout.vue';
import Modal from '@/Components/Modal.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    periodes: Array,
});

const showCreateModal = ref(false);

const form = useForm({
    nama_periode: '',
    nomor_periode: '',
    tahun_akademik: '2025/2026',
    tanggal_pelaksanaan: '',
    kuota_peserta: 500,
    tanggal_buka_pendaftaran: '',
    tanggal_tutup_pendaftaran: '',
    is_active: true,
});

const submitCreate = () => {
    form.post(route('admin.periode.store'), {
        onSuccess: () => {
            showCreateModal.value = false;
            form.reset();
        },
    });
};

const toggleActive = (id) => {
    router.patch(route('admin.periode.toggle', id));
};
</script>

<template>
    <Head title="Kelola Periode Wisuda" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            
            <!-- Header Card (Exact Dashboard Card Width & Padding) -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>Master Periode Wisuda</span>
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Kelola jadwal pelaksanaan, kuota peserta, dan status aktif periode wisuda institusi.
                    </p>
                </div>

                <button
                    @click="showCreateModal = true"
                    class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl transition shadow-sm flex items-center gap-2 shrink-0"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Tambah Periode Baru</span>
                </button>
            </div>

            <!-- Periode Table Card (Exact Dashboard Card Width & Padding) -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="font-bold text-sm text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        <span>Daftar Periode Wisuda ({{ periodes?.length || 0 }})</span>
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                        <thead class="bg-gray-50 dark:bg-gray-700/50 text-[11px] uppercase font-bold text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                            <tr>
                                <th class="py-3 px-3">No. Periode</th>
                                <th class="py-3 px-3">Nama Periode</th>
                                <th class="py-3 px-3">Tahun Akademik</th>
                                <th class="py-3 px-3">Pelaksanaan</th>
                                <th class="py-3 px-3">Kuota / Peserta</th>
                                <th class="py-3 px-3">Status</th>
                                <th class="py-3 px-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 font-medium">
                            <tr v-for="p in periodes" :key="p.id" class="hover:bg-gray-50/80 dark:hover:bg-gray-700/40 transition">
                                <td class="py-3 px-3 font-mono font-bold text-indigo-600 dark:text-indigo-400">#{{ p.nomor_periode }}</td>
                                <td class="py-3 px-3 font-bold text-gray-900 dark:text-white">{{ p.nama_periode }}</td>
                                <td class="py-3 px-3">{{ p.tahun_akademik }}</td>
                                <td class="py-3 px-3">{{ p.tanggal_pelaksanaan }}</td>
                                <td class="py-3 px-3 font-mono font-bold text-gray-700 dark:text-gray-300">{{ p.wisudawan_count || 0 }} / {{ p.kuota_peserta || '∞' }}</td>
                                <td class="py-3 px-3">
                                    <span
                                        :class="[
                                            'px-3 py-1 rounded-full text-[11px] font-bold border inline-flex items-center gap-1.5',
                                            p.is_active
                                                ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-800'
                                                : 'bg-gray-100 text-gray-600 border-gray-200 dark:bg-gray-700 dark:text-gray-400 dark:border-gray-600'
                                        ]"
                                    >
                                        <span :class="['w-1.5 h-1.5 rounded-full', p.is_active ? 'bg-emerald-500 animate-pulse' : 'bg-gray-400']"></span>
                                        {{ p.is_active ? 'AKTIF' : 'Non-Aktif' }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-right">
                                    <button
                                        @click="toggleActive(p.id)"
                                        :class="[
                                            'px-3 py-1.5 rounded-xl text-xs font-bold border transition',
                                            p.is_active
                                                ? 'border-rose-200 text-rose-600 hover:bg-rose-50 dark:border-rose-800 dark:text-rose-400 dark:hover:bg-rose-950/40'
                                                : 'border-indigo-200 text-indigo-600 hover:bg-indigo-50 dark:border-indigo-800 dark:text-indigo-400 dark:hover:bg-indigo-950/40'
                                        ]"
                                    >
                                        {{ p.is_active ? 'Non-aktifkan' : 'Set Aktif' }}
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="!periodes || periodes.length === 0">
                                <td colspan="7" class="py-8 text-center text-gray-400 text-xs">Belum ada periode wisuda yang dibuat.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- Create Modal -->
        <Modal :show="showCreateModal" @close="showCreateModal = false">
            <div class="p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Tambah Periode Wisuda Baru</span>
                    </h3>
                </div>

                <form @submit.prevent="submitCreate" class="space-y-4">
                    <div>
                        <label class="block text-[11px] font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Nama Periode</label>
                        <input v-model="form.nama_periode" type="text" placeholder="Contoh: Wisuda Ke-76 Tahun 2026" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-xs" required />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Nomor Periode</label>
                            <input v-model="form.nomor_periode" type="number" placeholder="76" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-xs" required />
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Tahun Akademik</label>
                            <input v-model="form.tahun_akademik" type="text" placeholder="2025/2026" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-xs" required />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Tgl Pelaksanaan</label>
                            <input v-model="form.tanggal_pelaksanaan" type="date" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-xs" required />
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Kuota Peserta</label>
                            <input v-model="form.kuota_peserta" type="number" placeholder="500" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-xs" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Tgl Buka Pendaftaran</label>
                            <input v-model="form.tanggal_buka_pendaftaran" type="datetime-local" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-xs" required />
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Tgl Tutup Pendaftaran</label>
                            <input v-model="form.tanggal_tutup_pendaftaran" type="datetime-local" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-xs" required />
                        </div>
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <input v-model="form.is_active" type="checkbox" id="is_active" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                        <label for="is_active" class="text-xs font-semibold text-gray-700 dark:text-gray-300">Set sebagai periode aktif saat ini</label>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <button type="button" @click="showCreateModal = false" class="px-4 py-2 text-xs font-bold text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition">Batal</button>
                        <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl transition shadow-sm">Simpan Periode</button>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
