<script setup>
// 1. Impor aset gambar
// Pastikan path ini sesuai dengan struktur proyek Anda.
// Dalam setup Vite standar, ini akan berfungsi dengan benar.
import bmkgLogo from '@/assets/bmkg-logo.png';

// 2. Mendefinisikan props
// `defineProps` adalah cara standar di <script setup> untuk mendeklarasikan props.
// Kita bisa mendefinisikan tipe dan nilai default, mirip dengan di React.
const props = defineProps({
  userInfo: {
    type: Object,
    // Gunakan factory function '() => ({...})' untuk nilai default berupa objek atau array
    // untuk memastikan setiap instance komponen mendapatkan objek yang unik.
    default: () => ({
      name: "Budi Santoso",
      station: "Sta. Met. Tunggul Wulung"
    })
  }
});
</script>

<template>
  <!-- Sintaks template Vue sangat mirip dengan HTML biasa. -->
  <!-- `className` dari JSX diubah menjadi `class`. -->
  <header class="bg-card border-b border-border shadow-sm">
    <div class="container mx-auto px-4 sm:px-6 py-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <!-- 3. Binding atribut -->
          <!-- Gunakan ':' (v-bind shorthand) untuk mengikat atribut `src` ke variabel `bmkgLogo`. -->
          <img 
            :src="bmkgLogo" 
            alt="BMKG Logo" 
            class="h-12 w-auto"
          />
          <div>
            <h1 class="text-xl font-bold text-foreground">
              Sistem Manajemen Aerodrome Warning
            </h1>
            <p class="text-sm text-muted-foreground">
              BMKG - Badan Meteorologi, Klimatologi, dan Geofisika
            </p>
          </div>
        </div>
        
        <div class="text-right">
          <!-- 4. Interpolasi Teks -->
          <!-- Gunakan sintaks 'mustache' {{ }} untuk menampilkan data dari props atau state. -->
          <p class="text-sm font-medium text-foreground">
            Prakirawan: {{ userInfo.name }}
          </p>
          <p class="text-xs text-muted-foreground">
            {{ userInfo.station }}
          </p>
        </div>
      </div>
    </div>
  </header>
</template>
