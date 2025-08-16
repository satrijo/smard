<script setup>
import { ref, reactive, computed, watch, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { AlertTriangle, Copy, Plus, X } from 'lucide-vue-next';

// Import komponen UI yang tersedia
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectItem } from "@/components/ui/select";
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group";
import { Separator } from "@/components/ui/separator";

// Define emits untuk komunikasi dengan parent component
const emit = defineEmits(['warning-published']);

// Props untuk flash messages dari Inertia
const props = defineProps({
  flash: {
    type: Object,
    default: () => ({})
  }
});

// --- DATA & STATE ---

// Data konstan, sama seperti di React
const airports = [
  { code: "WAHL", name: "Tunggul Wulung - Cilacap" },
];

// Bandara default yang selalu dipilih
const defaultAirport = airports[0];

const phenomena = [
  { code: "TS", name: "Badai Guntur (TS)" },
  { code: "GR", name: "Hujan Es (GR)" },
  { code: "HVY RA", name: "Hujan Lebat (HVY RA)" },
  { code: "TSRA", name: "Badai Guntur dengan Hujan (TSRA)" },
  { code: "HVY TSRA", name: "Badai Guntur dengan Hujan Lebat (HVY TSRA)" },
  { code: "SFC WSPD", name: "Angin Kencang Tanpa Arah (SFC WSPD)" },
  { code: "SFC WIND", name: "Angin Kencang Dengan Arah (SFC WIND)" },
  { code: "VIS", name: "Jarak Pandang Rendah (VIS)" },
  { code: "SQ", name: "Squall (SQ)" },
  { code: "VA", name: "Abu Vulkanik (VA)" },
  { code: "TOX CHEM", name: "Bahan Kimia Beracun (TOX CHEM)" },
  { code: "TSUNAMI", name: "Tsunami" },
  { code: "CUSTOM", name: "Lainnya (Free Text)" }
];

const visibilityCauses = [
  { code: "HZ", name: "Haze" },
  { code: "BR", name: "Mist" },
  { code: "RA", name: "Hujan" },
  { code: "FG", name: "Fog" },
];

// Data forecaster yang tersedia
const forecasters = [
  { id: 1, name: "Nurfaijin, S.Si., M.Sc.", nip: "197111101997031001" },
  { id: 2, name: "Sawardi, S.T", nip: "197109251992021001" },
  { id: 3, name: "Suharti", nip: "197208181993012001" },
  { id: 4, name: "Hakim Mubasyir, S.Kom", nip: "197812221998031001" },
  { id: 5, name: "Agung Surono, SP", nip: "197912182000031001" },
  { id: 6, name: "Deas Achmad Rivai, S. Kom, M. Si", nip: "198906022010121001" },
  { id: 7, name: "Gaib Prawoto, A.Md", nip: "197507202009111001" },
  { id: 8, name: "Rendi Krisnawan, A.Md", nip: "198612122008121001" },
  { id: 9, name: "Adnan Dendy Mardika, S.Kom", nip: "198908192010121001" },
  { id: 10, name: "Feriharti Nugrohowati, S.T", nip: "198909052010122001" },
  { id: 11, name: "Desi Luqman Az Zahro, S.Kom", nip: "198912272012122001" },
  { id: 12, name: "Nurmaya, S.Tr.Met.", nip: "199101012009112001" },
  { id: 13, name: "Purwanggoro Sukipiadi, S.Kom", nip: "198311222012121001" },
  { id: 14, name: "Khamim Sodik, S.Kom", nip: "198408072012121001" },
  { id: 15, name: "Satriyo Unggul Wicaksono", nip: "199403122013121001" },
];

// Gunakan `reactive` untuk state formulir yang kompleks.
// Ini memungkinkan kita untuk memutasi objek secara langsung.
const formData = reactive({
  airportCode: defaultAirport.code, // Selalu set ke WAHL
  warningNumber: "",
  startTime: "",
  endTime: "",
  phenomena: [],
  source: "",
  intensity: "",
  observationTime: "",
  forecaster: "", // Tambahkan field forecaster
});

// Gunakan `ref` untuk nilai-nilai tunggal.
const selectedPhenomenon = ref("");
const previewMessage = ref("");
const translationMessage = ref(""); // Tambahkan ref untuk terjemahan
const isDraftCreated = ref(false);
const previewTextareaRef = ref(null); // Add ref for textarea
const translationTextareaRef = ref(null); // Add ref for translation textarea

// State untuk forecaster yang dipilih
const selectedForecaster = ref(null);

// Inisialisasi forecaster dari localStorage saat komponen dimount
const initializeForecaster = () => {
  const savedForecaster = localStorage.getItem('selectedForecaster');
  if (savedForecaster) {
    const forecaster = JSON.parse(savedForecaster);
    selectedForecaster.value = forecaster;
    formData.forecaster = forecaster.name;
  }
};

// Inisialisasi bandara saat komponen dimount
const initializeAirport = () => {
  formData.airportCode = defaultAirport.code;
  formData.warningNumber = generateWarningNumber();
};

// Fungsi untuk memilih forecaster
const selectForecaster = (forecaster) => {
  if (forecaster) {
    selectedForecaster.value = forecaster;
    formData.forecaster = forecaster.name;
    localStorage.setItem('selectedForecaster', JSON.stringify(forecaster));
    showToast("Forecaster Dipilih", `${forecaster.name} telah dipilih sebagai forecaster.`);
  } else {
    selectedForecaster.value = null;
    formData.forecaster = "";
    localStorage.removeItem('selectedForecaster');
    showToast("Forecaster Dihapus", "Forecaster telah dihapus, silakan pilih yang baru.");
  }
};

