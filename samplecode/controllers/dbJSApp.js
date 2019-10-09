/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
var app = angular.module('jasenApp', []);
angular.module('jasenApp', []).controller('personCtrl', function($scope,$http) {
    $scope.names = [
        {name:'Jasen',country:'United States'},
        {name:'Hege',country:'Sweden'},
        {name:'Kai',country:'Denmark'}
    ];
    $scope.firstName= '';
    $scope.lastName = '';
    $scope.companyName = '';
    $scope.jRevision = 'Version 2019-09-26.1348';
    $scope.fullName = function() {
        var result="";
        var companyLength=''+$scope.companyName;
        
        if(companyLength.length>0){
            result= $scope.firstName + " " + $scope.lastName + " from ";
        }else{
            result= $scope.firstName + " " + $scope.lastName;
        }
        return result;
    };
    
    $scope.fullNameArray =function(a){
        m=a+1;
        return $scope.names[a].name + " / " + $scope.names[a+1].country;
    };
    $scope.url='/samplecode/services/dbService.php';
    $scope.restResponseMsg="The service did not respond.";
    $scope.callService =function(firstN,lastN,companyN){
        var req = {
         method: 'POST',
         url: '/samplecode/services/dbService.php',
         headers: {
           'Content-Type': "application/x-www-form-urlencoded"
         },
         data: { 
            fName: firstN,
            lName: lastN,
            cName: companyN
        }
        };
        console.log('Contacting the Service at [ '+$scope.url +'] with data '+JSON.stringify(req.data));
        $http(req).then(
            function successCallback(response) {
              console.log(response.data);
              console.log(response.status);
              /** $scope.records=[
                "Alfreds Futterkiste",
                "Berglunds snabbköp",
                "Centro comercial Moctezuma",
                "Ernst Handel",
              ];
              */
              $scope.records = response.data.prt3;
            }, function errorCallback(response) {
              console.log(response.data);
              console.log(response.status);
              console.log(response.headers);
              console.log(response.config);
              $scope.records = response.data.prt3;
            });
        return true;
    };
});