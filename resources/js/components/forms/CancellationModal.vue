<script setup>
import { computed, reactive, watch, onMounted, onUnmounted, ref } from 'vue';
import { AlertTriangle, Copy } from 'lucide-vue-next';

// Import komponen UI yang tersedia
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";

// 1. Mendefinisikan props yang diterima dari komponen induk.
const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true,
  },
  warningRecord: {
    type: Object, // Bisa null jika tidak ada record yang dipilih
    default: null,
  },
  error: {
    type: String,
    default: null,
  },
});

// 2. Mendefinisikan event yang akan di-emit ke komponen induk.
// Ini menggantikan callback function props (onClose, onConfirm) dari React.
const emit = defineEmits(['close', 'confirm']);

// State untuk forecaster yang sedang aktif
const activeForecaster = ref(null);

// State untuk terjemahan yang dapat diedit
const editableCancellationTranslation = ref('');
const editableCancellationMessage = ref('');

// State untuk loading/processing
const isSavingCancellationMessage = ref(false);
const isSavingCancellationTranslation = ref(false);
const isConfirmingCancellation = ref(false);

// State untuk notifikasi toast sederhana
const toast = reactive({
  show: false,
  title: '',
  description: '',
  timeoutId: null,
});

const showToast = (title, description) => {
  if (toast.timeoutId) clearTimeout(toast.timeoutId);
  toast.title = title;
  toast.description = description;
  toast.show = true;
  toast.timeoutId = setTimeout(() => {
    toast.show = false;
  }, 3000);
};

// Fungsi untuk mengambil forecaster aktif dari localStorage
const getActiveForecaster = () => {
  const savedForecaster = localStorage.getItem('selectedForecaster');
  if (savedForecaster) {
    activeForecaster.value = JSON.parse(savedForecaster);
  }
};

// 3. Menggunakan `computed` untuk nilai yang diturunkan dari props.
// `cancellationMessage` akan otomatis diperbarui jika `props.warningRecord` berubah.
const cancellationMessage = computed(() => {
  if (!props.warningRecord) {
    return '';
  }

  // Nomor urut pembatalan (akan diisi setelah konfirmasi)
  const cancellationNum = props.warningRecord.cancellation_sequence_number || '?';
  const warningNum = props.warningRecord.sequence_number;
  
  // Format waktu validitas yang akan dibatalkan
  const startTime = new Date(props.warningRecord.startTime);
  const endTime = new Date(props.warningRecord.valid_to);
  
  const startDay = String(startTime.getUTCDate()).padStart(2, '0');
  const startHours = String(startTime.getUTCHours()).padStart(2, '0');
  const startMinutes = String(startTime.getUTCMinutes()).padStart(2, '0');
  
  const endDay = String(endTime.getUTCDate()).padStart(2, '0');
  const endHours = String(endTime.getUTCHours()).padStart(2, '0');
  const endMinutes = String(endTime.getUTCMinutes()).padStart(2, '0');
  
  const validityPeriod = `${startDay}${startHours}${startMinutes}/${endDay}${endHours}${endMinutes}`;
  
  // Format waktu validitas pembatalan (dari sekarang sampai sisa waktu validitas)
  const now = new Date();
  const cancellationStartDay = String(now.getUTCDate()).padStart(2, '0');
  const cancellationStartHours = String(now.getUTCHours()).padStart(2, '0');
  const cancellationStartMinutes = String(now.getUTCMinutes()).padStart(2, '0');
  
  // Gunakan waktu berakhir dari peringatan yang dibatalkan
  const cancellationEndDay = String(endTime.getUTCDate()).padStart(2, '0');
  const cancellationEndHours = String(endTime.getUTCHours()).padStart(2, '0');
  const cancellationEndMinutes = String(endTime.getUTCMinutes()).padStart(2, '0');
  
  const cancellationValidity = `${cancellationStartDay}${cancellationStartHours}${cancellationStartMinutes}/${cancellationEndDay}${cancellationEndHours}${cancellationEndMinutes}`;
  
  return `WAHL AD WRNG ${cancellationNum} VALID ${cancellationValidity} CNL AD WRNG ${warningNum} ${validityPeriod}=`;
});

