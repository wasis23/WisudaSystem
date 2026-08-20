<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    wisudawan: Object,
});

// Existing tracer_study_data, tracer_study relation, or default values
const savedData = props.wisudawan?.tracer_study_data || props.wisudawan?.tracer_study || {};

const form = useForm({
    // Section 1: Data Diri & Akademik
    nim: savedData.nim || props.wisudawan?.nim || '',
    nama_lengkap: savedData.nama_lengkap || props.wisudawan?.nama_lengkap || '',
    email: savedData.email || props.wisudawan?.email || props.wisudawan?.user?.email || '',
    no_whatsapp: savedData.no_whatsapp || props.wisudawan?.nomor_hp || '',
    prodi: savedData.prodi || (props.wisudawan?.program_studi?.nama_prodi ? [props.wisudawan.program_studi.nama_prodi] : []),
    prodi_lainnya: savedData.prodi_lainnya || '',
    jenis_kelas: savedData.jenis_kelas || 'Reguler',
    alamat_lengkap: savedData.alamat_lengkap || props.wisudawan?.alamat || '',

    // Section 2: Status Pekerjaan & Karir
    status_saat_ini: savedData.status_saat_ini || [],
    status_lainnya: savedData.status_lainnya || '',
    tempat_bekerja: savedData.tempat_bekerja || '',
    gaji_per_bulan: savedData.gaji_per_bulan || [],
    keselarasan_pekerjaan: savedData.keselarasan_pekerjaan || [],
    kesesuaian_pendidikan: savedData.kesesuaian_pendidikan || [],
    waktu_tunggu: savedData.waktu_tunggu || [],
    alamat_tempat_kerja: savedData.alamat_tempat_kerja || '',
    jenis_instansi: savedData.jenis_instansi || [],
    jenis_instansi_lainnya: savedData.jenis_instansi_lainnya || '',
    nama_perusahaan: savedData.nama_perusahaan || '',
    posisi_jabatan: savedData.posisi_jabatan || [],
    posisi_lainnya: savedData.posisi_lainnya || '',
    cakupan_tempat_kerja: savedData.cakupan_tempat_kerja || [],
    tingkat_tempat_kerja_lainnya: savedData.tingkat_tempat_kerja_lainnya || '',

    // Section 3: Kewirausahaan & Studi Lanjut
    nama_usaha: savedData.nama_usaha || '',
    gaji_usaha: savedData.gaji_usaha || [],
    keselarasan_usaha: savedData.keselarasan_usaha || [],
    studi_lanjut: savedData.studi_lanjut || [],
    kampus_studi_lanjut: savedData.kampus_studi_lanjut || '',
    alamat_kampus_studi_lanjut: savedData.alamat_kampus_studi_lanjut || '',
    sumber_dana: savedData.sumber_dana || [],
    sumber_dana_lainnya: savedData.sumber_dana_lainnya || '',

    // Section 4: Evaluasi Kompetensi & Pembelajaran (Matriks)
    kompetensi_lulus: savedData.kompetensi_lulus || {
        'Etika': '4',
        'Keahlian berdasarkan bidang ilmu': '4',
        'Bahasa Inggris': '3',
        'Penggunaan Teknologi Informasi': '4',
        'Komunikasi': '4',
        'Kerja sama tim': '4',
        'Pengembangan Diri': '4',
    },
    kompetensi_kerja: savedData.kompetensi_kerja || {
        'Etika': '4',
        'Keahlian berdasarkan bidang ilmu': '4',
        'Bahasa Inggris': '3',
        'Penggunaan Teknologi Informasi': '4',
        'Komunikasi': '4',
        'Kerja sama tim': '4',
        'Pengembangan Diri': '4',
    },
    metode_pembelajaran: savedData.metode_pembelajaran || {
        'Perkuliahan': '4',
        'Demonstrasi': '4',
        'Partisipasi dalam proyek riset': '3',
        'Magang': '4',
        'Praktikum': '4',
        'Kerja Lapangan': '4',
        'Diskusi': '4',
    },

    // Section 5: Kepuasan & Masukan
    kepuasan_layanan: savedData.kepuasan_layanan || [],
    saran_masukan: savedData.saran_masukan || '',
});

// Helper for multi-select checkboxes
const toggleCheckbox = (field, value) => {
    const index = form[field].indexOf(value);
    if (index > -1) {
        form[field].splice(index, 1);
    } else {
        form[field].push(value);
    }
};

const activeTab = ref(1);

const isSection1Valid = computed(() => {
    return form.nim && form.nama_lengkap && form.email && form.no_whatsapp && form.prodi.length > 0 && form.jenis_kelas && form.alamat_lengkap;
});

const isSection2Valid = computed(() => {
    return form.status_saat_ini.length > 0;
});

