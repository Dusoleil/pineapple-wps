registerController('MainController', ['$api', '$scope', '$cookies', function($api, $scope, $cookies) {
    $scope.depsdone = false;
    $scope.depsgood = false;
    $scope.depserr = "";
    $scope.autoStopServices = $cookies.get("wpsmod-AutoStopServices") === 'true' ?? false;

    $scope.toggleAutoStopServices = (function()
        {
            $cookies.put("wpsmod-AutoStopServices", $scope.autoStopServices);
        });

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

registerController('WashController', ['$api', '$scope', '$interval', function($api, $scope, $interval) {
    $scope.interfaces = [];
    $scope.selectedInterface = "";
    $scope.washTimeout = 15;
    $scope.scanResults = "";

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

    $scope.stopScan = (function()
        {
            $api.request(
                {
                    module: 'wps',
                    action: 'stopScan'
                },
                function(response)
                {
                }
            );
        });

    $scope.readScan = (function()
        {
            $api.request(
                {
                    module: 'wps',
                    action: 'readScan'
                },
                function(response)
                {
                    $scope.scanResults = response.scan;
                }
            );
        });

    $scope.stopServices = (function()
        {
            if($scope.$parent.autoStopServices)
                $scope.stopScan();
            $interval.cancel(scanintervalpromise);
        });

    $scope.getInterfaces();
    $scope.readScan();
    let scanintervalpromise = $interval($scope.readScan,1000);
    $scope.$on('$destroy',$scope.stopServices);
}]);

registerController('ReaverController', ['$api', '$scope', '$interval', function($api, $scope, $interval) {
    $scope.interfaces = [];
    $scope.selectedInterface = "";
    $scope.bssid = "";
    $scope.sessions = [];
    $scope.crackResults = "";

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

    $scope.reaverCrack = (function()
        {
            $api.request(
                {
                    module: 'wps',
                    action: 'reaverCrack',
                    interface: $scope.selectedInterface,
                    bssid: $scope.bssid
                },
                function(response)
                {
                    $scope.reaverSessions();
                }
            );
        });

    $scope.stopCrack = (function()
        {
            $api.request(
                {
                    module: 'wps',
                    action: 'stopCrack'
                },
                function(response)
                {
                    $scope.reaverSessions();
                }
            );
        });

    $scope.reaverSessions = (function()
        {
            $api.request(
                {
                    module: 'wps',
                    action: 'reaverSessions'
                },
                function(response)
                {
                    if(!response.error)
                        $scope.sessions = response;
                    else
                        $scope.sessions = [];
                    console.log(response);
                }
            );
        });

    $scope.readCrack = (function()
        {
            $api.request(
                {
                    module: 'wps',
                    action: 'readCrack',
                    bssid: $scope.bssid
                },
                function(response)
                {
                    $scope.crackResults = response.crack;
                }
            );
        });

    $scope.deleteCrack = (function()
        {
            $api.request(
                {
                    module: 'wps',
                    action: 'deleteCrack',
                    bssid: $scope.bssid
                },
                function(response)
                {
                    $scope.reaverSessions();
                }
            );
        });

    $scope.stopServices = (function()
        {
            if($scope.$parent.autoStopServices)
                $scope.stopCrack();
            $interval.cancel(crackintervalpromise);
        });

    $scope.getInterfaces();
    $scope.readCrack();
    $scope.reaverSessions();
    let crackintervalpromise = $interval($scope.readCrack,1000);
    $scope.$on('$destroy',$scope.stopServices);
}]);
