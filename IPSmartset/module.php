<?
class IPSmartset extends IPSModule{
    
    public function Create(){
        parent::Create();
        
        $this->RegisterPropertyString("host", "192.168.0.1");
        $this->RegisterPropertyString("port", 5000);
        $this->RegisterPropertyString("pollDevices", "");
        
        if (!IPS_VariableProfileExists('IPSM.State')) {
            IPS_CreateVariableProfile('IPSM.State', 1);
            IPS_SetVariableProfileAssociation('IPSM.State', 0, 'Unbestimmt', '', -1);
            IPS_SetVariableProfileAssociation('IPSM.State', 1, 'OK', '', -1);
            IPS_SetVariableProfileAssociation('IPSM.State', 2, 'Offline', '', -1);
            IPS_SetVariableProfileAssociation('IPSM.State', 3, 'Authentifikation fehlgeschlagen', '', -1);
            IPS_SetVariableProfileAssociation('IPSM.State', 4, 'Access-Token aktualisieren', '', -1);
            IPS_SetVariableProfileAssociation('IPSM.State', 5, 'Access-Token fehlerhaft', '', -1);
        }
        
        $smartsetStateID = $this->CreateVariableByIdent($this->InstanceID, "smartsetState", "Smartset Status", 1, "IPSM.State", 0, 100);
        
        $updateVarsID = $this->CreateEventByIdent($this->InstanceID, "updateVarsEvent", "UpdateVars", 1, $position = 101);
        IPS_SetEventActive($updateVarsID, false);
        IPS_SetEventCyclic($updateVarsID, 0, 0, 0, 0, 2, 1);
        IPS_SetEventScript($updateVarsID, 'IPSM_UpdateVars('.$this->InstanceID.');');
        
        $updateSessionID = $this->CreateEventByIdent($this->InstanceID, "updateSessionEvent", "UpdateSession", 1, $position = 102);
        IPS_SetEventActive($updateSessionID, false);
        IPS_SetEventCyclic($updateSessionID, 0, 0, 0, 0, 2, 1);
        IPS_SetEventScript($updateSessionID, 'IPSM_UpdateSession('.$this->InstanceID.');');
        
        $updateConnectionID = $this->CreateEventByIdent($this->InstanceID, "updateConnectionEvent", "UpdateConnection", 1, $position = 103);
        IPS_SetEventActive($updateConnectionID, false);
        IPS_SetEventCyclic($updateConnectionID, 0, 0, 0, 0, 3, 12);
        IPS_SetEventScript($updateConnectionID, 'IPSM_UpdateConnection('.$this->InstanceID.');');
        
        $changeWfValScrID   = $this->CreateScriptByIdent($this->InstanceID, "changeScr", "changeScr", $position = 104);
        $scriptContent      = '<? IPSM_WriteParameterValue('.$this->InstanceID.', $_IPS["VARIABLE"], $_IPS["VALUE"]);?>';
        IPS_SetScriptContent($changeWfValScrID, $scriptContent);
        
    }
    
    public function ApplyChanges(){
        parent::ApplyChanges();
    }
    
    public function Test(){
        //$this->UpdateConnection();
        //$this->GetGuiDescriptionForGateway(37282 /*[Scripte\Test\IPSmartset\WOLFLINK]*/, 1,1);
        //$this->UpdateVars();
        
        //$this->Logon();
        /*
         $url            = $this->GetURL("observeLocalGateways");
         $response       = $this->send($url.'?'.$this->addTime(), "", "GET");
         */
    }
    
    public function UpdateVars(){
        $pollDevicesJson   = $this->ReadPropertyString("pollDevices");
        $pollDevices       = json_decode($pollDevicesJson);
        foreach($pollDevices as $pollDevice){
            if($gatewayTagID = @IPS_GetObjectIDByIdent("gatewayTag", $pollDevice->varID)){
                $systemID  = GetValueString(IPS_GetObjectIDByIdent("id", $pollDevice->varID));
                $gatewayID = GetValueString(IPS_GetObjectIDByIdent("gatewayID", $pollDevice->varID));
                
                foreach(IPS_GetChildrenIDs($pollDevice->varID) as $childID){ //GetGuiDescription
                    if(IPS_GetObject($childID)["ObjectType"] == 0){
                        $hasGuiDescription = true;
                        break;
                    }
                }
                
                if(!@$hasGuiDescription){
                    $this->GetGuiDescriptionForGateway($pollDevice->varID, $gatewayID, $systemID);
                    $hasGuiDescription = false;
                }
                $this->GetAllVars($pollDevice->varID, $gatewayID, $systemID);
            }
        }
    }
    
