<?
class DoorBell extends IPSModule{
    
    public function Create(){
        parent::Create();
        
        $this->RegisterPropertyInteger("bellButtonID", 0);
		$this->RegisterPropertyString("bellButtonValue", "");
        $this->RegisterPropertyInteger("bellButtonLockTime", 10);
		
		$this->RegisterPropertyInteger("dayScriptID", 0);
		$this->RegisterPropertyInteger("nightScriptID", 0);
		
        $this->RegisterPropertyInteger("wfID", 0);
		$this->RegisterPropertyString("pushMessage", "Es hat geklingelt!");
        $this->RegisterPropertyInteger("pushSound", 1);
		
		$this->CreateCategoryByIdent($this->InstanceID, "bellPicturesCat", "Bell pictures", 1, 0);
		$this->CreateEventByIdent($this->InstanceID, "bellEvent", "bell", 0, 1);
		
		$this->RegisterPropertyInteger("webCamImageID", 0);
		$this->RegisterPropertyInteger("numPics", 10);
		$this->RegisterPropertyBoolean("linkImage", true);
		
		if(!IPS_VariableProfileExists('DOBE.State')) {
			IPS_CreateVariableProfile('DOBE.State', 1);
			IPS_SetVariableProfileAssociation('DOBE.State', 0, 'Aus', '', -1);
			IPS_SetVariableProfileAssociation('DOBE.State', 1, 'Tag', '', -1);
			IPS_SetVariableProfileAssociation('DOBE.State', 2, 'Nacht', '', -1);
		}
    }
    
    public function ApplyChanges(){
        parent::ApplyChanges();
		$stateID   			= $this->RegisterVariableInteger("state", "State", "DOBE.State", 2);
		$bellButtonID		= $this->ReadPropertyInteger("bellButtonID");
		$bellButtonValue	= $this->ReadPropertyString("bellButtonValue");
		if($bellButtonID > 0){
			$bellEventID = $this->GetIDForIdent("bellEvent");
			IPS_SetEventTrigger($bellEventID, 1, $bellButtonID);
			IPS_SetEventCondition($bellEventID, 0, 0, 0);
			IPS_SetEventConditionVariableRule($bellEventID, 0, 1, $bellButtonID, 0, intval($bellButtonValue));
			IPS_SetEventScript($bellEventID, "<? DOBE_Bell(".$this->InstanceID."); ?>");
			IPS_SetEventActive($bellEventID, true);
		}
    }
    
	/*
	public function MessageSink($timeStamp, $senderID, $message, $data) {
		$bellButtonID		= $this->ReadPropertyInteger("bellButtonID");
		$bellButtonValue	= $this->ReadPropertyString("bellButtonValue");
		$bellButtonLockTime = $this->ReadPropertyInteger("bellButtonLockTime");
		   
		if($data[0] != $data[2] && $data[0] == $bellButtonValue){ // data must be changed
			if( ($data[3] - intval($this->GetBuffer("lastBell"))) >= $bellButtonLockTime){
				$this->Bell();
				$this->SetBuffer("lastBell", $data[3]);
			}
		}
	}
	*/

    public function Bell(){
		$bellButtonLockTime		= $this->ReadPropertyInteger("bellButtonLockTime");
		if((time() - intval($this->GetBuffer("lastBell"))) >= $bellButtonLockTime){
			$wfID			      	= $this->ReadPropertyInteger("wfID");
			$pushMessage          	= $this->ReadPropertyString("pushMessage");
			$selectPushSound      	= $this->ReadPropertyInteger("pushSound");
			
			$bellPicturesCatID    	= $this->GetIDForIdent("bellPicturesCat");
			$webCamImageID        	= $this->ReadPropertyInteger("webCamImageID");
			$numPics              	= $this->ReadPropertyInteger("numPics");
			$linkImage            	= $this->ReadPropertyBoolean("linkImage");
			
			
			if($numPics > 0){
				foreach(IPS_GetChildrenIDs($bellPicturesCatID) as $bellPictureID){
					$bellPicture = IPS_GetObject($bellPictureID);
					$bellPictureList[$bellPicture["ObjectPosition"]] = $bellPicture["ObjectID"];
					ksort($bellPictureList);
				}
				
				if(!isset($bellPictureList)){ //Kein Bild vorhanden
					$numBellPictures = 0;
				}else{
					$numBellPictures = count($bellPictureList);
				}
				for($i=0;$i<=$numBellPictures;$i++){
					if($i == 0){
						IG_UpdateImage(IPS_GetParent ($webCamImageID));
						$pictureID = IPS_CreateMedia(1);
						IPS_SetName($pictureID, date("H:i:s d.m.Y"));
						IPS_SetParent($pictureID, $bellPicturesCatID);
						IPS_SetMediaCached($pictureID, true);
						IPS_SetMediaFile($pictureID, "media/".$pictureID.".".pathinfo(IPS_GetMedia($webCamImageID)['MediaFile'])['extension'], False);
						IPS_SetMediaContent($pictureID, IPS_GetMediaContent($webCamImageID));
					} else {
						IPS_SetPosition($bellPictureList[$i-1], $i);
					}
					if($i >= $numPics){
						IPS_DeleteMedia ($bellPictureList[$i-1], true);
					}
				}
					
				if($linkImage){
				  $targetID = $pictureID;
				}else{
				  $targetID = 0;
				}
			}else{
				$targetID = 0;
			}
			
			switch($selectPushSound) {
				case 0 : $pushSound = "alarm"; break;
				case 1 : $pushSound = "bell"; break;
				case 2 : $pushSound = "boom"; break;
				case 3 : $pushSound = "buzzer"; break;
				case 4 : $pushSound = "connected"; break;
				case 5 : $pushSound = "dark"; break;
				case 6 : $pushSound = "digital"; break;
				case 7 : $pushSound = "drums"; break;
				case 8 : $pushSound = "duck"; break;
				case 9 : $pushSound = "full"; break;
				case 10 : $pushSound = "happy"; break;
				case 11 : $pushSound = "horn"; break;
				case 12 : $pushSound = "inception"; break;
				case 13 : $pushSound = "kazoo"; break;
				case 14 : $pushSound = "roll"; break;
				case 15 : $pushSound = "siren"; break;
				case 16 : $pushSound = "space"; break;
				case 17 : $pushSound = "trickling"; break;
				case 18 : $pushSound = "turn"; break;
			}
			WFC_PushNotification($wfID, IPS_GetName($this->InstanceID), $pushMessage, $pushSound, $targetID);
			
			$dayScriptID	= $this->ReadPropertyInteger("dayScriptID");
			$nightScriptID	= $this->ReadPropertyInteger("nightScriptID");
			$stateID		= $this->GetIDForIdent("state");

			if(GetValueInteger($stateID) == 1){
				if($dayScriptID != 0){
					IPS_RunScript($dayScriptID);
				}
			} else if(GetValueInteger($stateID) == 2){
				if($nightScriptID != 0){
					IPS_RunScript($nightScriptID);
				}
			}

			$this->SetBuffer("lastBell", time());
		}
	}
	
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
}
?>