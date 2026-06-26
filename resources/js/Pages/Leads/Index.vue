<script setup>
import { Head } from '@inertiajs/vue3';
const props = defineProps({
    leads: Object
})
console.log(props.leads);
</script>
<template>

    <Head title="Data Rekap Leads" />
    <app-layout>
        <template #content>
            <div class="row g-3">
                <div class="col-12">
                    <callout />
                    <div class="card p-4">
                        <div class="card-header">
                            <div
                                class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                                <div>
                                    <h5 class="fw-bold mb-0">Database Prospek (Leads)</h5>
                                    <small class="text-muted">Prospek yang belum melakukan pembayaran deal</small>
                                </div>
                                <button class="btn btn-celestial px-3 py-2" data-bs-toggle="modal"
                                    data-bs-target="#modalAddLead">
                                    <i class="bi bi-plus-lg me-1"></i> Tambah Lead Baru
                                </button>
                            </div>
                        </div>

                        <div class="row g-2 mb-3 bg-light p-2.5 rounded-3 border">
                            <div class="col-12 col-md-4">
                                <input type="text" id="filter-lead-name" class="form-control form-control-sm border-0"
                                    placeholder="🔍 Cari nama klien/instansi...">
                            </div>
                            <div class="col-6 col-md-4">
                                <select id="filter-lead-temp" class="form-select form-select-sm border-0 fw-medium">
                                    <option value="ALL">🟣 Semua Suhu Prospek</option>
                                    <option value="HOT">🔥 Suhu: HOT (Siap Bayar)</option>
                                    <option value="WARM">⚡ Suhu: WARM (Tanya Detail)</option>
                                    <option value="COLD">❄️ Suhu: COLD (Hanya Kepo)</option>
                                </select>
                            </div>
                            <div class="col-6 col-md-4 text-md-end align-self-center">
                                <span class="text-muted small">Total: <b>3</b> Prospek aktif</span>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle" id="leads-table">
                                <thead class="bg-light text-muted small">
                                    <tr>
                                        <th scope="col" style="width: 40px;">#</th>
                                        <th scope="col">PROSPEK</th>
                                        <th scope="col">PRODUK MINAT</th>
                                        <th scope="col">SUHU</th>
                                        <th scope="col">TGL MASUK</th>
                                        <th scope="col">NEXT FOLLOW-UP</th>
                                        <th scope="col" class="text-end">AKSI</th>
                                    </tr>
                                </thead>
                                <tbody class="small">
                                    <tr data-temp="HOT">
                                        <td class="text-muted">1</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="client-avatar bg-danger-subtle text-danger me-2">HB</div>
                                                <div>
                                                    <h6 class="mb-0 fw-bold">Hendra Budi</h6>
                                                    <span class="text-muted small">0812-9988-1122</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="fw-medium">Paket Enterprise VIP</td>
                                        <td><span class="badge bg-danger">🔥 HOT</span></td>
                                        <td>12 Jun 2026</td>
                                        <td><span class="badge bg-warning-subtle text-dark border">Besok Pagi</span>
                                        </td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-success me-1 py-1"
                                                onclick="konversiClosing(this)" title="Deal! Jadikan Closing"><i
                                                    class="bi bi-check-circle-fill"></i></button>
                                            <button class="btn btn-sm btn-light me-1" onclick="editData('Hendra Budi')"
                                                title="Edit"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-light text-danger btn-delete-row"
                                                title="Hapus"><i class="bi bi-trash3"></i></button>
                                        </td>
                                    </tr>
                                    <tr data-temp="WARM">
                                        <td class="text-muted">2</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="client-avatar bg-warning-subtle text-warning me-2">PT</div>
                                                <div>
                                                    <h6 class="mb-0 fw-bold">PT. Angkasa Jaya</h6>
                                                    <span class="text-muted small">0857-4433-9900</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="fw-medium">Paket Premium Pro</td>
                                        <td><span class="badge bg-warning text-dark">⚡ WARM</span></td>
                                        <td>10 Jun 2026</td>
                                        <td><span class="text-muted">18 Jun 2026</span></td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-success me-1 py-1"
                                                onclick="konversiClosing(this)" title="Deal! Jadikan Closing"><i
                                                    class="bi bi-check-circle-fill"></i></button>
                                            <button class="btn btn-sm btn-light me-1"
                                                onclick="editData('PT. Angkasa Jaya')" title="Edit"><i
                                                    class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-light text-danger btn-delete-row"
                                                title="Hapus"><i class="bi bi-trash3"></i></button>
                                        </td>
                                    </tr>
                                    <tr data-temp="COLD">
                                        <td class="text-muted">3</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="client-avatar bg-info-subtle text-info me-2">RN</div>
                                                <div>
                                                    <h6 class="mb-0 fw-bold">Ibu Ratna</h6>
                                                    <span class="text-muted small">0811-2233-4455</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="fw-medium">Paket Basic</td>
                                        <td><span class="badge bg-info text-white">❄️ COLD</span></td>
                                        <td>01 Jun 2026</td>
                                        <td><span class="text-danger small fw-medium">Terlewat</span></td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-success me-1 py-1"
                                                onclick="konversiClosing(this)" title="Deal! Jadikan Closing"><i
                                                    class="bi bi-check-circle-fill"></i></button>
                                            <button class="btn btn-sm btn-light me-1" onclick="editData('Ibu Ratna')"
                                                title="Edit"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-light text-danger btn-delete-row"
                                                title="Hapus"><i class="bi bi-trash3"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </app-layout>
</template>
