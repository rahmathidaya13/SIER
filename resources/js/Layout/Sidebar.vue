<script setup>
import { Link, router, usePage } from "@inertiajs/vue3";
import { hasRole, hasPermission } from "@/Composables/useAuth";
import { useNotification } from "@/Composables/useNotification";
import { computed } from "vue";
defineProps({
  isOpen: Boolean,
});
const emit = defineEmits(["close", "openProfileModal"]);
const currentPath = usePage().props.path;

const { unreadCount } = useNotification();
const isSelectedActive = (pattern) => {
  const regex = new RegExp("^" + pattern.replace("*", ".*") + "$");
  return regex.test(currentPath);
};
const menuItems = [
  {
    title: "Notifikasi",
    icon: "bi bi-bell-fill",
    route: "notification.index",
    prefixUrl: "notification*",
    badge: unreadCount.value > 99 ? "99+" : unreadCount.value,
  },
  {
    title: "Dashboard",
    icon: "bi bi-speedometer2",
    route: "home",
    prefixUrl: "home*",
  },
  {
    title: "Produk",
    icon: "bi bi-bag-check-fill",
    route: "product.index",
    prefixUrl: "product*",
    permissions: ["product-view"],
  },
  {
    title: "Pelanggan",
    icon: "bi bi-person-fill",
    route: "customer.index",
    prefixUrl: "customer*",
    permissions: ["customer-view"],
  },
  {
    title: "Data Leads",
    icon: "bi bi-person-vcard",
    route: "leads.index",
    prefixUrl: "leads*",
    permissions: ["lead-view"],
  },
  {
    title: "Rekap Closing",
    icon: "bi bi-graph-up-arrow",
    route: "sales.index",
    prefixUrl: "sales*",
    permissions: ["closing-view"],
  },
  {
    title: "Sumber Trafik",
    icon: "bi bi-broadcast",
    route: "channel.index",
    prefixUrl: "channel*",
    roles: ["developer", "super_admin"],
    permissions: ["channel-view"],
  },
  {
    title: "Daftar Cabang",
    icon: "bi bi-building",
    route: "branch.index",
    prefixUrl: "branch*",
    roles: ["developer", "super_admin"],
  },
  {
    title: "Data Pegawai",
    icon: "bi bi-person-fill",
    route: "employee.index",
    prefixUrl: "employee*",
    roles: ["developer", "super_admin"],
  },
  {
    title: "Keluar",
    icon: "bi bi-box-arrow-right",
    route: "logout",
    prefixUrl: "logout*",
  },
];
const menus = computed(() => {
  return menuItems
    .filter((menu) => {
      let roleAllowed = true;
      let permissionAllowed = true;
      if (menu.roles?.length) {
        roleAllowed = menu.roles.some((role) => hasRole(role));
      }

      if (menu.permissions?.length) {
        permissionAllowed = menu.permissions.some((permission) =>
          hasPermission(permission)
        );
      }

      return roleAllowed && permissionAllowed;
    })
    .map((menu) => {
      if (menu.route === "notification.index") {
        return {
          ...menu,
          badge: unreadCount.value > 99 ? "99+" : unreadCount.value,
        };
      }
      return menu;
    });
});
const rolesName = (role) => {
  const roles = {
    developer: {
      label: "DEVELOPER",
      color: "bg-dark text-white",
      icon: "bi bi-code-slash",
    },
    super_admin: {
      label: "HEAD OFFICE",
      color: "bg-dark text-white",
      icon: "bi bi-bank",
    },
    supervisor: {
      label: "SUPERVISOR",
      color: "bg-primary text-white",
      icon: "bi bi-people-fill",
    },
    admin: {
      label: "ADMIN",
      color: "bg-info text-white",
      icon: "bi bi-gear-fill",
    },
    sales: {
      label: "SALES",
      color: "bg-success text-white",
      icon: "bi bi-people-fill",
    },
    teknisi: {
      label: "TEKNISI",
      color: "bg-secondary text-white",
      icon: "bi bi-people-fill",
    },
  };
  return roles[role] || "bg-light text-dark border";
};
const getNameRoles = () => {
  const role = usePage().props.auth.user.roles[0].name;
  return rolesName(role);
};
const getImageUrl = (item) => {
  if (item.photo !== null && item.photo !== undefined) {
    return `/storage/${item.photo}`; // load gambar dari storage jika ada
  }
  // return '/storage/no_image/user-icon.png'; // Fallback jika tidak ada gambar
};
const handleLogout = () => {
  router.post(route("logout"));
};
</script>
<template>
  <aside class="sidebar" :class="{ show: isOpen, 'hide-desktop': !isOpen }">
    <!-- <div class="sidebar-brand">
            <i class="bi bi-funnel-fill me-2"></i> {{ $page.props.app.name ?? 'Aplikasi' }}
        </div> -->

    <section
      class="p-3 border-bottom mx-3 mb-3 d-flex flex-column justify-content-center align-items-center"
      aria-label="User Info"
    >
      <img
        v-if="$page.props.auth.user?.profile?.photo"
        :src="getImageUrl($page.props.auth.user?.profile)"
        class="rounded-circle mb-2 object-fit-cover shadow-sm border shadow"
        width="80"
        height="80"
        :title="$page.props.auth.user?.name"
        :alt="$page.props.auth.user?.name"
        loading="lazy"
        decoding="async"
      />
      <span v-else class="icon">
        <i class="bi bi-person text-secondary"></i>
      </span>

      <button
        @click.prevent="emit('openProfileModal')"
        class="btn nav-link text-primary mb-2 mt-2"
      >
        <span class="mb-1 fw-bold text-capitalize">{{ $page.props.auth.user.name }}</span>
      </button>
      <span class="badge rounded-pill px-3 py-2 fw-bold" :class="getNameRoles().color">
        <i class="bi bi-shield-check me-1"></i> {{ getNameRoles().label }}
      </span>
    </section>

    <nav class="list-group list-group-flush sidebar-menu-scroll">
      <template v-for="menu in menus" :key="menu.route">
        <button
          v-if="menu.route === 'logout'"
          type="button"
          class="list-group-item d-flex justify-content-between align-items-center"
          @click="handleLogout"
        >
          <span> <i :class="menu.icon"></i> {{ menu.title }} </span>
        </button>

        <Link
          v-else
          :href="route(menu.route)"
          class="list-group-item d-flex justify-content-between align-items-center"
          :class="{ active: isSelectedActive(menu.prefixUrl) }"
        >
          <span> <i :class="menu.icon"></i> {{ menu.title }} </span>
          <span v-if="menu.badge" class="badge bg-primary rounded-pill">
            {{ menu.badge }}
          </span>
        </Link>
      </template>
    </nav>
  </aside>
