<script setup>
import { ref, computed, watch } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    detectedDatabases: {
        type: Array,
        default: () => [],
    },
    selectedDb: {
        type: String,
        default: '',
    },
    dbInfo: {
        type: Object,
        default: () => ({}),
    },
    tables: {
        type: Array,
        default: () => [],
    },
    selectedTable: {
        type: String,
        default: '',
    },
    tableSchema: {
        type: Array,
        default: () => [],
    },
    tableIndexes: {
        type: Array,
        default: () => [],
    },
    tableForeignKeys: {
        type: Array,
        default: () => [],
    },
    tableSql: {
        type: String,
        default: '',
    },
    tableData: {
        type: Object,
        default: null,
    },
    errorMessage: {
        type: String,
        default: null,
    },
});

// Active Tab ('data' | 'schema' | 'sql')
const activeTab = ref('data');

// Table search filter in sidebar
const tableFilter = ref('');
const filteredTables = computed(() => {
    if (!tableFilter.value.trim()) return props.tables;
    const q = tableFilter.value.toLowerCase();
    return props.tables.filter(t => t.name.toLowerCase().includes(q));
});

// Table data searching, sorting & pagination
const searchQuery = ref(props.tableData?.search || '');
const perPage = ref(props.tableData?.per_page || 15);
const sortBy = ref(props.tableData?.sort_by || '');
const sortDir = ref(props.tableData?.sort_dir || 'ASC');

// Change selected DB
const changeDatabase = (path) => {
    router.get(route('admin.sqlite-viewer.index'), { db: path }, {
        preserveState: false,
        preserveScroll: true,
    });
};

