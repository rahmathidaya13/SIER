<script setup>
import { Head } from '@inertiajs/vue3';
</script>
<template>

    <Head title="Home" />
    <app-layout>
        <template #content>
            <div class="row g-3 view-section">
                <div class="col-12">
                    <callout />
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small fw-medium">Total Prospek (Leads)</p>
                                <h3 class="fw-bold mb-0 text-info">342</h3>
                            </div>
                            <div class="stats-icon bg-info bg-opacity-10 text-info"><i class="bi bi-people"></i>
                            </div>
                        </div><small class="text-success mt-3 d-block"><i class="bi bi-arrow-up-short"></i> +24
                            Lead baru</small>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small fw-medium">Berhasil Closing</p>
                                <h3 class="fw-bold mb-0 text-success">128</h3>
                            </div>
                            <div class="stats-icon bg-success bg-opacity-10 text-success"><i
                                    class="bi bi-check2-circle"></i></div>
                        </div><small class="text-success mt-3 d-block">Naik 12%</small>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card p-3 h-100 border border-primary">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small fw-medium">Closing Rate</p>
                                <h3 class="fw-bold mb-0 text-primary">37.4%</h3>
                            </div>
                            <div class="stats-icon bg-primary bg-opacity-10 text-primary"><i
                                    class="bi bi-lightning-charge"></i></div>
                        </div><small class="text-muted mt-3 d-block">Sangat Sehat</small>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small fw-medium">Nominal Closing</p>
                                <h3 class="fw-bold mb-0 fs-4">Rp 48.5M</h3>
                            </div>
                            <div class="stats-icon bg-warning bg-opacity-10 text-warning"><i
                                    class="bi bi-cash-stack"></i></div>
                        </div><small class="text-success mt-3 d-block">85% Lunas</small>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card p-4">
                        <h6 class="fw-bold mb-4">Statistik Konversi Funnel (7 Hari Terakhir)</h6>
                        <canvas id="funnelChart" height="220"></canvas>
                    </div>
                </div>
            </div>
        </template>
    </app-layout>
</template>
