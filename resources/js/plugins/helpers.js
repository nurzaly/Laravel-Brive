import { usePage } from "@inertiajs/vue3";

/**
 * Format an ISO date string as dd-mm-yyyy HH:mm in the viewer's timezone.
 */
export function formatDate(value) {
    if (!value) return "";
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    const pad = (n) => String(n).padStart(2, "0");
    return `${pad(date.getDate())}-${pad(date.getMonth() + 1)}-${date.getFullYear()} ${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

/**
 * Global template helpers: can(), lang() and formatDate().
 */
export default {
    install(app) {
        app.mixin({
            methods: {
                can(permissions) {
                    const allPermissions = this.$page.props.auth.can ?? {};
                    return permissions.some((item) => allPermissions[item]);
                },
                lang() {
                    return usePage().props.language;
                },
                formatDate,
            },
        });
    },
};
