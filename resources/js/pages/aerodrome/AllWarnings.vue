<script setup>
import { ref, computed, watch } from 'vue'
import { Link, router, Head } from '@inertiajs/vue3'
import Header from '@/components/layout/Header.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectItem } from '@/components/ui/select'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { FileDown } from 'lucide-vue-next'

const props = defineProps({
  warnings: Object,
  filters: Object,
  statistics: Object,
  filterOptions: Object
})

// Reactive filters
const filters = ref({
  date_from: props.filters.date_from || '',
  date_to: props.filters.date_to || '',
  status: props.filters.status || 'all',
  phenomenon: props.filters.phenomenon || 'all',
  forecaster: props.filters.forecaster || 'all',
  search: props.filters.search || '',
  per_page: props.filters.per_page || 20
})

// Format UTC time
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

// Get status badge
const getStatusBadge = (warning) => {
  if (warning.is_cancellation) {
    return {
      text: 'Pembatalan',
      variant: 'secondary'
    }
  }
  
  switch (warning.status) {
    case 'CANCELLED':
      return {
        text: 'Dibatalkan',
        variant: 'destructive'
      }
    case 'EXPIRED':
      return {
        text: 'Kedaluwarsa',
        variant: 'outline'
      }
    case 'ACTIVE':
      return {
        text: 'Aktif',
        variant: 'default'
      }
    default:
      return {
        text: warning.status,
        variant: 'secondary'
      }
  }
}

// Apply filters
const applyFilters = () => {
  router.get('/aerodrome/all-warnings', filters.value, {
    preserveState: true,
    preserveScroll: true
  })
}

// Clear filters
const clearFilters = () => {
  filters.value = {
    date_from: '',
    date_to: '',
    status: 'all',
    phenomenon: 'all',
    forecaster: 'all',
    search: '',
    per_page: 20
  }
  applyFilters()
}

// Watch for filter changes
watch(filters, () => {
  applyFilters()
}, { deep: true })

// Pagination
const goToPage = (page) => {
  router.get('/aerodrome/all-warnings', { ...filters.value, page }, {
    preserveState: true,
    preserveScroll: true
  })
}

// Export PDF
const exportPdf = () => {
  const params = new URLSearchParams(filters.value)
  window.open(`/aerodrome/all-warnings/export-pdf?${params.toString()}`, '_blank')
}
</script>

