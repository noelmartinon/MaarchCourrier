mainApp.controller("basketCtrl", ["$scope", "$http", "$compile", function($scope, $http, $compile) {

  $scope.getView = function(res_id, service, module) {

    $http({
      method : 'POST',
      url    : globalConfig.coreurl + 'rest.php?module=' + module + '&service=' + service + '&method=getViewDatas',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      data   : $j.param({
        resId : res_id
      })
    }).then(function successCallback(response) {
      var elem = angular.element(response.data.result.view);

      $j('#divList').html(elem);
      $scope.signatureBook = response.data.result.datas;
      $compile(elem)($scope);

    }, function errorCallback(response) {
      console.log(response);
    });
  };

  $scope.changeSignatureBookLeftContent = function(id) {
    $scope.signatureBook.headerTab = id;
  };

  $scope.changeRightViewer = function(index) {
    $scope.signatureBook.viewerLink = $scope.signatureBook.viewerAttachments[index].viewerLink;
    $scope.signatureBook.selectedThumbnail = index;
  };

}]);