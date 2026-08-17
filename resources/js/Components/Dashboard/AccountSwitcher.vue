<template>
    <div class="bg-gray-800/90 backdrop-blur p-4 rounded-xl shadow-lg mb-6 border border-gray-700">
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
                <span class="text-xl">👥</span>
                <h3 class="text-base font-bold text-white">مدیریت و سوئیچ سریع بین اکانت‌ها (Multi-Account Switcher)</h3>
            </div>
            <button
                @click="showAddModal = true"
                class="px-2.5 py-1 bg-amber-500 hover:bg-amber-400 text-gray-950 rounded-lg text-xs font-bold transition flex items-center gap-1"
            >
                <span>+ افزودن اکانت جدید</span>
            </button>
        </div>

        <!-- لیست اکانت‌های ذخیره‌شده -->
        <div v-if="savedAccounts.length" class="flex flex-wrap gap-2">
            <div
                v-for="(acc, idx) in savedAccounts"
                :key="acc.tag"
                class="flex items-center gap-2 px-3 py-1.5 rounded-xl border transition"
                :class="currentTag === acc.tag ? 'bg-amber-500/20 border-amber-500 text-amber-300 font-bold' : 'bg-gray-700/60 border-gray-600/60 text-gray-300 hover:bg-gray-700'"
            >
                <button
                    @click="switchAccount(acc.tag)"
                    class="flex items-center gap-1.5 text-xs text-right"
                    :disabled="switching"
                >
                    <span>{{ acc.icon || '🛡️' }}</span>
                    <span>{{ acc.label || acc.name || acc.tag }}</span>
                    <span class="font-mono text-[10px] text-gray-400">({{ acc.tag }})</span>
                </button>

                <button
                    @click.stop="removeAccount(idx)"
                    class="text-gray-400 hover:text-red-400 text-xs px-1"
                    title="حذف از لیست سریع"
                >
                    ✕
                </button>
            </div>
        </div>

        <div v-else class="text-xs text-gray-400 py-2">
            هنوز اکانت دیگری ذخیره نشده است. با دکمه «افزودن اکانت جدید»، تگ‌های دیگرتان را ثبت کنید تا با ۱ کلیک بین آن‌ها جابجا شوید.
        </div>

        <!-- مودال افزودن اکانت -->
        <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
            <div class="bg-gray-800 border border-gray-700 rounded-2xl p-5 w-full max-w-sm shadow-2xl">
                <h4 class="text-base font-bold text-white mb-3">افزودن اکانت به لیست سریع</h4>

                <div class="space-y-3 mb-4">
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">نام یا عنوان دلخواه (مثال: اکانت اصلی)</label>
                        <input
                            v-model="newLabel"
                            type="text"
                            placeholder="اکانت اصلی / مینی اکانت"
                            class="w-full bg-gray-900 border border-gray-700 rounded-xl px-3 py-2 text-white text-xs focus:outline-none focus:border-amber-500"
                        />
                    </div>

                    <div>
                        <label class="block text-xs text-gray-400 mb-1">تگ بازیکن (Player Tag)</label>
                        <input
                            v-model="newTag"
                            type="text"
                            placeholder="2PP0J9VL#"
                            class="w-full bg-gray-900 border border-gray-700 rounded-xl px-3 py-2 text-white text-xs font-mono focus:outline-none focus:border-amber-500"
                        />
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <button
                        @click="showAddModal = false"
                        class="px-3 py-1.5 rounded-xl bg-gray-700 text-gray-300 text-xs font-bold hover:bg-gray-600"
                    >
                        انصراف
                    </button>
                    <button
                        @click="saveNewAccount"
                        :disabled="!newTag"
                        class="px-4 py-1.5 rounded-xl bg-amber-500 text-gray-950 text-xs font-bold hover:bg-amber-400 disabled:opacity-50"
                    >
                        ذخیره
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'AccountSwitcher',
    props: {
        currentTag: {
            type: String,
            default: ''
        },
        currentName: {
            type: String,
            default: ''
        }
    },
    data() {
        return {
            savedAccounts: [],
            showAddModal: false,
            newLabel: '',
            newTag: '',
            switching: false
        }
    },
    mounted() {
        this.loadAccounts()
    },
    methods: {
        loadAccounts() {
            try {
                const stored = localStorage.getItem('cocai_saved_accounts')
                if (stored) {
                    this.savedAccounts = JSON.parse(stored)
                }
            } catch (e) {
                console.error(e)
            }

            // اطمینان از وجود اکانت فعلی در لیست
            if (this.currentTag) {
                const exists = this.savedAccounts.some(a => a.tag === this.currentTag)
                if (!exists) {
                    this.savedAccounts.unshift({
                        tag: this.currentTag,
                        label: this.currentName || 'اکانت فعال',
                        icon: '⭐'
                    })
                    this.persist()
                }
            }
        },
        persist() {
            try {
                localStorage.setItem('cocai_saved_accounts', JSON.stringify(this.savedAccounts))
            } catch (e) {
                console.error(e)
            }
        },
        saveNewAccount() {
            if (!this.newTag.trim()) return

            let tag = this.newTag.trim().toUpperCase()
            if (!tag.startsWith('#')) tag = '#' + tag

            const exists = this.savedAccounts.some(a => a.tag === tag)
            if (!exists) {
                this.savedAccounts.push({
                    tag: tag,
                    label: this.newLabel.trim() || 'اکانت ' + (this.savedAccounts.length + 1),
                    icon: '🎮'
                })
                this.persist()
            }

            this.newLabel = ''
            this.newTag = ''
            this.showAddModal = false
        },
        removeAccount(idx) {
            this.savedAccounts.splice(idx, 1)
            this.persist()
        },
        switchAccount(tag) {
            if (tag === this.currentTag) return
            this.switching = true

            this.$inertia.post('/save-player-tag', {
                player_tag: tag
            }, {
                preserveScroll: true,
                onFinish: () => {
                    this.switching = false
                }
            })
        }
    }
}
</script>