const isSection4Valid = computed(() => {
    const aspekKeys = ['Etika', 'Keahlian berdasarkan bidang ilmu', 'Bahasa Inggris', 'Penggunaan Teknologi Informasi', 'Komunikasi', 'Kerja sama tim', 'Pengembangan Diri'];
    const metodeKeys = ['Perkuliahan', 'Demonstrasi', 'Partisipasi dalam proyek riset', 'Magang', 'Praktikum', 'Kerja Lapangan', 'Diskusi'];

    const validLulus = aspekKeys.every(k => form.kompetensi_lulus[k]);
    const validKerja = aspekKeys.every(k => form.kompetensi_kerja[k]);
    const validMetode = metodeKeys.every(k => form.metode_pembelajaran[k]);

    return validLulus && validKerja && validMetode;
});

const isSection5Valid = computed(() => {
    return form.kepuasan_layanan.length > 0 && form.saran_masukan.trim() !== '';
});

const submit = () => {
    form.post(route('wisudawan.tracer.store'));
};

const prodiOptions = [
    'Teknologi Rekayasa Otomotif',
    'Teknologi Rekayasa Perangkat Lunak',
    'Produksi Media',
    'Perhotelan',
    'Farmasi',
    'Manajemen Informasi Kesehatan',
    'Teknologi Laboratorium Medis',
];

const statusOptions = [
    'Bekerja (full time/part time)',
    'Berwirausaha',
    'Melanjutkan Pendidikan',
    'Tidak bekerja tetapi sedang mencari kerja',
    'Belum memungkinkan bekerja',
];

const gajiOptions = [
    '< Rp2.000.000,00',
    '> Rp2.000.000,00 - Rp3.000.000,00',
    'Rp3.000.000,00 - Rp5.000.000,00',
    'Rp5.000.000,00 - Rp10.000.000,00',
    '> Rp10.000.000,00',
];

const keselarasanOptions = ['Sangat Erat', 'Erat', 'Cukup Erat', 'Kurang Erat', 'Tidak Sama Sekali'];

const kesesuaianPendidikanOptions = [
    'Setingkat Lebih Tinggi',
    'Tingkat yang Sama',
    'Setingkat Lebih Rendah',
    'Tidak Perlu Pendidikan Tinggi',
];

const waktuTungguOptions = ['WT ≤ 3 bulan', '3 bulan < WT ≤ 6 bulan', 'WT > 6 bulan'];

const jenisInstansiOptions = [
    'Instansi pemerintah',
    'BUMN/BUMD',
    'Institusi/Organisasi Multilateral',
    'Organisasi non-profit/Lembaga Swadaya Masyarakat',
    'Perusahaan swasta',
    'Wiraswasta/perusahaan sendiri',
];

const posisiOptions = ['Founder', 'Co-founder', 'Staff', 'Freelance/kerja lepas'];

const cakupanOptions = [
    'Lokal/wilayah/wiraswasta tidak berbadan hukum',
    'Nasional/wirausaha berbadan hukum',
    'Multinasional/Internasional',
];

const gajiUsahaOptions = [
    '< Rp2.000.000,00',
    'Rp2.000.000,00 - Rp5.000.000,00',
    'Rp5.000.000,00 - Rp10.000.000',
    '> Rp10.000.000,00',
];

const sumberDanaOptions = [
    'Biaya Sendiri / Keluarga',
    'Beasiswa ADIK/AFIRMASI',
    'Beasiswa KIP KULIAH',
    'Beasiswa UKT/BPP',
    'Beasiswa Prestasi',
    'Beasiswa Hafidz Al-Quran',
    'Beasiswa Perusahaan/Swasta',
    'Beasiswa Yatim',
];

const kepuasanOptions = ['Sangat Puas', 'Puas', 'Cukup Puas', 'Kurang Puas', 'Tidak Sama Sekali'];

const aspekKompetensi = [
    'Etika',
    'Keahlian berdasarkan bidang ilmu',
    'Bahasa Inggris',
    'Penggunaan Teknologi Informasi',
    'Komunikasi',
    'Kerja sama tim',
    'Pengembangan Diri',
];

const metodePembelajaranList = [
    'Perkuliahan',
    'Demonstrasi',
    'Partisipasi dalam proyek riset',
    'Magang',
    'Praktikum',
    'Kerja Lapangan',
    'Diskusi',
];
</script>

