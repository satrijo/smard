<script setup>
import { computed, reactive, watch, onMounted, onUnmounted } from 'vue';
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
});

// 2. Mendefinisikan event yang akan di-emit ke komponen induk.
// Ini menggantikan callback function props (onClose, onConfirm) dari React.
const emit = defineEmits(['close', 'confirm']);

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
  const fullMessage = `${cancellationMessage.value}\n\n${cancellationTranslation.value}`;
  navigator.clipboard.writeText(fullMessage);
  showToast("Berhasil Disalin", "Pesan pembatalan lengkap telah disalin ke clipboard.");
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
  if (props.warningRecord) {
    // 4. Meng-emit event 'confirm' dengan membawa ID record.
    emit('confirm', props.warningRecord.id);
  }
};

const handleClose = () => {
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
  } else {
    document.removeEventListener('keydown', handleKeydown);
  }
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
          <!-- Informasi forecaster -->
          <div v-if="warningRecord.forecaster_name" class="mt-2 pt-2 border-t border-border/50">
            <p class="text-sm text-blue-600">
              <span class="font-medium">Forecaster:</span> {{ warningRecord.forecaster_name }} ({{ warningRecord.forecaster_nip }})
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
              <div class="bg-muted p-3 rounded-lg font-mono text-sm border">
                {{ cancellationMessage }}
              </div>
              <button @click="copyMessage" class="absolute top-2 right-2 p-2 rounded-md hover:bg-background/80">
                <Copy class="h-4 w-4" />
              </button>
            </div>
            
            <!-- Terjemahan Bahasa Indonesia -->
            <div class="bg-blue-50 border border-blue-200 p-3 rounded-lg">
              <h5 class="font-medium text-blue-900 mb-2">Terjemahan dalam Bahasa Indonesia:</h5>
              <p class="text-sm text-blue-800 leading-relaxed">
                {{ cancellationTranslation }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="flex justify-end gap-3 p-6 border-t">
        <!-- 8. Event handler di Vue menggunakan `@click`. -->
        <button @click="handleClose" class="px-4 py-2 border rounded-md text-sm font-medium hover:bg-accent">
          Tutup
        </button>
        <button @click="handleConfirm" class="px-4 py-2 bg-destructive text-destructive-foreground rounded-md text-sm font-medium hover:bg-destructive/90">
          Ya, Batalkan Peringatan
        </button>
      </div>
    </div>
  </div>
</template>