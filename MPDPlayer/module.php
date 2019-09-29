<?
	class MPDPlayer extends IPSModule
	{
		public function Create() {
			//Never delete this line!
            parent::Create();

			$this->RegisterPropertyString("Password", "");

			$this->RegisterTimer("KeepAliveTimer", 30000, 'MPDP_KeepAlive($_IPS[\'TARGET\']);');
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

		public function RequestAction($Ident, $Value) {

			switch($Ident) {
				case "Power":
					SetValue($this->GetIDForIdent($Ident), $Value);
					break;

				case "Status":
					switch($Value) {
						case 0: //Prev
								$this->Previous();
								SetValue($this->GetIDForIdent($Ident), $Value);
								break;
						case 1: //Stop
								$this->Stop();
								SetValue($this->GetIDForIdent($Ident), $Value);
								break;
						case 2: //Pause
								$this->Pause(1);
								SetValue($this->GetIDForIdent($Ident), $Value);
								break;
						case 3: //Play
								if(GetValue($this->GetIDForIdent($Ident))!=2) {
										$this->Play();
								}
								else {
										$this->Pause(0);
								}
								SetValue($this->GetIDForIdent($Ident), $Value);
								break;
						case 4: //Next
								$this->Next();
								SetValue($this->GetIDForIdent($Ident), $Value);
								break;
				}
				break;

				default:
					throw new Exception("Invalid Ident");
			}

		}


		public function Play()
		{
			$this->Send("play\n");
		}

		public function Pause(int $status)
		{
			$this->Send("pause ".$status."\n");
		}

		public function Stop()
		{
			$this->Send("stop\n");
		}

		public function Previous()
		{
			$this->Send("previous\n");		}

		public function Next()
		{
			$this->Send("next\n");
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

		public function SetNewStation() {
			$this->Send("clear\n");
			$this->Send("add http://172.27.2.205:9981/stream/channel/14f799071150331b9a7994ca8c61f8c7 \n");
			$this->Send("play\n");
		}

		public function KeepAlive()
		{
			$this->Send("ping\n");
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