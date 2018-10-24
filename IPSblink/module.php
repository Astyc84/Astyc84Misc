<?
class IPSblink extends IPSModule{
    
    public function Create(){
        parent::Create();
        
        $this->RegisterPropertyBoolean("onlyOn", false);
        $this->RegisterPropertyInteger("lastState", 0);
        $this->RegisterPropertyInteger("blinkNum", 1);
        $this->RegisterPropertyInteger("timeOn", 1);
        $this->RegisterPropertyInteger("timeOff", 1);
        $this->RegisterPropertyString("devices", "[]");
    }
    
    public function ApplyChanges(){
        parent::ApplyChanges();
        if(!@IPS_GetObjectIDByIdent("eventBlink", $this->InstanceID)){
            $eid = IPS_CreateEvent(1);
            IPS_SetParent($eid, $this->InstanceID);
            IPS_SetName($eid, $this->Translate("Timecontroller for flashing"));
            IPS_SetIdent($eid, "eventBlink");
            IPS_SetEventActive($eid, false);
        }
    }
    
    public function Stop(){
        $eid		= @IPS_GetObjectIDByIdent("eventBlink", $this->InstanceID);
        if(IPS_GetEvent($eid)["EventActive"]){
            $lastState 		= $this->ReadPropertyInteger("lastState");
            
            $devices = json_decode($this->GetBuffer("devices"),true);
            foreach($devices as $deviceID => $values){
                if($values["ident"] == "-#Var#-"){
                    $varType    = substr($values["actionVar"] ,0,1);
                    $deviceID   =  intval(substr($values["actionVar"],1));
                    switch($varType){
                        case 0 :
                            switch($lastState){
                                case 0 : SetValueBoolean($deviceID, $values["minValue"]);break;
                                case 1 : SetValueBoolean($deviceID, $values["maxValue"]);break;
                                case 2 : SetValueBoolean($deviceID, $values["firstState"]);break;
                            }
                        break;
                        case 1 :
                            switch($lastState){
                                case 0 : SetValueInteger($deviceID, $values["minValue"]);break;
                                case 1 : SetValueInteger($deviceID, $values["maxValue"]);break;
                                case 2 : SetValueInteger($deviceID, $values["firstState"]);break;
                            }
                        break;
                        case 2 :
                            switch($lastState){
                                case 0 : SetValueFloat($deviceID, $values["minValue"]);break;
                                case 1 : SetValueFloat($deviceID, $values["maxValue"]);break;
                                case 2 : SetValueFloat($deviceID, $values["firstState"]);break;
                            }
                        break;
                    }
                } else {
                    switch($lastState){
                        case 0 : IPS_RequestAction($values["actionVar"], $values["ident"], $values["minValue"]); break;
                        case 1 : IPS_RequestAction($values["actionVar"], $values["ident"], $values["maxValue"]); break;
                        case 2 : IPS_RequestAction($values["actionVar"], $values["ident"], $values["firstState"]); break;
                }
            }
            IPS_SetEventActive($eid, false);
        }
        }
    }
    
    public function Blink(){
        $count			= 0;
        $onlyOn 		= $this->ReadPropertyBoolean("onlyOn");
        $devices 		= $this->GetDevices();
        
        foreach($devices as $deviceID => $values){
            if($onlyOn && ($values["state"] == $values["minValue"])){ // delete off
                unset($devices[$deviceID]);
            }
        }
        
        if(!empty($devices)){
            IPSblink_SetOn($this->InstanceID, $count);
        }
    }
    
