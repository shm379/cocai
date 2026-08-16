<template>
    <div id="player-tag-form" class="w-full max-w-md mb-6">
        <div class="p-6 bg-gray-800/90 backdrop-blur rounded-xl shadow-lg border border-red-500/30">
            <h2 class="text-lg font-medium text-red-300 mb-2">
                برای ادامه، لطفاً Player Tag خود را وارد کنید:
            </h2>
            <p class="text-sm text-gray-400 mb-4">
                مثال: <code class="text-red-300">#RPLVYLL2</code> — تگ را از پروفایل کلش خود کپی کنید.
            </p>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label for="player_tag" class="block text-sm font-medium text-red-300">
                        Player Tag
                    </label>
                    <input
                        type="text"
                        id="player_tag"
                        v-model="playerTagLocal"
                        @input="onInput"
                        class="mt-1 block w-full px-4 py-2 border border-red-500 rounded-lg bg-gray-700 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-red-500 direction-ltr"
                        placeholder="#RPLVYLL2"
                        required
                    />
                    <p v-if="error" class="mt-2 text-sm text-red-400">{{ error }}</p>
                </div>

                <!-- پیش‌نمایش -->
                <div
                    v-if="preview"
                    class="p-3 bg-gray-700/50 rounded-lg border border-green-500/30 animate-fade-in"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-white font-bold">{{ preview.name }}</p>
                            <p class="text-sm text-gray-300">
                                تاون‌هال {{ preview.town_hall }} | کاپ {{ preview.trophies }}
                            </p>
                            <p v-if="preview.clan" class="text-xs text-gray-400">
                                کلن: {{ preview.clan }}
                            </p>
                        </div>
                        <span class="text-2xl">🎯</span>
                    </div>
                </div>

                <button
                    :disabled="saving || !playerTagLocal"
                    type="submit"
                    class="w-full py-2 px-4 bg-red-600 text-white rounded-lg shadow hover:bg-red-700 transition duration-200 disabled:opacity-50 flex items-center justify-center gap-2"
                >
                    <span v-if="saving">در حال دریافت اطلاعات بازی...</span>
                    <span v-else>ثبت و شروع</span>
                </button>
            </form>
        </div>
    </div>
</template>

<script>
export default {
    name: "PlayerTagForm",
    props: {
        saving: {
            type: Boolean,
            default: false
        }
    },
    emits: ['submit'],
    data() {
        return {
            playerTagLocal: '',
            error: '',
            preview: null,
            debounceTimer: null,
            loadingPreview: false
        }
    },
    beforeUnmount() {
        if (this.debounceTimer) clearTimeout(this.debounceTimer);
    },
    methods: {
        onInput() {
            this.preview = null;
            this.error = '';

            if (this.debounceTimer) clearTimeout(this.debounceTimer);

            const sanitized = this.playerTagLocal.replace('#', '').trim().toUpperCase();
            if (!/^[0-9A-Z]{5,12}$/.test(sanitized)) {
                return;
            }

            this.debounceTimer = setTimeout(() => {
                this.fetchPreview(sanitized);
            }, 600);
        },
        async fetchPreview(tag) {
            this.loadingPreview = true;

            try {
                const response = await window.axios.get('/api/player-preview', {
                    params: { player_tag: tag }
                });
                this.preview = response.data;
            } catch (error) {
                this.preview = null;
                if (error.response?.status === 422) {
                    this.error = error.response.data.error || 'تگ یافت نشد.';
                }
            } finally {
                this.loadingPreview = false;
            }
        },
        submit() {
            this.error = '';

            const sanitized = this.playerTagLocal.replace('#', '').trim().toUpperCase();

            if (!/^[0-9A-Z]{5,12}$/.test(sanitized)) {
                this.error = 'تگ بازیکن معتبر نیست. فقط حروف و اعداد (مثلاً #RPLVYLL2).'
                return;
            }

            this.$emit('submit', sanitized);
        }
    }
}
</script>

<style scoped>
.direction-ltr {
    direction: ltr;
    text-align: left;
}

.animate-fade-in {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-5px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
