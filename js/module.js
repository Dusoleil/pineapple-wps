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

registerController('WashController', ['$api', '$scope', function($api, $scope) {
    $scope.interfaces = [];
    $scope.selectedInterface = "";

    $scope.getInterfaces = (function()
        {
            $api.request(
                {
                    module: 'wps',
                    action: 'getInterfaces'
                },
                function(response)
                {
                    $scope.interfaces = response.interfaces;
                    $scope.selectedInterface = $scope.interfaces[0];
                    console.log(response)
                }
            );
        });
}]);

registerController('ReaverController', ['$api', '$scope', function($api, $scope) {
    $scope.interfaces = [];
    $scope.selectedInterface = "";

    $scope.getInterfaces = (function()
        {
            $api.request(
                {
                    module: 'wps',
                    action: 'getInterfaces'
                },
                function(response)
                {
                    $scope.interfaces = response.interfaces;
                    $scope.selectedInterface = $scope.interfaces[0];
                    console.log(response)
                }
            );
        });
}]);
