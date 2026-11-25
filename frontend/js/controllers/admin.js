angular.module('leaveflow')

.controller('AdminDashCtrl', function (Api, Auth) {
  const vm = this;
  vm.user = Auth.user; vm.today = new Date();
  Api.adminDashboard().then((d) => Object.assign(vm, d));
})

.controller('RequestsCtrl', function (Api, $location) {
  const vm = this;
  vm.f = {};
  if ($location.search().user_id) vm.f.user_id = $location.search().user_id;
  vm.statuses = ['Pending', 'Approved', 'Rejected', 'Cancelled'];
  vm.types = ['Annual', 'Sick', 'Casual', 'Maternity', 'Paternity', 'Unpaid'];
  const clean = () => {
    const p = {};
    Object.entries(vm.f).forEach(([k, v]) => { if (v) p[k] = v instanceof Date ? v.toLocaleDateString('en-CA') : v; });
    return p;
  };
  vm.load = () => Api.requests(clean()).then((r) => { vm.leaves = r; });
  vm.reset = () => { vm.f = {}; vm.load(); };
  Api.employees().then((e) => { vm.employees = e; });
  vm.load();
})

.controller('ReviewCtrl', function ($routeParams, $location, Api, Flash) {
  const vm = this;
  vm.comment = '';
  Api.request($routeParams.id).then((l) => { vm.leave = l; })
    .catch(() => { Flash('danger', 'Leave request not found.'); $location.path('/admin/requests'); });
  vm.decide = (status) => {
    Api.review(vm.leave.id, status, vm.comment).then(() => {
      Flash('success', 'Leave request has been ' + status + ' successfully.');
      $location.path('/admin/requests');
    }).catch((e) => Flash('danger', (e.data && e.data.error) || 'Update failed.'));
  };
})

.controller('EmployeesCtrl', function (Api, Flash) {
  const vm = this;
  vm.showForm = false;
  vm.form = { role: 'employee' };
  vm.load = () => Api.employees().then((e) => { vm.employees = e; });
  vm.save = () => {
    vm.error = '';
    Api.addEmployee(vm.form).then((u) => {
      Flash('success', "User '" + u.name + "' created successfully.");
      vm.form = { role: 'employee' }; vm.showForm = false; vm.load();
    }).catch((e) => { vm.error = (e.data && e.data.error) || 'Could not create user.'; });
  };
  vm.load();
})

.controller('ReportsCtrl', function (Api) {
  const vm = this;
  const now = new Date();
  vm.f = { from: new Date(now.getFullYear(), now.getMonth(), 1), to: now, user_id: '' };
  const iso = (d) => d.toLocaleDateString('en-CA');
  vm.load = () => {
    const p = { from: iso(vm.f.from), to: iso(vm.f.to) };
    if (vm.f.user_id) p.user_id = vm.f.user_id;
    Api.report(p).then((r) => { vm.report = r; });
  };
  Api.employees().then((e) => { vm.employees = e; });
  vm.load();
});
