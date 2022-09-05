<script setup lang="ts">
interface Props {
    allowCustom: boolean
    label?: string
    shortlink: string
    shortlinkId?: number
    redirectLabel?: string
    redirectType: string
    showRedirectOption: boolean
}

withDefaults(defineProps<Props>(), {
    label: 'Shortlink',
    shortlink: '',
    redirectLabel: 'Redirect Type',
})
</script>

<template>
    <div class="mb-4">
        <div class="flex items-center">
            <h6 class="h6">
                {{ label }}
            </h6>
            <button type="button" title="regenerate"
                    class="ml-auto inline-flex mb-2 text-gray-800 hover:text-red-craft transition-colors ease-in-out duration-150">
                <span class="inline-flex">
                    regenerate
                </span>
                <svg width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.25 4.75L8.75 7L11.25 9.25"></path>
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12.75 19.25L15.25 17L12.75 14.75"></path>
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 7H13.25C16.5637 7 19.25 9.68629 19.25 13V13.25"></path>
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.25 17H10.75C7.43629 17 4.75 14.3137 4.75 11V10.75"></path>
                </svg>
            </button>
        </div>
        <div class="meta">
            <div class="field">
                <label class="heading">
                        {{ label }}
                </label>
                <div class="input ltr">
                    <input type="text" id="shortlink" class="text fullwidth" name="shortlink-uri" :value="shortlink" :disabled="!allowCustom">
                    <div class="absolute top-0 -right-4 flex items-center h-full w-8">
                        <button type="button" title="copy" class="text-gray-800 hover:text-red-craft transition-colors ease-in-out duration-150">
                            <svg width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6.5 15.25V15.25C5.5335 15.25 4.75 14.4665 4.75 13.5V6.75C4.75 5.64543 5.64543 4.75 6.75 4.75H13.5C14.4665 4.75 15.25 5.5335 15.25 6.5V6.5"></path>
                                <rect width="10.5" height="10.5" x="8.75" y="8.75" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" rx="2"></rect>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            <div v-if="showRedirectOption" class="field">
                <label class="heading">
                    {{ redirectLabel }}
                </label>
                <div class="input ltr">
                    <div class="select">
                        <select name="shortlink-redirect-type">
                            <option value="301" :selected="redirectType === '301'">Permanent</option>
                            <option value="302" :selected="redirectType === '302'">Temporary</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" name="shortlinkId" :value="shortlinkId">
    </div>
</template>