// State untuk notifikasi toast sederhana
const toast = reactive({
  show: false,
  title: '',
  description: '',
  variant: 'default',
  timeoutId: null,
});


// --- METHODS ---

const showToast = (title, description, variant = 'default') => {
  if (toast.timeoutId) clearTimeout(toast.timeoutId);
  toast.title = title;
  toast.description = description;
  toast.variant = variant;
  toast.show = true;
  toast.timeoutId = setTimeout(() => {
    toast.show = false;
  }, 4000);
};

// State untuk menyimpan sequence number terbaru
const currentSequenceNumber = ref(1);
const sequencePollingInterval = ref(null);

// Fungsi untuk mengambil sequence number terbaru dari backend
const fetchNextSequenceNumber = async () => {
  try {
    const response = await fetch('/aerodrome/warnings/next-sequence');
    if (response.ok) {
      const data = await response.json();
      const newSequenceNumber = data.sequence_number;
      
      // Hanya update jika sequence number berubah
      if (currentSequenceNumber.value !== newSequenceNumber) {
        currentSequenceNumber.value = newSequenceNumber;
        formData.warningNumber = `AD WRNG ${currentSequenceNumber.value}`;
        
        // Tampilkan notifikasi jika sequence number berubah (misalnya saat reset harian)
        if (newSequenceNumber === 1 && currentSequenceNumber.value !== 1) {
          showToast("Nomor Reset", "Nomor peringatan telah direset untuk hari baru.", "default");
        }
      }
    }
  } catch (error) {
    console.error('Error fetching sequence number:', error);
    // Fallback ke sequence number 1 jika gagal
    formData.warningNumber = `AD WRNG 1`;
  }
};

// Fungsi untuk memulai polling otomatis
const startSequencePolling = () => {
  // Hentikan polling yang sudah ada jika ada
  if (sequencePollingInterval.value) {
    clearInterval(sequencePollingInterval.value);
  }
  
  // Mulai polling setiap 30 detik
  sequencePollingInterval.value = setInterval(async () => {
    await fetchNextSequenceNumber();
  }, 30000); // Check setiap 30 detik
};

// Fungsi untuk menghentikan polling
const stopSequencePolling = () => {
  if (sequencePollingInterval.value) {
    clearInterval(sequencePollingInterval.value);
    sequencePollingInterval.value = null;
  }
};

const generateWarningNumber = () => {
  return `AD WRNG ${currentSequenceNumber.value}`;
};

// Gunakan `watch` untuk bereaksi terhadap perubahan pada properti tertentu.
// Ini lebih deklaratif daripada menempatkan logika di dalam fungsi update.
watch(() => formData.airportCode, (newCode) => {
  if (newCode) {
    formData.warningNumber = generateWarningNumber();
  } else {
    formData.warningNumber = "";
  }
});

const addPhenomenon = () => {
  if (!selectedPhenomenon.value) {
    showToast("Pilih Fenomena", "Mohon pilih fenomena sebelum menambahkan.", "destructive");
    return;
  }
  const exists = formData.phenomena.some(p => p.type === selectedPhenomenon.value);
  if (exists) {
    showToast("Fenomena Sudah Ada", "Fenomena ini sudah ditambahkan ke daftar.", "destructive");
    return;
  }

  const newPhenomenon = {
    id: Date.now().toString(),
    type: selectedPhenomenon.value,
    // Inisialisasi properti berdasarkan tipe fenomena
    windSpeed: '',
    gustSpeed: '',
    windDirection: '',
    visibility: '',
    visibilityCause: '',
    customDescription: '',
    cycloneName: '',
    toxChemDescription: ''
  };

  formData.phenomena.push(newPhenomenon);
  selectedPhenomenon.value = ""; // Reset select
  showToast("Fenomena Ditambahkan", "Fenomena berhasil ditambahkan ke daftar.");
};

const removePhenomenon = (id) => {
  formData.phenomena = formData.phenomena.filter(p => p.id !== id);
};

const formatDateTime = (dateTimeString) => {
  if (!dateTimeString) return "";
  // Pastikan kita menggunakan UTC
  const date = new Date(dateTimeString + 'Z'); // Tambahkan 'Z' untuk memastikan UTC
  const day = date.getUTCDate().toString().padStart(2, '0');
  const hour = date.getUTCHours().toString().padStart(2, '0');
  const minute = date.getUTCMinutes().toString().padStart(2, '0');
  return `${day}${hour}${minute}`;
};

// Helper untuk mendapatkan waktu UTC saat ini dalam format datetime-local
const getCurrentUTCDatetime = () => {
  const now = new Date();
  const year = now.getUTCFullYear();
  const month = (now.getUTCMonth() + 1).toString().padStart(2, '0');
  const day = now.getUTCDate().toString().padStart(2, '0');
  const hours = now.getUTCHours().toString().padStart(2, '0');
  const minutes = now.getUTCMinutes().toString().padStart(2, '0');
  return `${year}-${month}-${day}T${hours}:${minutes}`;
};

