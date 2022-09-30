<script setup lang="ts">
import { computed, ref } from 'vue'

interface Props {
    allowCustom: boolean
    label?: string
    shortlink: string
    shortlinkId?: number
    redirectLabel?: string
    redirectType: string
    showRedirectOption: boolean
    shortlinkUrls: Array<any>
    errors?: Array<string>
}

const props = withDefaults(defineProps<Props>(), {
    label: 'Shortlink',
    shortlink: '',
    shortlinkId: null,
    redirectLabel: 'Redirect Type',
    errors: null
})

const shortlinkInput = ref(props.shortlink)

const sanitisedShortlinkId = computed(() => props.shortlinkId === 0 ? null : props.shortlinkId)
</script>

<template>
    <div class="mb-4">
        <div class="flex items-center mb-2">
            <h6 class="h6">
                {{ label }}
            </h6>

            <!--<button type="button" title="regenerate"
                                                                                class="ml-auto inline-flex mb-2 transition-colors ease-in-out duration-150 bg-red-craft hover:bg-red-craft-hover text-white text-xs px-3 py-[2px] rounded-md"
                                                >
                                                                <span class="inline-flex">
                                                                                regenerate
                                                                </span>
                                                                <svg width="18" height="18" fill="none" viewBox="0 0 24 24">
                                                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.25 4.75L8.75 7L11.25 9.25"></path>
                                                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12.75 19.25L15.25 17L12.75 14.75"></path>
                                                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 7H13.25C16.5637 7 19.25 9.68629 19.25 13V13.25"></path>
                                                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.25 17H10.75C7.43629 17 4.75 14.3137 4.75 11V10.75"></path>
                                                                </svg>
                                                </button>-->
        </div>
        <ul 
            v-if="errors" 
            class="text-red-500 mb-2"
        >
            <li
                v-for="error in errors"
                :key="error"
            >
                {{ error }}
            </li>
        </ul>
        <div class="meta">
            <div class="field">
                <label class="heading">
                    {{ label }}
                </label>
                <div class="input ltr">
                    <input
                        id="shortlink"
                        v-model="shortlinkInput"
                        type="text"
                        class="text fullwidth"
                        name="shortlink-uri"
                        :disabled="!allowCustom"
                    >
                    <!--<div class="absolute top-0 -right-4 flex items-center h-full w-8">
                                                                                                <button type="button" title="copy" class="text-gray-800 hover:text-red-craft transition-colors ease-in-out duration-150">
                                                                                                                <svg width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                                                                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6.5 15.25V15.25C5.5335 15.25 4.75 14.4665 4.75 13.5V6.75C4.75 5.64543 5.64543 4.75 6.75 4.75H13.5C14.4665 4.75 15.25 5.5335 15.25 6.5V6.5"></path>
                                                                                                                                <rect width="10.5" height="10.5" x="8.75" y="8.75" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" rx="2"></rect>
                                                                                                                </svg>
                                                                                                </button>
                                                                                </div>-->
                </div>
            </div>
            <div
                v-if="showRedirectOption"
                class="field"
            >
                <label class="heading">
                    {{ redirectLabel }}
                </label>
                <div class="input ltr">
                    <div class="select">
                        <select name="shortlink-redirect-type">
                            <option
                                value="301"
                                :selected="redirectType === '301'"
                            >
                                Permanent
                            </option>
                            <option
                                value="302"
                                :selected="redirectType === '302'"
                            >
                                Temporary
                            </option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <dl class="meta read-only">
            <div
                v-if="shortlinkUrls"
                class="shortlink-urls"
            >
                <dd
                    v-for="shortlinkUrl in shortlinkUrls"
                    :key="shortlinkUrl.shortlinkUrl"
                    class="value pb-1 text-xs"
                >
                    <a
                        :href="`${ shortlinkUrl.shortlinkUrl }/${ shortlinkInput }`"
                        :title="shortlinkUrl.shortlinkUrl"
                        target="_blank"
                    >{{ shortlinkUrl.shortlinkUrl }}/{{ shortlinkInput }}</a>
                </dd>
            </div>
        </dl>
        <input
            type="hidden"
            name="shortlinkId"
            :value="sanitisedShortlinkId"
        >
    </div>
</template>