// Terjemahan dalam Bahasa Indonesia
const cancellationTranslation = computed(() => {
  if (!props.warningRecord) {
    return '';
  }

  const cancellationNum = props.warningRecord.cancellation_sequence_number || '?';
  const warningNum = props.warningRecord.sequence_number;
  const startTime = new Date(props.warningRecord.startTime);
  const endTime = new Date(props.warningRecord.valid_to);
  
  const startDay = startTime.getUTCDate();
  const startMonth = startTime.getUTCMonth();
  const startHours = startTime.getUTCHours();
  const startMinutes = startTime.getUTCMinutes();
  
  const endHours = endTime.getUTCHours();
  const endMinutes = endTime.getUTCMinutes();
  
  const monthNames = [
    'JANUARI', 'FEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI',
    'JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOVEMBER', 'DESEMBER'
  ];
  
  const startTimeFormatted = `${String(startHours).padStart(2, '0')}.${String(startMinutes).padStart(2, '0')}`;
  const endTimeFormatted = `${String(endHours).padStart(2, '0')}.${String(endMinutes).padStart(2, '0')}`;
  
  // Format waktu pembatalan (dari sekarang sampai sisa waktu validitas)
  const now = new Date();
  const cancellationStartDay = now.getUTCDate();
  const cancellationStartMonth = now.getUTCMonth();
  const cancellationStartHours = now.getUTCHours();
  const cancellationStartMinutes = now.getUTCMinutes();
  
  // Gunakan waktu berakhir dari peringatan yang dibatalkan
  const cancellationEndHours = endTime.getUTCHours();
  const cancellationEndMinutes = endTime.getUTCMinutes();
  
  const cancellationStartTimeFormatted = `${String(cancellationStartHours).padStart(2, '0')}.${String(cancellationStartMinutes).padStart(2, '0')}`;
  const cancellationEndTimeFormatted = `${String(cancellationEndHours).padStart(2, '0')}.${String(cancellationEndMinutes).padStart(2, '0')}`;
  
  return `AERODROME WARNING STASIUN METEOROLOGI TUNGGUL WULUNG NOMOR ${cancellationNum}, BERLAKU TANGGAL ${cancellationStartDay} ${monthNames[cancellationStartMonth]} 2025, ANTARA PUKUL ${cancellationStartTimeFormatted} - ${cancellationEndTimeFormatted} UTC, BAHWA PERINGATAN DINI CUACA NOMOR ${warningNum} (TANGGAL ${startDay} ${monthNames[startMonth]} 2025, ANTARA PUKUL ${startTimeFormatted} - ${endTimeFormatted} UTC) TELAH BERAKHIR / DIBATALKAN.`;
});

// --- METHODS ---

const copyMessage = () => {
  navigator.clipboard.writeText(editableCancellationMessage.value);
  showToast("Pesan Disalin", "Pesan pembatalan telah disalin ke clipboard.");
};

const copyTranslation = () => {
  navigator.clipboard.writeText(editableCancellationTranslation.value);
  showToast("Terjemahan Disalin", "Terjemahan pembatalan telah disalin ke clipboard.");
};

const saveEditedCancellationTranslation = () => {
  if (isSavingCancellationTranslation.value) return; // Prevent double click
  
  isSavingCancellationTranslation.value = true;
  
  // Here you can add logic to save the edited translation
  // For now, we'll just show a toast notification
  setTimeout(() => {
    showToast("Terjemahan Disimpan", "Terjemahan pembatalan yang diedit telah disimpan.");
    isSavingCancellationTranslation.value = false;
  }, 500);
};

