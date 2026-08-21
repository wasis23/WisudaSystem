<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    canLogin: {
        type: Boolean,
        default: true,
    },
    canRegister: {
        type: Boolean,
        default: true,
    },
    activePeriode: {
        type: Object,
        default: null,
    },
    totalWisudawan: {
        type: Number,
        default: 0,
    },
    prodiList: {
        type: Array,
        default: () => [],
    },
    laravelVersion: String,
    phpVersion: String,
});

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('id-ID', options);
};

const defaultProdi = [
    { nama_prodi: 'D3 Farmasi', jenjang: 'D3', kode_prodi: 'FAR' },
    { nama_prodi: 'D3 Rekam Medis & Informasi Kesehatan', jenjang: 'D3', kode_prodi: 'RMIK' },
    { nama_prodi: 'D3 Manajemen Informatika', jenjang: 'D3', kode_prodi: 'MI' },
    { nama_prodi: 'D3 Komunikasi Massa', jenjang: 'D3', kode_prodi: 'KM' },
    { nama_prodi: 'D3 Teknologi Otomotif', jenjang: 'D3', kode_prodi: 'TO' },
];

const displayProdiList = computed(() => {
    return props.prodiList && props.prodiList.length > 0 ? props.prodiList : defaultProdi;
});
</script>

