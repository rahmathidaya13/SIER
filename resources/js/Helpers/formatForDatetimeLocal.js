export function formatForDatetimeLocal(dateString) {
    if (!dateString) return "";

    // Ubah string DB atau Date object menjadi ISO string, lalu ambil 16 karakter pertama (YYYY-MM-DDTHH:mm)
    const date = new Date(dateString);

    // Penanganan jika timezone tergeser (menggunakan ISO lokal)
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");
    const hours = String(date.getHours()).padStart(2, "0");
    const minutes = String(date.getMinutes()).padStart(2, "0");

    return `${year}-${month}-${day}T${hours}:${minutes}`;
}
