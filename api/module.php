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
        if ($conn)
        {
            fclose($conn);
            return true;
        }
        return false;
    }

    private function deps()
    {
        $goodtogo = false;
        $error = "";
        $reaver = $this->checkDependency("reaver");
        $pixie = $this->checkDependency("pixiewps");
        $goodtogo = $reaver && $pixie;
        if (!$goodtogo)
        {
            if ($this->checkConnection())
            {
                $reaver = $this->installDependency("reaver");
                //reaver's wash requires a libpcap .so that isn't there in the default libpcap on the pineapple
                //openwrt's newer libpcap package adds a symlink for the missing library to the installed libpcap library
                exec("opkg upgrade libpcap");
                $pixie = $this->installDependency("pixiewps");
                $goodtogo = $reaver && $pixie;
                if (!$goodtogo)
                {
                    if ($this->checkRunning("opkg"))
                    {
                        $error = "opkg already running";
                    }
                    else
                    {
                        $error = "opkg failure";
                    }
                }
            }
            else
            {
                $error = "No Internet Connection";
            }
        }
        $this->response = array("deps" => $goodtogo, "error" => $error);
    }

    private function getInterfaces()
    {
        $interfaces = array();
        exec("iwconfig 2>/dev/null | grep 'wlan' | grep 'mon' | awk '{print $1}'",$interfaces);
        $this->response = array("interfaces" => $interfaces);
    }
}

?>
