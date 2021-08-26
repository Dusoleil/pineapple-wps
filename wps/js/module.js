registerController('MainController', ['$api', '$scope', '$cookies', function($api, $scope, $cookies) {
    $scope.depsdone = false;
    $scope.depsgood = false;
    $scope.depserr = "";
    $scope.autoStopServices = $cookies.get("wpsmod-AutoStopServices") === 'true' ?? false;
    $scope.targetSelectEvent = 'ReaverTargetSelect';

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

registerController('WashController', ['$api', '$scope', '$rootScope', '$interval', function($api, $scope, $rootScope, $interval) {
    $scope.interfaces = [];
    $scope.selectedInterface = "";
    $scope.channel = "all";
    $scope.washTimeout = "15";
    $scope.scanResults = [];

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
                    channel: $scope.channel === 'all' ? '' : $scope.channel,
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
                    $scope.scanResults = [];
                    if(!response.error && response.length != 0)
                        $scope.scanResults = response;
                }
            );
        });

    $scope.selectTarget = (function(ap)
        {
            $scope.stopScan();
            $rootScope.$broadcast($scope.$parent.targetSelectEvent, ap, $scope.channel);
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
    $(window).bind("beforeunload",$scope.stopServices);
}]);

registerController('ReaverController', ['$api', '$scope', '$interval', function($api, $scope, $interval) {
    $scope.interfaces = [];
    $scope.selectedInterface = "";
    $scope.bssid = "";
    $scope.channel = "auto";
    $scope.pixieDust = false;
    $scope.sessions = [];
    $scope.crackResults = "";
    $scope.pin = "";
    $scope.password = "";

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

    let runReaver = (function(pin)
        {
            $api.request(
                {
                    module: 'wps',
                    action: 'reaverCrack',
                    interface: $scope.selectedInterface,
                    bssid: $scope.bssid,
                    channel: $scope.channel === 'auto' ? '' : $scope.channel,
                    pixie: pin ? false : $scope.pixieDust,
                    pin: pin
                },
                function(response)
                {
                    $scope.reaverSessions();
                }
            );
        });

    $scope.reaverCrack = (function()
        {
            runReaver("");
        });

    $scope.reaverPin = (function()
        {
            runReaver($scope.pin);
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
                    $scope.sessions = response.sessions;
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
                    if($scope.pin === '')
                        $scope.pin = response.pin;
                    $scope.password = response.pass;
                }
            );
        });

    $scope.downloadCrack = (function()
        {
            $api.request(
                {
                    module: 'wps',
                    action: 'downloadCrack',
                    bssid: $scope.bssid
                },
                function(response)
                {
                    if(!response.error)
                        window.open('/api/?download=' + response.download, "_blank");
                    console.log(response);
                    $scope.reaverSessions();
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

    $scope.changeSession = (function()
        {
            $scope.pin = '';
            $scope.password = '';
        });

    let onTargetSelect = (function(event, ap, channel)
        {
            $scope.bssid = ap.bssid;
            $scope.channel = channel==='all' ? 'auto' : channel;
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
    let targetSelectEvent = $scope.$on($scope.$parent.targetSelectEvent, onTargetSelect);
    $scope.$on('$destroy',targetSelectEvent);
    $scope.$on('$destroy',$scope.stopServices);
    $(window).bind("beforeunload",$scope.stopServices);
}]);
