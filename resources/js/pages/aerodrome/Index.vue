<script setup>
import { ref, reactive, onMounted, onUnmounted } from 'vue';
import { Link, router, Head } from '@inertiajs/vue3';
import Header from '@/components/layout/Header.vue';
import AerodromeWarningForm from '@/components/forms/AerodromeWarningForm.vue';
import CancellationModal from '@/components/forms/CancellationModal.vue';
import { Archive, AlertTriangle, Copy, X, RefreshCw, Pause, Play } from 'lucide-vue-next';

const props = defineProps({
  airport: {
    type: Object,
    required: false,
    default: () => ({ name: 'Unknown Airport', icao_code: 'UNK' })
  },
  selectedPegawai: {
    type: Object,
    required: false,
    default: () => ({ nama_lengkap: 'Unknown User', id: null })
  },
  phenomena: {
    type: Array,
    required: false,
    default: () => []
  },
  activeWarnings: {
    type: Array,
    required: false,
    default: () => []
  },
  flash: {
    type: Object,
    default: () => ({})
  }
});

// State untuk data active warnings
const activeWarningsData = ref(props.activeWarnings);

// State untuk statistics
const warningStats = ref({
  active: 0,
  active_and_valid: 0,
  expired: 0,
  cancelled: 0,
  total: 0
});

// State untuk real-time updates
const realTimeState = reactive({
  isPolling: false,
  pollingInterval: null,
  lastUpdate: null,
  autoRefresh: true,
  refreshInterval: 30000,
  nextRefreshCountdown: 30,
  connectionStatus: 'online',
  isLoading: false,
});

// State untuk modal pembatalan
const cancellationModal = reactive({
  isOpen: false,
  warningRecord: null,
});

// State untuk toast
const toast = reactive({
  show: false,
  title: '',
  description: '',
  type: 'info',
  timeoutId: null,
});

// Fungsi untuk menampilkan toast
const showToast = (title, description, type = 'info') => {
  if (toast.timeoutId) clearTimeout(toast.timeoutId);
  toast.title = title;
  toast.description = description;
  toast.type = type;
  toast.show = true;
  toast.timeoutId = setTimeout(() => {
    toast.show = false;
  }, 4000);
};

// Fungsi untuk mengambil data warnings
const fetchActiveWarnings = async () => {
  try {
    const response = await fetch('/aerodrome/warnings');
    const result = await response.json();
    
    if (result.success) {
      activeWarningsData.value = result.data;
    }
  } catch (error) {
    console.error('Error fetching active warnings:', error);
  }
};

// Fungsi untuk mengambil statistics
const fetchWarningStats = async () => {
  try {
    const response = await fetch('/aerodrome/warnings/statistics');
    const result = await response.json();
    
    if (result.success) {
      warningStats.value = result.data;
    }
  } catch (error) {
    console.error('Error fetching warning statistics:', error);
  }
};

// Fungsi untuk refresh data
const refreshData = async () => {
  try {
    realTimeState.isLoading = true;
    
    await Promise.all([
      fetchActiveWarnings(),
      fetchWarningStats()
    ]);
    
    realTimeState.lastUpdate = new Date();
    realTimeState.connectionStatus = 'online';
    realTimeState.isLoading = false;
    
  } catch (error) {
    console.error('Error refreshing data:', error);
    realTimeState.connectionStatus = 'error';
    realTimeState.isLoading = false;
  }
};

// Fungsi untuk memulai auto-refresh
const startAutoRefresh = () => {
  if (realTimeState.isPolling) return;
  
  realTimeState.isPolling = true;
  realTimeState.nextRefreshCountdown = 30;
  
  // Timer untuk countdown
  const countdownInterval = setInterval(() => {
    if (realTimeState.autoRefresh && realTimeState.isPolling) {
      realTimeState.nextRefreshCountdown--;
      if (realTimeState.nextRefreshCountdown <= 0) {
        realTimeState.nextRefreshCountdown = 30;
      }
    } else {
      clearInterval(countdownInterval);
    }
  }, 1000);
  
  // Timer untuk refresh data
  realTimeState.pollingInterval = setInterval(() => {
    if (realTimeState.autoRefresh) {
      refreshData();
    }
  }, realTimeState.refreshInterval);
  
  console.log('🔄 Auto-refresh started');
};