const saveEditedCancellationMessage = () => {
  if (isSavingCancellationMessage.value) return; // Prevent double click
  
  isSavingCancellationMessage.value = true;
  
  // Here you can add logic to save the edited message
  // For now, we'll just show a toast notification
  setTimeout(() => {
    showToast("Pesan Disimpan", "Pesan pembatalan yang diedit telah disimpan.");
    isSavingCancellationMessage.value = false;
  }, 500);
};

// Fungsi helper ini bisa tetap sama.
const formatPhenomenonName = (phenomenon) => {
  // ... (Logika fungsi ini sama persis dengan di React)
  const { type, details } = phenomenon;
  switch (type) {
    case "TS": return "Badai Guntur (TS)";
    case "GR": return "Hujan Es (GR)";
    case "HVY_RA": return "Hujan Lebat (HVY RA)";
    // Tambahkan case lainnya sesuai kebutuhan
    default: return type;
  }
};

const handleConfirm = () => {
  if (isConfirmingCancellation.value) return; // Prevent double click
  
  if (props.warningRecord) {
    isConfirmingCancellation.value = true;
    
    // 4. Meng-emit event 'confirm' dengan membawa ID record, pesan dan terjemahan yang diedit.
    emit('confirm', {
      id: props.warningRecord.id,
      editedMessage: editableCancellationMessage.value,
      editedTranslation: editableCancellationTranslation.value
    });
    
    // Reset loading state after a timeout in case the parent doesn't handle it
    setTimeout(() => {
      isConfirmingCancellation.value = false;
    }, 10000); // 10 seconds timeout
  }
};

const handleClose = () => {
  // Reset loading states when closing
  isSavingCancellationMessage.value = false;
  isSavingCancellationTranslation.value = false;
  isConfirmingCancellation.value = false;
  
  // 5. Meng-emit event 'close' untuk memberi tahu induk agar menutup modal.
  emit('close');
};

// Keyboard event handler untuk menutup modal dengan Escape
const handleKeydown = (event) => {
  if (event.key === 'Escape') {
    handleClose();
  }
};

// Watch untuk menambahkan/menghapus event listener
watch(() => props.isOpen, (isOpen) => {
  if (isOpen) {
    document.addEventListener('keydown', handleKeydown);
    getActiveForecaster(); // Ambil forecaster aktif saat modal dibuka
  } else {
    document.removeEventListener('keydown', handleKeydown);
  }
});

// Watch untuk mengupdate editable translation ketika cancellationTranslation berubah
watch(() => cancellationTranslation.value, (newTranslation) => {
  editableCancellationTranslation.value = newTranslation;
});

// Watch untuk mengupdate editable message ketika cancellationMessage berubah
watch(() => cancellationMessage.value, (newMessage) => {
  editableCancellationMessage.value = newMessage;
});

// Reset loading states when modal closes
watch(() => props.isOpen, (isOpen) => {
  if (!isOpen) {
    isSavingCancellationMessage.value = false;
    isSavingCancellationTranslation.value = false;
    isConfirmingCancellation.value = false;
  }
});

// Watch for error prop to reset loading states
watch(() => props.error, (error) => {
  if (error) {
    isConfirmingCancellation.value = false;
  }
});

onMounted(() => {
  getActiveForecaster(); // Ambil forecaster aktif saat komponen dimount
});

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown);
});

</script>

