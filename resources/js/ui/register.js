import { adminShell } from './admin-shell.js';
import { confirmForm } from './confirm-form.js';

/**
 * Registers the shared Alpine data providers. Called once from app.js before
 * Alpine.start().
 */
export function registerUi(Alpine) {
    Alpine.data('adminShell', adminShell);
    Alpine.data('confirmForm', confirmForm);
}