    public function SetOn(int $count){
        $blinkNum		= $this->ReadPropertyInteger("blinkNum");
        $timeOn 		= $this->ReadPropertyInteger("timeOn");
        $lastState 		= $this->ReadPropertyInteger("lastState");
        
        if($count >= $blinkNum && $lastState == 0){
            $this->SetEvent("", 0, false);
            $this->SetBuffer("devices", "");
            
            
        } else if($count >= $blinkNum && $lastState == 2){
            $this->SetEvent("", 0, false);
            
            $devices = json_decode($this->GetBuffer("devices"),true);
            foreach($devices as $deviceID => $values){
                if($values["ident"] == "-#Var#-"){
                    $varType    = substr($values["actionVar"] ,0,1);
                    $deviceID   =  intval(substr($values["actionVar"],1));
                    switch($varType){
                        case 0 : SetValueBoolean($deviceID, $values["firstState"]);break;
                        case 1 : SetValueInteger($deviceID, $values["firstState"]);break;
                        case 2 : SetValueFloat($deviceID, $values["firstState"]);break;
                    }
                } else {
                    IPS_RequestAction($values["actionVar"], $values["ident"], $values["firstState"]);
                }
            }
            $this->SetBuffer("devices", "");
            
        } else {
            $devices = json_decode($this->GetBuffer("devices"),true);
            foreach($devices as $deviceID => $values){
                if($values["ident"] == "-#Var#-"){
                    $varType    = substr($values["actionVar"] ,0,1);
                    $deviceID   =  intval(substr($values["actionVar"],1));
                    switch($varType){
                        case 0 : SetValueBoolean($deviceID, $values["maxValue"]);break;
                        case 1 : SetValueInteger($deviceID, $values["maxValue"]);break;
                        case 2 : SetValueFloat($deviceID, $values["maxValue"]);break;
                    }
                } else {
                    IPS_RequestAction($values["actionVar"], $values["ident"], $values["maxValue"]);
                }
            }
            
            $count++;
            $this->SetEvent("IPSblink_SetOff(".$this->InstanceID.",".$count.");", $timeOn, true);
        }
    }
    
    public function SetOff(int $count){
        $blinkNum		= $this->ReadPropertyInteger("blinkNum");
        $timeOff 		= $this->ReadPropertyInteger("timeOff");
        $lastState 		= $this->ReadPropertyInteger("lastState");
        
        if($count >= $blinkNum && $lastState == 1){
            $this->SetEvent("", 0, false);
            
        } else if($count >= $blinkNum && $lastState == 2){
            $this->SetEvent("", 0, false);
            
            $devices = json_decode($this->GetBuffer("devices"),true);
            foreach($devices as $deviceID => $values){
                if($values["ident"] == "-#Var#-"){
                    $varType    = substr($values["actionVar"] ,0,1);
                    $deviceID   =  intval(substr($values["actionVar"],1));
                    switch($varType){
                        case 0 : SetValueBoolean($deviceID, $values["firstState"]);break;
                        case 1 : SetValueInteger($deviceID, $values["firstState"]);break;
                        case 2 : SetValueFloat($deviceID, $values["firstState"]);break;
                    }
                } else {
                    IPS_RequestAction($values["actionVar"], $values["ident"], $values["firstState"]);
                }
            }
            $this->SetBuffer("devices", "");
            
        } else {
            $devices = json_decode($this->GetBuffer("devices"),true);
            foreach($devices as $deviceID => $values){
                if($values["ident"] == "-#Var#-"){
                    $varType    = substr($values["actionVar"] ,0,1);
                    $deviceID   =  intval(substr($values["actionVar"],1));
                    switch($varType){
                        case 0 : SetValueBoolean($deviceID, $values["minValue"]);break;
                        case 1 : SetValueInteger($deviceID, $values["minValue"]);break;
                        case 2 : SetValueFloat($deviceID, $values["minValue"]);break;
                    }
                } else {
                    IPS_RequestAction($values["actionVar"], $values["ident"], $values["minValue"]);
                }
            }
            $this->SetEvent("IPSblink_SetOn(".$this->InstanceID.",".$count.");", $timeOff, true);
        }
    }
    
    private function SetEvent(string $script, int $seconds, bool $state){
        $eid		= @IPS_GetObjectIDByIdent("eventBlink", $this->InstanceID);
        $eTime 		= (time() + $seconds);
        $hour 		= strftime("%H", $eTime);
        $minute 	= strftime("%M", $eTime);
        $seconds 	= strftime("%S", $eTime);
        IPS_SetEventCyclic($eid, 0, 0, 0, 0, 0, 0);
        IPS_SetEventCyclicTimeFrom($eid, $hour, $minute, $seconds);
        IPS_SetEventCyclicTimeTo($eid, 0, 0, 0);
        IPS_SetEventScript($eid, $script);
        IPS_SetEventActive($eid, $state);
    }
    
