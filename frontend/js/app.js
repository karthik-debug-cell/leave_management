angular.module('leaveflow', ['ngRoute'])

.config(function ($routeProvider, $locationProvider, $httpProvider) {
  $locationProvider.html5Mode(true);

  const guard = (role) => ({
    guard: ['$location', 'Auth', function ($location, Auth) {
      if (!Auth.isLoggedIn()) return $location.path('/login');
      if (role && Auth.user.role !== role) return $location.path(Auth.home());
    }],
  });

  $routeProvider
    .when('/login', { templateUrl: 'views/login.html', controller: 'LoginCtrl', controllerAs: 'vm' })
    .when('/employee/dashboard', { templateUrl: 'views/employee-dashboard.html', controller: 'EmpDashCtrl', controllerAs: 'vm', resolve: guard('employee') })
    .when('/employee/apply',     { templateUrl: 'views/employee-apply.html',     controller: 'ApplyCtrl',   controllerAs: 'vm', resolve: guard('employee') })
    .when('/employee/history',   { templateUrl: 'views/employee-history.html',   controller: 'HistoryCtrl', controllerAs: 'vm', resolve: guard('employee') })
    .when('/admin/dashboard', { templateUrl: 'views/admin-dashboard.html', controller: 'AdminDashCtrl',  controllerAs: 'vm', resolve: guard('admin') })
    .when('/admin/requests',  { templateUrl: 'views/admin-requests.html',  controller: 'RequestsCtrl',   controllerAs: 'vm', resolve: guard('admin') })
    .when('/admin/requests/:id', { templateUrl: 'views/admin-review.html', controller: 'ReviewCtrl',     controllerAs: 'vm', resolve: guard('admin') })
    .when('/admin/employees', { templateUrl: 'views/admin-employees.html', controller: 'EmployeesCtrl',  controllerAs: 'vm', resolve: guard('admin') })
    .when('/admin/reports',   { templateUrl: 'views/admin-reports.html',   controller: 'ReportsCtrl',    controllerAs: 'vm', resolve: guard('admin') })
    .otherwise({ redirectTo: '/login' });

  $httpProvider.interceptors.push(function ($q, $injector) {
    return {
      request(config) {
        const token = localStorage.getItem('lf_token');
        if (token && config.url.startsWith('/api')) config.headers.Authorization = 'Bearer ' + token;
        return config;
      },
      responseError(rej) {
        if (rej.status === 401 && !rej.config.url.endsWith('/auth/login')) {
          $injector.get('Auth').clear();
          $injector.get('$location').path('/login');
        }
        return $q.reject(rej);
      },
    };
  });
})

.run(function ($rootScope, $location, Auth) {
  // Redirect to the role's home page if already logged in and visiting /login
  $rootScope.$on('$routeChangeStart', (e, next) => {
    if (next.originalPath === '/login' && Auth.isLoggedIn()) $location.path(Auth.home());
  });
})

// Small shared helpers used in templates
.filter('statusClass', () => (s) => 'status-' + String(s || '').toLowerCase())
.factory('Flash', function ($rootScope, $timeout) {
  let t;
  return (type, message) => {
    $rootScope.$broadcast('flash', { type, message });
    $timeout.cancel(t);
    t = $timeout(() => $rootScope.$broadcast('flash', null), 5000);
  };
});
