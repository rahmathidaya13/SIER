import { usePage } from "@inertiajs/vue3";

const getPage = () => usePage();
export function hasRole(roles) {
    const page = getPage();
    // Gunakan Optional Chaining (?.) untuk jaga-jaga jika user belum login (null)
    const userRoles =
        page.props.auth?.user?.roles?.map((role) => role.name) || [];
    // Pastikan userRoles adalah array sebelum melakukan pengecekan
    if (Array.isArray(roles)) {
        return roles.some((r) => userRoles.includes(r));
    }
    return userRoles.includes(roles);
}

export function hasPermission(permission) {
    const page = getPage();
    const userPermissions =
        page.props.auth?.user?.permissions?.map(
            (permission) => permission.name
        ) || [];
    if (Array.isArray(permission)) {
        return permission.some((perm) => userPermissions.includes(perm));
    }
    return userPermissions.includes(permission);
}

// Helper mengambil ID user yang sedang login
export const getAuthUserId = () => {
    const page = getPage();
    return page.props.auth?.user_id ?? page.props.auth?.user?.id ?? null;
};

// Helper mengambil Role pembuat data dari berbagai struktur JSON
export const getCreatorRole = (item) => {
    if (!item) return "";
    return (
        item?.creator_role_name ??
        item?.role_name ?? // Jika dipakai di tabel User/Pegawai
        item?.created_by?.role_name ??
        item?.creator?.role_name ??
        item?.creator?.roles?.[0]?.name ??
        item?.roles?.[0]?.name ??
        ""
    ).toLowerCase();
};

// Helper mengambil ID pembuat data
export const getCreatorId = (item) => {
    if (!item) return null;
    if (typeof item.created_by === "object" && item.created_by !== null) {
        return item.created_by.id ?? null;
    }
    return item?.creator?.id ?? item?.created_by ?? item?.id_user ?? null;
};

/**
 * Fungsi Reusable untuk mengecek hak akses tombol & hierarki data
 *
 * @param {String|null} permission - Nama permission (cth: 'product-edit')
 * @param {Object|null} item - Baris data tabel (null jika tombol Tambah di luar tabel)
 * @param {Object} options - Opsi tambahan:
 *   - roles: Role yang otomatis bypass (default: ['developer', 'super_admin'])
 *   - strictOwner: Jika true, level bawah (Sales/Admin) hanya boleh edit miliknya sendiri (default: false untuk Master Data, set true untuk Leads/Customer/Sales)
 */
export const canAccess = (permission, item = null, options = {}) => {
    const { roles = ["developer", "super_admin"], strictOwner = false } =
        Array.isArray(options) ? { roles: options } : options;

    // 1. Cek apakah user punya role bypass atau permission dasarnya
    const hasBasicAccess =
        (roles.length > 0 && hasRole(roles)) ||
        (permission && hasPermission(permission));

    if (!hasBasicAccess) {
        return false;
    }

    // 2. Jika tidak ada parameter item (misal tombol "Tambah Data"), langsung izinkan
    if (!item) {
        return true;
    }

    // 3. Level Tertinggi (Developer & Super Admin): Bebas akses ke semua item
    if (hasRole(["developer", "super_admin"])) {
        return true;
    }

    const creatorRole = getCreatorRole(item);

    // 4. Level Menengah (Supervisor):
    // Boleh ubah/hapus jika punya permission, TAPI DILARANG menyentuh data buatan Super Admin & Developer
    if (hasRole(["supervisor"])) {
        return !["developer", "super_admin"].includes(creatorRole);
    }

    // 5. Level Bawah (Admin, Sales, Teknisi):
    // Mutlak DILARANG menyentuh data buatan Developer, Super Admin, maupun Supervisor
    if (["developer", "super_admin", "supervisor"].includes(creatorRole)) {
        return false;
    }

    // 6. Jika strictOwner diaktifkan (misal di tabel Customer, Leads, atau Sales Closing),
    // sesama level bawah (Sales A vs Sales B) hanya boleh mengedit data miliknya sendiri
    if (strictOwner) {
        const creatorId = getCreatorId(item);
        const authUserId = getAuthUserId();
        if (creatorId && authUserId) {
            return String(creatorId) === String(authUserId);
        }
    }

    return true;
};