    public function GetConfigurationForm() {
        $formdata 	= json_decode(file_get_contents(__DIR__ . "/form.json"));
        $devices 	= json_decode($this->ReadPropertyString("devices"));
        foreach($devices as $device) {
            if(IPS_ObjectExists($device->id) && $device->id !== 0) {
                $status = "OK";
                $rowColor = "#C0FFC0";
                if (!IPS_VariableExists($device->id)) {
                    $status = $this->Translate("Not a variable");
                    $rowColor = "#FFC0C0";
                } else if (($this->GetProfileAction(IPS_GetVariable($device->id)) <= 10000) && empty($this->GetProfileName(IPS_GetVariable($device->id)))){
                    $status =  $this->Translate("No profile set");
                    $rowColor = "#FFC0C0";
                }
                $formdata->elements[7]->values[] = Array(
                    "name"		=> IPS_GetName(IPS_GetParent($device->id)),
                    "variable" 	=> IPS_GetName($device->id),
                    "status"	=> $status,
                    "rowColor" 	=> $rowColor,
                    );
            } else {
                $formdata->elements[7]->values[] = Array(
                    "variable" => $this->Translate("Not found!"),
                    "rowColor" => "#FFC0C0",
                    );
            }
        }
        return json_encode($formdata);
    }
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    private function GetDevices(){
        $devices = json_decode($this->ReadPropertyString("devices"));
        if(empty($devices)){
            echo $this->Translate("No devices set");
        }else{
            foreach($devices as $device){
                if(IPS_VariableExists($device->id) && $device->id !== 0){
                    $var		= IPS_GetVariable($device->id);
                    $actionVar  = $this->GetProfileAction($var);
                    $varProfile = $this->GetProfileName($var);
                    
                   
                    $profile = IPS_GetVariableProfile($varProfile);
                    $ident   = IPS_GetObject($device->id)["ObjectIdent"];
                    switch($var['VariableType']){
                        default :
                            $minValue = boolval($profile["MinValue"]); // Default / Bool
                            $maxValue = boolval($profile["MaxValue"]);
                            if($actionVar < 10000) {
                                $ident      = "-#Var#-";
                                $actionVar  = '0'.$device->id;
                            }
                            break;
                        case 1 	:
                            $minValue = intval($profile["MinValue"]); // Int
                            $maxValue = intval($profile["MaxValue"]);
                            if($actionVar < 10000) {
                                $ident      = "-#Var#-";
                                $actionVar  = '1'.$device->id;
                            }
                            break;
                        case 2 	:
                            $minValue = floatval($profile["MinValue"]); // Float
                            $maxValue = floatval($profile["MaxValue"]);
                            if($actionVar < 10000) {
                                $ident      = "-#Var#-";
                                $actionVar  = '2'.$device->id;
                            }
                            break;
                    }
                    $variables[$device->id]["ident"]    = $ident;
                    $variables[$device->id]["state"]	= GetValue($device->id);
                    $variables[$device->id]["minValue"]	= $minValue;
                    $variables[$device->id]["maxValue"]	= $maxValue;
                    $variables[$device->id]["actionVar"]= $actionVar;
                    if(empty($variables[$device->id]["firstState"])){
                        $variables[$device->id]["firstState"] = GetValue($device->id);
                    }
                }
            }
            $this->SetBuffer("devices", json_encode($variables));
            return $variables;
        }
    }
    private function GetProfileName($var) {
        if($var['VariableCustomProfile'] != ""){
            return $var['VariableCustomProfile'];
        }else{
            return $var['VariableProfile'];
        }
    }
    
    private function GetProfileAction($var) {
        if($var['VariableCustomAction'] != ""){
            return $var['VariableCustomAction'];
        }else{
            return $var['VariableAction'];
        }
    }
}
?>