// Helper untuk mengkonversi waktu lokal ke UTC string untuk input datetime-local
const localToUTCDatetimeString = (localDateTimeString) => {
  if (!localDateTimeString) return "";
  const localDate = new Date(localDateTimeString);
  const year = localDate.getUTCFullYear();
  const month = (localDate.getUTCMonth() + 1).toString().padStart(2, '0');
  const day = localDate.getUTCDate().toString().padStart(2, '0');
  const hours = localDate.getUTCHours().toString().padStart(2, '0');
  const minutes = localDate.getUTCMinutes().toString().padStart(2, '0');
  return `${year}-${month}-${day}T${hours}:${minutes}`;
};

const generatePhenomenaString = () => {
  return formData.phenomena.map(p => {
    switch (p.type) {
      case "SFC WSPD": 
        if (p.windSpeed && p.gustSpeed) {
          return `SFC WSPD ${p.windSpeed}KT MAX ${p.gustSpeed}KT`;
        }
        return p.type;
      case "SFC WIND": 
        if (p.windDirection && p.windSpeed && p.gustSpeed) {
          return `SFC WIND ${p.windDirection}/${p.windSpeed}KT MAX ${p.gustSpeed}KT`;
        }
        return p.type;
      case "VIS": 
        if (p.visibility) {
          const vis = p.visibilityCause ? `VIS ${p.visibility}M ${p.visibilityCause}` : `VIS ${p.visibility}M`;
          return vis;
        }
        return p.type;
      case "TC": 
        const tc = p.cycloneName ? `TC ${p.cycloneName}` : p.type;
        return tc;
      case "TOX CHEM": 
        const toxChem = p.toxChemDescription && p.toxChemDescription.trim() ? `TOX CHEM ${p.toxChemDescription}` : p.type;
        return toxChem;
      case "CUSTOM": 
        const custom = p.customDescription ? p.customDescription : p.type;
        return custom;
      default: return p.type;
    }
  }).join(" ");
};

// Fungsi untuk menghasilkan terjemahan dalam bahasa Indonesia
const generateIndonesianTranslation = () => {
  if (!formData.airportCode || !formData.warningNumber || !formData.startTime ||
      !formData.endTime || formData.phenomena.length === 0 || !formData.source || !formData.intensity) {
    return "";
  }

  // Format tanggal dan waktu
  const startDate = new Date(formData.startTime + 'Z');
  const endDate = new Date(formData.endTime + 'Z');
  
  const formatIndonesianDate = (date) => {
    const day = date.getUTCDate().toString().padStart(2, '0');
    const month = date.getUTCMonth();
    const year = date.getUTCFullYear();
    const hour = date.getUTCHours().toString().padStart(2, '0');
    
    const monthNames = [
      'JANUARI', 'FEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI',
      'JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOVEMBER', 'DESEMBER'
    ];
    
    return `${day} ${monthNames[month]} ${year}`;
  };

  const formatIndonesianTime = (date) => {
    const hour = date.getUTCHours().toString().padStart(2, '0');
    const minute = date.getUTCMinutes().toString().padStart(2, '0');
    return `${hour}.${minute}`;
  };

  // Terjemahan fenomena
  const translatePhenomena = () => {
    return formData.phenomena.map(p => {
      switch (p.type) {
        case "TS":
          return "BADAI GUNTUR";
        case "GR":
          return "HUJAN ES";
        case "HVY RA":
          return "HUJAN LEBAT";
        case "TSRA":
          return "BADAI GUNTUR DENGAN HUJAN";
        case "HVY TSRA":
          return "BADAI GUNTUR DENGAN HUJAN LEBAT";
        case "SFC WSPD":
          if (p.windSpeed && p.gustSpeed) {
            return `ANGIN KENCANG ${p.windSpeed} KNOTS MAKSIMUM ${p.gustSpeed} KNOTS`;
          }
          return "ANGIN KENCANG";
        case "SFC WIND":
          if (p.windDirection && p.windSpeed && p.gustSpeed) {
            return `ANGIN KENCANG DARI ARAH ${p.windDirection} DERAJAT DENGAN KECEPATAN ${p.windSpeed} KNOTS MAKSIMUM ${p.gustSpeed} KNOTS`;
          }
          return "ANGIN KENCANG DENGAN ARAH";
        case "VIS":
          if (p.visibility) {
            let visText = `VISIBILITY ${p.visibility} METER`;
            if (p.visibilityCause) {
              const causeTranslations = {
                'HZ': 'TERJADI KEKABURAN UDARA YANG MENYEBABKAN JARAK PANDANG BERKURANG (HAZE)',
                'BR': 'TERJADI KABUT TIPIS YANG MENYEBABKAN JARAK PANDANG BERKURANG',
                'RA': 'TERJADI HUJAN YANG MENYEBABKAN JARAK PANDANG BERKURANG',
                'FG': 'TERJADI KABUT TEBAL YANG MENYEBABKAN JARAK PANDANG BERKURANG'
              };
              visText += `, ${causeTranslations[p.visibilityCause] || p.visibilityCause}`;
            }
            return visText;
          }
          return "JARAK PANDANG RENDAH";
        case "SQ":
          return "SQUALL";
        case "VA":
          return "ABU VULKANIK";
        case "TOX CHEM":
          if (p.toxChemDescription && p.toxChemDescription.trim()) {
            return `BAHAN KIMIA BERACUN ${p.toxChemDescription}`;
          }
          return "BAHAN KIMIA BERACUN";
        case "TSUNAMI":
          return "TSUNAMI";
        case "CUSTOM":
          return p.customDescription || "FENOMENA LAINNYA";
        default:
          return p.type;
      }
    }).join(", ");
  };

  // Terjemahan sumber
  const translateSource = () => {
    switch (formData.source) {
      case "OBS":
        return "BERDASARKAN DATA OBSERVASI";
      case "FCST":
        return "BERDASARKAN DATA FORECAST";
      default:
        return formData.source;
    }
  };

  // Terjemahan intensitas
  const translateIntensity = () => {
    switch (formData.intensity) {
      case "WKN":
        return "DIPRAKIRAKAN INTENSITAS AKAN BERKURANG";
      case "INTSF":
        return "DIPRAKIRAKAN INTENSITAS AKAN MENINGKAT";
      case "NC":
        return "DIPRAKIRAKAN INTENSITAS TANPA PERUBAHAN";
      default:
        return formData.intensity;
    }
  };

  // Waktu observasi jika ada
  let observationTimeText = "";
  if (formData.source === "OBS" && formData.observationTime) {
    const obsDate = new Date(formData.observationTime + 'Z');
    observationTimeText = ` PUKUL ${formatIndonesianTime(obsDate)} UTC`;
  }

  // Nomor urut dari warning number
  const sequenceNumber = formData.warningNumber.split(' ')[2] || '1';

  // Membuat terjemahan lengkap
  const translation = `AERODROME WARNING STASIUN METEOROLOGI TUNGGUL WULUNG NOMOR ${sequenceNumber}, BERLAKU TANGGAL ${formatIndonesianDate(startDate)}, ANTARA PUKUL ${formatIndonesianTime(startDate)} - ${formatIndonesianTime(endDate)} UTC, ${translatePhenomena()}, ${translateSource()}${observationTimeText}, ${translateIntensity()}.`;

  return translation;
};

