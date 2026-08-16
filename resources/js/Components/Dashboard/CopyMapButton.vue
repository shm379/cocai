<template>
    <button
        @click="copyLink"
        :disabled="!link || copied"
        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-medium transition"
        :class="buttonClass"
    >
        <span v-if="copied">✓ کپی شد</span>
        <span v-else>📋 کپی لینک</span>
    </button>
</template>

<script>
export default {
    name: 'CopyMapButton',
    props: {
        link: {
            type: String,
            default: ''
        }
    },
    data() {
        return {
            copied: false,
            timeout: null
        }
    },
    computed: {
        buttonClass() {
            return this.copied
                ? 'bg-green-600 text-white'
                : 'bg-red-600 hover:bg-red-500 text-white disabled:opacity-50';
        }
    },
    beforeUnmount() {
        if (this.timeout) clearTimeout(this.timeout);
    },
    methods: {
        async copyLink() {
            if (!this.link) return;

            try {
                await navigator.clipboard.writeText(this.link);
                this.copied = true;

                if (this.timeout) clearTimeout(this.timeout);
                this.timeout = setTimeout(() => {
                    this.copied = false;
                }, 2000);
            } catch (error) {
                console.error('Copy failed:', error);
                // Fallback
                const textarea = document.createElement('textarea');
                textarea.value = this.link;
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);

                this.copied = true;
                if (this.timeout) clearTimeout(this.timeout);
                this.timeout = setTimeout(() => {
                    this.copied = false;
                }, 2000);
            }
        }
    }
}
</script>
