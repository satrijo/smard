<script setup>
import { useForm, Head } from '@inertiajs/vue3'
import { Link } from '@inertiajs/vue3'
import Header from '@/components/layout/Header.vue'

const props = defineProps({
  warnings: {
    type: Array,
    default: () => []
  }
})

const formatUTCTime = (dateTime) => {
  if (!dateTime || dateTime === 'null' || dateTime === 'undefined') {
    return 'N/A';
  }
  
  const date = new Date(dateTime);
  
  if (isNaN(date.getTime())) {
    return 'Invalid Date';
  }
  
  const day = date.getUTCDate().toString().padStart(2, '0');
  const month = (date.getUTCMonth() + 1).toString().padStart(2, '0');
  const year = date.getUTCFullYear();
  const hours = date.getUTCHours().toString().padStart(2, '0');
  const minutes = date.getUTCMinutes().toString().padStart(2, '0');
  return `${day}/${month}/${year} ${hours}:${minutes} UTC`;
}

const getStatusBadge = (warning) => {
  if (warning.is_cancellation) {
    return {
      text: 'Pembatalan',
      class: 'bg-blue-100 text-blue-800'
    }
  }
  
  switch (warning.status) {
    case 'CANCELLED':
      return {
        text: 'Dibatalkan',
        class: 'bg-red-100 text-red-800'
      }
    case 'EXPIRED':
      return {
        text: 'Kedaluwarsa',
        class: 'bg-yellow-100 text-yellow-800'
      }
    case 'ACTIVE':
      return {
        text: 'Aktif',
        class: 'bg-green-100 text-green-800'
      }
    default:
      return {
        text: warning.status,
        class: 'bg-gray-100 text-gray-800'
      }
  }
}
</script>

<template>
  <Head :title="$page.props.metaTitle || 'Arsip Hari Ini - Aerodrome Warning System'" />
  <div class="min-h-screen bg-background">
    <Header />

    <main class="container mx-auto px-4 sm:px-6 py-8">
             <div class="flex items-center justify-between mb-6">
         <div>
           <h1 class="text-3xl font-bold leading-tight text-foreground">Arsip Peringatan Hari Ini</h1>
           <p class="text-sm text-muted-foreground mt-1">{{ new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}</p>
         </div>
         <div class="flex space-x-3">
           <Link href="/aerodrome" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium">
             Dashboard
           </Link>
         </div>
       </div>
      
             <!-- No Warnings Message -->
       <div v-if="warnings.length === 0" class="text-center py-12">
         <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
         </svg>
         <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada peringatan hari ini</h3>
         <p class="mt-1 text-sm text-gray-500">Belum ada peringatan yang diterbitkan, dibatalkan, atau kedaluwarsa hari ini.</p>
       </div>

      <!-- Warnings List -->
      <div v-else class="space-y-6">
        <div
          v-for="warning in warnings"
          :key="warning.id"
          class="bg-card border border-border rounded-lg shadow-sm"
        >
          <div class="p-6">
            <div class="flex items-center justify-between">
              <div class="flex-1">
                <div class="flex items-center space-x-3 mb-4">
                  <h3 class="text-lg font-medium text-foreground">
                    {{ warning.is_cancellation ? 'Pembatalan' : 'Peringatan' }} #{{ warning.sequence_number }}
                  </h3>
                  <span 
                    :class="getStatusBadge(warning).class"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  >
                    {{ getStatusBadge(warning).text }}
                  </span>
                </div>
                
                <!-- Preview Message -->
                <div class="mb-4">
                  <p class="text-sm text-muted-foreground mb-2">Sandi:</p>
                  <div class="bg-muted p-3 rounded-lg font-mono text-sm">
                    {{ warning.preview_message }}
                  </div>
                </div>

                <!-- Translation Message -->
                <div class="mb-4">
                  <p class="text-sm text-muted-foreground mb-2">Terjemahan:</p>
                  <div class="bg-blue-50 border border-blue-200 p-3 rounded-lg text-sm">
                    {{ warning.translation_message }}
                  </div>
                </div>
                
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                  <div>
                    <p class="text-sm text-muted-foreground">Diterbitkan oleh</p>
                    <p class="text-sm font-medium text-foreground">{{ warning.forecaster_name }} ({{ warning.forecaster_nip }})</p>
                  </div>
                  <div>
                    <p class="text-sm text-muted-foreground">Waktu terbit</p>
                    <p class="text-sm font-medium text-foreground">{{ formatUTCTime(warning.created_at) }}</p>
                  </div>
                  <div>
                    <p class="text-sm text-muted-foreground">Berlaku dari</p>
                    <p class="text-sm font-medium text-foreground">{{ formatUTCTime(warning.startTime) }}</p>
                  </div>
                  <div>
                    <p class="text-sm text-muted-foreground">Berlaku sampai</p>
                    <p class="text-sm font-medium text-foreground">{{ formatUTCTime(warning.valid_to) }}</p>
                  </div>
                </div>

                <!-- Phenomena (only for non-cancellation warnings) -->
                <div v-if="!warning.is_cancellation && warning.phenomena.length > 0" class="mt-4">
                  <p class="text-sm text-muted-foreground mb-2">Fenomena Cuaca:</p>
                  <div class="flex flex-wrap gap-2">
                    <span
                      v-for="phenomenon in warning.phenomena"
                      :key="phenomenon.type"
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                    >
                      {{ phenomenon.name }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>
