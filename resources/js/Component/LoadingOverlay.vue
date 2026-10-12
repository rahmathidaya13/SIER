<template>
  <Teleport to="body">
    <Transition name="loading-fade">
      <div
        v-if="show"
        class="loading-overlay"
        role="status"
        aria-live="polite"
        aria-label="Memuat halaman"
      >
        <div class="loading-content">
          <div class="loading-spinner"></div>

          <div class="loading-text">Memuat halaman...</div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
defineProps({
  show: {
    type: Boolean,
    default: false,
  },
});
</script>

<style scoped>
.loading-overlay {
  position: fixed;
  inset: 0;
  z-index: 99999;

  display: flex;
  align-items: center;
  justify-content: center;

  background: rgba(0, 0, 0, 0.65);
  backdrop-filter: blur(3px);
  -webkit-backdrop-filter: blur(3px);
}

.loading-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;

  color: #fff;
  text-align: center;
}

.loading-spinner {
  width: 60px;
  height: 60px;

  border: 8px solid rgba(255, 255, 255, 0.25);
  border-top-color: #fff;
  border-radius: 50%;

  animation: loading-spin 0.8s linear infinite;
}

.loading-text {
  font-size: 16px;
  font-weight: 500;
  letter-spacing: 0.3px;
}

.loading-fade-enter-active,
.loading-fade-leave-active {
  transition: opacity 0.2s ease;
}

.loading-fade-enter-from,
.loading-fade-leave-to {
  opacity: 0;
}

@keyframes loading-spin {
  to {
    transform: rotate(360deg);
  }
}

@media (prefers-reduced-motion: reduce) {
  .loading-spinner {
    animation-duration: 1.8s;
  }

  .loading-fade-enter-active,
  .loading-fade-leave-active {
    transition-duration: 0.01ms;
  }
}
</style>
