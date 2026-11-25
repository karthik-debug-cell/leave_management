angular.module('leaveflow').factory('Api', function ($http) {
  const d = (r) => r.data;
  return {
    // employee
    empDashboard: () => $http.get('/api/leaves/dashboard').then(d),
    leaveTypes:   () => $http.get('/api/leaves/types').then(d),
    myLeaves:     (status) => $http.get('/api/leaves', { params: { status } }).then(d),
    apply:        (body) => $http.post('/api/leaves', body).then(d),
    cancel:       (id) => $http.patch('/api/leaves/' + id + '/cancel').then(d),
    // admin
    adminDashboard: () => $http.get('/api/admin/dashboard').then(d),
    requests:       (params) => $http.get('/api/admin/requests', { params }).then(d),
    request:        (id) => $http.get('/api/admin/requests/' + id).then(d),
    review:         (id, status, admin_comment) => $http.patch('/api/admin/requests/' + id, { status, admin_comment }).then(d),
    employees:      () => $http.get('/api/admin/employees').then(d),
    addEmployee:    (body) => $http.post('/api/admin/employees', body).then(d),
    report:         (params) => $http.get('/api/admin/reports', { params }).then(d),
  };
});
