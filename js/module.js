registerController('MainController', ['$api', '$scope', function($api, $scope) {
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
}]);

registerController('WashController', ['$api', '$scope', function($api, $scope) {
    $scope.interfaces = [];
    $scope.selectedInterface = "";
    $scope.washTimeout = 15;

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

    $scope.washScan = (function()
        {
            $api.request(
                {
                    module: 'wps',
                    action: 'washScan',
                    interface: $scope.selectedInterface,
                    timeout: $scope.washTimeout
                },
                function(response)
                {
                }
            );
        });

    $scope.getInterfaces();
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

    $scope.getInterfaces();
}]);
