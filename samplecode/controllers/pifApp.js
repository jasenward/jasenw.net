/* 
 * DEPLOYMENT INSTRUCTIONS: Alter the url in pifAppConfig to be the path
 * to the local pifAppEntryPoint.php file
 * Instantial configuration variables and objects
 */
var app = angular.module('pifApp', []);
var pifAppConfig ={
    url:'https://www.jasenw.net/samplecode/services/pifService.php',
    restResponseMsg:"The service did not respond.",
    contentType:"application/x-www-form-urlencoded",
    defaultImageSrc:'https://www.jasenw.net/samplecode/images/genericSearchImage.png'
};

angular.module('pifApp', ['ngSanitize']).controller('personCtrl', function($scope,$http) {
    $scope.email = '';
    $scope.jRevision = 'Version 2019.09.26.1513';
    $scope.pifAppReply=pifAppConfig.defaultImageSrc;
    $scope.url=pifAppConfig.url;
    $scope.restResponseMsg=pifAppConfig.restResponseMsg;
    $scope.callService =function(){
        var rewriteEmail = $scope.email.replace(".",":");
        var req = {
         method: 'POST',
         url: pifAppConfig.url,
         headers: {
           'Content-Type': pifAppConfig.contentType
         },
         data: { 
            email: rewriteEmail
        }
        };
        console.log('Contacting the Service at [ '+$scope.url +'] with data '+req.data.email);
        $http(req).then(
            function successCallback(response) {
                console.log("Email Sent: "+req.data.email);
                console.log(response.data);
                console.log(response.status);
                var imgURL=decodeURIComponent(response.data.pifAppReply);
                if(imgURL.length>0){
                    $scope.pifAppReply=imgURL;
                }else{
                    $scope.pifAppReply=pifAppConfig.defaultImageSrc;
                }
              $scope.jarMessage=response.data.message;
            }, function errorCallback(response) {
                console.log(response.data);
                console.log(response.data);
                console.log(response.status);
                console.log(response.headers);
                console.log(response.config);
                $scope.pifAppReply=pifAppConfig.defaultImageSrc;
                $scope.message=response.data.message;
            });
        return true;
    };
    app.filter('pifAppReply', ['$sce', function($sce) {
    var div = document.createElement('div');
    return function(text) {
        div.innerHTML = text;
        return $sce.trustAsHtml(div.textContent);
    };
}]);
});