<template>
  <!-- Implementasi Toast Sederhana -->
  <div v-if="toast.show" class="fixed top-5 right-5 bg-gray-900 text-white p-4 rounded-lg shadow-lg z-[100] transition-opacity duration-300" role="alert">
    <p class="font-bold">{{ toast.title }}</p>
    <p class="text-sm">{{ toast.description }}</p>
  </div>
  
  <!-- 6. Kontrol visibilitas modal menggunakan v-if dan prop `isOpen`. -->
  <!-- `v-if` memastikan modal dan isinya tidak ada di DOM saat tidak aktif. -->
  <div v-if="isOpen && warningRecord" class="fixed inset-0 bg-black/60 z-[9999] flex items-center justify-center" @click="handleClose">
    <!-- Saya menggunakan elemen HTML standar untuk meniru komponen Dialog. -->
    <!-- Ganti dengan komponen Dialog kustom Anda. -->
    <div class="bg-card text-card-foreground rounded-lg shadow-lg max-w-2xl w-full mx-4" @click.stop>
      <!-- Header -->
      <div class="p-6 border-b">
        <h3 class="flex items-center gap-2 text-lg font-semibold text-destructive">
          <AlertTriangle class="h-5 w-5" />
          Konfirmasi Pembatalan Aerodrome Warning
        </h3>
        <p class="text-sm text-muted-foreground mt-1">
          Pastikan semua fenomena telah mereda sebelum membatalkan peringatan ini.
        </p>
      </div>

      <!-- Content -->
      <div class="p-6 space-y-6">
        <div class="bg-muted/50 p-4 rounded-lg">
          <h4 class="font-semibold mb-2">Detail Peringatan</h4>
          <p class="text-sm">
            Anda akan membatalkan <strong>Peringatan No: {{ warningRecord.warningNumber }}</strong> untuk
            <strong>{{ warningRecord.airportCode }}</strong>.
          </p>
          <!-- Informasi forecaster yang membuat peringatan asli -->
          <div v-if="warningRecord.forecaster_name" class="mt-2 pt-2 border-t border-border/50">
            <p class="text-sm text-muted-foreground">
              <span class="font-medium">Dibuat oleh:</span> {{ warningRecord.forecaster_name }} ({{ warningRecord.forecaster_nip }})
            </p>
          </div>
          <!-- Informasi forecaster yang sedang melakukan pembatalan -->
          <div v-if="activeForecaster" class="mt-2 pt-2 border-t border-border/50">
            <p class="text-sm text-blue-600">
              <span class="font-medium">Pembatalan oleh:</span> {{ activeForecaster.name }} ({{ activeForecaster.nip }})
            </p>
          </div>
          <!-- Peringatan jika forecaster belum dipilih -->
          <div v-else class="mt-2 pt-2 border-t border-border/50">
            <p class="text-sm text-red-600">
              <span class="font-medium">⚠️ Peringatan:</span> Forecaster belum dipilih. Silakan pilih forecaster terlebih dahulu.
            </p>
          </div>
        </div>

        <div>
          <h4 class="font-semibold mb-3">Peringatan ini berisi fenomena:</h4>
          <div class="flex flex-wrap gap-2">
            <!-- 7. Menggunakan v-for untuk me-render list. -->
            <span v-for="(phenomenon, index) in warningRecord.phenomena" :key="index"
                  class="px-2.5 py-0.5 text-xs font-semibold bg-secondary text-secondary-foreground rounded-full">
              {{ formatPhenomenonName(phenomenon) }}
            </span>
          </div>
        </div>
        
        <!-- Error Message -->
        <div v-if="error" class="bg-red-50 border border-red-200 p-4 rounded-lg flex items-start gap-3">
          <AlertTriangle class="h-5 w-5 text-red-600 mt-0.5 flex-shrink-0" />
          <div>
            <h4 class="font-semibold mb-1 text-red-800">ERROR</h4>
            <p class="text-sm text-red-700">
              {{ error }}
            </p>
          </div>
        </div>

        <div class="bg-amber-50 border border-amber-200 p-4 rounded-lg flex items-start gap-3">
          <AlertTriangle class="h-5 w-5 text-amber-600 mt-0.5 flex-shrink-0" />
          <div>
            <h4 class="font-semibold mb-1">PERHATIAN</h4>
            <p class="text-sm text-amber-700">
              Pembatalan berlaku untuk <strong>SEMUA</strong> fenomena di atas. Jika salah satu fenomena masih berlangsung, disarankan untuk membatalkan peringatan ini dan menerbitkan yang baru.
            </p>
          </div>
        </div>

        <div>
          <h4 class="font-semibold mb-2">Pesan Pembatalan yang Akan Diterbitkan:</h4>
          <div class="space-y-3">
            <!-- Sandi Pembatalan -->
            <div class="relative">
              <div class="flex items-center justify-between mb-2">
                <h5 class="font-medium">Pembatalan:</h5>
                <button 
                  @click="saveEditedCancellationMessage" 
                  :disabled="isSavingCancellationMessage"
                  class="flex items-center gap-1 px-2 py-1 bg-gray-600 text-white rounded text-xs hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <svg v-if="isSavingCancellationMessage" class="animate-spin h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  <svg v-else class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                  </svg>
                  {{ isSavingCancellationMessage ? 'Menyimpan...' : 'Simpan' }}
                </button>
              </div>
              <textarea 
                v-model="editableCancellationMessage" 
                class="w-full p-3 bg-muted rounded-lg font-mono text-sm border resize-none focus:ring-2 focus:ring-gray-500 focus:border-gray-500"
                rows="2"
                placeholder="Pesan pembatalan akan muncul di sini..."
              ></textarea>
              <button 
                @click="copyMessage" 
                :disabled="isSavingCancellationMessage"
                class="absolute top-2 right-2 p-2 rounded-md hover:bg-background/80 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <Copy class="h-4 w-4" />
              </button>
              <p class="text-xs text-gray-600 mt-1">
                📝 Anda dapat mengedit pesan pembatalan di atas sesuai kebutuhan, kemudian klik "Simpan".
              </p>
            </div>
            
            <!-- Terjemahan Bahasa Indonesia -->
            <div class="bg-blue-50 border border-blue-200 p-3 rounded-lg">
              <div class="flex items-center justify-between mb-2">
                <h5 class="font-medium text-blue-900">Terjemahan:</h5>
                <button 
                  @click="saveEditedCancellationTranslation" 
                  :disabled="isSavingCancellationTranslation"
                  class="flex items-center gap-1 px-2 py-1 bg-blue-600 text-white rounded text-xs hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <svg v-if="isSavingCancellationTranslation" class="animate-spin h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  <svg v-else class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                  </svg>
                  {{ isSavingCancellationTranslation ? 'Menyimpan...' : 'Simpan' }}
                </button>
              </div>
              <div class="relative">
                <textarea 
                  v-model="editableCancellationTranslation" 
                  class="w-full p-3 border border-blue-200 rounded-md bg-white text-sm text-blue-800 leading-relaxed resize-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  rows="3"
                  placeholder="Terjemahan akan muncul di sini..."
                ></textarea>
                <button 
                  @click="copyTranslation" 
                  :disabled="isSavingCancellationTranslation"
                  class="absolute top-2 right-2 p-2 rounded-md hover:bg-blue-100 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <Copy class="h-4 w-4" />
                </button>
              </div>
              <p class="text-xs text-blue-600 mt-1">
                📝 Anda dapat mengedit terjemahan di atas sesuai kebutuhan, kemudian klik "Simpan".
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="flex justify-end gap-3 p-6 border-t">
        <!-- 8. Event handler di Vue menggunakan `@click`. -->
        <button 
          @click="handleClose" 
          :disabled="isConfirmingCancellation"
          class="px-4 py-2 border rounded-md text-sm font-medium hover:bg-accent disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Tutup
        </button>
        <button 
          @click="handleConfirm" 
          :disabled="isConfirmingCancellation"
          class="px-4 py-2 bg-destructive text-destructive-foreground rounded-md text-sm font-medium hover:bg-destructive/90 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
        >
          <svg v-if="isConfirmingCancellation" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          {{ isConfirmingCancellation ? 'Membatalkan...' : 'Ya, Batalkan Peringatan' }}
        </button>
      </div>
    </div>
  </div>
</template>