// Fungsi untuk menghentikan auto-refresh
const stopAutoRefresh = () => {
  if (realTimeState.pollingInterval) {
    clearInterval(realTimeState.pollingInterval);
    realTimeState.pollingInterval = null;
  }
  realTimeState.isPolling = false;
  console.log('⏹️ Auto-refresh stopped');
};

// Fungsi untuk toggle auto-refresh
const toggleAutoRefresh = () => {
  realTimeState.autoRefresh = !realTimeState.autoRefresh;
  
  if (realTimeState.autoRefresh) {
    showToast("Auto-refresh Diaktifkan", "Data akan diperbarui setiap 30 detik", 'success');
  } else {
    showToast("Auto-refresh Dinonaktifkan", "Data tidak akan diperbarui otomatis", 'warning');
  }
};

// Fungsi untuk refresh manual
const manualRefresh = async () => {
  showToast("Memperbarui Data", "Mengambil data terbaru...", 'info');
  await refreshData();
  showToast("Data Diperbarui", "Data telah diperbarui", 'success');
};

// Fungsi untuk copy preview message lengkap
const copyMessage = (record) => {
  // Ambil preview message dari record, jika tidak ada gunakan sandi sebagai fallback
  const messageToCopy = record.preview_message || `AD WRNG ${record.sequence_number}`;
  navigator.clipboard.writeText(messageToCopy).then(() => {
    showToast("Pesan Disalin", "Preview message lengkap telah disalin ke clipboard");
  }).catch(err => {
    console.error('Gagal menyalin:', err);
    showToast("Gagal Menyalin", "Tidak dapat menyalin pesan ke clipboard.");
  });
};

// Fungsi untuk membuka modal pembatalan
const openCancellationModal = async (record) => {
  try {
    // Ambil sequence number pembatalan dari backend
    const response = await fetch(`/aerodrome/warnings/next-sequence`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    });

    const result = await response.json();
    
    const modalRecord = {
      ...record,
      warningNumber: record.warningNumber || `AD WRNG ${record.sequence_number}`,
      validityEnd: record.validityEnd || '151200',
      cancellation_sequence_number: result.success ? result.sequence_number : '?'
    };
    
    cancellationModal.isOpen = true;
    cancellationModal.warningRecord = modalRecord;
  } catch (error) {
    console.error('Error getting cancellation sequence number:', error);
    // Fallback jika gagal mengambil sequence number
    const modalRecord = {
      ...record,
      warningNumber: record.warningNumber || `AD WRNG ${record.sequence_number}`,
      validityEnd: record.validityEnd || '151200',
      cancellation_sequence_number: '?'
    };
    
    cancellationModal.isOpen = true;
    cancellationModal.warningRecord = modalRecord;
  }
};

// Fungsi untuk menutup modal pembatalan
const closeCancellationModal = () => {
  cancellationModal.isOpen = false;
  cancellationModal.warningRecord = null;
};

