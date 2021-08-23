<?php namespace pineapple;

class wps extends Module
{
    public function route()
    {
        switch ($this->request->action) {
            case 'deps':
                $this->deps();
                break;
            case 'getInterfaces':
                $this->getInterfaces();
                break;
        }
    }

    private function checkConnection()
    {
        $conn = @fsockopen("www.wifipineapple.com",80);
        if($conn)
        {
            fclose($conn);
            return true;
        }
        return false;
    }

    private function handleDepList($deps, $install)
    {
        $goodtogo = true;
        $error = "";
        if($install && !$this->checkConnection())
        {
            $goodtogo = false;
            $error = "No Internet Connection";
        }
        else
        {
            foreach($deps as $dep)
            {
                $chk = $dep;
                $pck = $dep;
                if(is_array($dep))
                {
                    $chk = $dep[0];
                    $pck = $dep[1];
                }
                if($install)
                {
                    $goodtogo = $this->installDependency($pck);
                    if($chk != $pck)
                        $goodtogo = $this->checkDependency($chk);
                }
                else
                {
                    $goodtogo = $this->checkDependency($chk);
                }
                if(!$goodtogo)
                {
                    if($this->checkRunning("opkg"))
                    {
                        $error = "opkg already running";
                    }
                    else
                    {
                        $error = "opkg failure";
                    }
                    break;
                }
            }
        }
        return array("deps" => $goodtogo, "error" => $error);
    }

    private function deps()
    {
        $deps = array(array("timeout","coreutils-timeout"),"reaver","pixiewps");
        $ret = $this->handleDepList($deps, false);
        if(!$ret['deps'])
        {
            $ret = $this->handleDepList($deps, true);
            //reaver's wash requires a libpcap .so that isn't there in the default libpcap on the pineapple
            //openwrt's newer libpcap package adds a symlink for the missing library to the installed libpcap library
            if($ret['deps'])
                exec("opkg upgrade libpcap");
        }
        $this->response = $ret;
    }

    private function getInterfaces()
    {
        $interfaces = array();
        exec("iwconfig 2>/dev/null | grep 'wlan' | grep 'mon' | awk '{print $1}'",$interfaces);
        $this->response = array("interfaces" => $interfaces);
    }
}

?>
