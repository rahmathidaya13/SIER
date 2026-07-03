<script setup>
import { Form, Head, usePage, router, Link } from '@inertiajs/vue3';
import { onMounted, reactive, ref } from 'vue';
import { useFilter } from '@/Composables/useFilter';
import { useConfirm } from "@/Composables/useConfirm"
import { highlight } from "@/Helpers/Highlight";
import axios from 'axios';
</script>
<template>

    <Head title="Rekap Closing" />
    <app-layout>
        <template #content>
             <div class="row g-3">
                <div class="col-12">
                    <callout />
                    <div class="card border-0 rounded-4 overflow-hidden">

                        <div class="card-header p-4 bg-white">
                            <div
                                class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                                <div>
                                    <h5 class="fw-bold mb-1">Data Penjualan & Invoice</h5>
                                    <small class="text-muted">Manajemen transaksi masuk dan pelunasan tagihan pelanggan.</small>
                                </div>
                                <!-- <button @click.prevent="openCreateModal" class="btn btn-c-primary px-4 py-2">
                                    <i class="bi bi-plus-circle me-1"></i> Lead Baru
                                </button> -->
                            </div>
                        </div>

                        <!-- <div class="row p-3 g-2 align-items-center">
                            <div class="col-12 col-md-12 col-lg-12 col-xl-6">
                                <div class="position-relative">
                                    <i class="bi bi-search input-icon-left"></i>
                                    <input-text ref="keywordRef" :isValid="false" v-model="filters.keyword"
                                        inputClass="input-fixed-height input-height-1 rounded-pill"
                                        placeholder="Cari nama...." />
                                </div>
                            </div>
                            <div class="col-xl-2 col-3 col-md-3 col-lg-3">
                                <select-input :isValid="false" name="temperature" v-model="filters.temperature"
                                    text="Suhu Prospek" :options="[
                                        { label: '🌍 Semua Suhu', value: 'all' },
                                        { label: '🔥 HOT (Siap Bayar)', value: 'hot' },
                                        { label: '🌤️ WARM (Tanya Detail)', value: 'warm' },
                                        { label: '❄️ COLD (Hanya Kepo)', value: 'cold' }
                                    ]" />
                            </div>
                            <div class="col-xl-2 col-3 col-md-3 col-lg-3">
                                <select-input :isValid="false" name="status" v-model="filters.status"
                                    text="Status Prospek" :options="[
                                        { label: '📋 Semua Status', value: 'all' },
                                        { label: '🆕 Baru Masuk', value: 'new' },
                                        { label: '📞 Follow Up', value: 'follow_up' },
                                        { label: '🎉 Closing / Terjual', value: 'closed_won' },
                                        { label: '❌ Tidak Jadi', value: 'closed_lost' }
                                    ]" />
                            </div>
                            <div class="col-xl-1 col-3 col-md-3 col-lg-3">
                                <select-input :isValid="false" name="limit" v-model="filters.limit"
                                    text="Pilih Batas Data" :options="[
                                        { label: '☰ 10 Data', value: 10 },
                                        { label: '☰ 25 Data', value: 25 },
                                        { label: '☰ 50 Data', value: 50 },
                                        { label: '☰ 100 Data', value: 100 },
                                    ]" />
                            </div>
                            <div class="col-xl-1 col-3 col-md-3 col-lg-3">
                                <select-input :isValid="false" name="order_by" v-model="filters.order_by"
                                    text="Pilih Urutan" :options="[
                                        { label: '🔼 Terbaru', value: 'latest' },
                                        { label: '🔽 Terlama', value: 'oldest' }
                                    ]" />
                            </div>

                        </div> -->

                        <div class="card-body p-0">

                        </div>
                    </div>
                </div>

            </div>
        </template>
    </app-layout>
</template>