    public function GetAllVars($parentID, $gatewayID, $systemID){
        foreach(IPS_GetChildrenIDs($parentID) as $childID){
            $childObj = IPS_GetObject($childID);
            if($childObj["ObjectType"] == 0){
                if(strpos($childObj["ObjectIdent"], "bundleID") !== false){
                    $this->GetParameterValues($childID, $gatewayID, $systemID);
                }
            }
            if(IPS_HasChildren($childID)){
                $this->GetAllVars($childID, $gatewayID, $systemID);
            }
        }
    }
    
    private function GetParameterValues($parentID, $gatewayID, $systemID){
        $parentObj = IPS_GetObject($parentID);
        if(strpos($parentObj["ObjectIdent"], "bundleID") !== false){
            $bundle = array(
                "id" => str_replace ("bundleID_", "", $parentObj["ObjectIdent"])
            );
            foreach(IPS_GetChildrenIDs($parentID) as $childID){
                $childObj = IPS_GetObject($childID);
                if(strpos($childObj["ObjectIdent"], "valueID") !== false){
                    $bundle["values"][] = str_replace ("valueID_", "", $childObj["ObjectIdent"]);
                }
                if($childObj["ObjectIdent"] == "lastAccess"){
                    $bundle["lastAccess"] = GetValueString($childID);
                }
            }
        }
        $url    = $this->GetURL("getParameterValues");
        if(!isset($bundle["lastAccess"]) || empty($bundle["lastAccess"])){
            $bundle["lastAccess"] = null;
        }
        if(isset($bundle["values"])){
            $data   = array(
                "BundleId"		=> $bundle["id"],
                "IsSubBundle" 	=> false,
                "ValueIdList" 	=> $bundle["values"],
                "GatewayId" 	=> $gatewayID,
                "SystemId" 		=> $systemID,
                "LastAccess" 	=> $bundle["lastAccess"],
                "GuiIdChanged" 	=> true
            );
            $send   = $this->send($url, $data, "POST");
            if(isset($send["response"]["Values"])){
                foreach($send["response"]["Values"] as $value){
                    $ipsValueID = IPS_GetObjectIDByIdent("valueID_".$value["ValueId"], $parentID);
                    if($value["Value"] != null){
                        SetValue($ipsValueID, $value["Value"]);
                    }
                }
                $lastAccessID = $this->CreateVariableByIdent($parentID, "lastAccess", "Letzter Zugriff", 3, "", "", 100);
                SetValueString($lastAccessID, $send["response"]["LastAccess"]);
                IPS_SetHidden($lastAccessID, true);
            }
        }
    }
    
    public function UpdateConnection(){
        $this->Logout();
        $pollDevicesJson   = $this->ReadPropertyString("pollDevices");
        $pollDevices       = json_decode($pollDevicesJson);
        foreach($pollDevices as $pollDevice){
            if($gatewayTagID = @IPS_GetObjectIDByIdent("gatewayTag", $pollDevice->varID)){
                $this->ConnectGateway($pollDevice->varID, $pollDevice->password);
                $this->GetGuiDescriptionForGateway($pollDevice->varID);
            }
        }
    }
    
    public function Logout(){
        $url 		= $this->GetURL("closeSystem", true);
        $data		= '{}';
        $response   = $this->send($url, $data, "POST");
        
        $url 		= $this->GetURL("logout", true);
        $data		= '';
        $response   = $this->send($url.'?'.$this->addTime(), "", "GET");
    }
    
