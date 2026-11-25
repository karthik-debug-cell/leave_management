angular.module('leaveflow').controller('ShellCtrl', function ($scope, $location, Auth) {
  const vm = this;
  vm.auth = Auth;
  vm.flash = null;
  vm.is = (p) => $location.path().startsWith(p);
  vm.logout = () => { Auth.clear(); $location.path('/login'); };
  $scope.$on('flash', (e, f) => { vm.flash = f; });
});
