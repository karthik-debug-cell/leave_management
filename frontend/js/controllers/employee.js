const dayDiff = (a, b) => Math.round((new Date(b) - new Date(a)) / 86400000) + 1;
const todayISO = () => new Date().toLocaleDateString('en-CA');

angular.module('leaveflow')

.controller('EmpDashCtrl', function (Api, Auth) {
  const vm = this;
  vm.user = Auth.user;
  vm.today = new Date();
  Api.empDashboard().then((d) => Object.assign(vm, d));
})

.controller('ApplyCtrl', function ($location, Api, Flash) {
  const vm = this;
  vm.today = todayISO();                       // string for the min="" attribute
  const iso = (d) => (d ? d.toLocaleDateString('en-CA') : null);
  vm.error = '';
  vm.saving = false;
  vm.types = [];
  vm.form = { leave_type: '', start_date: null, end_date: null, is_half_day: false,
              half_day_session: 'First Half', reason: '', contact_number: '' };

  Api.leaveTypes().then((t) => { vm.types = t; });

  vm.balance = () => vm.types.find((t) => t.leave_type === vm.form.leave_type);
  vm.days = () => {
    const f = vm.form;
    if (f.is_half_day) return f.start_date ? 0.5 : null;
    if (!f.start_date || !f.end_date) return null;
    const n = dayDiff(f.start_date, f.end_date);
    return n > 0 ? n : null;
  };
  vm.insufficient = () => {
    const b = vm.balance(), d = vm.days();
    return !!(b && b.remaining !== null && d && d > b.remaining);
  };

  // Keep end date in sync
  vm.startChanged = () => {
    const f = vm.form;
    if (f.is_half_day || !f.end_date || f.end_date < f.start_date) f.end_date = f.start_date;
  };
  vm.halfToggled = () => { if (vm.form.is_half_day) vm.form.end_date = vm.form.start_date; };

  vm.submit = () => {
    vm.error = '';
    const f = vm.form;
    if (!f.leave_type || !f.start_date || !f.reason || (!f.is_half_day && !f.end_date)) {
      vm.error = 'Please fill in all required fields.'; return;
    }
    if (vm.insufficient()) { vm.error = 'Your remaining balance for this leave type is not enough.'; return; }
    vm.saving = true;
    Api.apply({
      leave_type: f.leave_type, start_date: iso(f.start_date),
      end_date: iso(f.is_half_day ? f.start_date : f.end_date),
      is_half_day: f.is_half_day,
      half_day_session: f.is_half_day ? f.half_day_session : null,
      reason: f.reason, contact_number: f.contact_number || null,
    }).then(() => {
      Flash('success', 'Your leave request has been submitted successfully!');
      $location.path('/employee/history');
    }).catch((e) => { vm.error = (e.data && e.data.error) || 'Could not submit request.'; })
      .finally(() => { vm.saving = false; });
  };
})

.controller('HistoryCtrl', function (Api, Flash) {
  const vm = this;
  vm.filter = '';
  vm.statuses = ['Pending', 'Approved', 'Rejected', 'Cancelled'];
  vm.load = () => Api.myLeaves(vm.filter || undefined).then((r) => { vm.leaves = r; });
  vm.cancel = (l) => {
    if (!confirm('Cancel this leave request?')) return;
    Api.cancel(l.id).then(() => { Flash('success', 'Leave request cancelled.'); vm.load(); })
      .catch((e) => Flash('danger', (e.data && e.data.error) || 'Could not cancel.'));
  };
  vm.load();
});
