angular.module('leaveflow').controller('LoginCtrl', function ($location, Auth) {
  const vm = this;
  vm.form = {};
  vm.error = '';
  vm.loading = false;
  vm.fill = (email, pw) => { vm.form = { email, password: pw }; };
  vm.submit = () => {
    vm.error = ''; vm.loading = true;
    Auth.login(vm.form.email, vm.form.password)
      .then(() => $location.path(Auth.home()))
      .catch((e) => { vm.error = (e.data && e.data.error) || 'Login failed.'; })
      .finally(() => { vm.loading = false; });
  };
});