</template>
<style scoped>
.sidebar {
  position: fixed;
  top: 0;
  left: 0;
  width: var(--sidebar-width);
  height: 100vh;
  z-index: 1040;
  background: white;
  transform: translateX(-100%);
  transition: transform 0.35s cubic-bezier(0.2, 0, 0, 1);

  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.sidebar.show {
  transform: translateX(0);
}

.sidebar .sidebar-brand {
  padding: 0 1.5rem;
  font-size: 1.35rem;
  font-weight: 700;
  color: var(--primary-color);
  display: flex;
  align-items: center;
  height: var(--header-height);
  letter-spacing: 0.5px;
}

/* Hiasan List Menu */
.list-group-item {
  border: none;
  padding: 0.85rem 1.5rem;
  color: #6c7383;
  font-weight: 500;
  font-size: 0.92rem;
  margin: 0.1rem 1.5rem;
  border-radius: 10px;
  transition: all 0.2s ease;
  cursor: pointer;
}

.list-group-item:hover {
  color: var(--primary-color);
  /* background-color: #fff4f5; */
  transform: translateX(6px);
}

.list-group-item.active {
  background-color: var(--primary-color);
  color: white;
  box-shadow: 0 4px 12px rgba(255, 94, 108, 0.3);
}

.list-group-item i {
  margin-right: 12px;
  font-size: 1.15rem;
}

/* Hanya daftar menu yang dapat di-scroll */
.sidebar-menu-scroll {
  flex: 1 1 auto;
  min-height: 0;
  overflow-y: auto;
  overflow-x: hidden;
  overscroll-behavior-y: contain;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: thin;
  scrollbar-color: rgba(148, 163, 184, 0.4) transparent;
}

/* Custom scrollbar Chrome, Edge, dan Safari */
.sidebar-menu-scroll::-webkit-scrollbar {
  width: 5px;
}

.sidebar-menu-scroll::-webkit-scrollbar-track {
  background: transparent;
}

.sidebar-menu-scroll::-webkit-scrollbar-thumb {
  background-color: rgba(148, 163, 184, 0.4);
  border-radius: 100vh;
}

.sidebar-menu-scroll::-webkit-scrollbar-thumb:hover {
  background-color: var(--bs-primary);
}

/* Logika pergeseran wujud KHUSUS Sidebar */
@media (min-width: 992px) {
  .sidebar {
    transform: translateX(0);
  }

  .sidebar.hide-desktop {
    transform: translateX(-100%);
  }
}

.icon {
  font-size: 4rem;
  border-radius: 50%;
  width: 80px;
  height: 80px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #e9ecef;
  border: 1px solid #cfcfcf;
}
</style>
