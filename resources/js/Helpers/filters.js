/**
 * Membersihkan object filter:
 * - Menghapus null
 * - Menghapus undefined
 * - Menghapus string kosong
 * - Optional whitelist key
 */
export function cleanFilters(filters) {
    return Object.fromEntries(
        Object.entries(filters).filter(([_, value]) => {
            return (
                value !== null &&
                value !== undefined &&
                value !== "" &&
                value !== "all"
            );
        })
    );
}
