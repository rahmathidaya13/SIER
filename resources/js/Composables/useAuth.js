import { usePage } from "@inertiajs/vue3";
export function hasRole(roles) {
    const page = usePage();
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
    const page = usePage();
    const userPermissions =
        page.props.auth?.user?.permissions?.map(
            (permission) => permission.name
        ) || [];
    if (Array.isArray(permission)) {
        return permission.some((perm) => userPermissions.includes(perm));
    }
    return userPermissions.includes(permission);
}
