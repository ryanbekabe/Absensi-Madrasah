    </main><!-- end .page-content -->
</div><!-- end .main-content -->
</div><!-- end .app-wrapper -->

<!-- Sidebar Overlay (mobile) -->
<div id="sidebarOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:999;" onclick="closeSidebar()"></div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<!-- Custom JS -->
<script src="<?= APP_URL ?>/assets/js/app.js"></script>

<script>
// Live clock
(function updateClock() {
    const el = document.getElementById('liveClock');
    if (el) {
        const now = new Date();
        const pad = n => String(n).padStart(2,'0');
        el.textContent = pad(now.getHours())+':'+pad(now.getMinutes())+':'+pad(now.getSeconds());
    }
    setTimeout(updateClock, 1000);
})();

// Auto-dismiss flash
setTimeout(() => {
    document.querySelectorAll('[id^="flash-alert"]').forEach(el => {
        el.style.transition = 'opacity 0.5s';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 500);
    });
}, 5000);
</script>
</body>
</html>