    public function WriteParameterValue($valueID, $value){
        $parentID  = IPS_GetParent($valueID);
        $parentObj = IPS_GetObject($parentID);
        $valueObj  = IPS_GetObject($valueID);
        if(strpos($parentObj["ObjectIdent"], "bundleID") !== false && strpos($valueObj["ObjectIdent"], "valueID") !== false){
            $url       = $this->GetURL("writeParameterValues");
            $setValue[]   = array(
                "ValueId"       => str_replace ("valueID_", "", $valueObj["ObjectIdent"]),
                "Value"         => $value,
                "ParameterName" => $valueObj["ObjectName"]
            );
            
            $data = array(
                "WriteParameterValues" => $setValue,
                "SystemId"             => 1,
                "GatewayId"            => 1,
                "BundleId"             => str_replace("bundleID_", "", $parentObj["ObjectIdent"])
            );
            $send = $this->send($url, $data, "POST");
            if($send["code"] == 200){
                SetValue($valueID, $value);
            }
        }
    }
    
    public function SaveResponse(){
        $pollDevicesJson   = $this->ReadPropertyString("pollDevices");
        $pollDevices       = json_decode($pollDevicesJson);
        foreach($pollDevices as $pollDevice){
            if($systemID   = @GetValueString(@IPS_GetObjectIDByIdent("id", $pollDevice->varID)) &&
                $gatewayID  = @GetValueString(@IPS_GetObjectIDByIdent("gatewayID", $pollDevice->varID))){
                    if($systemID > 0 && $gatewayID > 0){
                        $url	= $this->GetURL("getGuiDescriptionForGateway");
                        $send   = $this->send($url.'?GatewayId='.$gatewayID.'&SystemId='.$systemID.'&'.$this->addTime(), "", "GET");
                        if($send["code"] == 200){
                            $docID = IPS_CreateMedia(5);
                            IPS_SetName($docID, "Antwortdatei_".IPS_GetName($pollDevice->varID));
                            IPS_SetParent($docID, $pollDevice->varID);
                            IPS_SetMediaFile($docID, "media/".$docID.".txt", False);
                            IPS_SetMediaContent($docID, base64_encode(json_encode($send["response"])));
                        }
                    }else{
                        echo "Fehler";
                    }
            }else{
                echo "Fehler";
            }
        }
    }
    
    public function GetGuiDescriptionForGateway($gatewayCatID){
        $systemID   = @GetValueString(@IPS_GetObjectIDByIdent("id", $gatewayCatID));
        $gatewayID  = @GetValueString(@IPS_GetObjectIDByIdent("gatewayID", $gatewayCatID));
        
        if($systemID > 0 && $gatewayID > 0){
            $url	= $this->GetURL("getGuiDescriptionForGateway");
            $send   = $this->send($url.'?GatewayId='.$gatewayID.'&SystemId='.$systemID.'&'.$this->addTime(), "", "GET");
            $this->BuildTree($send["response"], $gatewayCatID);
        }else{
            echo "Fehler";
        }
    }
    