const generatePreview = () => {
  if (!selectedForecaster.value) {
    showToast("Forecaster Belum Dipilih", "Mohon pilih forecaster terlebih dahulu.", "destructive");
    return;
  }
  
  if (!formData.airportCode || !formData.warningNumber || !formData.startTime ||
      !formData.endTime || formData.phenomena.length === 0 || !formData.source || !formData.intensity) {
    showToast("Form Belum Lengkap", "Mohon lengkapi semua field yang ditandai wajib (*).", "destructive");
    return;
  }

  // Validasi khusus untuk waktu observasi jika source adalah OBS
  if (formData.source === "OBS" && !formData.observationTime) {
    showToast("Form Belum Lengkap", "Waktu observasi wajib diisi jika sumber adalah Observasi (OBS).", "destructive");
    return;
  }

  // Pastikan waktu yang diproses adalah UTC
  const startFormatted = formatDateTime(formData.startTime);
  const endFormatted = formatDateTime(formData.endTime);
  const phenomenaString = generatePhenomenaString();
  
  // Validasi phenomena string tidak boleh kosong
  if (!phenomenaString || String(phenomenaString).trim() === '') {
    showToast("Detail Fenomena Kosong", "Mohon lengkapi detail untuk setiap fenomena yang ditambahkan.", "destructive");
    return;
  }

  // Validasi khusus untuk fenomena VIS - jarak pandang wajib diisi
  const visPhenomena = formData.phenomena.filter(p => p.type === 'VIS');
  for (const vis of visPhenomena) {
    if (!vis.visibility || vis.visibility === '') {
      showToast("Detail Fenomena Kosong", "Jarak pandang wajib diisi untuk fenomena VIS.", "destructive");
      return;
    }
  }

  // Validasi khusus untuk fenomena angin
  const windPhenomena = formData.phenomena.filter(p => p.type === 'SFC WSPD' || p.type === 'SFC WIND');
  for (const wind of windPhenomena) {
    if (wind.type === 'SFC WSPD') {
      if (!wind.windSpeed || !wind.gustSpeed) {
        showToast("Detail Fenomena Kosong", "Kecepatan angin dan maksimum wajib diisi untuk fenomena SFC WSPD.", "destructive");
        return;
      }
    } else if (wind.type === 'SFC WIND') {
      if (!wind.windDirection || !wind.windSpeed || !wind.gustSpeed) {
        showToast("Detail Fenomena Kosong", "Arah, kecepatan, dan maksimum angin wajib diisi untuk fenomena SFC WIND.", "destructive");
        return;
      }
    }
  }

  // Validasi khusus untuk fenomena CUSTOM
  const customPhenomena = formData.phenomena.filter(p => p.type === 'CUSTOM');
  for (const custom of customPhenomena) {
    if (!custom.customDescription || String(custom.customDescription).trim() === '') {
      showToast("Detail Fenomena Kosong", "Deskripsi wajib diisi untuk fenomena CUSTOM.", "destructive");
      return;
    }
  }


  
  let preview = `${formData.airportCode} AD WRNG ${formData.warningNumber.split(' ')[2]} VALID ${startFormatted}/${endFormatted} ${phenomenaString}`;

  if (formData.source === "OBS" && formData.observationTime) {
    const obsFormatted = formatDateTime(formData.observationTime);
    preview += ` OBS AT ${obsFormatted}Z`;
  } else {
    preview += ` ${formData.source}`;
  }

        preview += ` ${formData.intensity}=`;

   previewMessage.value = preview;
   
   // Generate terjemahan dalam bahasa Indonesia
   translationMessage.value = generateIndonesianTranslation();
   
   isDraftCreated.value = true;
  
  showToast("Draf Berhasil Dibuat", "Silakan review pesan sesuai format baku.");
  
  // Focus on preview section after a short delay to ensure DOM is updated
  setTimeout(() => {
    if (previewTextareaRef.value) {
      previewTextareaRef.value.focus();
      previewTextareaRef.value.select();
    }
    // Also focus on translation textarea if available
    if (translationTextareaRef.value) {
      translationTextareaRef.value.focus();
    }
  }, 100);
};

