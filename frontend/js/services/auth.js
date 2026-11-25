angular.module('leaveflow').factory('Auth', function ($http) {
  const saved = localStorage.getItem('lf_user');
  const svc = {
    user: saved ? JSON.parse(saved) : null,
    isLoggedIn: () => !!svc.user && !!localStorage.getItem('lf_token'),
    isAdmin: () => !!svc.user && svc.user.role === 'admin',
    home: () => (svc.isAdmin() ? '/admin/dashboard' : '/employee/dashboard'),
    login(email, password) {
      return $http.post('/api/auth/login', { email, password }).then((res) => {
        localStorage.setItem('lf_token', res.data.token);
        localStorage.setItem('lf_user', JSON.stringify(res.data.user));
        svc.user = res.data.user;
        return svc.user;
      });
    },
    clear() {
      localStorage.removeItem('lf_token');
      localStorage.removeItem('lf_user');
      svc.user = null;
    },
  };
  return svc;
});
