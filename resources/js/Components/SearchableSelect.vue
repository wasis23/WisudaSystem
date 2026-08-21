<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    options: {
        type: Array,
        default: () => [],
    },
    placeholder: {
        type: String,
        default: 'Cari dosen / pilih nama...',
    },
    required: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue', 'change']);

const isOpen = ref(false);
const searchQuery = ref(props.modelValue || '');
const containerRef = ref(null);
const searchInputRef = ref(null);
const highlightedIndex = ref(-1);

watch(() => props.modelValue, (newVal) => {
    searchQuery.value = newVal || '';
});

// Filter options based on search query
const filteredOptions = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();
    if (!query) {
        return props.options.slice(0, 30); // show top 30 initially
    }
    return props.options
        .filter(item => {
            const nama = (item.nama || '').toLowerCase();
            const nidn = (item.nidn || '').toLowerCase();
            const nip = (item.nip || '').toLowerCase();
            return nama.includes(query) || nidn.includes(query) || nip.includes(query);
        })
        .slice(0, 50);
});

const openDropdown = () => {
    isOpen.value = true;
    highlightedIndex.value = -1;
};

const closeDropdown = () => {
    isOpen.value = false;
    highlightedIndex.value = -1;
};

const handleInput = (e) => {
    const val = e.target.value;
    searchQuery.value = val;
    emit('update:modelValue', val);
    emit('change', val);
    if (!isOpen.value) {
        isOpen.value = true;
    }
    highlightedIndex.value = -1;
};

const selectOption = (option) => {
    searchQuery.value = option.nama;
    emit('update:modelValue', option.nama);
    emit('change', option.nama);
    closeDropdown();
};

const clearSelection = () => {
    searchQuery.value = '';
    emit('update:modelValue', '');
    emit('change', '');
    closeDropdown();
    searchInputRef.value?.focus();
};

const handleKeyDown = (e) => {
    if (!isOpen.value) {
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            openDropdown();
            e.preventDefault();
        }
        return;
    }

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (highlightedIndex.value < filteredOptions.value.length - 1) {
            highlightedIndex.value++;
            scrollToHighlighted();
        }
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        if (highlightedIndex.value > 0) {
            highlightedIndex.value--;
            scrollToHighlighted();
        }
    } else if (e.key === 'Enter') {
        if (highlightedIndex.value >= 0 && highlightedIndex.value < filteredOptions.value.length) {
            e.preventDefault();
            selectOption(filteredOptions.value[highlightedIndex.value]);
        } else {
            closeDropdown();
        }
    } else if (e.key === 'Escape') {
        e.preventDefault();
        closeDropdown();
    }
};

const listContainerRef = ref(null);
const scrollToHighlighted = () => {
    nextTick(() => {
        if (!listContainerRef.value) return;
        const activeItem = listContainerRef.value.querySelector('.is-highlighted');
        if (activeItem) {
            activeItem.scrollIntoView({ block: 'nearest' });
        }
    });
};

const handleClickOutside = (event) => {
    if (containerRef.value && !containerRef.value.contains(event.target)) {
        closeDropdown();
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div ref="containerRef" class="relative w-full">
        <!-- Input & Trigger -->
        <div class="relative flex items-center">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            <input
                ref="searchInputRef"
                type="text"
                :value="searchQuery"
                :placeholder="placeholder"
                :required="required"
                @focus="openDropdown"
                @click="openDropdown"
                @input="handleInput"
                @keydown="handleKeyDown"
                class="w-full pl-9 pr-14 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-xs sm:text-sm placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition shadow-sm"
                autocomplete="off"
            />

            <!-- Action buttons: Clear & Dropdown arrow -->
            <div class="absolute inset-y-0 right-0 flex items-center pr-2.5 gap-1">
                <button
                    v-if="searchQuery"
                    type="button"
                    @click.stop="clearSelection"
                    class="p-1 rounded-md text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                    title="Kosongkan"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                <button
                    type="button"
                    @click.stop="isOpen = !isOpen"
                    class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"
                >
                    <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': isOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Dropdown Menu -->
        <transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
        >
            <div
                v-if="isOpen"
                ref="listContainerRef"
                class="absolute z-50 mt-1.5 w-full max-h-64 overflow-y-auto rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-2xl divide-y divide-slate-100 dark:divide-slate-800"
            >
                <div v-if="filteredOptions.length === 0" class="p-3 text-center text-xs text-slate-500 dark:text-slate-400">
                    <p class="font-medium">Tidak ada dosen yang cocok.</p>
                    <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">Teks yang Anda ketik tetap akan tersimpan sebagai nama manual.</p>
                </div>

                <div
                    v-for="(option, index) in filteredOptions"
                    :key="option.id || index"
                    @mousedown.prevent="selectOption(option)"
                    class="p-2.5 px-3 cursor-pointer text-left transition flex items-center justify-between gap-2 hover:bg-indigo-50 dark:hover:bg-indigo-950/50"
                    :class="{
                        'bg-indigo-50 dark:bg-indigo-950/60 is-highlighted': index === highlightedIndex,
                        'bg-indigo-50/50 dark:bg-slate-800 font-semibold text-indigo-600 dark:text-indigo-400': option.nama === searchQuery
                    }"
                >
                    <div class="min-w-0 flex-1">
                        <div class="text-xs sm:text-sm font-medium text-slate-900 dark:text-slate-100 truncate">
                            {{ option.nama }}
                        </div>
                        <div v-if="option.nidn || option.nip" class="flex items-center gap-2 mt-0.5 text-[11px]">
                            <span v-if="option.nidn" class="font-mono text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/60 px-1.5 py-0.5 rounded text-[10px] font-semibold">
                                NIDN: {{ option.nidn }}
                            </span>
                            <span v-else-if="option.nip" class="font-mono text-slate-500 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded text-[10px]">
                                NIP: {{ option.nip }}
                            </span>
                        </div>
                    </div>

                    <svg
                        v-if="option.nama === searchQuery"
                        class="w-4 h-4 text-indigo-600 dark:text-indigo-400 flex-shrink-0"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    >
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </div>
            </div>
        </transition>
    </div>
</template>
