/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
var app = angular.module('jasenApp', []);
angular.module('jasenApp', ['ngSanitize']).controller('personCtrl', function($scope,$http) {
    $scope.bookNumber = '';
    $scope.book= 'John';
    $scope.chapter='3';
    $scope.verse='16';
    $scope.jRevision = 'Version 2019.09.25';
    $scope.bibleReference = function() {
        var bookNumber='';
        if($scope.bookNumber.length>0){
            bookNumber=$scope.bookNumber;
        }
        
        var result=bookNumber  + $scope.book + '.' + $scope.chapter + '.' + $scope.verse;
        return result;
    };
    $scope.url='/samplecode/services/bibleService.php';
    $scope.restResponseMsg="The service did not respond.";
    $scope.callService =function(){
        var req = {
         method: 'POST',
         url: '/samplecode/services/bibleService.php',
         headers: {
           'Content-Type': "application/x-www-form-urlencoded"
         },
         data: { 
            bRef: $scope.bibleReference()
        }
        };
        console.log('Contacting the Service at [ '+$scope.url +'] with data '+JSON.stringify(req.data));
        $http(req).then(
            function successCallback(response) {
              console.log(response.data);
              console.log(response.status);
              $scope.bibleReply=decodeURIComponent(response.data.replace(/\+/g, ' '));
            }, function errorCallback(response) {
              console.log(response.data);
              console.log(response.status);
              console.log(response.headers);
              console.log(response.config);
            });
        return true;
    };
    app.filter('bibleReply', ['$sce', function($sce) {
    var div = document.createElement('div');
    return function(text) {
        div.innerHTML = text;
        return $sce.trustAsHtml(div.textContent);
    };
}]);
});