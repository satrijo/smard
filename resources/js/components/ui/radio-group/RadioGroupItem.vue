<template>
  <div class="flex items-center space-x-2">
    <input
      type="radio"
      :id="id"
      :value="value"
      :checked="modelValue === value"
      @change="$emit('update:modelValue', value)"
      class="h-4 w-4 border-gray-300 text-primary focus:ring-primary"
      v-bind="$attrs"
    />
    <label
      :for="id"
      class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
    >
      <slot />
    </label>
  </div>
</template>

<script setup>
defineProps({
  id: {
    type: String,
    required: true
  },
  value: {
    type: [String, Number],
    required: true
  },
  modelValue: {
    type: [String, Number],
    default: ''
  }
});

defineEmits(['update:modelValue']);
</script>
