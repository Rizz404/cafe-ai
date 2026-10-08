/**
 * Alpine data for <x-ui.confirm-form>: a dialog that must be confirmed before
 * the form is submitted.
 */
export function confirmForm() {
    return {
        isOpen: false,

        open() {
            this.isOpen = true;
        },

        close() {
            this.isOpen = false;
        },
    };
}
