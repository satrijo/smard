<template>
  <div
    class="grid gap-2"
    :class="orientation === 'horizontal' ? 'grid-cols-auto' : 'grid-cols-1'"
    v-bind="$attrs"
  >
    <slot />
  </div>
</template>

<script setup>
defineProps({
  modelValue: {
    type: [String, Number],
    default: ''
  },
  orientation: {
    type: String,
    default: 'vertical',
    validator: (value) => ['horizontal', 'vertical'].includes(value)
  }
});

defineEmits(['update:modelValue']);
</script>
