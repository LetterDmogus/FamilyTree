<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { 
    Monitor, 
    Download, 
    Focus, 
    Plus, 
    Minus,
    ImageIcon,
    Sparkles,
    BrainCircuit,
    ShieldCheck,
    Users,
    Heart,
    LogIn,
    Database,
    Save,
    Zap,
    Info,
    MessageSquareQuote
} from 'lucide-vue-next';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import admin from '@/routes/admin';

interface SettingValue {
    id: number;
    value: string | number | boolean;
    type: 'string' | 'integer' | 'boolean';
    description: string;
}

const props = defineProps<{
    settings: Record<string, SettingValue>;
}>();

const sortedSettings = computed(() => {
    return Object.entries(props.settings).map(([key, item]) => ({
        key,
        ...item
    }));
});

const form = useForm({
    site_name: props.settings.site_name?.value ?? 'Wise Mystical Tree',
    max_spouses: props.settings.max_spouses?.value ?? 0,
    allow_same_sex: Boolean(props.settings.allow_same_sex?.value),
    allow_dead_login: Boolean(props.settings.allow_dead_login?.value),
    allow_custom_metadata: Boolean(props.settings.allow_custom_metadata?.value),
    priority_limit: props.settings.priority_limit?.value ?? 10,
});

const downloadBackup = () => {
    const routeDef = admin.settings.backup();
    window.location.href = typeof routeDef === 'string' ? routeDef : routeDef.url;
};

const submit = () => {
    const routeDef = admin.settings.wiseTree.update();
    const url = typeof routeDef === 'string' ? routeDef : routeDef.url;

    form.transform((data) => ({
        settings: Object.entries(data).map(([key, value]) => ({
            key,
            value
        }))
    })).put(url, {
        preserveScroll: true
    });
};

const getIcon = (key: string) => {
    return {
        site_name: Sparkles,
        max_spouses: Users,
        allow_same_sex: Heart,
        allow_dead_login: LogIn,
        allow_custom_metadata: Database,
        priority_limit: Zap
    }[key] || Info;
};

const getTitle = (key: string) => {
    const titles: Record<string, string> = {
        site_name: 'Nama Website',
        max_spouses: 'Batas Pasangan',
        allow_same_sex: 'Pernikahan Sesama Gender',
        allow_dead_login: 'Akses Login Almarhum',
        allow_custom_metadata: 'Metadata Kustom',
        priority_limit: 'Batas Prioritas Data'
    };

    return titles[key] || key.replace(/_/g, ' ').toUpperCase();
};
</script>