    private function BuildTree($data, $parentID){
        if(isset($data["MenuItems"])){
            foreach($data["MenuItems"] as &$menuItem){
                $nextParentID  = $this->CreateCategoryByIdent($parentID, $menuItem["SortId"], $menuItem["Name"]);
                $this->BuildTree($menuItem, $nextParentID);
            }
        }
        if(isset($data["SubMenuEntries"])){
            foreach($data["SubMenuEntries"] as &$subMenuEntries){
                $nextParentID  = $this->CreateCategoryByIdent($parentID, $subMenuEntries["SortId"], $subMenuEntries["Name"]);
                $this->BuildTree($subMenuEntries, $nextParentID);
            }
        }
        if(isset($data["TabViews"])){
            foreach($data["TabViews"] as &$tabViews){
                if($varID = @IPS_GetVariableIDByName($tabViews["TabName"], $parentID)){
                    IPS_SetIdent($varID, "bundleID_".$tabViews["BundleId"]);
                } else {
                    $nextParentID  = $this->CreateCategoryByIdent($parentID, "bundleID_".$tabViews["BundleId"], $tabViews["TabName"]);
                }
                $this->BuildTree($tabViews, $nextParentID);
            }
        }
        if(isset($data["ParameterDescriptors"])){
            foreach($data["ParameterDescriptors"] as &$parameterDescriptors){
                switch(intval($parameterDescriptors["ControlType"])){
                    default : $varType = 100;  break; // No Type
                    case 0  : $varType = 1;    break; // Array
                    case 3  : $varType = 1;    break; // Array
                    case 6  :
                        switch(intval($parameterDescriptors["Decimals"])){
                            case 0 : $varType = 1;break; // Integer
                            case 1 : $varType = 2;break; // Float
                        }
                        break;
                    case 10 :
                        $varType = 3; // String
                        break;
                        
                }
                //VarID aktualisieren
                if($varID = @IPS_GetVariableIDByName($parameterDescriptors["Name"], $parentID)){
                    IPS_SetIdent($varID, "valueID_".$parameterDescriptors["ValueId"]);
                } else {
                    if(isset($varType) && $varType >= 0 && $varType <= 3){
                        $nextParentID = $this->CreateVariableByIdent($parentID, "valueID_".$parameterDescriptors["ValueId"], $parameterDescriptors["Name"], $varType);
                        if($parameterDescriptors["IsReadOnly"] != 1){
                            IPS_SetVariableCustomAction($nextParentID, IPS_GetObjectIDByIdent("changeScr", $this->InstanceID));
                        }
                        
                        // Profile
                        if(!is_array($parameterDescriptors["ListItems"])){
                            if(strpos($parameterDescriptors["Unit"], "°C")){
                                switch($varType){
                                    case 1 : $profileName = "IPSM.TemperatureI";break;
                                    case 2 : $profileName = "IPSM.TemperatureF";break;
                                }
                                if(!empty($parameterDescriptors["MinValueCondition"]) && !empty($parameterDescriptors["MinValueCondition"])){
                                    $profileName .= '_'.$parameterDescriptors["MinValueCondition"];
                                    $profileName .= '_'.$parameterDescriptors["MaxValueCondition"];
                                }
                                if (!IPS_VariableProfileExists($profileName)) {
                                    IPS_CreateVariableProfile($profileName, $varType);
                                    IPS_SetVariableProfileDigits($profileName, intval($parameterDescriptors["Decimals"]));
                                    IPS_SetVariableProfileValues($profileName, $parameterDescriptors["MinValueCondition"], $parameterDescriptors["MaxValueCondition"], $parameterDescriptors["StepWidth"]);
                                    IPS_SetVariableProfileText($profileName, "", " °C");
                                }
                                IPS_SetVariableCustomProfile ($nextParentID, $profileName);
                            } else if($parameterDescriptors["Unit"] == "Std"){
                                switch($varType){
                                    case 1 : $profileName = "IPSM.HoursI";break;
                                    case 2 : $profileName = "IPSM.HoursF";break;
                                }
                                if(!empty($parameterDescriptors["MinValueCondition"]) && !empty($parameterDescriptors["MinValueCondition"])){
                                    $profileName .= '_'.$parameterDescriptors["MinValueCondition"];
                                    $profileName .= '_'.$parameterDescriptors["MaxValueCondition"];
                                }
                                if (!IPS_VariableProfileExists($profileName)) {
                                    IPS_CreateVariableProfile($profileName, $varType);
                                    IPS_SetVariableProfileDigits($profileName, intval($parameterDescriptors["Decimals"]));
                                    IPS_SetVariableProfileValues($profileName, $parameterDescriptors["MinValueCondition"], $parameterDescriptors["MaxValueCondition"], $parameterDescriptors["StepWidth"]);
                                    IPS_SetVariableProfileText($profileName, "", " Stunde(n)");
                                }
                                IPS_SetVariableCustomProfile ($nextParentID, $profileName);
                            }
                        } else {
                            $profileName = str_replace(' ', '_', $parameterDescriptors["Name"]);
                            $profileName = 'IPSM.'.preg_replace('/[^A-Za-z0-9\-]/', '', $profileName);
                            if (!IPS_VariableProfileExists($profileName)) {
                                IPS_CreateVariableProfile($profileName, 1);
                                foreach($parameterDescriptors["ListItems"] as $listItem){
                                    IPS_SetVariableProfileAssociation($profileName, $listItem["Value"], $listItem["DisplayText"], '', -1);
                                }
                            }
                            IPS_SetVariableCustomProfile ($nextParentID, $profileName);
                        }
                    }
                }
            }
        }
    }
    