<template>
    <Head title="Sistem Informasi Wisuda - Politeknik Indonusa Surakarta" />

    <div class="min-h-screen bg-slate-50 text-slate-800 font-sans antialiased selection:bg-blue-600 selection:text-white">
        
        <!-- Top Official Announcement Bar -->
        <div class="bg-blue-900 text-white text-xs py-2 px-4 border-b border-blue-800">
            <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2">
                <div class="flex items-center gap-2 font-medium">
                    <span class="bg-amber-400 text-blue-950 font-bold px-2 py-0.5 rounded text-[10px] uppercase tracking-wide">Pengumuman</span>
                    <span>
                        Pendaftaran Wisuda
                        <span class="font-bold underline">{{ activePeriode?.nama_periode || 'Periode Wisuda Ke-75' }}</span>
                        Resmi Dibuka!
                    </span>
                </div>
                <div class="flex items-center gap-4 text-blue-200 text-[11px]">
                    <span> Kampus Utama: Jl. KH. Samanhudi No. 84 Surakarta</span>
                    <span class="hidden md:inline"> (0271) 718-694</span>
                </div>
            </div>
        </div>

        <!-- Main Navigation Header -->
        <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-20">
                    
                    <!-- Institution Brand Logo & Title -->
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-blue-900 text-amber-400 flex items-center justify-center font-bold text-2xl shadow-md border-2 border-amber-400/40">
                            
                        </div>
                        <div>
                            <h1 class="text-base sm:text-lg font-bold text-slate-900 leading-tight">
                                POLITEKNIK INDONUSA SURAKARTA
                            </h1>
                            <p class="text-xs text-blue-700 font-semibold tracking-wide uppercase">
                                Portal Resmi Sistem Informasi Wisuda
                            </p>
                        </div>
                    </div>

                    <!-- Navigation Menu -->
                    <nav class="hidden lg:flex items-center gap-7 text-sm font-semibold text-slate-600">
                        <a href="#agenda" class="hover:text-blue-700 transition">Agenda Wisuda</a>
                        <a href="#prodi" class="hover:text-blue-700 transition">Program Studi</a>
                        <a href="#panduan" class="hover:text-blue-700 transition">Panduan & Syarat</a>
                        <a href="#alur" class="hover:text-blue-700 transition">Alur Pendaftaran</a>
                    </nav>

                    <!-- Authentication Links -->
                    <div class="flex items-center gap-3">
                        <template v-if="$page.props.auth.user">
                            <Link
                                :href="route('dashboard')"
                                class="px-5 py-2.5 bg-blue-700 hover:bg-blue-800 text-white text-sm font-bold rounded-lg shadow-sm transition flex items-center gap-2"
                            >
                                <span>Dashboard Portal</span>
                                <span>→</span>
                            </Link>
                        </template>
                        <template v-else>
                            <Link
                                v-if="canLogin"
                                :href="route('login')"
                                class="px-5 py-2 bg-blue-700 hover:bg-blue-800 text-white text-sm font-semibold rounded-lg shadow-sm transition flex items-center gap-2"
                            >
                                <span>Login Portal Wisuda</span>
                                <span>→</span>
                            </Link>
                        </template>
                    </div>

                </div>
            </div>
        </header>

        <!-- Hero & Agenda Section (Light Campus Design) -->
        <section class="py-12 lg:py-16 bg-gradient-to-b from-slate-100 via-white to-slate-50 border-b border-slate-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-stretch">
                    
                    <!-- Left Hero Content (Flex col for equal height) -->
                    <div class="lg:col-span-7 flex flex-col justify-between bg-white border border-slate-300 rounded-2xl p-6 sm:p-8 shadow-md">
                        <div class="space-y-4">
                            <span class="px-3 py-1 bg-blue-50 border border-blue-200 text-blue-800 text-xs font-bold rounded-md inline-block">
                                 Portal Informasi Kelulusan
                            </span>

                            <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 leading-tight tracking-tight">
                                Pendaftaran Wisuda & Layanan Informasi Kelulusan
                            </h2>

                            <p class="text-sm sm:text-base text-slate-600 leading-relaxed">
                                Selamat datang di Portal Resmi Wisuda Politeknik Indonusa Surakarta. Layanan ini digunakan oleh Calon Wisudawan untuk pendaftaran wisuda, verifikasi pas foto resmi, pengisian kutipan & judul TA, serta presensi digital pada hari pelaksanaan wisuda.
                            </p>
                        </div>

                        <!-- Important Information Cards (Boxes) -->
                        <div class="pt-6 mt-6 border-t border-slate-200 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex items-start gap-3">
                                <div class="w-10 h-10 rounded-lg bg-blue-900 text-white flex items-center justify-center text-lg font-bold shrink-0">
                                    
                                </div>
                                <div class="space-y-0.5">
                                    <p class="text-xs font-bold text-blue-900 uppercase tracking-wide">Lokasi Acara</p>
                                    <p class="text-xs text-slate-800 font-semibold leading-snug">Auditorium Utama Kampus Politeknik Indonusa Surakarta</p>
                                </div>
                            </div>

                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex items-start gap-3">
                                <div class="w-10 h-10 rounded-lg bg-blue-900 text-white flex items-center justify-center text-lg font-bold shrink-0">
                                    
                                </div>
                                <div class="space-y-0.5">
                                    <p class="text-xs font-bold text-blue-900 uppercase tracking-wide">Ketentuan Toga</p>
                                    <p class="text-xs text-slate-800 font-semibold leading-snug">Toga Resmi Kampus & Kemeja Putih Lengan Panjang</p>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Right Official Schedule Card (Flex col for equal height) -->
                    <div class="lg:col-span-5 flex flex-col justify-between bg-white border border-slate-300 rounded-2xl shadow-md overflow-hidden" id="agenda">
                        <div>
                            <div class="bg-blue-900 text-white px-6 py-4 flex items-center justify-between">
                                <div class="space-y-0.5">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-amber-300">Jadwal Resmi</span>
                                    <h3 class="font-bold text-base text-white">
                                        {{ activePeriode?.nama_periode || 'Wisuda Ke-75 Politeknik Indonusa' }}
                                    </h3>
                                </div>
                                <span class="px-2.5 py-1 bg-emerald-500 text-white font-bold text-xs rounded-full">
                                    Aktif
                                </span>
                            </div>

                            <div class="p-6 space-y-4 text-sm">
                                <div class="flex items-start gap-4 p-3.5 bg-blue-50/70 border border-blue-100 rounded-xl">
                                    <div class="w-10 h-10 rounded-lg bg-blue-900 text-white flex items-center justify-center font-bold shrink-0">
                                        
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-500 font-medium">Tanggal Pelaksanaan Wisuda</p>
                                        <p class="font-bold text-blue-950 text-base">
                                            {{ activePeriode?.tanggal_pelaksanaan ? formatDate(activePeriode.tanggal_pelaksanaan) : '03 Oktober 2026' }}
                                        </p>
                                    </div>
                                </div>

                                <div class="space-y-2 pt-1">
                                    <div class="flex justify-between items-center py-2 border-b border-slate-100 text-xs">
                                        <span class="text-slate-500">Buka Pendaftaran:</span>
                                        <span class="font-semibold text-slate-800">
                                            {{ activePeriode?.tanggal_buka_pendaftaran ? formatDate(activePeriode.tanggal_buka_pendaftaran) : '18 Agustus 2026' }}
                                        </span>
                                    </div>

                                    <div class="flex justify-between items-center py-2 border-b border-slate-100 text-xs">
                                        <span class="text-slate-500">Tutup Pendaftaran:</span>
                                        <span class="font-semibold text-slate-800">
                                            {{ activePeriode?.tanggal_tutup_pendaftaran ? formatDate(activePeriode.tanggal_tutup_pendaftaran) : '21 September 2026' }}
                                        </span>
                                    </div>

                                    <div class="flex justify-between items-center py-2 border-b border-slate-100 text-xs">
                                        <span class="text-slate-500">Kuota Peserta:</span>
                                        <span class="font-semibold text-blue-700">
                                            {{ activePeriode?.kuota_peserta || 500 }} Mahasiswa
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="p-6 pt-0">
                            <Link
                                :href="route('login')"
                                class="w-full py-3 bg-blue-700 hover:bg-blue-800 text-white font-bold text-xs rounded-lg transition text-center block shadow-sm"
                            >
                                Login Akun Peserta Wisuda
                            </Link>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <!-- Information & Guidelines Section -->
        <section class="py-14 bg-white border-b border-slate-200" id="panduan">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
                
                <div class="text-center max-w-2xl mx-auto space-y-2">
                    <span class="text-xs font-bold text-blue-700 uppercase tracking-wider">Layanan Sistem Portal</span>
                    <h3 class="text-2xl sm:text-3xl font-extrabold text-slate-900">Ketentuan & Alur Layanan Wisuda</h3>
                    <p class="text-sm text-slate-500">Panduan lengkap bagi mahasiswa calon wisudawan Politeknik Indonusa Surakarta.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    
                    <div class="p-6 rounded-xl border border-slate-200 bg-slate-50 space-y-3">
                        <div class="w-10 h-10 rounded-lg bg-blue-100 text-blue-800 flex items-center justify-center font-bold text-lg">
                            
                        </div>
                        <h4 class="font-bold text-slate-900 text-base">1. Ketentuan Pas Foto</h4>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Calon wisudawan wajib mengunggah Pas Foto resmi berlatar belakang merah/biru dengan pakaian toga atau kemeja rapi untuk keperluan slide proyektor panggung dan cetak Buku Kenangan.
                        </p>
                    </div>

                    <div class="p-6 rounded-xl border border-slate-200 bg-slate-50 space-y-3">
                        <div class="w-10 h-10 rounded-lg bg-blue-100 text-blue-800 flex items-center justify-center font-bold text-lg">
                            
                        </div>
                        <h4 class="font-bold text-slate-900 text-base">2. Kutipan & Judul TA</h4>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Isi motto hidup, ucapan terima kasih, dan judul Tugas Akhir/Skripsi dengan teliti. Data ini akan ditampilkan pada layar panggung saat penyerahan ijazah.
                        </p>
                    </div>

                    <div class="p-6 rounded-xl border border-slate-200 bg-slate-50 space-y-3">
                        <div class="w-10 h-10 rounded-lg bg-blue-100 text-blue-800 flex items-center justify-center font-bold text-lg">
                            
                        </div>
                        <h4 class="font-bold text-slate-900 text-base">3. Proyektor Panggung & Presensi</h4>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Pada hari-H pelaksanaan wisuda, panitia melakukan presensi kehadiran digital. Data wisudawan yang hadir akan tayang di proyektor panggung utama secara urut dan presisi.
                        </p>
                    </div>

                </div>
            </div>
        </section>

        <!-- Program Studi Section (Light Academic Grid) -->
        <section class="py-14 bg-slate-50 border-b border-slate-200" id="prodi">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
                
                <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                    <div>
                        <span class="text-xs font-bold text-blue-700 uppercase tracking-wider">Politeknik Indonusa Surakarta</span>
                        <h3 class="text-2xl font-extrabold text-slate-900 mt-1">Program Studi Terdaftar</h3>
                    </div>
                    <p class="text-xs text-slate-500 max-w-md">
                        Peserta wisuda mencakup seluruh mahasiswa lulusan Program Studi Diploma Politeknik Indonusa Surakarta.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                    <div
                        v-for="prodi in displayProdiList"
                        :key="prodi.id || prodi.kode_prodi"
                        class="p-4 bg-white border border-slate-200 rounded-xl shadow-sm hover:border-blue-500 transition space-y-2"
                    >
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                            {{ prodi.jenjang || 'D3' }}
                        </span>
                        <h4 class="font-bold text-xs text-slate-800 leading-snug">
                            {{ prodi.nama_prodi }}
                        </h4>
                        <p class="text-[11px] font-mono text-slate-400">Kode: {{ prodi.kode_prodi }}</p>
                    </div>
                </div>

            </div>
        </section>

        <!-- Step-by-Step Process Timeline -->
        <section class="py-14 bg-white border-b border-slate-200" id="alur">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
                <div class="text-center max-w-xl mx-auto space-y-1">
                    <span class="text-xs font-bold text-blue-700 uppercase tracking-wider">Tahapan Pendaftaran</span>
                    <h3 class="text-2xl font-extrabold text-slate-900">Langkah Mudah Pendaftaran Wisuda</h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="p-5 border border-slate-200 rounded-xl bg-slate-50/50 space-y-3 relative">
                        <div class="w-8 h-8 rounded-full bg-blue-900 text-white font-bold text-xs flex items-center justify-center">
                            1
                        </div>
                        <h4 class="font-bold text-sm text-slate-900">Login Portal Wisuda</h4>
                        <p class="text-xs text-slate-500">Masuk ke portal menggunakan akun terintegrasi SIAKAD.</p>
                    </div>

                    <div class="p-5 border border-slate-200 rounded-xl bg-slate-50/50 space-y-3 relative">
                        <div class="w-8 h-8 rounded-full bg-blue-900 text-white font-bold text-xs flex items-center justify-center">
                            2
                        </div>
                        <h4 class="font-bold text-sm text-slate-900">Pengisian Form & Foto</h4>
                        <p class="text-xs text-slate-500">Lengkapi data pribadi, prodi, alamat, dan unggah Pas Foto resmi.</p>
                    </div>

                    <div class="p-5 border border-slate-200 rounded-xl bg-slate-50/50 space-y-3 relative">
                        <div class="w-8 h-8 rounded-full bg-blue-900 text-white font-bold text-xs flex items-center justify-center">
                            3
                        </div>
                        <h4 class="font-bold text-sm text-slate-900">Input Kutipan & Judul TA</h4>
                        <p class="text-xs text-slate-500">Kirimkan motto ucapan dan judul tugas akhir untuk kompilasi buku wisuda.</p>
                    </div>

                    <div class="p-5 border border-slate-200 rounded-xl bg-slate-50/50 space-y-3 relative">
                        <div class="w-8 h-8 rounded-full bg-emerald-600 text-white font-bold text-xs flex items-center justify-center">
                            4
                        </div>
                        <h4 class="font-bold text-sm text-slate-900">Pelaksanaan Wisuda</h4>
                        <p class="text-xs text-slate-500">Hadir di lokasi acara, lakukan presensi panitia, dan ikuti prosesi panggung.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Official Campus Footer -->
        <footer class="bg-blue-950 text-white py-10 text-xs">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-8 pb-8 border-b border-blue-900">
                    
                    <div class="md:col-span-6 space-y-3">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl"></span>
                            <div>
                                <h4 class="font-bold text-sm text-white">POLITEKNIK INDONUSA SURAKARTA</h4>
                                <p class="text-blue-300 text-[11px]">Kampus Berbasis Kompetensi & Terakreditasi</p>
                            </div>
                        </div>
                        <p class="text-blue-200/80 leading-relaxed text-[11px] max-w-md">
                            Sistem Informasi Manajemen Wisuda ini merupakan sistem resmi pengelolaan prosesi wisuda dan pendataan lulusan Politeknik Indonusa Surakarta.
                        </p>
                    </div>

                    <div class="md:col-span-3 space-y-2">
                        <h5 class="font-bold text-white text-xs uppercase tracking-wider">Kontak Kampus</h5>
                        <p class="text-blue-200 text-[11px]"> Jl. KH. Samanhudi No. 84, Surakarta</p>
                        <p class="text-blue-200 text-[11px]"> (0271) 718-694</p>
                        <p class="text-blue-200 text-[11px]"> info@poltekindonusa.ac.id</p>
                    </div>

                    <div class="md:col-span-3 space-y-2">
                        <h5 class="font-bold text-white text-xs uppercase tracking-wider">Portal Akses</h5>
                        <ul class="space-y-1 text-blue-200 text-[11px]">
                            <li><Link :href="route('login')" class="hover:underline">Login Peserta & Panitia</Link></li>
                        </ul>
                    </div>

                </div>

                <div class="pt-6 flex flex-col sm:flex-row items-center justify-between text-blue-300/70 text-[11px] gap-2">
                    <p>© {{ new Date().getFullYear() }} Politeknik Indonusa Surakarta. Hak Cipta Dilindungi.</p>
                    <p class="font-mono text-[10px]">Laravel v{{ laravelVersion }} (PHP v{{ phpVersion }})</p>
                </div>
            </div>
        </footer>

    </div>
</template>
