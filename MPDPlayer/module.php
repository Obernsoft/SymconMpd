<?
	class MPDPlayer extends IPSModule
	{
		public function create() {
            // Diese Zeile nicht löschen.
            parent::Create();

			$this->RegisterPropertyString("Password", "");

			$this->RegisterTimer("KeepAliveTimer", 30000, 'MPDP_KeepAlive($_IPS[\'TARGET\'])');
		}

		public function ApplyChanges()
		{
			//Never delete this line!
			parent::ApplyChanges();

			$this->RegisterVariableBoolean("Power", "Power", "~Switch",1);
			$this->EnableAction("Power");

			$this->RegisterVariableString("Titel", "Titel","",2);


			$this->RegisterProfileIntegerEx("Mpd.Status", "Information", "", "", Array( Array(0, " << ", "", -1),
																					Array(1, " Stop ",   "", -1),
																					Array(2, " Pause ",  "", -1),
																					Array(3, " Play ",   "", -1),
																					Array(4, " >> ",    "", -1) ));

			$this->RegisterVariableInteger("Status","Status","Mpd.Status",3);
			SetValue($this->GetIDForIdent("Status"), 1);
			$this->EnableAction("Status");

			//Connect to available splitter or create a new one
			$this->ForceParent("{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}");
		}

		public function Send(string $Text)
		{
			$this->SendDataToParent(json_encode(Array("DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}", "Buffer" => $Text)));
		}

		public function ReceiveData($JSONString)
		{
			$data = json_decode($JSONString);
			IPS_LogMessage("MPDPlayer", utf8_decode($data->Buffer));
			//Parse and write values to our variables
			//echo $data;
		}

		public function KeepAlive()
		{
			$this->Send("ping");
		}


	   //Remove on next Symcon update
		protected function RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize) {

			if(!IPS_VariableProfileExists($Name)) {
				IPS_CreateVariableProfile($Name, 1);
			} else {
				$profile = IPS_GetVariableProfile($Name);
				if($profile['ProfileType'] != 1)
				throw new Exception("Variable profile type does not match for profile ".$Name);
			}

			IPS_SetVariableProfileIcon($Name, $Icon);
			IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
			IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);

		}

		protected function RegisterProfileIntegerEx($Name, $Icon, $Prefix, $Suffix, $Associations) {
			if ( sizeof($Associations) === 0 ){
				$MinValue = 0;
				$MaxValue = 0;
			} else {
				$MinValue = $Associations[0][0];
				$MaxValue = $Associations[sizeof($Associations)-1][0];
			}

			$this->RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, 0);

			foreach($Associations as $Association) {
				IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
			}

		}





	}
?>