<?php namespace pineapple;

class wps extends Module
{
    private $washlog = "/tmp/washscan.log";
    private $reaverlogdir = "/tmp/reavercrack/";

    public function route()
    {
        switch ($this->request->action) {
            case 'deps':
                $this->deps();
                break;
            case 'getInterfaces':
                $this->getInterfaces();
                break;
            case 'washScan':
                $this->washScan();
                break;
            case 'stopScan':
                $this->stopScan();
                break;
            case 'readScan':
                $this->readScan();
                break;
            case 'reaverCrack':
                $this->reaverCrack();
                break;
            case 'stopCrack':
                $this->stopCrack();
                break;
            case 'reaverSessions':
                $this->reaverSessions();
                break;
            case 'readCrack':
                $this->readCrack();
                break;
            case 'deleteCrack':
                $this->deleteCrack();
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

    private function washScan()
    {
        $this->stopScan();
        unlink($this->washlog);
        $cmd = "timeout ".$this->request->timeout ." wash -i ".$this->request->interface ." >".$this->washlog;
        $this->execBackground($cmd);
    }

    private function stopScan()
    {
        exec("killall -2 wash");
    }

    private function readScan()
    {
        $scan = @file_get_contents($this->washlog);
        if(!$scan)
            $scan = "No log found or log empty.  You need to perform a scan first!";
        $this->response = array("scan" => $scan);
    }

    private function reaverCrack()
    {
        $this->stopCrack();
        if(!file_exists($this->reaverlogdir))
            mkdir($this->reaverlogdir);
        $sess = str_replace(":", "", trim($this->request->bssid));
        $log = $this->reaverlogdir .$sess .".log";
        $sessfile = $this->reaverlogdir .$sess .".wpc";
        $cmd = "reaver -i ".$this->request->interface ." -b ".trim($this->request->bssid) ." -s ".$sessfile ." -vv >>".$log;
        $this->execBackground($cmd);
    }

    private function stopCrack()
    {
        exec("killall -2 reaver");
    }

    private function reaverSessions()
    {
        $sessions = array();
        $glob = glob($this->reaverlogdir .'*');
        if($glob)
        {
            foreach($glob as $file)
            {
                array_push($sessions, trim(chunk_split(pathinfo($file)['filename'],2,':'),':'));
            }
            $sessions = array_unique($sessions);
        }
        $this->response = array("sessions" => $sessions);
    }

    private function readCrack()
    {
        $sess = str_replace(":", "", trim($this->request->bssid));
        $log = $this->reaverlogdir .$sess .".log";
        $crack = '';
        if(file_exists($log))
            exec('tail -n 20 ' .$log, $crack);
        if($crack)
            $crack = join("\n",$crack);
        else
            $crack = "No log found or log empty.  You need to start a crack first!";
        $this->response = array("crack" => $crack);
    }

    private function deleteCrack()
    {
        $sess = str_replace(":", "", trim($this->request->bssid));
        $log = $this->reaverlogdir .$sess .".log";
        $sessfile = $this->reaverlogdir .$sess .".wpc";
        unlink($log);
        unlink($sessfile);
    }
}

?>
