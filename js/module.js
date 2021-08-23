registerController('MainController', ['$api', '$scope', function($api, $scope) {
    $scope.greeting = "";
    $scope.content = "";

    $scope.depsdone = false;
    $scope.depsgood = false;
    $scope.depserr = "";

    $api.request(
        {
            module: 'wps',
            action: 'deps'
        },
        function(response)
        {
            $scope.depsdone = true;
            $scope.depsgood = response.deps;
            $scope.depserr = response.error;
            console.log(response)
        }
    );

    $api.request({
        module: 'wps',
        action: 'getContents'
    }, function(response) {
        if (response.success === true) {
            $scope.greeting = response.greeting;
            $scope.content = response.content;
        }
        console.log(response)
    });
}]);
