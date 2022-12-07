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
    devMode: boolean
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

const showShortlinkUrl = (visibility) => {
    if (props.devMode) {
        return true
    }

    if (visibility) {
        return true
    }

    return false
}

const sanitisedShortlinkId = computed(() => props.shortlinkId === 0 ? null : props.shortlinkId)
</script>

<template>
  <div class="mb-4">
    <div class="flex items-center mb-2">
      <h6 class="h6">
        {{ label }}
      </h6>
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
            v-if="showShortlinkUrl(shortlinkUrl.showWhenDevmodeIsOff)"
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
