/* EmPay — App JS: Sidebar toggle, live clock, flash auto-dismiss */

// ─── Sidebar Toggle ───
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    if (!sidebar) return;
    sidebar.classList.toggle('-translate-x-full');
    if (overlay) overlay.classList.toggle('hidden');
}

document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('sidebar-toggle');
    if (btn) btn.addEventListener('click', toggleSidebar);

    // ─── Live Clock ───
    const clockEl = document.getElementById('live-clock');
    if (clockEl) {
        const updateClock = () => {
            clockEl.textContent = new Date().toLocaleTimeString('en-IN', {
                hour: '2-digit', minute: '2-digit', second: '2-digit'
            });
        };
        updateClock();
        setInterval(updateClock, 1000);
    }

    // ─── Auto-dismiss flash messages after 5s ───
    setTimeout(() => {
        document.querySelectorAll('.flash-msg').forEach(el => {
            el.style.transition = 'opacity 0.3s, transform 0.3s';
            el.style.opacity = '0';
            el.style.transform = 'translateY(-10px)';
            setTimeout(() => el.remove(), 300);
        });
    }, 5000);
});