// Select a table
const selectTable = (tableName) => {
    router.get(route('admin.sqlite-viewer.index'), {
        db: props.selectedDb,
        table: tableName,
        per_page: perPage.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Handle table search submit
const handleSearch = () => {
    router.get(route('admin.sqlite-viewer.index'), {
        db: props.selectedDb,
        table: props.selectedTable,
        search: searchQuery.value,
        per_page: perPage.value,
        sort_by: sortBy.value,
        sort_dir: sortDir.value,
        page: 1,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Clear search
const clearSearch = () => {
    searchQuery.value = '';
    handleSearch();
};

// Handle Sort
const handleSort = (columnName) => {
    if (sortBy.value === columnName) {
        sortDir.value = sortDir.value === 'ASC' ? 'DESC' : 'ASC';
    } else {
        sortBy.value = columnName;
        sortDir.value = 'ASC';
    }
    router.get(route('admin.sqlite-viewer.index'), {
        db: props.selectedDb,
        table: props.selectedTable,
        search: searchQuery.value,
        per_page: perPage.value,
        sort_by: sortBy.value,
        sort_dir: sortDir.value,
        page: props.tableData?.page || 1,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Change page
const goToPage = (page) => {
    if (page < 1 || (props.tableData?.last_page && page > props.tableData.last_page)) return;
    router.get(route('admin.sqlite-viewer.index'), {
        db: props.selectedDb,
        table: props.selectedTable,
        search: searchQuery.value,
        per_page: perPage.value,
        sort_by: sortBy.value,
        sort_dir: sortDir.value,
        page: page,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Modal Detail Row Viewer
const isRowDetailOpen = ref(false);
const activeRowDetail = ref(null);
const viewRowDetail = (row) => {
    activeRowDetail.value = row;
    isRowDetailOpen.value = true;
};
const closeRowDetail = () => {
    isRowDetailOpen.value = false;
    activeRowDetail.value = null;
};

// Modal Upload SQLite DB
const isUploadModalOpen = ref(false);
const uploadForm = useForm({
    sqlite_file: null,
});
const handleFileUpload = (e) => {
    uploadForm.sqlite_file = e.target.files[0];
};
const submitUpload = () => {
    uploadForm.post(route('admin.sqlite-viewer.upload'), {
        onSuccess: () => {
            isUploadModalOpen.value = false;
            uploadForm.reset();
        },
    });
};

// Sync Data from MySQL to database.sqlite
const isSyncingMysql = ref(false);
const syncFromMysql = () => {
    if (!confirm('Apakah Anda ingin menyinkronkan seluruh tabel & data dari database MySQL ke database/database.sqlite?')) return;
    isSyncingMysql.value = true;
    router.post(route('admin.sqlite-viewer.sync-mysql'), {}, {
        onFinish: () => {
            isSyncingMysql.value = false;
        }
    });
};

// Custom SQL Query Runner
const sqlQuery = ref(props.selectedTable ? `SELECT * FROM "${props.selectedTable}" LIMIT 50;` : 'SELECT sqlite_version();');
const isExecutingQuery = ref(false);
const queryResult = ref(null);
const queryError = ref(null);

watch(() => props.selectedTable, (newTable) => {
    if (newTable && (!sqlQuery.value || sqlQuery.value.startsWith('SELECT * FROM'))) {
        sqlQuery.value = `SELECT * FROM "${newTable}" LIMIT 50;`;
    }
});

const executeCustomSql = async () => {
    if (!sqlQuery.value.trim()) return;

    isExecutingQuery.value = true;
    queryError.value = null;
    queryResult.value = null;

    try {
        const response = await axios.post(route('admin.sqlite-viewer.query'), {
            db: props.selectedDb,
            query: sqlQuery.value,
        });

        queryResult.value = response.data;
    } catch (err) {
        queryError.value = err.response?.data?.error || err.message || 'Terjadi kesalahan saat mengeksekusi query.';
    } finally {
        isExecutingQuery.value = false;
    }
};

// SQL Quick Templates
const setSqlTemplate = (template) => {
    const table = props.selectedTable || 'sqlite_master';
    switch (template) {
        case 'select_all':
            sqlQuery.value = `SELECT * FROM "${table}" LIMIT 100;`;
            break;
        case 'count':
            sqlQuery.value = `SELECT COUNT(*) AS total_records FROM "${table}";`;
            break;
        case 'pragma_info':
            sqlQuery.value = `PRAGMA table_info("${table}");`;
            break;
        case 'pragma_index':
            sqlQuery.value = `PRAGMA index_list("${table}");`;
            break;
        case 'list_tables':
            sqlQuery.value = `SELECT name, type, sql FROM sqlite_master WHERE type IN ('table', 'view') ORDER BY name;`;
            break;
    }
};

// Format Cell Content for Display
const formatCell = (val) => {
    if (val === null || val === undefined) return '<span class="text-gray-400 italic">NULL</span>';
    if (typeof val === 'boolean') return val ? 'TRUE' : 'FALSE';
    if (typeof val === 'object') return JSON.stringify(val);
    const str = String(val);
    if (str.length > 80) return str.substring(0, 77) + '...';
    return str;
};

// Copy SQL to clipboard
const copySuccess = ref(false);
const copySql = () => {
    if (!props.tableSql) return;
    navigator.clipboard.writeText(props.tableSql).then(() => {
        copySuccess.value = true;
        setTimeout(() => copySuccess.value = false, 2000);
    });
};
</script>

<template>
    <Head title="SQLite Database Viewer" />

    <AdminLayout>
        <div class="space-y-6">
            
            <!-- HEADER & DATABASE SELECTOR TOOLBAR -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="p-2 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                                </svg>
                            </span>
                            <div>
                                <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                                    SQLite Database Viewer & Explorer
                                </h1>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Inspeksi skema tabel, preview data rekord, dan jalankan custom SQL query untuk database SQLite
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Actions & DB Switcher -->
                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Select Database -->
                        <div class="relative min-w-[260px]">
                            <select
                                :value="selectedDb"
                                @change="changeDatabase($event.target.value)"
                                class="w-full text-xs font-medium bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl px-3 py-2 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            >
                                <option
                                    v-for="db in detectedDatabases"
                                    :key="db.path"
                                    :value="db.path"
                                >
                                    {{ db.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Sync from MySQL Button -->
                        <button
                            @click="syncFromMysql"
                            :disabled="isSyncingMysql"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition disabled:opacity-50"
                            title="Sinkronkan data dari MySQL ke database/database.sqlite"
                        >
                            <svg v-if="isSyncingMysql" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span>{{ isSyncingMysql ? 'Menyinkronkan...' : 'Sync dari MySQL' }}</span>
                        </button>

                        <!-- Upload DB Button -->
                        <button
                            @click="isUploadModalOpen = true"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold text-gray-700 dark:text-gray-200 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 transition"
                            title="Unggah file SQLite (.sqlite, .db)"
                        >
                            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                            <span>Upload DB</span>
                        </button>

                        <!-- Download Current DB -->
                        <a
                            :href="route('admin.sqlite-viewer.download', { db: selectedDb })"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold text-gray-700 dark:text-gray-200 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 transition"
                            title="Download file database SQLite"
                        >
                            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            <span>Download</span>
                        </a>
                    </div>
                </div>

                <!-- Database Metadata Badges -->
                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-3">
                    <div class="bg-gray-50 dark:bg-gray-700/40 p-2.5 rounded-xl">
                        <span class="text-[10px] text-gray-400 uppercase font-semibold block">File Database</span>
                        <span class="text-xs font-semibold text-gray-800 dark:text-gray-200 truncate block" :title="dbInfo.path">
                            {{ dbInfo.filename }}
                        </span>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/40 p-2.5 rounded-xl">
                        <span class="text-[10px] text-gray-400 uppercase font-semibold block">Ukuran File</span>
                        <span class="text-xs font-semibold text-indigo-600 dark:text-indigo-400">
                            {{ dbInfo.size_formatted }}
                        </span>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/40 p-2.5 rounded-xl">
                        <span class="text-[10px] text-gray-400 uppercase font-semibold block">Total Tabel</span>
                        <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">
                            {{ dbInfo.total_tables }} tabel <span v-if="dbInfo.total_views > 0">({{ dbInfo.total_views }} view)</span>
                        </span>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/40 p-2.5 rounded-xl">
                        <span class="text-[10px] text-gray-400 uppercase font-semibold block">SQLite Engine</span>
                        <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                            v{{ dbInfo.sqlite_version }}
                        </span>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/40 p-2.5 rounded-xl">
                        <span class="text-[10px] text-gray-400 uppercase font-semibold block">Izin Tulis</span>
                        <span :class="dbInfo.is_writable ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-500'" class="text-xs font-semibold">
                            {{ dbInfo.is_writable ? 'Writable' : 'Read-Only' }}
                        </span>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/40 p-2.5 rounded-xl">
                        <span class="text-[10px] text-gray-400 uppercase font-semibold block">Modifikasi</span>
                        <span class="text-xs font-semibold text-gray-700 dark:text-gray-300 truncate block">
                            {{ dbInfo.last_modified }}
                        </span>
                    </div>
                </div>

                <!-- Error message banner if any -->
                <div v-if="errorMessage" class="mt-4 p-3.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-xs text-rose-700 dark:text-rose-300 flex items-start gap-2">
                    <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <span class="font-bold">Error Database: </span>
                        <span>{{ errorMessage }}</span>
                    </div>
                </div>
            </div>

            <!-- MAIN WORKSPACE: SIDEBAR TABLES + CONTENT -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                
                <!-- LEFT SIDEBAR: TABLES LIST (4 cols) -->
                <div class="lg:col-span-4 space-y-3">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold uppercase text-gray-400 tracking-wider">
                                Daftar Tabel ({{ tables.length }})
                            </span>
                            <span class="text-[11px] px-2 py-0.5 rounded-full bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 font-semibold">
                                SQLite
                            </span>
                        </div>

                        <!-- Table Filter Input -->
                        <div class="relative mb-3">
                            <input
                                v-model="tableFilter"
                                type="text"
                                placeholder="Cari nama tabel..."
                                class="w-full text-xs bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl px-3 py-2 pl-8 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            />
                            <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>

                        <!-- Table Items List -->
                        <div class="space-y-1 max-h-[540px] overflow-y-auto pr-1">
                            <div v-if="filteredTables.length === 0" class="p-4 text-center text-xs text-gray-400">
                                Tidak ada tabel yang sesuai.
                            </div>

                            <button
                                v-for="tbl in filteredTables"
                                :key="tbl.name"
                                @click="selectTable(tbl.name)"
                                :class="[
                                    'w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-medium text-left transition',
                                    selectedTable === tbl.name
                                        ? 'bg-indigo-600 text-white shadow-sm font-semibold'
                                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/50'
                                ]"
                            >
                                <div class="flex items-center gap-2 min-w-0">
                                    <svg v-if="tbl.type === 'table'" class="w-3.5 h-3.5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <svg v-else class="w-3.5 h-3.5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <span class="truncate">{{ tbl.name }}</span>
                                </div>

                                <div class="flex items-center gap-1 shrink-0 ml-2">
                                    <span
                                        :class="[
                                            'text-[10px] px-1.5 py-0.5 rounded-md font-mono',
                                            selectedTable === tbl.name
                                                ? 'bg-indigo-700 text-indigo-100'
                                                : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400'
                                        ]"
                                    >
                                        {{ tbl.row_count >= 0 ? tbl.row_count : '?' }} rows
                                    </span>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- RIGHT MAIN WORKSPACE: TABS CONTENT (8 cols) -->
                <div class="lg:col-span-8 space-y-4">
                    
                    <!-- WORKSPACE TABS & TABLE INFO BAR -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                        
                        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="text-base font-bold text-gray-900 dark:text-white">
                                        {{ selectedTable ? selectedTable : 'Belum Memilih Tabel' }}
                                    </h2>
                                    <span v-if="selectedTable" class="px-2 py-0.5 text-[11px] rounded-md bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 font-semibold font-mono">
                                        {{ tableData?.total ?? 0 }} records
                                    </span>
                                </div>
                            </div>

                            <!-- Tabs Navigation -->
                            <div class="flex items-center bg-gray-100 dark:bg-gray-700/60 p-1 rounded-xl">
                                <button
                                    @click="activeTab = 'data'"
                                    :class="[
                                        'px-3.5 py-1.5 text-xs font-semibold rounded-lg transition',
                                        activeTab === 'data'
                                            ? 'bg-white dark:bg-gray-800 text-indigo-600 dark:text-indigo-400 shadow-sm'
                                            : 'text-gray-600 dark:text-gray-300 hover:text-gray-900'
                                    ]"
                                >
                                    Tabel Data
                                </button>
                                <button
                                    @click="activeTab = 'schema'"
                                    :class="[
                                        'px-3.5 py-1.5 text-xs font-semibold rounded-lg transition',
                                        activeTab === 'schema'
                                            ? 'bg-white dark:bg-gray-800 text-indigo-600 dark:text-indigo-400 shadow-sm'
                                            : 'text-gray-600 dark:text-gray-300 hover:text-gray-900'
                                    ]"
                                >
                                    Struktur Skema
                                </button>
                                <button
                                    @click="activeTab = 'sql'"
                                    :class="[
                                        'px-3.5 py-1.5 text-xs font-semibold rounded-lg transition',
                                        activeTab === 'sql'
                                            ? 'bg-white dark:bg-gray-800 text-indigo-600 dark:text-indigo-400 shadow-sm'
                                            : 'text-gray-600 dark:text-gray-300 hover:text-gray-900'
                                    ]"
                                >
                                    SQL Query
                                </button>
                            </div>
                        </div>

                        <!-- TAB 1: TABLE DATA PREVIEW -->
                        <div v-if="activeTab === 'data'" class="p-6 space-y-4">
                            
                            <!-- Filter & Export Controls Bar -->
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                
                                <!-- Search inside table -->
                                <div class="flex items-center gap-2 flex-1 max-w-md">
                                    <div class="relative w-full">
                                        <input
                                            v-model="searchQuery"
                                            @keyup.enter="handleSearch"
                                            type="text"
                                            placeholder="Filter rekord (Tekan Enter)..."
                                            class="w-full text-xs bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl px-3 py-2 pl-8 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                        />
                                        <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                        <button
                                            v-if="searchQuery"
                                            @click="clearSearch"
                                            class="absolute right-2.5 top-2.5 text-gray-400 hover:text-gray-600"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                    <button
                                        @click="handleSearch"
                                        class="px-3 py-2 text-xs font-semibold bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl transition"
                                    >
                                        Filter
                                    </button>
                                </div>

                                <!-- Export & Limit Actions -->
                                <div class="flex items-center gap-2 shrink-0">
                                    <!-- Items per page -->
                                    <select
                                        v-model="perPage"
                                        @change="handleSearch"
                                        class="text-xs bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl px-2.5 py-1.5 text-gray-800 dark:text-gray-200"
                                    >
                                        <option :value="10">10 / halaman</option>
                                        <option :value="15">15 / halaman</option>
                                        <option :value="25">25 / halaman</option>
                                        <option :value="50">50 / halaman</option>
                                        <option :value="100">100 / halaman</option>
                                    </select>

                                    <!-- Export CSV -->
                                    <a
                                        v-if="selectedTable"
                                        :href="route('admin.sqlite-viewer.export', { db: selectedDb, table: selectedTable, format: 'csv' })"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-xl border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 transition"
                                        title="Export tabel ke CSV"
                                    >
                                        CSV
                                    </a>

                                    <!-- Export JSON -->
                                    <a
                                        v-if="selectedTable"
                                        :href="route('admin.sqlite-viewer.export', { db: selectedDb, table: selectedTable, format: 'json' })"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-xl border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 transition"
                                        title="Export tabel ke JSON"
                                    >
                                        JSON
                                    </a>
                                </div>

                            </div>

                            <!-- Data Records Table -->
                            <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden bg-white dark:bg-gray-800 shadow-inner">
                                <div class="overflow-x-auto max-h-[500px]">
                                    <table class="w-full text-left text-xs border-collapse">
                                        <thead class="bg-gray-50 dark:bg-gray-700/80 sticky top-0 z-10 text-gray-600 dark:text-gray-300 font-semibold border-b border-gray-200 dark:border-gray-700">
                                            <tr>
                                                <th class="py-2.5 px-3 w-12 text-center text-gray-400">#</th>
                                                <th class="py-2.5 px-2 w-14 text-center">Aksi</th>
                                                <th
                                                    v-for="col in tableSchema"
                                                    :key="col.name"
                                                    @click="handleSort(col.name)"
                                                    class="py-2.5 px-3 whitespace-nowrap cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600/50 transition select-none"
                                                >
                                                    <div class="flex items-center gap-1">
                                                        <span>{{ col.name }}</span>
                                                        <span v-if="col.pk" class="text-[9px] px-1 bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 rounded font-mono">PK</span>
                                                        <span v-if="sortBy === col.name" class="text-indigo-600 dark:text-indigo-400">
                                                            {{ sortDir === 'ASC' ? '▲' : '▼' }}
                                                        </span>
                                                    </div>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 text-gray-700 dark:text-gray-300">
                                            <tr
                                                v-for="(row, idx) in (tableData?.rows || [])"
                                                :key="idx"
                                                class="hover:bg-indigo-50/40 dark:hover:bg-indigo-950/20 transition"
                                            >
                                                <td class="py-2 px-3 text-center text-gray-400 font-mono text-[11px]">
                                                    {{ (tableData?.from || 1) + idx }}
                                                </td>
                                                <td class="py-2 px-2 text-center">
                                                    <button
                                                        @click="viewRowDetail(row)"
                                                        class="p-1 rounded hover:bg-indigo-100 dark:hover:bg-indigo-950/70 text-indigo-600 dark:text-indigo-400 transition"
                                                        title="Lihat Detail Baris"
                                                    >
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                        </svg>
                                                    </button>
                                                </td>
                                                <td
                                                    v-for="col in tableSchema"
                                                    :key="col.name"
                                                    class="py-2 px-3 whitespace-nowrap font-mono text-[11px] max-w-[280px] truncate"
                                                    v-html="formatCell(row[col.name])"
                                                >
                                                </td>
                                            </tr>

                                            <tr v-if="!tableData?.rows || tableData.rows.length === 0">
                                                <td :colspan="(tableSchema.length || 0) + 2" class="py-8 text-center text-xs text-gray-400">
                                                    Tidak ada data ditemukan pada tabel ini.
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Pagination Controls -->
                            <div v-if="tableData && tableData.total > 0" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-2">
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    Menampilkan baris <span class="font-semibold text-gray-800 dark:text-gray-200">{{ tableData.from }}</span> - 
                                    <span class="font-semibold text-gray-800 dark:text-gray-200">{{ tableData.to }}</span> dari 
                                    <span class="font-semibold text-gray-800 dark:text-gray-200">{{ tableData.total }}</span> total data
                                </div>

                                <div class="flex items-center gap-1">
                                    <button
                                        @click="goToPage(1)"
                                        :disabled="tableData.page <= 1"
                                        class="px-2.5 py-1 rounded-lg text-xs font-medium border border-gray-200 dark:border-gray-700 disabled:opacity-40 hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                                    >
                                        « Awal
                                    </button>
                                    <button
                                        @click="goToPage(tableData.page - 1)"
                                        :disabled="tableData.page <= 1"
                                        class="px-2.5 py-1 rounded-lg text-xs font-medium border border-gray-200 dark:border-gray-700 disabled:opacity-40 hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                                    >
                                        ‹ Prev
                                    </button>
                                    <span class="px-3 py-1 text-xs font-semibold text-indigo-600 dark:text-indigo-400">
                                        Hal. {{ tableData.page }} / {{ tableData.last_page }}
                                    </span>
                                    <button
                                        @click="goToPage(tableData.page + 1)"
                                        :disabled="tableData.page >= tableData.last_page"
                                        class="px-2.5 py-1 rounded-lg text-xs font-medium border border-gray-200 dark:border-gray-700 disabled:opacity-40 hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                                    >
                                        Next ›
                                    </button>
                                    <button
                                        @click="goToPage(tableData.last_page)"
                                        :disabled="tableData.page >= tableData.last_page"
                                        class="px-2.5 py-1 rounded-lg text-xs font-medium border border-gray-200 dark:border-gray-700 disabled:opacity-40 hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                                    >
                                        Akhir »
                                    </button>
                                </div>
                            </div>

                        </div>

                        <!-- TAB 2: SCHEMA & STRUCTURE -->
                        <div v-if="activeTab === 'schema'" class="p-6 space-y-6">
                            
                            <!-- Columns Definition Table -->
                            <div>
                                <h3 class="text-xs font-bold uppercase text-gray-400 tracking-wider mb-3">
                                    Kolom & Tipe Data
                                </h3>
                                <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                                    <table class="w-full text-left text-xs border-collapse">
                                        <thead class="bg-gray-50 dark:bg-gray-700/80 text-gray-600 dark:text-gray-300 font-semibold border-b border-gray-200 dark:border-gray-700">
                                            <tr>
                                                <th class="py-2.5 px-3 w-10 text-center text-gray-400">CID</th>
                                                <th class="py-2.5 px-3">Nama Kolom</th>
                                                <th class="py-2.5 px-3">Tipe Data</th>
                                                <th class="py-2.5 px-3">Nullable</th>
                                                <th class="py-2.5 px-3">Nilai Default</th>
                                                <th class="py-2.5 px-3">Primary Key</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-gray-700 dark:text-gray-300">
                                            <tr v-for="col in tableSchema" :key="col.cid" class="hover:bg-gray-50 dark:hover:bg-gray-700/40 font-mono text-[11px]">
                                                <td class="py-2 px-3 text-center text-gray-400">{{ col.cid }}</td>
                                                <td class="py-2 px-3 font-semibold text-gray-900 dark:text-white">{{ col.name }}</td>
                                                <td class="py-2 px-3 text-indigo-600 dark:text-indigo-400">{{ col.type || 'BLOB/ANY' }}</td>
                                                <td class="py-2 px-3">
                                                    <span :class="col.notnull ? 'text-amber-600 dark:text-amber-400 font-semibold' : 'text-emerald-600 dark:text-emerald-400'">
                                                        {{ col.notnull ? 'NOT NULL' : 'NULL' }}
                                                    </span>
                                                </td>
                                                <td class="py-2 px-3 text-gray-500">{{ col.dflt_value !== null ? col.dflt_value : 'NULL' }}</td>
                                                <td class="py-2 px-3">
                                                    <span v-if="col.pk" class="px-2 py-0.5 bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 rounded font-bold">
                                                        PK ({{ col.pk }})
                                                    </span>
                                                    <span v-else class="text-gray-400">-</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Indexes List -->
                            <div v-if="tableIndexes.length > 0">
                                <h3 class="text-xs font-bold uppercase text-gray-400 tracking-wider mb-3">
                                    Indeks (Indexes)
                                </h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div
                                        v-for="idx in tableIndexes"
                                        :key="idx.name"
                                        class="p-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/30 text-xs font-mono"
                                    >
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="font-bold text-gray-900 dark:text-white">{{ idx.name }}</span>
                                            <span v-if="idx.unique" class="px-1.5 py-0.5 bg-indigo-100 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 text-[10px] rounded font-semibold">
                                                UNIQUE
                                            </span>
                                        </div>
                                        <div class="text-gray-500 dark:text-gray-400 text-[11px]">
                                            Kolom: <span class="text-indigo-600 dark:text-indigo-400">{{ idx.columns.join(', ') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Foreign Keys -->
                            <div v-if="tableForeignKeys.length > 0">
                                <h3 class="text-xs font-bold uppercase text-gray-400 tracking-wider mb-3">
                                    Foreign Keys
                                </h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div
                                        v-for="(fk, idx) in tableForeignKeys"
                                        :key="idx"
                                        class="p-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/30 text-xs font-mono"
                                    >
                                        <div class="text-gray-900 dark:text-white font-semibold flex items-center gap-1.5">
                                            <span>{{ fk.from }}</span>
                                            <svg class="w-3 h-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                            </svg>
                                            <span>{{ fk.table }}({{ fk.to }})</span>
                                        </div>
                                        <div class="text-[10px] text-gray-500 mt-1">
                                            ON UPDATE {{ fk.on_update }} | ON DELETE {{ fk.on_delete }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- SQL DDL Create Statement -->
                            <div v-if="tableSql">
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="text-xs font-bold uppercase text-gray-400 tracking-wider">
                                        Skrip DDL (CREATE TABLE)
                                    </h3>
                                    <button
                                        @click="copySql"
                                        class="text-xs text-indigo-600 dark:text-indigo-400 font-semibold hover:underline inline-flex items-center gap-1"
                                    >
                                        <svg v-if="copySuccess" class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>{{ copySuccess ? 'Tersalin!' : 'Salin SQL' }}</span>
                                    </button>
                                </div>
                                <pre class="p-4 rounded-xl bg-gray-900 text-gray-100 font-mono text-xs overflow-x-auto border border-gray-800 leading-relaxed">{{ tableSql }}</pre>
                            </div>

                        </div>

                        <!-- TAB 3: SQL QUERY EDITOR & RUNNER -->
                        <div v-if="activeTab === 'sql'" class="p-6 space-y-4">
                            
                            <!-- Template Snippets -->
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="text-xs text-gray-400 mr-1">Snippet Cepat:</span>
                                <button
                                    @click="setSqlTemplate('select_all')"
                                    class="px-2.5 py-1 rounded-lg text-xs bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 transition"
                                >
                                    SELECT * LIMIT 100
                                </button>
                                <button
                                    @click="setSqlTemplate('count')"
                                    class="px-2.5 py-1 rounded-lg text-xs bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 transition"
                                >
                                    COUNT(*)
                                </button>
                                <button
                                    @click="setSqlTemplate('pragma_info')"
                                    class="px-2.5 py-1 rounded-lg text-xs bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 transition"
                                >
                                    PRAGMA table_info
                                </button>
                                <button
                                    @click="setSqlTemplate('list_tables')"
                                    class="px-2.5 py-1 rounded-lg text-xs bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 transition"
                                >
                                    sqlite_master
                                </button>
                            </div>

                            <!-- SQL Textarea -->
                            <div class="relative">
                                <textarea
                                    v-model="sqlQuery"
                                    rows="5"
                                    placeholder="Tulis perintah SQL di sini... (Contoh: SELECT * FROM tabel LIMIT 20;)"
                                    class="w-full font-mono text-xs bg-gray-900 text-emerald-400 border border-gray-800 rounded-xl p-3 focus:ring-2 focus:ring-indigo-500 leading-relaxed shadow-inner"
                                    @keydown.ctrl.enter="executeCustomSql"
                                    @keydown.meta.enter="executeCustomSql"
                                ></textarea>
                            </div>

                            <!-- Action Bar -->
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] text-gray-400">
                                    Shortcut: <kbd class="px-1.5 py-0.5 bg-gray-200 dark:bg-gray-700 rounded text-[10px] font-mono">Ctrl</kbd> + <kbd class="px-1.5 py-0.5 bg-gray-200 dark:bg-gray-700 rounded text-[10px] font-mono">Enter</kbd> untuk jalankan.
                                </span>

                                <button
                                    @click="executeCustomSql"
                                    :disabled="isExecutingQuery"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-sm transition disabled:opacity-50"
                                >
                                    <svg v-if="isExecutingQuery" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>Jalankan Query</span>
                                </button>
                            </div>

                            <!-- Query Execution Error -->
                            <div v-if="queryError" class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-xs text-rose-700 dark:text-rose-300">
                                <span class="font-bold">Error Eksekusi: </span>{{ queryError }}
                            </div>

                            <!-- Query Results Grid -->
                            <div v-if="queryResult" class="space-y-3 pt-2">
                                <div class="flex items-center justify-between text-xs text-gray-500">
                                    <div>
                                        <span v-if="queryResult.is_select">
                                            Ditemukan <strong class="text-gray-900 dark:text-white">{{ queryResult.total_returned }}</strong> baris
                                            <span v-if="queryResult.has_more" class="text-amber-500 font-semibold">(dibatasi 500 baris max)</span>
                                        </span>
                                        <span v-else class="text-emerald-600 dark:text-emerald-400 font-semibold">
                                            {{ queryResult.message }}
                                        </span>
                                    </div>
                                    <div class="font-mono text-[11px]">
                                        Waktu: {{ queryResult.execution_time_ms }} ms
                                    </div>
                                </div>

                                <div v-if="queryResult.is_select && queryResult.columns?.length > 0" class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-inner">
                                    <div class="overflow-x-auto max-h-[400px]">
                                        <table class="w-full text-left text-xs border-collapse">
                                            <thead class="bg-gray-50 dark:bg-gray-700/80 sticky top-0 z-10 text-gray-600 dark:text-gray-300 font-semibold border-b border-gray-200 dark:border-gray-700">
                                                <tr>
                                                    <th class="py-2 px-3 w-10 text-center text-gray-400">#</th>
                                                    <th v-for="col in queryResult.columns" :key="col" class="py-2 px-3 whitespace-nowrap">
                                                        {{ col }}
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-gray-700 dark:text-gray-300">
                                                <tr v-for="(row, rIdx) in queryResult.rows" :key="rIdx" class="hover:bg-indigo-50/40 dark:hover:bg-indigo-950/20 font-mono text-[11px]">
                                                    <td class="py-1.5 px-3 text-center text-gray-400">{{ rIdx + 1 }}</td>
                                                    <td
                                                        v-for="col in queryResult.columns"
                                                        :key="col"
                                                        class="py-1.5 px-3 whitespace-nowrap max-w-[280px] truncate"
                                                        v-html="formatCell(row[col])"
                                                    >
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

        <!-- MODAL: VIEW ROW DETAIL -->
        <div
            v-if="isRowDetailOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
        >
            <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-2xl w-full p-6 shadow-xl border border-gray-100 dark:border-gray-700 space-y-4 max-h-[85vh] flex flex-col">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <span>Detail Rekord</span>
                        <span class="text-xs px-2 py-0.5 bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 rounded-md font-mono">
                            {{ selectedTable }}
                        </span>
                    </h3>
                    <button
                        @click="closeRowDetail"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="overflow-y-auto space-y-3 flex-1 pr-1">
                    <div
                        v-for="(val, key) in (activeRowDetail || {})"
                        :key="key"
                        class="p-3 rounded-xl bg-gray-50 dark:bg-gray-700/40 border border-gray-100 dark:border-gray-700 space-y-1"
                    >
                        <div class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider font-mono">
                            {{ key }}
                        </div>
                        <div class="font-mono text-xs text-gray-800 dark:text-gray-200 break-all whitespace-pre-wrap leading-relaxed">
                            {{ val !== null ? val : 'NULL' }}
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-100 dark:border-gray-700 pt-3 flex justify-end">
                    <button
                        @click="closeRowDetail"
                        class="px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-xl text-xs font-semibold transition"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL: UPLOAD SQLITE DB -->
        <div
            v-if="isUploadModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
        >
            <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full p-6 shadow-xl border border-gray-100 dark:border-gray-700 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">
                        Unggah Database SQLite
                    </h3>
                    <button
                        @click="isUploadModalOpen = false"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitUpload" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            Pilih File Database (.sqlite, .db, .sqlite3)
                        </label>
                        <input
                            type="file"
                            accept=".sqlite,.sqlite3,.db"
                            @change="handleFileUpload"
                            required
                            class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-gray-700 dark:file:text-gray-200"
                        />
                        <p class="text-[10px] text-gray-400 mt-1">
                            Maksimal ukuran berkas 50 MB.
                        </p>
                    </div>

                    <div class="border-t border-gray-100 dark:border-gray-700 pt-3 flex justify-end gap-2">
                        <button
                            type="button"
                            @click="isUploadModalOpen = false"
                            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-xl text-xs font-semibold transition"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="uploadForm.processing || !uploadForm.sqlite_file"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-sm transition disabled:opacity-50"
                        >
                            {{ uploadForm.processing ? 'Mengunggah...' : 'Unggah & Buka' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </AdminLayout>
</template>
