<script setup>
// 1. Impor aset gambar
// Pastikan path ini sesuai dengan struktur proyek Anda.
// Dalam setup Vite standar, ini akan berfungsi dengan benar.
import bmkgLogo from '@/assets/bmkg-logo.png';
import { ref, onMounted, onUnmounted } from 'vue';

// State untuk fitur dinamis
const currentTime = ref(new Date());
const isScrolled = ref(false);

// Update waktu setiap detik
let timeInterval;

onMounted(() => {
  timeInterval = setInterval(() => {
    currentTime.value = new Date();
  }, 1000);
  
  // Listen untuk scroll event
  window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
  if (timeInterval) {
    clearInterval(timeInterval);
  }
  window.removeEventListener('scroll', handleScroll);
});

const handleScroll = () => {
  isScrolled.value = window.scrollY > 10;
};

// Format waktu Indonesia
const formatTime = (date) => {
  return date.toLocaleTimeString('id-ID', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  });
};

// Format waktu UTC
const formatUTCTime = (date) => {
  return date.toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    timeZone: 'UTC'
  }) + ' UTC';
};

const formatDate = (date) => {
  return date.toLocaleDateString('id-ID', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};
</script>

<template>
  <!-- Header yang sederhana dengan logo dan jam -->
  <header 
    class="bg-card border-b border-border shadow-sm transition-all duration-300"
    :class="{ 'shadow-md': isScrolled }"
  >
    <div class="container mx-auto px-4 sm:px-6 py-4">
      <div class="flex items-center justify-between">
        <!-- Logo dan Branding -->
        <div class="flex items-center space-x-4">
          <img 
            :src="bmkgLogo" 
            alt="BMKG Logo" 
            class="h-12 w-auto transition-transform duration-300 hover:scale-105"
          />
          <div>
            <h1 class="text-xl font-bold text-foreground transition-colors duration-300">
              Sistem Manajemen Aerodrome Warning
            </h1>
            <p class="text-sm text-muted-foreground">
              Stasiun Meteorologi Kelas III Tunggul Wulung - Cilacap
            </p>
          </div>
        </div>
        
        <!-- Jam Real-time -->
        <div class="text-right">
          <div class="flex flex-col items-end space-y-1">
            <!-- Jam Lokal -->
            <div class="text-xs font-mono text-foreground">
              {{ formatTime(currentTime) }} <span class="text-muted-foreground font-normal">Local</span>
            </div>
            <!-- Jam UTC -->
            <div class="text-xs font-mono text-blue-600">
              {{ formatUTCTime(currentTime) }}
            </div>
            <!-- Tanggal -->
            <div class="text-xs text-muted-foreground">
              {{ formatDate(currentTime) }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>