    private function GetURL($function){
        $return["function"] = "GetURL";
        $host = $this->ReadPropertyString("host");
        $port = $this->ReadPropertyString("port");
        
        if($host > 0 && $port > 0){
            $url = 'http://'.$host.':'.$port.'/';
            switch($function){
                case 'logon'                		: $url .= 'connect/token'; break; // OK
                case 'updateSession'       			: $url .= 'api/portal/UpdateSession'; break;
                case 'logout'               		: $url .= 'api/local-connection/Disconnect'; break;
                case 'logout'               		: $url .= 'api/local-connection/Disconnect'; break;
                case 'closeSystem'             		: $url .= 'api/portal/CloseSystem'; break;
                case 'triggerTerminate'             : $url .= 'api/local-connection/TriggerTerminate'; break;
                case 'observeLocalGateways' 		: $url .= 'api/local-connection/ObserveLocalGateways'; break; // OK
                case 'connectGateway'       		: $url .= 'api/local-connection/ConnectGateway';break; // OK
                case 'terminate'       				: $url .= 'api/local-connection/TriggerTerminate';break; // OK
                case 'getGuiDescriptionForGateway'  : $url .= 'api/portal/GetGuiDescriptionForGateway';break;
                case 'getParameterValues'           : $url .= 'api/portal/GetParameterValues';break;
                case 'writeParameterValues'         : $url .= 'api/portal/WriteParameterValues';break;
            }
            return $url;
            
        } else {
            $this->SendDebug($return["function"], "ERROR: Host or port unset", 0);
            die();
        }
    }
    
    public function ObserveLocalGateways(){
        $url    = $this->GetURL("observeLocalGateways");
        $send   = $this->send($url.'?'.$this->addTime(), "", "GET");
        switch($send["code"]){
            default : echo "Unbekannter Fehler: ".$send["code"];break;
            case 0  : echo "Smartset nicht erreichbar oder offline!";break;
            case 200:
                foreach($send["response"] as $responseDevice){
                    if(!empty($responseDevice["Hostname"]) && !empty($responseDevice["IpAddress"]) && !empty($responseDevice["GatewayTag"])){
                        $hostnameCatID  = $this->CreateCategoryByIdent($this->InstanceID, str_replace(":", "_", $responseDevice["GatewayTag"]), $responseDevice["Hostname"]);
                        $hostnameID     = $this->CreateVariableByIdent($hostnameCatID, "hostname", "Hostname", 3, "", 0, 3);
                        $ipAdressID     = $this->CreateVariableByIdent($hostnameCatID, "ipAdress", "IP-Adresse", 3, "", 0, 4);
                        $gatewayTagID   = $this->CreateVariableByIdent($hostnameCatID, "gatewayTag", "Gateway-Tag", 3, "", 0, 5);
                        $deviceStateID  = $this->CreateVariableByIdent($hostnameCatID, "deviceState", "Status", 1, "IPSM.State", 0, 6);
                        SetValueString($hostnameID, $responseDevice["Hostname"]);
                        SetValueString($ipAdressID, $responseDevice["IpAddress"]);
                        SetValueString($gatewayTagID, $responseDevice["GatewayTag"]);
                        
                        SetValueInteger(IPS_GetObjectIDByIdent("deviceState", $hostnameCatID), 1);
                        echo "Bitte jetzt die abzufragenden Geräte in der Liste hinzufügen!";
                    }
                }
                break;
        }
    }
    
