function escapeRegExp(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
}

export function highlight(text, keyword) {
    if (!keyword || !text) return text;

    const escapedKeyword = escapeRegExp(keyword);
    const regex = new RegExp(`(${escapedKeyword})`, "gi");

    return text.toString().replace(regex, `<mark class="highlight">$1</mark>`);
}

export function highlightForId(text, keyword) {
    if (!keyword || !text) return text;

    // 1. Bersihkan keyword dari spasi/strip yang mungkin diketik user
    const cleanKeyword = keyword.toString().replace(/[\s-]/g, "");

    if (!cleanKeyword) return text;

    // 2. MAGIC: Pecah keyword jadi per huruf, lalu selipkan pola '[-]*'
    // Contoh: "5755" menjadi "5[-]*7[-]*5[-]*5"
    // Artinya: Cari angka 5, lalu boleh ada strip atau tidak, lalu angka 7, dst.
    const pattern = cleanKeyword.split("").join("[-]*");

    // 3. Buat Regex dengan flag 'i' (case insensitive) dan 'g' (global)
    const regex = new RegExp(`(${pattern})`, "gi");

    // 4. Replace text asli dengan highlight, tapi pertahankan format aslinya ($1)
    return text.toString().replace(regex, '<mark class="highlight">$1</mark>');
}
