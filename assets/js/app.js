// ============================================================
// assets/js/app.js — LeaveFlow Global JavaScript
// ============================================================

document.addEventListener('DOMContentLoaded', () => {

  // ---- Date Validation: end_date >= start_date ----
  const startDate = document.getElementById('start_date');
  const endDate   = document.getElementById('end_date');

  if (startDate && endDate) {
    // Set min value of start_date to today
    const today = new Date().toISOString().split('T')[0];
    startDate.setAttribute('min', today);

    startDate.addEventListener('change', () => {
      endDate.setAttribute('min', startDate.value);
      if (endDate.value && endDate.value < startDate.value) {
        endDate.value = startDate.value;
      }
      updateDayCount();
    });

    endDate.addEventListener('change', updateDayCount);

    // ---- Day Count Display ----
    function updateDayCount() {
      const counter = document.getElementById('day_count');
      if (!counter || !startDate.value || !endDate.value) return;

      const start  = new Date(startDate.value);
      const end    = new Date(endDate.value);
      const diff   = Math.round((end - start) / (1000 * 60 * 60 * 24)) + 1;

      counter.textContent = diff > 0 ? `${diff} day${diff > 1 ? 's' : ''}` : '—';
    }
  }

  // ---- Auto-dismiss alerts after 5 seconds ----
  const alerts = document.querySelectorAll('.alert.alert-dismissible');
  alerts.forEach(alert => {
    setTimeout(() => {
      const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
      bsAlert.close();
    }, 5000);
  });

  // ---- Confirm delete/reject dialogs ----
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => {
      if (!confirm(el.dataset.confirm)) e.preventDefault();
    });
  });

  // ---- Animate cards on load ----
  document.querySelectorAll('.stat-card, .card').forEach((card, i) => {
    card.classList.add('animate-in');
    card.style.animationDelay = `${i * 0.05}s`;
  });

});
