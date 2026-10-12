<script setup>
import { computed } from "vue";
const props = defineProps({
  image: {
    type: Object,
    default: null,
  },
  show: {
    type: Boolean,
    default: false,
  },
});
const emit = defineEmits(["update:show"]);
const close = () => {
  emit("update:show", false);
};

const imageUrl = computed(() => {
  const product = props.image;
  if (!product) {
    return "/storage/no_image/no-image.png";
  }

  // Gambar dari sumber scraping
  if (product.source === "scrape" && product.image_link) {
    return product.image_link;
  }

  // Gambar yang tersimpan di Laravel Storage
  if (product.image_path) {
    return `/storage/${product.image_path}`;
  }
  return "/storage/no_image/no-image.png";
});
</script>
<template>
  <div class="row" v-if="props.show">
    <div class="col-12">
      <modal
        width="550px"
        size="modal-lg"
        icon="bi bi-image text-primary"
        :show="props.show"
        title="Preview Gambar Produk"
        :mobileFullscreen="false"
        @closed="close"
        :footer="false"
      >
        <template #body>
          <div class="text-center">
            <img
              :src="imageUrl"
              class="image-preview-modal"
              alt="Preview Gambar Produk"
              @error="
                $event.target.onerror = null;
                $event.target.src = '/storage/no_image/no-image.png';
              "
            />
          </div>
        </template>
      </modal>
    </div>
  </div>
</template>
<style scoped>
.image-preview-modal {
  max-width: 100%;
  height: 65vh;
  object-fit: contain;
  border: 1px solid #cdd0d3;
  border-radius: 0.375rem;
}
</style>