const copyToClipboard = () => {
  navigator.clipboard.writeText(previewMessage.value);
  showToast("Berhasil Disalin", "Pesan telah disalin ke clipboard.");
};

const copyTranslationToClipboard = () => {
  navigator.clipboard.writeText(translationMessage.value);
  showToast("Terjemahan Disalin", "Terjemahan telah disalin ke clipboard.");
};

const saveEditedMessage = () => {
  // Here you can add logic to save the edited message
  // For now, we'll just show a toast notification
  showToast("Pesan Disimpan", "Pesan yang diedit telah disimpan.");
};

const saveEditedTranslation = () => {
  // Here you can add logic to save the edited translation
  // For now, we'll just show a toast notification
  showToast("Terjemahan Disimpan", "Terjemahan yang diedit telah disimpan.");
};

const resetForm = () => {
  Object.assign(formData, {
    airportCode: defaultAirport.code, // Selalu reset ke WAHL
    warningNumber: "", // Akan diisi setelah fetch sequence number
    startTime: "",
    endTime: "",
    forecaster: selectedForecaster.value ? selectedForecaster.value.name : "", // Pertahankan forecaster yang dipilih
    phenomena: [],
    source: "",
    intensity: "",
    observationTime: "",
  });
     selectedPhenomenon.value = "";
   previewMessage.value = "";
   translationMessage.value = "";
   isDraftCreated.value = false;
   
   // Ambil sequence number terbaru setelah reset
   fetchNextSequenceNumber();
};

const setCurrentUTCTime = () => {
  const now = getCurrentUTCDatetime();
  formData.startTime = now;
  // Set end time to 1 hour later
  const endTime = new Date();
  endTime.setUTCHours(endTime.getUTCHours() + 1);
  const endYear = endTime.getUTCFullYear();
  const endMonth = (endTime.getUTCMonth() + 1).toString().padStart(2, '0');
  const endDay = endTime.getUTCDate().toString().padStart(2, '0');
  const endHours = endTime.getUTCHours().toString().padStart(2, '0');
  const endMinutes = endTime.getUTCMinutes().toString().padStart(2, '0');
  formData.endTime = `${endYear}-${endMonth}-${endDay}T${endHours}:${endMinutes}`;
  
  showToast("Periode Validitas Diisi", "Periode validitas telah diisi dengan UTC sekarang.");
};

const setCurrentObservationTime = () => {
  const now = getCurrentUTCDatetime();
  formData.observationTime = now;
  showToast("Waktu Observasi Diisi", "Waktu observasi telah diisi dengan UTC sekarang.");
};

const publishWarning = () => {
  if (!selectedForecaster.value) {
    showToast("Forecaster Belum Dipilih", "Mohon pilih forecaster terlebih dahulu.", "destructive");
    return;
  }
  
  // Di aplikasi Inertia, Anda akan mengirim data ke backend di sini.
  router.post('/aerodrome/warnings', {
    ...formData,
    previewMessage: previewMessage.value,
    translationMessage: translationMessage.value,
    forecaster_id: selectedForecaster.value.id,
    forecaster_name: selectedForecaster.value.name,
    forecaster_nip: selectedForecaster.value.nip
  });
};

// Helper untuk mendapatkan nama fenomena
const getPhenomenonName = (type) => {
  return phenomena.find(p => p.code === type)?.name || type;
};

// Watch untuk flash messages dari Inertia
watch(() => props.flash, (flash) => {
  if (flash.success) {
    showToast("Berhasil", flash.success);
    resetForm();
    emit('warning-published');
  }
  if (flash.error) {
    showToast("Gagal", flash.error, "destructive");
  }
}, { immediate: true });

// Inisialisasi forecaster saat komponen dimount
onMounted(() => {
  initializeForecaster();
  initializeAirport();
  fetchNextSequenceNumber(); // Ambil sequence number terbaru dari backend
  startSequencePolling(); // Mulai polling otomatis
});

// Cleanup saat komponen unmount
onUnmounted(() => {
  stopSequencePolling(); // Hentikan polling saat komponen dihapus
});

</script>