    private function send($url, $data, $type){
        $return["function"] = "Send";
        $this->SendDebug($return["function"], "START", 0);
        
        if(empty($this->getBuffer("AccessToken"))){
            $this->SendDebug($return["function"], "ERROR: AccessToken wrong or empty", 0);
            $logon = $this->Logon();
            if($logon["code"] != 200){
                SetValueInteger(IPS_GetObjectIDByIdent("smartsetState", $this->InstanceID), 3);
                return 0;
            }
        }
        
        $header     = array(
            'Accept-Language: de,en-US;q=0.7,en;q=0.3',
            'Accept-Encoding: gzip, deflate',
            'X-Requested-With: XMLHttpRequest',
            'Connection: keep-alive',
            'Content-Type: application/json; charset=utf-8',
            'Authorization: '.$this->GetBuffer("TokenType").' '.$this->GetBuffer("AccessToken")
        );
        $dataJson   = json_encode($data);
        array_push($header, 'Content-Length: '.strlen($dataJson));
        
        $curl   = curl_init($url);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $dataJson);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $type);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:61.0) Gecko/20100101 Firefox/61.0');
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        $curlResp   = curl_exec($curl);
        $errorCode  = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        $respArray  = json_decode($curlResp, true);
        
        $return["code"]     = $errorCode;
        $return["response"] = $respArray;
        
        switch($errorCode){
            default :
                SetValueInteger(IPS_GetObjectIDByIdent("smartsetState", $this->InstanceID), 0);
                $return["error"] = "ERROR: Unknown #".$errorCode;
                break;
            case 0 :
                SetValueInteger(IPS_GetObjectIDByIdent("smartsetState", $this->InstanceID), 2);
                $return["error"] = "ERROR: Smartset offline";
                break;
                //case 400 : // WRONG SESSION
            case 401 : // WRONG Access-Token
                $accessRetry = intval($this->GetBuffer("AccessRetry"));
                if($accessRetry < 2){
                    SetValueInteger(IPS_GetObjectIDByIdent("smartsetState", $this->InstanceID), 4);
                    $this->SendDebug($return["function"], "ERROR: AccessToken retry", 0);
                    $this->SetBuffer("AccessRetry", $accessRetry+1);
                    $this->SetBuffer("AccessToken", "");
                    $this->send($url, $data, $type);
                }else{
                    SetValueInteger(IPS_GetObjectIDByIdent("smartsetState", $this->InstanceID), 5);
                    $this->SendDebug($return["function"], "ERROR: AccessToken wrong", 0);
                    $this->SetBuffer("AccessRetry", 0);;
                }
                break;
                //case 500 : $this->GetGuiDescriptionForGateway($gatewayCatID, $gatewayID, $systemID); //Wrong IDs
            case 200 :
                SetValueInteger(IPS_GetObjectIDByIdent("smartsetState", $this->InstanceID), 1);
                $return["error"] = "OK: Command success";
                break;
        }
        if(isset($return["error"])){
            $this->SendDebug($return["function"], $return["error"], 0);
        }
        $this->SendDebug($return["function"], "END", 0);
        return $return;
    }
    
    private function Logon(){
        $return["function"] = "Logon";
        $this->SendDebug($return["function"], "START", 0);
        
        $url        = $this->GetURL("logon");
        $header     = array(
            'Accept-Language: de,en-US;q=0.7,en;q=0.3',
            'Accept-Encoding: gzip, deflate, br',
            'X-Requested-With: XMLHttpRequest',
            'Connection: keep-alive',
            'Content-Type: application/x-www-form-urlencoded; charset=UTF-8'
        );
        
        $data       = array(
            'grant_type' 		=> 'password',
            'password' 			=> '123',
            'scope'				=> 'offline_access+openid',
            'username' 			=> 'StandaloneAppUser',
            'website_version'	=> 27
        );
        $dataQuery  = http_build_query($data)."\n";
        array_push($header, 'Content-Length: '.strlen($dataQuery));
        
        $curl       = curl_init($url);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $dataQuery);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:61.0) Gecko/20100101 Firefox/61.0');
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        $curlResp   = curl_exec($curl);
        $errorCode  = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        $respArray  = json_decode($curlResp, true);
        
        $return["code"]     = $errorCode;
        $return["response"] = $respArray;
        
        switch($errorCode){
            default :
                SetValueInteger(IPS_GetObjectIDByIdent("smartsetState", $this->InstanceID), 0);
                $return["error"] = "ERROR: Unknown #".$errorCode;
                break;
            case 0 :
                SetValueInteger(IPS_GetObjectIDByIdent("smartsetState", $this->InstanceID), 2);
                $return["error"] = "ERROR: Smartset offline";
                break;
                //case 400 :
            case 401 :
                SetValueInteger(IPS_GetObjectIDByIdent("smartsetState", $this->InstanceID), 3);
                $return["error"] = "ERROR: Smartset authentification wrong";
                break;
            case 200 :
                SetValueInteger(IPS_GetObjectIDByIdent("smartsetState", $this->InstanceID), 1);
                $return["error"] = "OK: Smartset authentification success";
                $this->SetBuffer("TokenType", $respArray["token_type"]);
                $this->SetBuffer("AccessToken", $respArray["access_token"]);
                IPS_SetEventActive(IPS_GetObjectIDByIdent("updateSessionEvent", $this->InstanceID), true);
                break;
        }
        $this->SendDebug($return["function"], $return["error"], 0);
        $this->SendDebug($return["function"], "END", 0);
        return $return;
    }
    
    private function ConnectGateway($gatewayCatID, $password){
        $gatewayTag         = GetValueString(IPS_GetObjectIDByIdent("gatewayTag", $gatewayCatID));
        
        $return["function"] = "ConnectGateway";
        $this->SendDebug($return["function"], "START", 0);
        $this->Terminate();
        $this->SendDebug($return["function"], "Connect", 0);
        $url                = $this->GetURL("connectGateway");
        $send               = $this->send($url.'?GatewayTag='.$gatewayTag.'&Password='.$password.'&'.$this->addTime(), "", "GET");
        $return["code"]     = $send["code"];
        switch($send["code"]){
            case 400:
                SetValueInteger(IPS_GetObjectIDByIdent("deviceState", $gatewayCatID), 3);
                $return["error"] = "ISM-Password wrong";
                $this->SendDebug($return["function"], $return["error"], 0);
                break;
            case 200:
                if(!empty($send["response"]["Id"]) && !empty($send["response"]["GatewayId"])){
                    SetValueInteger(IPS_GetObjectIDByIdent("deviceState", $gatewayCatID), 1);
                    $return["error"] = "OK";
                    
                    $idID                       = $this->CreateVariableByIdent($gatewayCatID, "id", "ID", 3, "", 0, 1);
                    $gatewayIdID                = $this->CreateVariableByIdent($gatewayCatID, "gatewayID", "Gateway-ID", 3, "", 0, 2);
                    $gatewaySoftwareVersionID   = $this->CreateVariableByIdent($gatewayCatID, "gatewaySoftwareVersion", "Software-Version", 3, "", 0, 6);
                    IPS_SetHidden ($idID, true);
                    IPS_SetHidden ($gatewayIdID, true);
                    SetValueString($idID, $send["response"]["Id"]);
                    SetValueString($gatewayIdID, $send["response"]["GatewayId"]);
                    SetValueString($gatewaySoftwareVersionID, $send["response"]["GatewaySoftwareVersion"]);
                    $this->SendDebug($return["function"], $return["error"], 0);
                }else{
                    SetValueInteger(IPS_GetObjectIDByIdent("deviceState", $gatewayCatID), 0);
                    $return["error"] = "No content in response";
                    $this->SendDebug($return["function"], $return["error"], 0);
                }
                break;
        }
        return $return;
        $this->SendDebug($return["function"], "END", 0);
    }
    
    public function UpdateSession(){
        $return["function"] = "UpdateSession";
        $this->SendDebug($return["function"], "START", 0);
        $this->SendDebug($return["function"], "Update session", 0);
        $url    = $this->GetURL("updateSession");
        $data   = '{}';
        $send   = $this->send($url, $data, "POST");
        if($send["code"] == 400){
            $this->SetBuffer("AccessToken", "");
            $logon = $this->Logon();
        }
        
        if(isset($send["response"]["Maintenance"])){
            $this->SendDebug($return["function"], 'Maintenance: '. (int) $send["response"]["Maintenance"], 0);
        }
        $this->SendDebug($return["function"], "END", 0);
    }
    
    private function Terminate(){
        $return["function"] = "Terminate";
        $this->SendDebug($return["function"], "START", 0);
        $this->SendDebug($return["function"], "Send terminate", 0);
        $url    = $this->GetURL("terminate", true);
        $send   = $this->send($url.'?'.$this->addTime(), "", "GET");
        $this->SendDebug($return["function"], "END", 0);
    }
    
    private function addTime(){
        return '_='.(round(microtime(true) * 1000));
    }
    
    public function GetConfigurationForm()
    {
        $data = json_decode(file_get_contents(__DIR__ . "/form.json"));
        if($this->ReadPropertyString("pollDevices") != "") {
            $pollDevices = json_decode($this->ReadPropertyString("pollDevices"));
            foreach($pollDevices as $pollDevice) {
                if(!$gatewayTagID = @IPS_GetObjectIDByIdent("gatewayTag", $pollDevice->varID)){
                    $connect["error"] = "Kein Gateway-Tag gefunden";
                } else if($gatewayTagID > 0 && empty(GetValueString($gatewayTagID))){
                    $connect["error"] = "Gateway-Tag ist leer";
                } else if(empty($pollDevice->password)){
                    $connect["error"] = "ISM-Passwort fehlt";
                } else {
                    $connect = $this->ConnectGateway($pollDevice->varID, $pollDevice->password);
                    if($connect["code"] == 200){
                        $this->GetGuiDescriptionForGateway($pollDevice->varID);
                    }
                }
                
                if(isset($connect["error"]) && $connect["error"] != "OK"){
                    $data->elements[2]->values[] = Array(
                        "varID"         => $pollDevice->varID,
                        "name"          => $connect["error"],
                        "state"         => 'FEHLER',
                        "rowColor"      => "#ff0000"
                        );
                }else{
                    $data->elements[2]->values[] = Array(
                        "varID"         => $pollDevice->varID,
                        "name"          => IPS_GetName($pollDevice->varID),
                        "state"         => 'OK',
                        "rowColor"      => "#00ff00"
                        );
                    IPS_SetEventActive(IPS_GetObjectIDByIdent("updateVarsEvent", $this->InstanceID), true);
                    IPS_SetEventActive(IPS_GetObjectIDByIdent("updateConnectionEvent", $this->InstanceID), true);
                }
            }
        }
        return json_encode($data);
    }
    
    ##########################################################################################################
    # EVENT
    private function CreateEventByIdent($parentID, $ident, $name, $type, $position = 0)
    {
        $eID = @IPS_GetObjectIDByIdent($ident, $parentID);
        if($eID === false)
        {
            $eID = IPS_CreateEvent($type);
            IPS_SetParent($eID, $parentID);
            IPS_SetName($eID, $name);
            IPS_SetIdent($eID, $ident);
            IPS_SetPosition($eID, $position);
        }
        return $eID;
    }
    #
    ##########################################################################################################
    ##########################################################################################################
    # create a CATEGORY
    private function CreateCategoryByIdent($parentID, $ident, $name, $position = 0)
    {
        $cID = @IPS_GetObjectIDByIdent($ident, $parentID);
        if($cID === false)
        {
            $cID = IPS_CreateCategory();
            IPS_SetParent($cID, $parentID);
            IPS_SetName($cID, $name);
            IPS_SetIdent($cID, $ident);
            IPS_SetPosition($cID, $position);
        }
        return $cID;
    }
    #
    ##########################################################################################################
    ##########################################################################################################
    # create a VARIABLE
    private function CreateVariableByIdent($parentID, $ident, $name, $type, $profile = "", $actionID = 0, $position = 0, $icon = "")
    {
        $vID = @IPS_GetObjectIDByIdent($ident, $parentID);
        if($vID === false)
        {
            $vID = IPS_CreateVariable($type);
            IPS_SetParent($vID, $parentID);
            IPS_SetName($vID, $name);
            IPS_SetIdent($vID, $ident);
            if($profile != "")
            {
                IPS_SetVariableCustomProfile($vID, $profile);
            }
            if($actionID > 0)
            {
                IPS_SetVariableCustomAction($vID, $actionID);
            }
            if($icon !="")
            {
                IPS_SetIcon($vID, $icon);
            }
            IPS_SetPosition($vID, $position);
        }
        return $vID;
    }
    #
    ##########################################################################################################
    ##########################################################################################################
    # create a SCRIPT
    private function CreateScriptByIdent($parentID, $ident, $name, $position = 0, $icon = "")
    {
        $sID = @IPS_GetObjectIDByIdent($ident, $parentID);
        if($sID === false)
        {
            $sID = IPS_CreateScript(0);
            IPS_SetParent($sID, $parentID);
            IPS_SetName($sID, $name);
            IPS_SetIdent($sID, $ident);
            if($icon !="")
            {
                IPS_SetIcon($sID, $icon);
            }
            IPS_SetPosition($sID, $position);
        }
        return $sID;
    }
    #
    ##########################################################################################################
}
?>