<template>
    <Head title="Pendataan Career & Tracer Study Alumni 2026" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <span>🎓</span>
                        <span>Pendataan Career & Tracer Study Alumni 2026</span>
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Politeknik Indonusa Surakarta — Mohon lengkapi kuesioner pelacakan alumni sesuai dengan Google Forms resmi 2026.
                    </p>
                </div>
                <Link
                    :href="route('wisudawan.dashboard')"
                    class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-semibold text-xs rounded-xl transition self-start sm:self-auto border border-slate-200 dark:border-slate-700"
                >
                    ← Kembali ke Dashboard
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Notice Banner -->
                <div class="mb-6 p-4 rounded-2xl bg-blue-900 text-white shadow-md flex items-start gap-3 border border-blue-800">
                    <span class="text-2xl">📋</span>
                    <div class="space-y-1">
                        <h3 class="text-sm font-bold text-amber-300">Wajib Diisi untuk Syarat Pendaftaran Wisuda</h3>
                        <p class="text-xs text-blue-100 leading-relaxed">
                            Formulir ini disesuaikan 100% presisi dengan formulir resmi <strong>"Pendataan Career & Tracer Study Alumni 2026 - Politeknik Indonusa Surakarta"</strong>. Isian yang bertanda bintang (<span class="text-rose-400 font-bold">*</span>) wajib diisi.
                        </p>
                    </div>
                </div>

                <!-- Navigation Tabs -->
                <div class="mb-6 flex flex-wrap gap-2 border-b border-slate-200 dark:border-slate-700 pb-3">
                    <button
                        type="button"
                        @click="activeTab = 1"
                        :class="[
                            'px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 border',
                            activeTab === 1
                                ? 'bg-blue-700 text-white border-blue-700 shadow-sm'
                                : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-50'
                        ]"
                    >
                        <span>1. Data Diri & Akademik</span>
                        <span v-if="isSection1Valid" class="text-emerald-300">✓</span>
                    </button>

                    <button
                        type="button"
                        @click="activeTab = 2"
                        :class="[
                            'px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 border',
                            activeTab === 2
                                ? 'bg-blue-700 text-white border-blue-700 shadow-sm'
                                : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-50'
                        ]"
                    >
                        <span>2. Status Pekerjaan</span>
                        <span v-if="isSection2Valid" class="text-emerald-300">✓</span>
                    </button>

                    <button
                        type="button"
                        @click="activeTab = 3"
                        :class="[
                            'px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 border',
                            activeTab === 3
                                ? 'bg-blue-700 text-white border-blue-700 shadow-sm'
                                : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-50'
                        ]"
                    >
                        <span>3. Wirausaha & Studi Lanjut</span>
                    </button>

                    <button
                        type="button"
                        @click="activeTab = 4"
                        :class="[
                            'px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 border',
                            activeTab === 4
                                ? 'bg-blue-700 text-white border-blue-700 shadow-sm'
                                : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-50'
                        ]"
                    >
                        <span>4. Evaluasi Kompetensi</span>
                        <span v-if="isSection4Valid" class="text-emerald-300">✓</span>
                    </button>

                    <button
                        type="button"
                        @click="activeTab = 5"
                        :class="[
                            'px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 border',
                            activeTab === 5
                                ? 'bg-blue-700 text-white border-blue-700 shadow-sm'
                                : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-50'
                        ]"
                    >
                        <span>5. Kepuasan & Masukan</span>
                        <span v-if="isSection5Valid" class="text-emerald-300">✓</span>
                    </button>
                </div>

                <!-- Main Form Card -->
                <form @submit.prevent="submit" class="bg-white dark:bg-slate-800 rounded-3xl p-6 sm:p-8 border border-slate-200 dark:border-slate-700 shadow-xl space-y-8">
                    
                    <!-- SECTION 1: Data Diri & Akademik -->
                    <div v-show="activeTab === 1" class="space-y-6">
                        <div class="border-b border-slate-200 dark:border-slate-700 pb-3">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <span class="px-2.5 py-0.5 rounded-lg bg-blue-100 text-blue-800 text-xs">Bagian 1</span>
                                <span>Data Diri & Akademik Alumni</span>
                            </h3>
                            <p class="text-xs text-slate-500 mt-1">Isi identitas diri dan informasi program studi tempat menempuh pendidikan.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <!-- Pertanyaan 1: NIM -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    NIM <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="form.nim"
                                    type="text"
                                    class="w-full text-xs border-slate-300 dark:border-slate-700 rounded-xl dark:bg-slate-900 dark:text-white"
                                    placeholder="Nomor Induk Mahasiswa"
                                    required
                                />
                            </div>

                            <!-- Pertanyaan 2: Nama Lengkap -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    Nama Lengkap <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="form.nama_lengkap"
                                    type="text"
                                    class="w-full text-xs border-slate-300 dark:border-slate-700 rounded-xl dark:bg-slate-900 dark:text-white"
                                    placeholder="Nama Lengkap Alumni"
                                    required
                                />
                            </div>

                            <!-- Pertanyaan 3: Email -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    Alamat Email <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    class="w-full text-xs border-slate-300 dark:border-slate-700 rounded-xl dark:bg-slate-900 dark:text-white"
                                    placeholder="contoh@gmail.com"
                                    required
                                />
                            </div>

                            <!-- Pertanyaan 4: No WhatsApp -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    No. WhatsApp <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="form.no_whatsapp"
                                    type="tel"
                                    class="w-full text-xs border-slate-300 dark:border-slate-700 rounded-xl dark:bg-slate-900 dark:text-white"
                                    placeholder="08..."
                                    required
                                />
                            </div>
                        </div>

                        <!-- Pertanyaan 5: Program Studi (Checkbox Group) -->
                        <div class="space-y-2 pt-2">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                Program Studi <span class="text-rose-500">*</span>
                                <span class="text-[11px] font-normal text-slate-500 block">Centang semua yang sesuai.</span>
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <label
                                    v-for="opt in prodiOptions"
                                    :key="opt"
                                    class="flex items-center gap-2.5 p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50 hover:bg-slate-100 transition cursor-pointer text-xs"
                                >
                                    <input
                                        type="checkbox"
                                        :value="opt"
                                        :checked="form.prodi.includes(opt)"
                                        @change="toggleCheckbox('prodi', opt)"
                                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <span class="text-slate-800 dark:text-slate-200">{{ opt }}</span>
                                </label>
                            </div>
                            <div class="mt-2 flex items-center gap-2">
                                <span class="text-xs text-slate-500 whitespace-nowrap">Yang lain:</span>
                                <input
                                    v-model="form.prodi_lainnya"
                                    type="text"
                                    placeholder="Ketik nama prodi lain jika ada..."
                                    class="w-full text-xs border-slate-300 dark:border-slate-700 rounded-xl dark:bg-slate-900 dark:text-white"
                                />
                            </div>
                        </div>

                        <!-- Pertanyaan 6: Jenis Kelas (Radio) -->
                        <div class="space-y-2 pt-2">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                Jenis Kelas <span class="text-rose-500">*</span>
                                <span class="text-[11px] font-normal text-slate-500 block">Tandai satu pilihan.</span>
                            </label>
                            <div class="flex flex-wrap gap-4">
                                <label v-for="jk in ['Reguler', 'Transfer', 'Karyawan', 'RPL']" :key="jk" class="flex items-center gap-2 cursor-pointer text-xs">
                                    <input
                                        type="radio"
                                        v-model="form.jenis_kelas"
                                        :value="jk"
                                        class="text-blue-600 focus:ring-blue-500"
                                        required
                                    />
                                    <span class="text-slate-800 dark:text-slate-200 font-medium">{{ jk }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Pertanyaan 7: Alamat Lengkap -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Alamat Lengkap <span class="text-rose-500">*</span>
                            </label>
                            <textarea
                                v-model="form.alamat_lengkap"
                                rows="3"
                                class="w-full text-xs border-slate-300 dark:border-slate-700 rounded-xl dark:bg-slate-900 dark:text-white"
                                placeholder="Alamat tinggal lengkap (Jalan, RT/RW, Desa, Kecamatan, Kabupaten/Kota)"
                                required
                            ></textarea>
                        </div>

                        <div class="pt-4 flex justify-end">
                            <button
                                type="button"
                                @click="activeTab = 2"
                                class="px-5 py-2.5 bg-blue-700 hover:bg-blue-800 text-white font-bold text-xs rounded-xl shadow transition"
                            >
                                Lanjut ke Bagian 2 →
                            </button>
                        </div>
                    </div>

                    <!-- SECTION 2: Status Pekerjaan & Karir -->
                    <div v-show="activeTab === 2" class="space-y-6">
                        <div class="border-b border-slate-200 dark:border-slate-700 pb-3">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <span class="px-2.5 py-0.5 rounded-lg bg-blue-100 text-blue-800 text-xs">Bagian 2</span>
                                <span>Status Pekerjaan & Karir Saat Ini</span>
                            </h3>
                            <p class="text-xs text-slate-500 mt-1">Lengkapi informasi pekerjaan, posisi, instansi, dan penghasilan per bulan.</p>
                        </div>

                        <!-- Pertanyaan 8: Status Saat Ini (Checkbox Group) -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                Jelaskan status Anda saat ini? <span class="text-rose-500">*</span>
                                <span class="text-[11px] font-normal text-slate-500 block">Centang semua yang sesuai.</span>
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <label
                                    v-for="opt in statusOptions"
                                    :key="opt"
                                    class="flex items-center gap-2.5 p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50 hover:bg-slate-100 transition cursor-pointer text-xs"
                                >
                                    <input
                                        type="checkbox"
                                        :value="opt"
                                        :checked="form.status_saat_ini.includes(opt)"
                                        @change="toggleCheckbox('status_saat_ini', opt)"
                                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <span class="text-slate-800 dark:text-slate-200">{{ opt }}</span>
                                </label>
                            </div>
                            <div class="mt-2 flex items-center gap-2">
                                <span class="text-xs text-slate-500 whitespace-nowrap">Yang lain:</span>
                                <input
                                    v-model="form.status_lainnya"
                                    type="text"
                                    placeholder="Ketik status lain..."
                                    class="w-full text-xs border-slate-300 dark:border-slate-700 rounded-xl dark:bg-slate-900 dark:text-white"
                                />
                            </div>
                        </div>

                        <!-- Pertanyaan 9: Tempat Bekerja -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Jika Anda bekerja, di mana tempat Anda bekerja?
                            </label>
                            <input
                                v-model="form.tempat_bekerja"
                                type="text"
                                class="w-full text-xs border-slate-300 dark:border-slate-700 rounded-xl dark:bg-slate-900 dark:text-white"
                                placeholder="Kota / Nama Instansi tempat bekerja"
                            />
                        </div>

                        <!-- Pertanyaan 10: Penghasilan / Gaji per Bulan (Checkbox Group) -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                Jika Anda bekerja, berapa gaji Anda perbulan?
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <label
                                    v-for="opt in gajiOptions"
                                    :key="opt"
                                    class="flex items-center gap-2.5 p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50 hover:bg-slate-100 transition cursor-pointer text-xs"
                                >
                                    <input
                                        type="checkbox"
                                        :value="opt"
                                        :checked="form.gaji_per_bulan.includes(opt)"
                                        @change="toggleCheckbox('gaji_per_bulan', opt)"
                                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <span class="text-slate-800 dark:text-slate-200">{{ opt }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Pertanyaan 11: Keselarasan Bidang Studi -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                Seberapa erat hubungan antara bidang studi dengan pekerjaan Anda?
                            </label>
                            <div class="flex flex-wrap gap-2">
                                <label
                                    v-for="opt in keselarasanOptions"
                                    :key="opt"
                                    class="flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 cursor-pointer text-xs"
                                >
                                    <input
                                        type="checkbox"
                                        :value="opt"
                                        :checked="form.keselarasan_pekerjaan.includes(opt)"
                                        @change="toggleCheckbox('keselarasan_pekerjaan', opt)"
                                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <span>{{ opt }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Pertanyaan 12: Kesesuaian Tingkat Pendidikan -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                Tingkat pendidikan apa yang paling tepat/sesuai untuk pekerjaan Anda saat ini?
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <label
                                    v-for="opt in kesesuaianPendidikanOptions"
                                    :key="opt"
                                    class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 cursor-pointer text-xs"
                                >
                                    <input
                                        type="checkbox"
                                        :value="opt"
                                        :checked="form.kesesuaian_pendidikan.includes(opt)"
                                        @change="toggleCheckbox('kesesuaian_pendidikan', opt)"
                                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <span>{{ opt }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Pertanyaan 13: Waktu Tunggu -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                Waktu Tunggu Lulusan mendapatkan pekerjaan pertama
                            </label>
                            <div class="flex flex-wrap gap-2">
                                <label
                                    v-for="opt in waktuTungguOptions"
                                    :key="opt"
                                    class="flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 cursor-pointer text-xs"
                                >
                                    <input
                                        type="checkbox"
                                        :value="opt"
                                        :checked="form.waktu_tunggu.includes(opt)"
                                        @change="toggleCheckbox('waktu_tunggu', opt)"
                                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <span>{{ opt }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Pertanyaan 14: Lokasi Alamat Tempat Bekerja -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Dimana lokasi/alamat tempat Anda bekerja?
                            </label>
                            <textarea
                                v-model="form.alamat_tempat_kerja"
                                rows="2"
                                class="w-full text-xs border-slate-300 dark:border-slate-700 rounded-xl dark:bg-slate-900 dark:text-white"
                                placeholder="Alamat instansi / kantor..."
                            ></textarea>
                        </div>

                        <!-- Pertanyaan 15: Jenis Instansi -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                Apa jenis perusahaan/instansi/institusi tempat anda bekerja sekarang?
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <label
                                    v-for="opt in jenisInstansiOptions"
                                    :key="opt"
                                    class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 cursor-pointer text-xs"
                                >
                                    <input
                                        type="checkbox"
                                        :value="opt"
                                        :checked="form.jenis_instansi.includes(opt)"
                                        @change="toggleCheckbox('jenis_instansi', opt)"
                                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <span>{{ opt }}</span>
                                </label>
                            </div>
                            <div class="mt-2 flex items-center gap-2">
                                <span class="text-xs text-slate-500 whitespace-nowrap">Yang lain:</span>
                                <input
                                    v-model="form.jenis_instansi_lainnya"
                                    type="text"
                                    placeholder="Jenis instansi lain..."
                                    class="w-full text-xs border-slate-300 dark:border-slate-700 rounded-xl dark:bg-slate-900 dark:text-white"
                                />
                            </div>
                        </div>

                        <!-- Pertanyaan 16 & 17: Nama Perusahaan & Posisi -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    Apa nama perusahaan/kantor tempat Anda bekerja?
                                </label>
                                <input
                                    v-model="form.nama_perusahaan"
                                    type="text"
                                    class="w-full text-xs border-slate-300 dark:border-slate-700 rounded-xl dark:bg-slate-900 dark:text-white"
                                    placeholder="Contoh: PT Indonusa Media / RSUD Surakarta"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    Apa posisi anda di perusahaan/kantor tempat anda bekerja?
                                </label>
                                <div class="flex flex-wrap gap-2 mb-2">
                                    <label v-for="opt in posisiOptions" :key="opt" class="flex items-center gap-1.5 text-xs border border-slate-200 dark:border-slate-700 px-2.5 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-900">
                                        <input
                                            type="checkbox"
                                            :value="opt"
                                            :checked="form.posisi_jabatan.includes(opt)"
                                            @change="toggleCheckbox('posisi_jabatan', opt)"
                                            class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                        />
                                        <span>{{ opt }}</span>
                                    </label>
                                </div>
                                <input
                                    v-model="form.posisi_lainnya"
                                    type="text"
                                    placeholder="Yang lain (posisi spesifik)..."
                                    class="w-full text-xs border-slate-300 dark:border-slate-700 rounded-xl dark:bg-slate-900 dark:text-white"
                                />
                            </div>
                        </div>

                        <!-- Pertanyaan 18: Cakupan Tempat Kerja -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                Apa tingkat tempat kerja Anda?
                            </label>
                            <div class="grid grid-cols-1 gap-2">
                                <label
                                    v-for="opt in cakupanOptions"
                                    :key="opt"
                                    class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 cursor-pointer text-xs"
                                >
                                    <input
                                        type="checkbox"
                                        :value="opt"
                                        :checked="form.cakupan_tempat_kerja.includes(opt)"
                                        @change="toggleCheckbox('cakupan_tempat_kerja', opt)"
                                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <span>{{ opt }}</span>
                                </label>
                            </div>
                            <div class="mt-2 flex items-center gap-2">
                                <span class="text-xs text-slate-500 whitespace-nowrap">Yang lain:</span>
                                <input
                                    v-model="form.tingkat_tempat_kerja_lainnya"
                                    type="text"
                                    placeholder="Cakupan lain..."
                                    class="w-full text-xs border-slate-300 dark:border-slate-700 rounded-xl dark:bg-slate-900 dark:text-white"
                                />
                            </div>
                        </div>

                        <div class="pt-4 flex justify-between">
                            <button
                                type="button"
                                @click="activeTab = 1"
                                class="px-5 py-2.5 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-xl hover:bg-slate-200 transition"
                            >
                                ← Kembali ke Bagian 1
                            </button>
                            <button
                                type="button"
                                @click="activeTab = 3"
                                class="px-5 py-2.5 bg-blue-700 hover:bg-blue-800 text-white font-bold text-xs rounded-xl shadow transition"
                            >
                                Lanjut ke Bagian 3 →
                            </button>
                        </div>
                    </div>

                    <!-- SECTION 3: Kewirausahaan & Studi Lanjut -->
                    <div v-show="activeTab === 3" class="space-y-6">
                        <div class="border-b border-slate-200 dark:border-slate-700 pb-3">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <span class="px-2.5 py-0.5 rounded-lg bg-blue-100 text-blue-800 text-xs">Bagian 3</span>
                                <span>Kewirausahaan & Studi Lanjut</span>
                            </h3>
                            <p class="text-xs text-slate-500 mt-1">Lengkapi rincian usaha mandiri, pendidikan lanjut, dan sumber pembiayaan kuliah.</p>
                        </div>

                        <!-- Pertanyaan 19: Nama Usaha -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Jika Anda berwirausaha, apa nama usaha Anda?
                            </label>
                            <input
                                v-model="form.nama_usaha"
                                type="text"
                                class="w-full text-xs border-slate-300 dark:border-slate-700 rounded-xl dark:bg-slate-900 dark:text-white"
                                placeholder="Nama usaha / brand bisnis mandiri"
                            />
                        </div>

                        <!-- Pertanyaan 20: Penghasilan Usaha per Bulan -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                Jika Anda berwirausaha, berapa penghasilan usaha Anda perbulan?
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <label
                                    v-for="opt in gajiUsahaOptions"
                                    :key="opt"
                                    class="flex items-center gap-2.5 p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50 hover:bg-slate-100 transition cursor-pointer text-xs"
                                >
                                    <input
                                        type="checkbox"
                                        :value="opt"
                                        :checked="form.gaji_usaha.includes(opt)"
                                        @change="toggleCheckbox('gaji_usaha', opt)"
                                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <span>{{ opt }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Pertanyaan 21: Keselarasan Usaha -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                Seberapa erat hubungan antara bidang studi dengan usaha Anda?
                            </label>
                            <div class="flex flex-wrap gap-2">
                                <label
                                    v-for="opt in keselarasanOptions"
                                    :key="opt"
                                    class="flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 cursor-pointer text-xs"
                                >
                                    <input
                                        type="checkbox"
                                        :value="opt"
                                        :checked="form.keselarasan_usaha.includes(opt)"
                                        @change="toggleCheckbox('keselarasan_usaha', opt)"
                                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <span>{{ opt }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Pertanyaan 22: Status Studi Lanjut -->
                        <div class="space-y-2 pt-2 border-t border-slate-100 dark:border-slate-700/60">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                Apakah Anda studi lanjut?
                            </label>
                            <div class="flex gap-4">
                                <label v-for="sl in ['Ya', 'Tidak']" :key="sl" class="flex items-center gap-2 cursor-pointer text-xs">
                                    <input
                                        type="checkbox"
                                        :value="sl"
                                        :checked="form.studi_lanjut.includes(sl)"
                                        @change="toggleCheckbox('studi_lanjut', sl)"
                                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <span class="text-slate-800 dark:text-slate-200 font-semibold">{{ sl }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Pertanyaan 23 & 24: Kampus & Alamat Studi Lanjut -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    Jika Anda studi lanjut, di mana tempat kampus Anda?
                                </label>
                                <input
                                    v-model="form.kampus_studi_lanjut"
                                    type="text"
                                    class="w-full text-xs border-slate-300 dark:border-slate-700 rounded-xl dark:bg-slate-900 dark:text-white"
                                    placeholder="Nama Perguruan Tinggi studi lanjut..."
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    Jika Anda studi lanjut, di mana alamat kampus Anda?
                                </label>
                                <input
                                    v-model="form.alamat_kampus_studi_lanjut"
                                    type="text"
                                    class="w-full text-xs border-slate-300 dark:border-slate-700 rounded-xl dark:bg-slate-900 dark:text-white"
                                    placeholder="Kota / Alamat kampus..."
                                />
                            </div>
                        </div>

                        <!-- Pertanyaan 25: Sumber Pembiayaan Kuliah -->
                        <div class="space-y-2 pt-2 border-t border-slate-100 dark:border-slate-700/60">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                Sebutkan sumber dana dalam pembiayaan kuliah? (Selama kuliah di Politeknik Indonusa Surakarta)
                                <span class="text-[11px] font-normal text-slate-500 block">Centang semua yang sesuai.</span>
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                                <label
                                    v-for="opt in sumberDanaOptions"
                                    :key="opt"
                                    class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 cursor-pointer text-xs"
                                >
                                    <input
                                        type="checkbox"
                                        :value="opt"
                                        :checked="form.sumber_dana.includes(opt)"
                                        @change="toggleCheckbox('sumber_dana', opt)"
                                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <span>{{ opt }}</span>
                                </label>
                            </div>
                            <div class="mt-2 flex items-center gap-2">
                                <span class="text-xs text-slate-500 whitespace-nowrap">Yang lain:</span>
                                <input
                                    v-model="form.sumber_dana_lainnya"
                                    type="text"
                                    placeholder="Sumber beasiswa/dana lainnya..."
                                    class="w-full text-xs border-slate-300 dark:border-slate-700 rounded-xl dark:bg-slate-900 dark:text-white"
                                />
                            </div>
                        </div>

                        <div class="pt-4 flex justify-between">
                            <button
                                type="button"
                                @click="activeTab = 2"
                                class="px-5 py-2.5 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-xl hover:bg-slate-200 transition"
                            >
                                ← Kembali ke Bagian 2
                            </button>
                            <button
                                type="button"
                                @click="activeTab = 4"
                                class="px-5 py-2.5 bg-blue-700 hover:bg-blue-800 text-white font-bold text-xs rounded-xl shadow transition"
                            >
                                Lanjut ke Bagian 4 →
                            </button>
                        </div>
                    </div>

                    <!-- SECTION 4: Evaluasi Kompetensi & Pembelajaran (Matriks Grid) -->
                    <div v-show="activeTab === 4" class="space-y-8">
                        <div class="border-b border-slate-200 dark:border-slate-700 pb-3">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <span class="px-2.5 py-0.5 rounded-lg bg-blue-100 text-blue-800 text-xs">Bagian 4</span>
                                <span>Evaluasi Kompetensi & Metode Pembelajaran (Matriks)</span>
                            </h3>
                            <p class="text-xs text-slate-500 mt-1">Berikan penilaian dari skala 1 (Sangat Rendah) sampai 5 (Sangat Tinggi).</p>
                        </div>

                        <!-- Pertanyaan 26: Tingkat Penguasaan Kompetensi Saat Lulus -->
                        <div class="space-y-3">
                            <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                Pertanyaan 26: Pada saat lulus, pada tingkat mana kompetensi di bawah ini Anda kuasai? <span class="text-rose-500">*</span>
                            </h4>
                            <div class="overflow-x-auto border border-slate-200 dark:border-slate-700 rounded-2xl">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-slate-100 dark:bg-slate-700/60 font-bold text-slate-700 dark:text-slate-200 border-b border-slate-200 dark:border-slate-700">
                                        <tr>
                                            <th class="p-3">Aspek Kompetensi</th>
                                            <th v-for="n in 5" :key="n" class="p-3 text-center w-12">{{ n }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                        <tr v-for="aspek in aspekKompetensi" :key="aspek" class="hover:bg-slate-50 dark:hover:bg-slate-900/40">
                                            <td class="p-3 font-medium text-slate-800 dark:text-slate-200">{{ aspek }}</td>
                                            <td v-for="n in 5" :key="n" class="p-3 text-center">
                                                <input
                                                    type="radio"
                                                    :name="'lulus_' + aspek"
                                                    :value="String(n)"
                                                    v-model="form.kompetensi_lulus[aspek]"
                                                    class="text-blue-600 focus:ring-blue-500"
                                                    required
                                                />
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Pertanyaan 27: Tingkat Kebutuhan Kompetensi dalam Pekerjaan Saat Ini -->
                        <div class="space-y-3">
                            <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                Pertanyaan 27: Pada saat ini, pada tingkat mana kompetensi di bawah ini diperlukan dalam pekerjaan? <span class="text-rose-500">*</span>
                            </h4>
                            <div class="overflow-x-auto border border-slate-200 dark:border-slate-700 rounded-2xl">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-slate-100 dark:bg-slate-700/60 font-bold text-slate-700 dark:text-slate-200 border-b border-slate-200 dark:border-slate-700">
                                        <tr>
                                            <th class="p-3">Aspek Kompetensi</th>
                                            <th v-for="n in 5" :key="n" class="p-3 text-center w-12">{{ n }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                        <tr v-for="aspek in aspekKompetensi" :key="aspek" class="hover:bg-slate-50 dark:hover:bg-slate-900/40">
                                            <td class="p-3 font-medium text-slate-800 dark:text-slate-200">{{ aspek }}</td>
                                            <td v-for="n in 5" :key="n" class="p-3 text-center">
                                                <input
                                                    type="radio"
                                                    :name="'kerja_' + aspek"
                                                    :value="String(n)"
                                                    v-model="form.kompetensi_kerja[aspek]"
                                                    class="text-blue-600 focus:ring-blue-500"
                                                    required
                                                />
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Pertanyaan 28: Penekanan Metode Pembelajaran -->
                        <div class="space-y-3">
                            <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                Pertanyaan 28: Menurut Anda seberapa besar penekanan pada metode pembelajaran di bawah ini dilaksanakan di program studi Anda? <span class="text-rose-500">*</span>
                            </h4>
                            <div class="overflow-x-auto border border-slate-200 dark:border-slate-700 rounded-2xl">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-slate-100 dark:bg-slate-700/60 font-bold text-slate-700 dark:text-slate-200 border-b border-slate-200 dark:border-slate-700">
                                        <tr>
                                            <th class="p-3">Metode Pembelajaran</th>
                                            <th v-for="n in 5" :key="n" class="p-3 text-center w-12">{{ n }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                        <tr v-for="metode in metodePembelajaranList" :key="metode" class="hover:bg-slate-50 dark:hover:bg-slate-900/40">
                                            <td class="p-3 font-medium text-slate-800 dark:text-slate-200">{{ metode }}</td>
                                            <td v-for="n in 5" :key="n" class="p-3 text-center">
                                                <input
                                                    type="radio"
                                                    :name="'metode_' + metode"
                                                    :value="String(n)"
                                                    v-model="form.metode_pembelajaran[metode]"
                                                    class="text-blue-600 focus:ring-blue-500"
                                                    required
                                                />
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="pt-4 flex justify-between">
                            <button
                                type="button"
                                @click="activeTab = 3"
                                class="px-5 py-2.5 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-xl hover:bg-slate-200 transition"
                            >
                                ← Kembali ke Bagian 3
                            </button>
                            <button
                                type="button"
                                @click="activeTab = 5"
                                class="px-5 py-2.5 bg-blue-700 hover:bg-blue-800 text-white font-bold text-xs rounded-xl shadow transition"
                            >
                                Lanjut ke Bagian 5 →
                            </button>
                        </div>
                    </div>

                    <!-- SECTION 5: Kepuasan & Masukan -->
                    <div v-show="activeTab === 5" class="space-y-6">
                        <div class="border-b border-slate-200 dark:border-slate-700 pb-3">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <span class="px-2.5 py-0.5 rounded-lg bg-blue-100 text-blue-800 text-xs">Bagian 5</span>
                                <span>Kepuasan Layanan & Masukan Alumni</span>
                            </h3>
                            <p class="text-xs text-slate-500 mt-1">Berikan masukan konstruktif untuk kemajuan Politeknik Indonusa Surakarta.</p>
                        </div>

                        <!-- Pertanyaan 29: Kepuasan Layanan Kampus -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                Pertanyaan 29: Bagaimana kepuasan anda terhadap layanan yang diselenggaran oleh Politeknik Indonusa Surakarta selama Anda menempuh pendidikan? <span class="text-rose-500">*</span>
                                <span class="text-[11px] font-normal text-slate-500 block">Centang pilihan yang paling sesuai.</span>
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                                <label
                                    v-for="opt in kepuasanOptions"
                                    :key="opt"
                                    class="flex items-center gap-2.5 p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50 hover:bg-slate-100 transition cursor-pointer text-xs"
                                >
                                    <input
                                        type="checkbox"
                                        :value="opt"
                                        :checked="form.kepuasan_layanan.includes(opt)"
                                        @change="toggleCheckbox('kepuasan_layanan', opt)"
                                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <span class="font-medium text-slate-800 dark:text-slate-200">{{ opt }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Pertanyaan 30: Saran & Masukan -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                Pertanyaan 30: Saran untuk pengembangan pembelajaran maupun sarana dan prasarana yang mendukung proses pembelajaran di Politeknik Indonusa Surakarta! <span class="text-rose-500">*</span>
                            </label>
                            <textarea
                                v-model="form.saran_masukan"
                                rows="5"
                                class="w-full text-xs border-slate-300 dark:border-slate-700 rounded-xl dark:bg-slate-900 dark:text-white"
                                placeholder="Tuliskan saran dan masukan lengkap Anda di sini..."
                                required
                            ></textarea>
                        </div>

                        <div class="pt-6 flex items-center justify-between border-t border-slate-200 dark:border-slate-700">
                            <button
                                type="button"
                                @click="activeTab = 4"
                                class="px-5 py-2.5 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-xl hover:bg-slate-200 transition"
                            >
                                ← Kembali ke Bagian 4
                            </button>

                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-8 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-lg transition disabled:opacity-50 flex items-center gap-2"
                            >
                                <span v-if="form.processing">Menyimpan Data Tracer Study...</span>
                                <span v-else>✓ Simpan & Selesaikan Form Tracer Study</span>
                            </button>
                        </div>
                    </div>

                </form>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