<template>
  <!-- Implementasi Toast Sederhana -->
  <div v-if="toast.show" class="fixed top-5 right-5 w-80 bg-gray-900 text-white p-4 rounded-lg shadow-lg z-50 transition-opacity duration-300" 
       :class="{ 'bg-red-700': toast.variant === 'destructive' }" role="alert">
    <p class="font-bold">{{ toast.title }}</p>
    <p class="text-sm">{{ toast.description }}</p>
  </div>

  <!-- Untuk komponen UI, saya akan gunakan elemen HTML standar dengan kelas Tailwind -->
  <div class="w-full max-w-5xl mx-auto border bg-card text-card-foreground shadow-sm rounded-lg">
    <div class="p-6 pb-4 mb-5">
      <h3 class="flex items-center gap-2 text-primary text-xl font-semibold">
        <AlertTriangle class="h-6 w-6" />
        Formulir Pembuatan Aerodrome Warning
      </h3>
             <p class="text-sm text-muted-foreground mt-1">
         Sistem ini mendukung peringatan multi-fenomena sesuai standar ICAO Annex 3.
       </p>
       <!-- Informasi bandara -->
       <div class="mt-3 p-3 bg-green-50 border border-green-200 rounded-lg">
         <p class="text-sm text-green-800">
           <span class="font-medium">Bandara:</span> {{ defaultAirport.code }} - {{ defaultAirport.name }}
         </p>
       </div>
      <!-- Informasi forecaster yang aktif -->
      <div v-if="selectedForecaster" class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
        <div class="flex items-center justify-between">
          <p class="text-sm text-blue-800">
            <span class="font-medium">Forecaster Aktif:</span> {{ selectedForecaster.name }} ({{ selectedForecaster.nip }})
          </p>
          <button 
            @click="selectForecaster(null)" 
            class="px-2 py-1 bg-red-600 text-white rounded text-xs hover:bg-red-700"
          >
            Ganti Forecaster
          </button>
        </div>
      </div>
      <!-- Peringatan jika forecaster belum dipilih -->
      <div v-else class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
        <p class="text-sm text-yellow-800">
          <span class="font-medium">⚠️ Peringatan:</span> Forecaster belum dipilih. Silakan pilih forecaster di bawah ini.
        </p>
      </div>
    </div>
    
    <div class="p-6 pt-0 space-y-8">
             <!-- Group 0: Pemilihan Forecaster -->
       <div v-if="!selectedForecaster" class="space-y-4">
         <h4 class="text-lg font-semibold text-foreground">0. Pemilihan Forecaster <span class="text-xs bg-blue-500/10 text-blue-600 px-2 py-1 rounded">Wajib</span></h4>
         <div class="space-y-4">
           <!-- Pilihan forecaster -->
           <div class="space-y-3">
             <p class="text-sm text-muted-foreground">Pilih forecaster yang akan membuat peringatan:</p>
             <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
               <button 
                 v-for="forecaster in forecasters" 
                 :key="forecaster.id"
                 @click="selectForecaster(forecaster)"
                 class="p-3 border border-border rounded-lg hover:bg-muted/50 text-left transition-colors"
               >
                 <p class="font-medium">{{ forecaster.name }}</p>
                 <p class="text-sm text-muted-foreground">{{ forecaster.nip }}</p>
               </button>
             </div>
           </div>
         </div>
              </div>

       <hr v-if="!selectedForecaster" class="border-border" />

       <!-- Group 1: Header Peringatan -->
      <div class="space-y-4">
        <h4 class="text-lg font-semibold text-foreground">1. Header Peringatan <span class="text-xs bg-destructive/10 text-destructive px-2 py-1 rounded">Wajib</span></h4>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <!-- Lokasi Bandara -->
          <div class="space-y-2">
            <label for="airport" class="text-sm font-medium">Lokasi Bandara (Kode ICAO) <span class="text-destructive">*</span></label>
            <!-- Informasi bandara yang selalu WAHL -->
            <div class="w-full mt-1 p-3 border rounded-md bg-muted/30 cursor-not-allowed">
              <div class="flex items-center justify-between">
                <div>
                  <p class="font-medium text-foreground">{{ defaultAirport.code }} - {{ defaultAirport.name }}</p>
                  <p class="text-sm text-muted-foreground">Cilacap, Central Java</p>
                </div>
                <div class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-medium">
                  Default
                </div>
              </div>
            </div>
            <!-- Hidden input untuk tetap menyimpan nilai -->
            <input v-model="formData.airportCode" type="hidden" />
          </div>
                     <!-- Nomor Peringatan -->
           <div class="space-y-2">
             <label for="warningNumber" class="text-sm font-medium">
               Nomor Peringatan
               <span class="inline-flex items-center gap-1 ml-2 text-xs text-green-600">
                 <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                 Auto-update
               </span>
             </label>
             <input v-model="formData.warningNumber" id="warningNumber" type="text" readonly class="w-full mt-1 p-2 border rounded-md bg-muted/50 cursor-not-allowed" placeholder="Otomatis terisi..." />
             <p class="text-xs text-muted-foreground">
               🔄 Nomor akan otomatis diperbarui setiap 30 detik dan reset di 00:00 UTC
             </p>
           </div>
                     <!-- Periode Validitas -->
           <div class="space-y-2">
             <label class="text-sm font-medium">Periode Validitas (UTC) <span class="text-destructive">*</span><button 
                   @click="setCurrentUTCTime" 
                   type="button"
                   class="px-2 py-1 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-xs ml-2"
                 >
                   UTC Now
                 </button></label>
             <div class="grid grid-cols-1 gap-3 mt-1">
               <div class="flex gap-2">
                 <input 
                   v-model="formData.startTime" 
                   type="datetime-local" 
                   class="flex-1 p-2 border rounded-md" 
                   :placeholder="getCurrentUTCDatetime()"
                 />
               </div>
               <input 
                 v-model="formData.endTime" 
                 type="datetime-local" 
                 class="w-full p-2 border rounded-md" 
                 :placeholder="getCurrentUTCDatetime()"
               />
             </div>
             <p class="text-xs text-muted-foreground">
               ⚠️ Waktu menggunakan UTC
             </p>
           </div>
        </div>
      </div>

      <hr class="border-border" />

      <!-- Group 2: Detail Fenomena -->
      <div class="space-y-6">
        <h4 class="text-lg font-semibold text-foreground">2. Detail Fenomena <span class="text-xs bg-destructive/10 text-destructive px-2 py-1 rounded">Wajib</span></h4>
        <div class="flex gap-3 items-end">
          <div class="flex-1 max-w-md">
            <label class="text-sm font-medium">Pilih Fenomena untuk Ditambahkan <span class="text-destructive">*</span></label>
            <select v-model="selectedPhenomenon" class="w-full mt-1 p-2 border rounded-md">
              <option disabled value="">Pilih fenomena...</option>
              <option v-for="p in phenomena" :key="p.code" :value="p.code">
                {{ p.code }} - {{ p.name }}
              </option>
            </select>
          </div>
          <button @click="addPhenomenon" class="flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90">
            <Plus class="h-4 w-4" /> Tambah
          </button>
        </div>

        <!-- Daftar Fenomena yang Ditambahkan -->
        <div>
          <label class="text-sm font-medium">Daftar Fenomena ({{ formData.phenomena.length }})</label>
          <div v-if="formData.phenomena.length === 0" class="mt-2 p-6 border-2 border-dashed rounded-lg text-center">
            <p class="text-muted-foreground">Belum ada fenomena yang ditambahkan.</p>
          </div>
          <div v-else class="mt-2 space-y-3">
            <!-- Render detail fenomena langsung di template -->
            <div v-for="p in formData.phenomena" :key="p.id" class="p-4 bg-accent/20 rounded-lg border border-accent/40">
              <div class="flex items-center justify-between mb-3">
                <h5 class="font-medium text-accent-foreground">{{ p.type }} - {{ getPhenomenonName(p.type) }}</h5>
                <button @click="removePhenomenon(p.id)" class="h-6 w-6 p-0 text-destructive hover:bg-destructive/10 rounded-md">
                  <X class="h-4 w-4 mx-auto" />
                </button>
              </div>
              
              <!-- Input Spesifik berdasarkan Tipe Fenomena -->
              <div v-if="p.type === 'SFC WSPD'" class="grid grid-cols-2 gap-3">
                <div>
                  <input v-model="p.windSpeed" type="number" placeholder="Kecepatan (KT)" class="w-full p-2 border rounded-md" />
                  <p class="text-xs text-muted-foreground mt-1">* Wajib</p>
                </div>
                <div>
                  <input v-model="p.gustSpeed" type="number" placeholder="Maksimum (KT)" class="w-full p-2 border rounded-md" />
                  <p class="text-xs text-muted-foreground mt-1">* Wajib</p>
                </div>
              </div>
              <div v-if="p.type === 'SFC WIND'" class="grid grid-cols-3 gap-3">
                <div>
                  <input v-model="p.windDirection" type="number" placeholder="Arah (°)" class="w-full p-2 border rounded-md" />
                  <p class="text-xs text-muted-foreground mt-1">* Wajib</p>
                </div>
                <div>
                  <input v-model="p.windSpeed" type="number" placeholder="Kecepatan (KT)" class="w-full p-2 border rounded-md" />
                  <p class="text-xs text-muted-foreground mt-1">* Wajib</p>
                </div>
                <div>
                  <input v-model="p.gustSpeed" type="number" placeholder="Maksimum (KT)" class="w-full p-2 border rounded-md" />
                  <p class="text-xs text-muted-foreground mt-1">* Wajib</p>
                </div>
              </div>
              <div v-if="p.type === 'VIS'" class="grid grid-cols-2 gap-3">
                <div>
                  <input v-model="p.visibility" type="number" placeholder="Jarak (meter)" class="w-full p-2 border rounded-md" />
                  <p class="text-xs text-muted-foreground mt-1">* Wajib</p>
                </div>
                <div>
                  <select v-model="p.visibilityCause" class="w-full p-2 border rounded-md">
                    <option value="">Pilih penyebab (opsional)</option>
                    <option v-for="cause in visibilityCauses" :key="cause.code" :value="cause.code">{{ cause.name }}</option>
                  </select>
                  <p class="text-xs text-muted-foreground mt-1">Opsional</p>
                </div>
              </div>
              <div v-if="p.type === 'CUSTOM'">
                 <input v-model="p.customDescription" type="text" placeholder="Deskripsi fenomena" class="w-full p-2 border rounded-md" maxlength="32" />
                 <p class="text-xs text-muted-foreground mt-1">* Wajib diisi</p>
              </div>
              <div v-if="p.type === 'TOX CHEM'">
                 <input v-model="p.toxChemDescription" type="text" placeholder="Deskripsi bahan kimia beracun (opsional)" class="w-full p-2 border rounded-md" maxlength="32" />
                 <p class="text-xs text-muted-foreground mt-1">Opsional</p>
              </div>
              <p v-if='!["SFC WSPD", "SFC WIND", "VIS", "CUSTOM"].includes(p.type)' class="text-xs text-muted-foreground italic">
                Tidak ada detail tambahan yang diperlukan.
              </p>
            </div>
          </div>
        </div>
      </div>

      <hr class="border-border" />

      <!-- Group 3: Informasi Tambahan -->
      <div class="space-y-4">
        <h4 class="text-lg font-semibold text-foreground">3. Informasi Tambahan <span class="text-xs bg-destructive/10 text-destructive px-2 py-1 rounded">Wajib</span></h4>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <!-- Sumber Informasi -->
          <div class="space-y-2">
            <label for="source" class="text-sm font-medium">Sumber Informasi <span class="text-destructive">*</span></label>
            <select v-model="formData.source" id="source" class="w-full mt-1 p-2 border rounded-md">
              <option disabled value="">Pilih sumber...</option>
              <option value="OBS">Observasi (OBS)</option>
              <option value="FCST">Forecast (FCST)</option>
            </select>
          </div>
          <!-- Intensitas -->
          <div class="space-y-2">
            <label for="intensity" class="text-sm font-medium">Intensitas <span class="text-destructive">*</span></label>
            <select v-model="formData.intensity" id="intensity" class="w-full mt-1 p-2 border rounded-md">
              <option disabled value="">Pilih intensitas...</option> 
              <option value="WKN">Melemah (WKN)</option>
              <option value="INTSF">Menguat (INTSF)</option>
              <option value="NC">Tanpa Perubahan (NC)</option>
            </select>
          </div>
                     <!-- Waktu Observasi (opsional, hanya jika source = OBS) -->
           <div class="space-y-2">
             <label for="observationTime" class="text-sm font-medium">Waktu Observasi (UTC)</label>
             <div class="flex gap-2">
               <input 
                 v-model="formData.observationTime" 
                 id="observationTime" 
                 type="datetime-local" 
                 class="flex-1 mt-1 p-2 border rounded-md"
                 :disabled="formData.source !== 'OBS'"
                 :placeholder="formData.source === 'OBS' ? getCurrentUTCDatetime() : 'Hanya untuk OBS'"
               />
               <button 
                 v-if="formData.source === 'OBS'"
                 @click="setCurrentObservationTime"
                 type="button"
                 class="mt-1 px-1 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-xs transition-colors"
                 title="Set waktu observasi ke UTC sekarang"
               >
                 UTC Now
               </button>
             </div>
             <p v-if="formData.source === 'OBS'" class="text-xs text-muted-foreground">
               Waktu observasi dalam UTC. Klik "UTC Now" untuk mengisi waktu sekarang.
             </p>
           </div>
        </div>
      </div>

      <hr class="border-border" />

      <!-- Area Aksi dan Pratinjau -->
      <div class="space-y-6">
        <h4 class="text-lg font-semibold text-foreground">3. Aksi dan Pratinjau</h4>
        <button @click="generatePreview" class="w-full max-w-md px-4 py-3 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 text-lg">
          Buat Draf Pesan
        </button>

                 <div v-if="previewMessage" class="space-y-4 p-6 bg-secondary/30 rounded-lg border">
           <div class="flex items-center justify-between">
             <h5 class="font-medium">Pratinjau</h5>
             <div class="flex gap-2">
               <button @click="saveEditedMessage" class="flex items-center gap-2 px-3 py-1.5 bg-primary text-primary-foreground rounded-md text-sm hover:bg-primary/90">
                 <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                 </svg>
                 Simpan
               </button>
               <button @click="copyToClipboard" class="flex items-center gap-2 px-3 py-1.5 border rounded-md text-sm hover:bg-muted">
                 <Copy class="h-4 w-4" /> Salin
               </button>
             </div>
           </div>
           <textarea 
             ref="previewTextareaRef"
             v-model="previewMessage" 
             class="w-full p-4 border rounded-md font-mono text-sm bg-background resize-none focus:ring-2 focus:ring-primary focus:border-primary"
             rows="3"
             placeholder="Pesan akan muncul di sini setelah Anda mengklik 'Buat Draf Pesan'..."
           ></textarea>
           <p class="text-xs text-muted-foreground">
             💡 Anda dapat mengedit pesan di atas sesuai kebutuhan, kemudian klik "Simpan" untuk menyimpan perubahan.
           </p>
         </div>

         <!-- Terjemahan dalam Bahasa Indonesia -->
         <div v-if="translationMessage" class="space-y-4 p-6 bg-blue-50 border border-blue-200 rounded-lg">
           <div class="flex items-center justify-between">
             <h5 class="font-medium text-blue-900">Terjemahan</h5>
             <div class="flex gap-2">
               <button @click="saveEditedTranslation" class="flex items-center gap-2 px-3 py-1.5 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700">
                 <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                 </svg>
                 Simpan
               </button>
               <button @click="copyTranslationToClipboard" class="flex items-center gap-2 px-3 py-1.5 border border-blue-300 rounded-md text-sm hover:bg-blue-100 text-blue-700">
                 <Copy class="h-4 w-4" /> Salin Terjemahan
               </button>
             </div>
           </div>
           <textarea 
             ref="translationTextareaRef"
             v-model="translationMessage" 
             class="w-full p-4 border border-blue-200 rounded-md bg-white text-sm leading-relaxed text-gray-800 resize-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
             rows="4"
             placeholder="Terjemahan akan muncul di sini..."
           ></textarea>
           <p class="text-xs text-blue-600">
             📝 Anda dapat mengedit terjemahan di atas sesuai kebutuhan, kemudian klik "Simpan" untuk menyimpan perubahan.
           </p>
         </div>

        <div v-if="isDraftCreated" class="flex flex-col sm:flex-row gap-4 pt-4">
          <button @click="publishWarning" class="flex-1 px-4 py-3 bg-green-600 hover:bg-green-700 text-white rounded-md">
            Terbitkan Peringatan
          </button>
          <button @click="resetForm" class="flex-1 px-4 py-3 border rounded-md hover:bg-muted">
            Reset Formulir
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
