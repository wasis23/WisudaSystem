<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    simpegEmployees: Array,
    dutyAssignments: Array,
    filters: Object,
});

const searchQuery = ref(props.filters?.search || '');

const form = useForm({
    simpeg_username: '',
    simpeg_id_sdm: '',
    simpeg_nip: '',
    nama_pegawai: '',
    duty_role: 'security',
});

const searchSimpeg = () => {
    router.get(route('admin.duty-assignments.index'), { search: searchQuery.value }, { preserveState: true });
};

const assignEmployee = (emp, role) => {
    form.simpeg_username = emp.username;
    form.simpeg_id_sdm = emp.id_sdm;
    form.simpeg_nip = emp.nip;
    form.nama_pegawai = emp.nama;
    form.duty_role = role;

    form.post(route('admin.duty-assignments.store'), {
        onSuccess: () => form.reset(),
    });
};

const toggleStatus = (duty) => {
    router.patch(route('admin.duty-assignments.toggle', duty.id));
};

const deleteDuty = (duty) => {
    if (confirm(`Hapus hak akses scan untuk ${duty.nama_pegawai}?`)) {
        router.delete(route('admin.duty-assignments.destroy', duty.id));
    }
};
</script>

<template>
    <Head title="Manajemen Penugasan Scan Security & Receptionist (SIMPEG)" />

    <AdminLayout>
        <div class="space-y-6">

            <!-- Header Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <span>👮‍♂️</span>
                        <span>Penugasan Gate Scan Security & Receptionist</span>
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Pemberian hak akses scan presensi wisudawan berbasis data pegawai SIMPEG.
                    </p>
                </div>
                <span class="px-4 py-1.5 bg-sky-50 dark:bg-sky-950/50 text-sky-700 dark:text-sky-300 font-semibold rounded-full text-xs border border-sky-200 dark:border-sky-800 shrink-0">
                    Referensi Database SIMPEG (wsia_profil)
                </span>
            </div>

            <!-- Active Assigned Officers -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                <h3 class="text-base font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                    <span>📋</span> Daftar Pegawai Bertugas Scan Presensi QR Code
                </h3>

                <div v-if="!dutyAssignments || dutyAssignments.length === 0" class="text-center py-10 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-2xl">
                    <span class="text-5xl">🛑</span>
                    <p class="text-gray-500 dark:text-gray-400 font-medium text-sm mt-3">Belum ada pegawai SIMPEG yang ditugaskan untuk scan presensi.</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Gunakan form pencarian di bawah untuk memilih pegawai dari SIMPEG.</p>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="duty in dutyAssignments" :key="duty.id"
                        class="p-5 rounded-2xl border transition shadow-sm relative overflow-hidden"
                        :class="duty.is_active ? 'bg-white dark:bg-gray-800 border-gray-100 dark:border-gray-700' : 'bg-gray-50 dark:bg-gray-900 border-gray-200 dark:border-gray-800 opacity-60'">
                        <div class="flex items-start justify-between">
                            <div>
                                <span class="px-3 py-1 text-[10px] font-extrabold uppercase rounded-full tracking-wider" :class="duty.duty_role === 'security' ? 'bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300' : 'bg-purple-100 dark:bg-purple-950/60 text-purple-800 dark:text-purple-300'">
                                    {{ duty.duty_role === 'security' ? '👮 Security Officer' : '👩‍💼 Receptionist' }}
                                </span>
                                <h4 class="font-bold text-sm text-gray-900 dark:text-white mt-3">{{ duty.nama_pegawai }}</h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400 font-mono mt-0.5">Username: {{ duty.simpeg_username }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 font-mono">NIP/NIDN: {{ duty.simpeg_nip || duty.simpeg_id_sdm || '-' }}</p>
                            </div>
                            <div class="flex flex-col items-end gap-2 shrink-0">
                                <button @click="toggleStatus(duty)" class="px-2.5 py-1 rounded-lg text-xs font-bold transition" :class="duty.is_active ? 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-200' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300'">
                                    {{ duty.is_active ? 'Aktif' : 'Non-Aktif' }}
                                </button>
                                <button @click="deleteDuty(duty)" class="text-xs text-red-500 hover:text-red-700 font-semibold p-1">
                                    🗑️ Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SIMPEG Employee Search & Duty Assignment -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 space-y-6">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Cari Pegawai dari SIMPEG (Live API)</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Pilih pegawai dari SIMPEG untuk diberikan hak akses scan presensi dari HP maupun laptop.</p>
                    </div>
                    <form @submit.prevent="searchSimpeg" class="flex items-center gap-2">
                        <input 
                            v-model="searchQuery" 
                            type="text" 
                            placeholder="Cari NIDN / Nama Pegawai..." 
                            class="rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-xs px-3.5 py-2 w-64 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none"
                        />
                        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-xl transition shadow-sm shrink-0">
                            🔍 Cari API
                        </button>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                        <thead class="bg-gray-50 dark:bg-gray-700/50 text-[11px] uppercase font-bold text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                            <tr>
                                <th class="py-3.5 px-4 font-bold">Pegawai</th>
                                <th class="py-3.5 px-4 font-bold">Username / Login SIMPEG</th>
                                <th class="py-3.5 px-4 font-bold">NIP / NIDN</th>
                                <th class="py-3.5 px-4 font-bold text-center">Tugaskan Sebagai</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 font-medium">
                            <tr v-for="emp in simpegEmployees" :key="emp.username" class="hover:bg-gray-50/80 dark:hover:bg-gray-700/40 transition">
                                <td class="py-3.5 px-4 font-bold text-gray-900 dark:text-white">
                                    {{ emp.nama }}
                                    <span class="block text-[11px] font-normal text-gray-400">{{ emp.email || emp.status }}</span>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-gray-700 dark:text-gray-300">{{ emp.username }}</td>
                                <td class="py-3.5 px-4 font-mono text-gray-500 dark:text-gray-400">{{ emp.nip || emp.nidn || '-' }}</td>
                                <td class="py-3.5 px-4 text-center space-x-2">
                                    <button 
                                        @click="assignEmployee(emp, 'security')" 
                                        class="px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-lg transition shadow-sm"
                                    >
                                        👮 Security
                                    </button>
                                    <button 
                                        @click="assignEmployee(emp, 'receptionist')" 
                                        class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-lg transition shadow-sm"
                                    >
                                        👩‍💼 Receptionist
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="!simpegEmployees || simpegEmployees.length === 0">
                                <td colspan="4" class="py-8 text-center text-gray-400 text-xs">
                                    Ketik nama / NIDN lalu klik <strong>Cari API</strong> untuk menemukan pegawai SIMPEG.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Direct Form Input Fallback -->
                <div class="pt-6 border-t border-gray-100 dark:border-gray-700 space-y-4">
                    <h4 class="font-bold text-xs text-gray-900 dark:text-white flex items-center gap-2">
                        <span>✏️</span> Penugasan Pegawai SIMPEG Manual (Ketik Langsung)
                    </h4>
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                        <input 
                            v-model="form.nama_pegawai" 
                            type="text" 
                            placeholder="Nama Lengkap Pegawai..." 
                            class="rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-xs px-3 py-2 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none"
                        />
                        <input 
                            v-model="form.simpeg_username" 
                            type="text" 
                            placeholder="Username SIMPEG / NIDN..." 
                            class="rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-xs px-3 py-2 font-mono text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none"
                        />
                        <select v-model="form.duty_role" class="rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-xs px-3 py-2 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none">
                            <option value="security">👮 Duty Security</option>
                            <option value="receptionist">👩‍💼 Duty Receptionist</option>
                        </select>
                        <button 
                            @click="form.post(route('admin.duty-assignments.store'))" 
                            :disabled="form.processing || !form.nama_pegawai || !form.simpeg_username"
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-bold text-xs rounded-xl transition shadow-sm"
                        >
                            + Simpan Penugasan
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>
