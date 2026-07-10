<script setup>
import { ref, onMounted, onBeforeUnmount, watch, shallowRef, computed } from 'vue';
import Chart from 'chart.js/auto'; // Import auto agar support Line, Bar, Doughnut, dll.

const props = defineProps({
    labels: {
        type: Array,
        required: true
    },
    datasets: { // Ubah 'data' jadi 'datasets' agar lebih fleksibel support multi-line/bar
        type: Array,
        default: null
    },
    // Jika user malas bikin struktur datasets, bisa pakai simpleData ini (backward compatibility)
    data: {
        type: Array,
        default: () => []
    },
    label: {
        type: String,
        default: 'Data Penjualan'
    },
    options: {
        type: Object,
        default: () => ({})
    },
    height: {
        type: Number,
        default: 300
    },
    type: {
        type: String,
        default: 'bar' // Bisa 'line', 'bar', 'doughnut'
    },
    tooltipPrefix: {
        type: String,
        default: ''
    },
    tooltipSuffix: {
        type: String,
        default: ''
    },
    hideScales: {
        type: Boolean,
        default: false
    }
});

const chartCanvas = ref(null);
// Gunakan shallowRef untuk performa (Vue tidak perlu deep watch object Chart.js)
const chartInstance = shallowRef(null);

// Fungsi membuat Gradient Warna (Agar terlihat Modern)
const createGradient = (ctx, colorStart, colorEnd) => {
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, colorStart);
    gradient.addColorStop(1, colorEnd);
    return gradient;
};

const renderChart = () => {
    if (chartInstance.value) {
        chartInstance.value.destroy();
    }

    const ctx = chartCanvas.value.getContext('2d');

    // DEFAULT STYLING: Modern Blue Gradient untuk Sales
    // Jika user tidak menyediakan warna spesifik, gunakan ini.
    const defaultGradient = createGradient(ctx, 'rgba(13, 110, 253, 0.9)', 'rgba(13, 110, 253, 0.1)');

    // Persiapkan Data
    let finalDatasets = [];

    if (props.datasets) {
        // Jika user kirim format lengkap datasets
        finalDatasets = props.datasets;
    } else {
        // Jika user kirim format simple (array angka saja)
        finalDatasets = [{
            label: props.label,
            data: props.data,
            backgroundColor: defaultGradient,
            borderColor: '#0d6efd',
            borderWidth: 2,
            borderRadius: 4, // Bar jadi agak bulat
            barPercentage: 0.6, // Ukuran bar tidak terlalu gemuk
            tension: 0.4, // Jika type='line', garisnya melengkung halus
            fill: true // Jika type='line', area bawah diwarnai
        }];
    }

    // DEFAULT OPTIONS: Agar Chart terlihat bersih & profesional
    const defaultOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: props.type !== 'bar', // Sembunyikan legend jika cuma 1 bar chart
                position: 'top',
                labels: {
                    usePointStyle: true,
                    boxWidth: 8
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0,0,0,0.8)',
                padding: 12,
                titleFont: { size: 13 },
                bodyFont: { size: 13 },
                cornerRadius: 8,
                displayColors: false,
                callbacks: {
                    label: function (context) {
                        let label = context.dataset.label || '';

                        if (label) {
                            label += ': ' + props.tooltipPrefix;
                        }

                        if (context.raw !== null && context.raw !== undefined) {
                            label += new Intl.NumberFormat('id-ID').format(context.raw) + props.tooltipSuffix;
                        }

                        return label;
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    borderDash: [5, 5], // Garis putus-putus (lebih rapi)
                    color: '#e9ecef',
                    drawBorder: false // Hilangkan garis border kiri
                },
                ticks: {
                    font: { size: 11 },
                    color: '#6c757d'
                }
            },
            x: {
                grid: {
                    display: false // Hilangkan garis vertikal (biar clean)
                },
                ticks: {
                    font: { size: 11 },
                    color: '#6c757d'
                }
            }
        }
    };

    // Gabungkan opsi default dengan opsi dari props (props menang jika tabrakan)
    const mergedOptions = { ...defaultOptions, ...props.options };
    if (props.hideScales) {
        delete mergedOptions.scales;
    }
    // Khusus untuk nested object (plugins/scales) perlu deep merge manual atau biarkan user override total.
    // Di sini kita pakai pendekatan simple merge level 1.

    chartInstance.value = new Chart(ctx, {
        type: props.type,
        data: {
            labels: props.labels,
            datasets: finalDatasets
        },
        options: mergedOptions
    });
};

onMounted(() => {
    renderChart();
});

// Watch perubahan data agar chart update real-time
watch(
    () => [props.labels, props.data, props.datasets, props.type],
    () => { renderChart(); },
    { deep: true }
);

onBeforeUnmount(() => {
    if (chartInstance.value) {
        chartInstance.value.destroy();
    }
});
</script>

<template>
    <div class="chart-container" :style="{ position: 'relative', height: height + 'px', width: '100%' }">
        <canvas ref="chartCanvas"></canvas>
    </div>
</template>

<style scoped>
/* Opsional: Jika ingin canvas transisi halus */
canvas {
    width: 100% !important;
    height: 100% !important;
}
</style>
