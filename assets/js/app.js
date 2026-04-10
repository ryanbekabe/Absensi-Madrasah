/**
 * app.js — Sistem Absensi Sekolah
 * Vanilla JavaScript
 */

'use strict';

// ============================================================
// Sidebar Toggle
// ============================================================
function openSidebar() {
    const sb = document.getElementById('sidebar');
    const ov = document.getElementById('sidebarOverlay');
    if (sb) sb.classList.add('open');
    if (ov) ov.style.display = 'block';
}

function closeSidebar() {
    const sb = document.getElementById('sidebar');
    const ov = document.getElementById('sidebarOverlay');
    if (sb) sb.classList.remove('open');
    if (ov) ov.style.display = 'none';
}

document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('sidebarToggle');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            const sb = document.getElementById('sidebar');
            if (sb && sb.classList.contains('open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    }

    // ============================================================
    // Absensi Radio Button Toggle
    // ============================================================
    document.querySelectorAll('.absensi-student-card').forEach(card => {
        const radios   = card.querySelectorAll('.radio-status');
        const labels   = card.querySelectorAll('.radio-label');

        labels.forEach(label => {
            label.addEventListener('click', () => {
                const val = label.dataset.val;
                if (!val) return;

                // Clear semua selected class di card ini
                labels.forEach(l => {
                    l.className = l.className.replace(/selected-[HISA]/g, '').trim();
                });

                // Set radio value
                radios.forEach(r => {
                    if (r.value === val) r.checked = true;
                });

                // Tambahkan selected class
                label.classList.add('selected-' + val);
            });
        });

        // Inisialisasi dari nilai yang sudah ada
        radios.forEach(r => {
            if (r.checked) {
                const lbl = card.querySelector('.radio-label[data-val="' + r.value + '"]');
                if (lbl) lbl.classList.add('selected-' + r.value);
            }
        });
    });

    // ============================================================
    // Auto-submit search form on select change
    // ============================================================
    document.querySelectorAll('[data-auto-submit]').forEach(el => {
        el.addEventListener('change', () => {
            el.closest('form').submit();
        });
    });

    // ============================================================
    // Confirm Delete
    // ============================================================
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', (e) => {
            const msg = el.dataset.confirm || 'Yakin ingin menghapus data ini?';
            if (!confirm(msg)) {
                e.preventDefault();
            }
        });
    });

    // ============================================================
    // Preview Foto Upload
    // ============================================================
    document.querySelectorAll('[data-preview]').forEach(input => {
        input.addEventListener('change', function() {
            const previewId = this.dataset.preview;
            const preview   = document.getElementById(previewId);
            if (!preview) return;
            const file = this.files[0];
            if (!file) return;
            if (!file.type.startsWith('image/')) {
                alert('File harus berupa gambar (jpg, png, webp).');
                this.value = '';
                return;
            }
            if (file.size > 2 * 1024 * 1024) {
                alert('Ukuran file maksimal 2MB.');
                this.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = e => {
                preview.src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        });
    });

    // ============================================================
    // Table Row Click (navigasi ke detail)
    // ============================================================
    document.querySelectorAll('[data-href]').forEach(row => {
        row.style.cursor = 'pointer';
        row.addEventListener('click', (e) => {
            if (!e.target.closest('a, button, .actions')) {
                window.location.href = row.dataset.href;
            }
        });
    });

    // ============================================================
    // Copy to clipboard
    // ============================================================
    document.querySelectorAll('[data-copy]').forEach(btn => {
        btn.addEventListener('click', () => {
            const text = btn.dataset.copy;
            navigator.clipboard.writeText(text).then(() => {
                const orig = btn.innerHTML;
                btn.innerHTML = '<i class="bi bi-check2"></i>';
                setTimeout(() => btn.innerHTML = orig, 2000);
            });
        });
    });

    // ============================================================
    // Input Numeric only
    // ============================================================
    document.querySelectorAll('[data-numeric]').forEach(el => {
        el.addEventListener('input', () => {
            el.value = el.value.replace(/[^0-9]/g, '');
        });
    });
});

// ============================================================
// Toast Notification
// ============================================================
function showToast(message, type = 'success', duration = 4000) {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.style.cssText = `
            position: fixed; bottom: 24px; right: 24px; z-index: 9999;
            display: flex; flex-direction: column; gap: 8px; pointer-events: none;
        `;
        document.body.appendChild(container);
    }

    const icons = { success: 'check-circle-fill', danger: 'exclamation-circle-fill', warning: 'exclamation-triangle-fill', info: 'info-circle-fill' };
    const toast = document.createElement('div');
    toast.style.cssText = `
        background: var(--bg-card); border: 1px solid var(--border-color);
        border-radius: 10px; padding: 12px 16px; min-width: 260px; max-width: 360px;
        display: flex; align-items: center; gap: 10px; font-size: 13px;
        color: var(--text-primary); pointer-events: all;
        box-shadow: 0 8px 24px rgba(0,0,0,0.4);
        animation: slideDown 0.3s ease; opacity: 1; transition: opacity 0.4s;
    `;
    toast.innerHTML = `<i class="bi bi-${icons[type] || 'info-circle-fill'}" style="color:var(--${type === 'danger' ? 'danger' : type === 'warning' ? 'warning' : type === 'info' ? 'info' : 'success'})"></i><span>${message}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 400);
    }, duration);
}

// ============================================================
// Format angka dengan titik ribuan
// ============================================================
function formatNumber(n) {
    return new Intl.NumberFormat('id-ID').format(n);
}

// ============================================================
// Chart.js defaults (dark mode)
// ============================================================
if (typeof Chart !== 'undefined') {
    Chart.defaults.color = '#94a3b8';
    Chart.defaults.borderColor = 'rgba(255,255,255,0.06)';
    Chart.defaults.font.family = "'Plus Jakarta Sans', system-ui, sans-serif";
    Chart.defaults.font.size = 12;
    Chart.defaults.plugins.legend.display = true;
    Chart.defaults.plugins.tooltip.backgroundColor = '#1a1d27';
    Chart.defaults.plugins.tooltip.borderColor = 'rgba(255,255,255,0.1)';
    Chart.defaults.plugins.tooltip.borderWidth = 1;
    Chart.defaults.plugins.tooltip.titleColor = '#f1f5f9';
    Chart.defaults.plugins.tooltip.bodyColor = '#94a3b8';
    Chart.defaults.plugins.tooltip.padding = 10;
}