// Fungsi untuk konfirmasi pembatalan
const confirmCancellation = async (id) => {
  try {
    showToast("Memproses Pembatalan", "Sedang memproses pembatalan peringatan...", 'info');
    
    const response = await fetch(`/aerodrome/warnings/${id}/cancel`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin'
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const result = await response.json();

    if (result.success) {
      showToast("Peringatan Dibatalkan", "Peringatan telah berhasil dibatalkan.", 'success');
      closeCancellationModal();
      await refreshData();
    } else {
      showToast("Gagal", result.message || "Terjadi kesalahan saat membatalkan peringatan.", 'error');
    }
  } catch (error) {
    console.error('Error cancelling warning:', error);
    showToast("Gagal", "Terjadi kesalahan saat membatalkan peringatan. Silakan coba lagi.", 'error');
  }
};

// Event listener untuk refresh data ketika form berhasil disubmit
const handleWarningPublished = () => {
  refreshData();
};

// Format UTC time to Indonesian format (DD/MM/YYYY HH:mm UTC)
const formatUTCTime = (dateTime) => {
  // Handle null, undefined, or invalid date
  if (!dateTime || dateTime === 'null' || dateTime === 'undefined') {
    return 'N/A';
  }
  
  const date = new Date(dateTime);
  
  // Check if date is valid
  if (isNaN(date.getTime())) {
    return 'Invalid Date';
  }
  
  const day = date.getUTCDate().toString().padStart(2, '0');
  const month = (date.getUTCMonth() + 1).toString().padStart(2, '0');
  const year = date.getUTCFullYear();
  const hours = date.getUTCHours().toString().padStart(2, '0');
  const minutes = date.getUTCMinutes().toString().padStart(2, '0');
  return `${day}/${month}/${year} ${hours}:${minutes} UTC`;
};

// Check if warning is expiring soon
const isWarningExpiringSoon = (validTo) => {
  const endTime = new Date(validTo);
  const now = new Date();
  const oneHourFromNow = new Date(now.getTime() + 60 * 60 * 1000);
  return endTime <= oneHourFromNow;
};

// Load data saat komponen dimount
onMounted(() => {
  refreshData();
  startAutoRefresh();
});

// Cleanup saat komponen unmount
onUnmounted(() => {
  stopAutoRefresh();
});
</script>

<template>
  <!-- Toast Notification -->
  <div v-if="toast.show" 
       class="fixed top-5 right-5 p-4 rounded-lg shadow-lg z-50 transition-opacity duration-300 max-w-sm border-l-4" 
       :class="{
         'bg-green-50 border-green-500 text-green-800': toast.type === 'success',
         'bg-blue-50 border-blue-500 text-blue-800': toast.type === 'info',
         'bg-yellow-50 border-yellow-500 text-yellow-800': toast.type === 'warning',
         'bg-red-50 border-red-500 text-red-800': toast.type === 'error'
       }"
       role="alert">
    <div class="flex items-start gap-3">
      <div class="flex-shrink-0">
        <div class="w-2 h-2 rounded-full animate-pulse" 
             :class="{
               'bg-green-500': toast.type === 'success',
               'bg-blue-500': toast.type === 'info',
               'bg-yellow-500': toast.type === 'warning',
               'bg-red-500': toast.type === 'error'
             }"></div>
      </div>
      <div class="flex-1 min-w-0">
        <p class="font-bold text-sm">{{ toast.title }}</p>
        <p class="text-xs opacity-80 mt-1">{{ toast.description }}</p>
        <div class="text-xs opacity-60 mt-2">
          {{ new Date().toLocaleTimeString('id-ID') }}
        </div>
      </div>
    </div>
  </div>

  <Head :title="$page.props.metaTitle || 'Dashboard - Aerodrome Warning System'" />
  <div class="min-h-screen bg-background">
    <Header />

    <main class="container mx-auto px-4 sm:px-6 py-8">
      <!-- Layout 2 Kolom -->
      <div class="grid grid-cols-12 gap-6">
        <!-- Kolom Kiri - Form Pembuatan Peringatan -->
        <div class="col-span-12 lg:col-span-8">
          <AerodromeWarningForm :phenomena="phenomena" :flash="flash" @warning-published="handleWarningPublished" />
        </div>

        <!-- Kolom Kanan - Daftar Peringatan Aktif -->
        <div class="col-span-12 lg:col-span-4">
          <!-- Statistics Card -->
          <div class="mb-4 border border-border rounded-lg bg-card text-card-foreground shadow-sm">
            <div class="p-4">
              <div class="flex items-center justify-between mb-3">
                <h4 class="text-sm font-medium text-muted-foreground">Statistik Peringatan</h4>
                <!-- Real-time Controls -->
                <div class="flex items-center gap-2">
                  <button
                    @click="manualRefresh"
                    class="p-1.5 rounded-md hover:bg-muted transition-colors"
                    :class="{ 'animate-spin': realTimeState.isLoading }"
                    :disabled="realTimeState.isLoading"
                    title="Refresh Manual"
                  >
                    <RefreshCw class="h-3 w-3" />
                  </button>
                  <button
                    @click="toggleAutoRefresh"
                    class="p-1.5 rounded-md hover:bg-muted transition-colors"
                    :class="{ 'text-green-600': realTimeState.autoRefresh, 'text-gray-400': !realTimeState.autoRefresh }"
                    :title="realTimeState.autoRefresh ? 'Auto-refresh Aktif' : 'Auto-refresh Nonaktif'"
                  >
                    <Play v-if="realTimeState.autoRefresh" class="h-3 w-3" />
                    <Pause v-else class="h-3 w-3" />
                  </button>
                </div>
              </div>
              <div class="grid grid-cols-2 gap-3 text-xs">
                <div class="text-center p-2 bg-green-50 rounded">
                  <div class="font-semibold text-green-700">{{ warningStats.active_and_valid }}</div>
                  <div class="text-green-600">Aktif & Valid</div>
                </div>
                <div class="text-center p-2 bg-yellow-50 rounded">
                  <div class="font-semibold text-yellow-700">{{ warningStats.expired }}</div>
                  <div class="text-yellow-600">Expired</div>
                </div>
                <div class="text-center p-2 bg-red-50 rounded">
                  <div class="font-semibold text-red-700">{{ warningStats.cancelled }}</div>
                  <div class="text-red-600">Dibatalkan</div>
                </div>
                <div class="text-center p-2 bg-gray-50 rounded">
                  <div class="font-semibold text-gray-700">{{ warningStats.total }}</div>
                  <div class="text-gray-600">Total</div>
                </div>
              </div>
                             <!-- Last Update Info -->
               <div v-if="realTimeState.lastUpdate" class="mt-3 pt-2 border-t border-border">
                 <div class="flex items-center justify-between text-xs text-muted-foreground">
                   <span>Update terakhir:</span>
                   <span>{{ new Date(realTimeState.lastUpdate).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', timeZone: 'UTC' }) }} UTC</span>
                 </div>
                <div class="flex items-center gap-1 text-xs text-muted-foreground">
                  <div class="w-2 h-2 rounded-full" :class="{
                    'bg-green-500 animate-pulse': realTimeState.autoRefresh,
                    'bg-gray-400': !realTimeState.autoRefresh
                  }"></div>
                  <span>{{ realTimeState.autoRefresh ? 'Auto-refresh aktif' : 'Auto-refresh nonaktif' }}</span>
                </div>
                <div v-if="realTimeState.autoRefresh && realTimeState.isPolling" class="flex items-center gap-1 text-xs text-blue-600">
                  <span>Refresh dalam:</span>
                  <span class="font-mono">{{ realTimeState.nextRefreshCountdown }}s</span>
                </div>
                <div class="flex items-center gap-1 text-xs">
                  <div class="w-2 h-2 rounded-full" :class="{
                    'bg-green-500': realTimeState.connectionStatus === 'online',
                    'bg-red-500': realTimeState.connectionStatus === 'error',
                    'bg-yellow-500': realTimeState.connectionStatus === 'offline'
                  }"></div>
                  <span :class="{
                    'text-green-600': realTimeState.connectionStatus === 'online',
                    'text-red-600': realTimeState.connectionStatus === 'error',
                    'text-yellow-600': realTimeState.connectionStatus === 'offline'
                  }">
                    {{ realTimeState.connectionStatus === 'online' ? 'Terhubung' : 
                       realTimeState.connectionStatus === 'error' ? 'Error Koneksi' : 'Offline' }}
                  </span>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Peringatan Aktif Card -->
          <div class="sticky top-6 border border-border rounded-lg bg-card text-card-foreground shadow-sm">
            <div class="p-6 pb-3">
              <div class="flex items-center justify-between">
                <h3 class="flex items-center gap-2 text-yellow-500 text-lg font-semibold tracking-tight">
                  <AlertTriangle class="h-5 w-5" />
                  Peringatan Aktif & Valid
                </h3>
                <!-- Real-time indicator -->
                <div class="flex items-center gap-2">
                  <div v-if="realTimeState.isLoading" class="w-4 h-4 border-2 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
                  <div v-else class="w-2 h-2 rounded-full" :class="{
                    'bg-green-500 animate-pulse': realTimeState.autoRefresh,
                    'bg-gray-400': !realTimeState.autoRefresh
                  }"></div>
                  <span class="text-xs text-muted-foreground">
                    {{ realTimeState.isLoading ? 'Loading...' : (realTimeState.autoRefresh ? 'Live' : 'Manual') }}
                  </span>
                </div>
              </div>
            </div>
            <div class="p-6 pt-0 space-y-3">
              <!-- Empty State -->
              <div v-if="activeWarningsData.length === 0" class="text-center py-4">
                <p class="text-sm text-muted-foreground">
                  Tidak ada peringatan aktif dan valid
                </p>
                <p class="text-xs text-muted-foreground mt-1">
                  Peringatan yang sudah expired atau dibatalkan tidak ditampilkan
                </p>
              </div>
              <!-- Warning List -->
              <div v-else>
                <div
                  v-for="record in activeWarningsData"
                  :key="record.id"
                  class="border border-border rounded-lg p-3 bg-muted/30 mt-2 transition-all duration-500"
                >
                  <div class="flex items-center justify-between mb-2">
                    <div class="font-mono text-sm font-semibold text-foreground">
                      AD WRNG {{ record.sequence_number }} - {{ airport.icao_code }}
                    </div>
                    <div class="text-xs text-muted-foreground">
                      {{ formatUTCTime(record.created_at) }}
                    </div>
                  </div>
                  
                  <!-- Informasi fenomena -->
                  <div class="text-sm text-muted-foreground mb-2">
                    <span class="font-medium">Fenomena:</span>
                    <span v-for="(phenomenon, index) in record.phenomena" :key="index" class="ml-1">
                      {{ phenomenon.name }}{{ index < record.phenomena.length - 1 ? ', ' : '' }}
                    </span>
                  </div>
                  
                                     <!-- Informasi periode validitas -->
                   <div class="text-xs text-muted-foreground mb-2">
                     <span class="font-medium">Validitas:</span>
                     <span class="ml-1">{{ formatUTCTime(record.startTime) }} - {{ formatUTCTime(record.valid_to) }}</span>
                   </div>
                  
                  <!-- Informasi forecaster -->
                  <div class="text-xs text-blue-600 mb-2" v-if="record.forecaster_name">
                    <span class="font-medium">Forecaster:</span> {{ record.forecaster_name }} ({{ record.forecaster_nip }})
                  </div>
                  
                  <div class="flex items-center justify-between mt-3 pt-2 border-t border-border/50">
                    <span class="text-xs" :class="{
                      'text-red-600 font-medium': isWarningExpiringSoon(record.valid_to),
                      'text-muted-foreground': !isWarningExpiringSoon(record.valid_to)
                    }">
                      Berakhir: {{ formatUTCTime(record.valid_to) }}
                      <span v-if="isWarningExpiringSoon(record.valid_to)" class="ml-1">⚠️</span>
                    </span>
                    <div class="flex gap-1">
                                             <button
                         @click="copyMessage(record)"
                         class="h-7 w-7 p-0 inline-flex items-center justify-center rounded-md hover:bg-muted"
                         aria-label="Salin preview message"
                         :title="`Salin preview message lengkap`"
                       >
                         <Copy class="h-3 w-3" />
                       </button>
                      <button
                        @click="openCancellationModal(record)"
                        class="h-7 w-7 p-0 inline-flex items-center justify-center rounded-md text-destructive hover:bg-destructive/10"
                        aria-label="Batalkan peringatan"
                        type="button"
                        :title="`Cancel warning AD WRNG ${record.sequence_number}`"
                      >
                        <X class="h-3 w-3" />
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="flex flex-col gap-2 mx-6 mb-6">
              <Link
                href="/aerodrome/archive"
                as="button"
                class="flex items-center gap-2 px-4 py-2 border border-border rounded-md text-sm font-medium hover:bg-muted"
              >
                <Archive class="h-4 w-4" />
                Arsip Hari Ini
              </Link>
              <Link
                href="/aerodrome/all-warnings"
                as="button"
                class="flex items-center gap-2 px-4 py-2 border border-border rounded-md text-sm font-medium hover:bg-muted"
              >
                <Archive class="h-4 w-4" />
                Semua Peringatan
              </Link>
            </div>
          </div>
          
        </div>
      </div>
    </main>

    <!-- Modal Pembatalan -->
    <CancellationModal
      :isOpen="cancellationModal.isOpen"
      :warningRecord="cancellationModal.warningRecord"
      @close="closeCancellationModal"
      @confirm="confirmCancellation"
    />
  </div>
</template>

