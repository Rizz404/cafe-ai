/**
 * Alpine data for the admin frame: the mobile menu drawer.
 */
export function adminShell() {
    return {
        drawerOpen: false,

        openDrawer() {
            this.drawerOpen = true;
        },

        closeDrawer() {
            this.drawerOpen = false;
        },
    };
}