<template>
  <Head :title="$page.props.metaTitle || 'Semua Peringatan - Aerodrome Warning System'" />
  <div class="min-h-screen bg-background">
    <Header />

    <main class="container mx-auto px-4 sm:px-6 py-8">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-3xl font-bold leading-tight text-foreground">Semua Peringatan</h1>
          <p class="text-sm text-muted-foreground mt-1">Riwayat lengkap semua peringatan aerodrome warning</p>
        </div>
        <div class="flex space-x-3">
          <Link href="/aerodrome" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium">
            Dashboard
          </Link>
        </div>
      </div>

      <!-- Statistics -->
      <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <Card>
          <CardContent class="p-4">
            <div class="text-2xl font-bold text-foreground">{{ statistics.total }}</div>
            <div class="text-sm text-muted-foreground">Total</div>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="p-4">
            <div class="text-2xl font-bold text-green-600">{{ statistics.active }}</div>
            <div class="text-sm text-muted-foreground">Aktif</div>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="p-4">
            <div class="text-2xl font-bold text-yellow-600">{{ statistics.expired }}</div>
            <div class="text-sm text-muted-foreground">Expired</div>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="p-4">
            <div class="text-2xl font-bold text-red-600">{{ statistics.cancelled }}</div>
            <div class="text-sm text-muted-foreground">Dibatalkan</div>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="p-4">
            <div class="text-2xl font-bold text-blue-600">{{ statistics.cancellations }}</div>
            <div class="text-sm text-muted-foreground">Pembatalan</div>
          </CardContent>
        </Card>
      </div>

      <!-- Filters -->
      <Card class="mb-6">
        <CardHeader>
          <CardTitle>Filter Pencarian</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <!-- Date From -->
            <div>
              <Label for="date_from">Dari Tanggal</Label>
              <Input
                id="date_from"
                v-model="filters.date_from"
                type="date"
                placeholder="Pilih tanggal"
              />
            </div>

            <!-- Date To -->
            <div>
              <Label for="date_to">Sampai Tanggal</Label>
              <Input
                id="date_to"
                v-model="filters.date_to"
                type="date"
                placeholder="Pilih tanggal"
              />
            </div>

            <!-- Status -->
            <div>
              <Label for="status">Status</Label>
              <Select v-model="filters.status">
                <option value="all">Semua Status</option>
                <option value="ACTIVE">Aktif</option>
                <option value="EXPIRED">Expired</option>
                <option value="CANCELLED">Dibatalkan</option>
              </Select>
            </div>

            <!-- Forecaster -->
            <div>
              <Label for="forecaster">Forecaster</Label>
              <Select v-model="filters.forecaster">
                <option value="all">Semua Forecaster</option>
                <option v-for="forecaster in filterOptions.forecasters" :key="forecaster" :value="forecaster">
                  {{ forecaster }}
                </option>
              </Select>
            </div>

            <!-- Search -->
            <div>
              <Label for="search">Pencarian</Label>
              <Input
                id="search"
                v-model="filters.search"
                placeholder="Cari sequence, sandi, atau terjemahan..."
              />
            </div>

            <!-- Per Page -->
            <div>
              <Label for="per_page">Per Halaman</Label>
              <Select v-model="filters.per_page">
                <option value="10">10</option>
                <option value="20">20</option>
                <option value="50">50</option>
                <option value="100">100</option>
              </Select>
            </div>
          </div>

          <!-- Filter Actions -->
          <div class="flex justify-end mt-4 space-x-2">
            <Button variant="outline" @click="clearFilters">
              Bersihkan Filter
            </Button>
          </div>
        </CardContent>
      </Card>

      <!-- Results Info -->
      <div class="flex justify-between items-center mb-4">
        <p class="text-sm text-muted-foreground">
          Menampilkan {{ warnings.from || 0 }} - {{ warnings.to || 0 }} dari {{ warnings.total || 0 }} hasil
        </p>
        <Button @click="exportPdf" class="flex items-center gap-2">
          <FileDown class="h-4 w-4" />
          Export PDF
        </Button>
      </div>

      <!-- Warnings List -->
      <div v-if="warnings.data.length === 0" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada peringatan ditemukan</h3>
        <p class="mt-1 text-sm text-gray-500">Coba ubah filter pencarian Anda.</p>
      </div>

      <div v-else class="space-y-4">
        <Card v-for="warning in warnings.data" :key="warning.id" class="hover:shadow-md transition-shadow">
          <CardContent class="p-6">
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <!-- Header -->
                <div class="flex items-center space-x-3 mb-4">
                  <h3 class="text-lg font-medium text-foreground">
                    {{ warning.is_cancellation ? 'Pembatalan' : 'Peringatan' }} #{{ warning.sequence_number }}
                  </h3>
                  <Badge :variant="getStatusBadge(warning).variant">
                    {{ getStatusBadge(warning).text }}
                  </Badge>
                </div>

                <!-- Preview Message -->
                <div class="mb-4">
                  <p class="text-sm text-muted-foreground mb-2">Sandi:</p>
                  <div class="bg-muted p-3 rounded-lg font-mono text-sm break-all">
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

                <!-- Details Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
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
                    <Badge v-for="phenomenon in warning.phenomena" :key="phenomenon.type" variant="outline">
                      {{ phenomenon.name }}
                    </Badge>
                  </div>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Pagination -->
      <div v-if="warnings.last_page > 1" class="flex justify-center mt-8">
        <nav class="flex items-center space-x-2">
          <!-- Previous -->
          <Button
            variant="outline"
            :disabled="warnings.current_page === 1"
            @click="goToPage(warnings.current_page - 1)"
          >
            Previous
          </Button>

          <!-- Page Numbers -->
          <template v-for="page in warnings.last_page" :key="page">
            <Button
              v-if="page === 1 || page === warnings.last_page || (page >= warnings.current_page - 2 && page <= warnings.current_page + 2)"
              :variant="page === warnings.current_page ? 'default' : 'outline'"
              @click="goToPage(page)"
            >
              {{ page }}
            </Button>
            <span v-else-if="page === warnings.current_page - 3 || page === warnings.current_page + 3" class="px-2">
              ...
            </span>
          </template>

          <!-- Next -->
          <Button
            variant="outline"
            :disabled="warnings.current_page === warnings.last_page"
            @click="goToPage(warnings.current_page + 1)"
          >
            Next
          </Button>
        </nav>
      </div>
    </main>
  </div>
</template>
