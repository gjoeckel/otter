/**
 * SimpleMessaging - minimal DOM message utility
 */
class SimpleMessaging {
    static show(id, message, type = 'info') {
        const el = document.getElementById(id);
        if (!el) return;
        el.className = `message ${type}-message`;
        el.textContent = message;
        el.style.display = 'block';
        if (type === 'info') {
            setTimeout(() => { el.style.display = 'none'; }, 5000);
        }
    }
    static hide(id) {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    }
    static error(id, message) { this.show(id, message, 'error'); }
    static success(id, message) { this.show(id, message, 'success'); }
    static warning(id, message) { this.show(id, message, 'warning'); }
    static info(id, message) { this.show(id, message, 'info'); }
}
if (typeof module !== 'undefined') module.exports = SimpleMessaging;