<template>
    <Head :title="`Aturan ${form.site_name}`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-8 font-sans select-none bg-slate-50/50 overflow-y-auto custom-scrollbar">
        
        <!-- Professional Header with Profile -->
        <div class="bg-white rounded-[2.5rem] border border-gray-100 shadow-sm overflow-hidden relative group">
            <div class="absolute top-0 right-0 p-12 opacity-[0.03] pointer-events-none group-hover:scale-110 transition-transform duration-700">
                <BrainCircuit class="w-64 h-64" />
            </div>

            <div class="p-8 md:p-12 flex flex-col md:flex-row items-center gap-10 relative z-10">
                <!-- Wise Tree Profile Image -->
                <div class="relative flex-shrink-0">
                    <div class="w-32 h-32 md:w-40 md:h-40 rounded-full border-8 border-slate-50 shadow-2xl overflow-hidden bg-emerald-50 flex items-center justify-center group-hover:rotate-3 transition-transform duration-500">
                        <img src="/storage/profiles/WiseTree.png" :alt="form.site_name" class="w-full h-full object-cover" @error="(e: any) => e.target.src = 'https://ui-avatars.com/api/?name=Wise+Tree&background=6366f1&color=fff'" />
                    </div>
                    <div class="absolute -bottom-2 -right-2 w-12 h-12 bg-emerald-500 rounded-2xl flex items-center justify-center text-white border-4 border-white shadow-lg shadow-emerald-100">
                        <ShieldCheck class="w-6 h-6 stroke-[2.5]" />
                    </div>
                </div>

                <div class="flex-1 text-center md:text-left space-y-4">
                    <div class="inline-flex items-center gap-2 px-3 py-1 bg-emerald-50 text-emerald-600 rounded-full border border-emerald-100">
                        <MessageSquareQuote class="w-3 h-3 fill-current" />
                        <span class="text-[9px] font-black uppercase tracking-[0.2em]">Pesan Pengelola</span>
                    </div>
                    <h1 class="text-3xl md:text-4xl font-black text-gray-900 tracking-tight leading-none">
                        {{ form.site_name }} <span class="text-emerald-600 font-black">Rules</span>
                    </h1>
                    <p class="text-gray-500 font-bold text-sm leading-relaxed max-w-2xl">
                        "Sebagai pengelola silsilah, Anda memiliki wewenang penuh untuk menentukan batasan hukum dan aturan operasional di dalam ekosistem ini. Gunakan pengaturan di bawah ini secara bijak."
                    </p>
                </div>
            </div>
        </div>

        <!-- Settings Cards -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div v-for="s in sortedSettings" :key="s.key" 
                @click="s.type === 'boolean' ? (form as any)[s.key] = !(form as any)[s.key] : null"
                :class="[
                    'bg-white border rounded-[2rem] p-8 flex items-center justify-between gap-6 transition-all duration-300 group',
                    s.type === 'boolean' && (form as any)[s.key] 
                        ? 'border-emerald-600 bg-emerald-50/50 shadow-xl shadow-emerald-100/20' 
                        : 'border-gray-100 hover:shadow-xl hover:shadow-emerald-50/50',
                    s.type === 'boolean' ? 'cursor-pointer' : ''
                ]"
            >
                
                <div class="flex items-center gap-5">
                    <div :class="[
                        'w-14 h-14 rounded-2xl flex items-center justify-center transition-all',
                        s.type === 'boolean' && (form as any)[s.key] ? 'bg-emerald-600 text-white shadow-lg' : 'bg-slate-50 text-gray-400 group-hover:bg-emerald-50 group-hover:text-emerald-600'
                    ]">
                        <component :is="getIcon(s.key)" class="w-6 h-6 stroke-[2]" />
                    </div>
                    <div>
                        <h3 :class="['font-black tracking-tight text-sm uppercase transition-colors', s.type === 'boolean' && (form as any)[s.key] ? 'text-emerald-900' : 'text-gray-900']">
                            {{ getTitle(s.key) }}
                        </h3>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">{{ s.description }}</p>
                    </div>
                </div>

                <div class="flex-shrink-0" @click.stop>
                    <!-- Boolean Checkbox -->
                    <div v-if="s.type === 'boolean'" class="flex items-center gap-4 bg-white/50 px-4 py-3 rounded-2xl border border-gray-100 shadow-sm">
                        <span class="text-[9px] font-black uppercase tracking-tighter" :class="(form as any)[s.key] ? 'text-emerald-600' : 'text-gray-400'">
                            {{ (form as any)[s.key] ? 'AKTIF' : 'MATI' }}
                        </span>
                        <Checkbox 
                            :checked="Boolean((form as any)[s.key])"
                            @update:checked="(v: boolean) => (form as any)[s.key] = v"
                            class="w-6 h-6 border-2 rounded-lg data-[state=checked]:bg-emerald-600 data-[state=checked]:border-emerald-600 transition-all"
                        />
                    </div>

                    <!-- Integer Input -->
                    <div v-else-if="s.type === 'integer'" class="flex items-center gap-2">
                        <Input 
                            v-model.number="(form as any)[s.key]" 
                            type="number" 
                            class="w-24 bg-slate-50 border-gray-100 rounded-xl font-black text-gray-900 text-center focus:ring-emerald-500 focus:bg-white h-12"
                        />
                    </div>

                    <!-- String Input -->
                    <div v-else-if="s.type === 'string'" class="flex items-center gap-2">
                        <Input 
                            v-model="(form as any)[s.key]" 
                            type="text" 
                            class="w-64 bg-slate-50 border-gray-100 rounded-xl font-black text-gray-900 px-6 focus:ring-emerald-500 focus:bg-white h-12"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Maintenance Section -->
        <div class="mt-8 bg-white rounded-[2.5rem] p-10 flex flex-col md:flex-row items-center justify-between gap-8 shadow-2xl shadow-emerald-100/20 border border-gray-100">
            <div class="flex items-center gap-6">
                <div class="w-16 h-16 bg-emerald-50 rounded-2xl flex items-center justify-center text-emerald-600">
                    <Database class="w-8 h-8" />
                </div>
                <div class="text-left">
                    <h3 class="text-gray-900 font-black uppercase text-sm tracking-widest">Sistem & Pemeliharaan</h3>
                    <p class="text-gray-400 text-[10px] font-bold uppercase mt-1 tracking-widest">Amankan data silsilah Anda dengan cadangan berkala</p>
                </div>
            </div>
            <Button 
                @click="downloadBackup"
                class="px-8 py-6 bg-gray-900 text-white rounded-2xl font-black uppercase text-[10px] tracking-widest hover:bg-emerald-600 transition-all flex items-center gap-3 shadow-xl"
            >
                <Download class="w-4 h-4 stroke-[3]" />
                Download Backup (.sql)
            </Button>
        </div>

        <!-- Footer Action -->
        <div class="flex items-center justify-center pt-8 pb-12">
            <Button 
                @click="submit" 
                :disabled="form.processing"
                class="px-12 py-8 bg-gray-900 text-white rounded-3xl font-black uppercase text-xs tracking-[0.2em] hover:bg-emerald-600 hover:scale-105 transition-all shadow-2xl shadow-emerald-100 flex items-center gap-3 disabled:opacity-50"
            >
                <Save v-if="!form.processing" class="w-4 h-4 stroke-[3]" />
                <BrainCircuit v-else class="w-4 h-4 animate-pulse" />
                Simpan Aturan Global
            </Button>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }

/* Hide number input arrows */
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
</style>
