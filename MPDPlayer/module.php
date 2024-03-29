<?
	class MPDPlayer extends IPSModule
	{
		public function Create() {
			//Never delete this line!
            parent::Create();

			$this->RegisterPropertyString("Password", "");

			$this->RegisterPropertyString(
				'RadioStations', '[{"position":1,"station":"NDR2 Niedersachsen","station_url":"http://172.27.2.205:9981/stream/channel/800c150e9a6b16078a4a3b3b5aee0672"},
				{"position":2,"station":"MDR Jump","station_url":"http://172.27.2.205:9981/stream/channel/0888328132708be0905731457bba8ae0"},
				{"position":3,"station":"Inselradio Mallorca","station_url":"http://172.27.2.205:9981/stream/channel/14f799071150331b9a7994ca8c61f8c7"}]'
            );

            $this->RegisterPropertyBoolean("HideVolume",false);
            $this->RegisterPropertyBoolean("HideTitle",false);
            $this->RegisterPropertyBoolean("HideTimeElapsed",false);

			$this->RegisterTimer("KeepAliveTimer", 1000, 'MPDP_KeepAlive($_IPS[\'TARGET\']);');
		}

		public function ApplyChanges()
		{
			//Never delete this line!
			parent::ApplyChanges();

			$this->RegisterVariableBoolean("Power", "Power", "~Switch",1);
			$this->EnableAction("Power");

			$this->RegisterVariableInteger("Volume","Volume","~Intensity.100",2);
			SetValue($this->GetIDForIdent("Volume"), 50);
			$this->EnableAction("Volume");

			$this->RegisterVariableString("Titel", "Titel","",3);
			IPS_SetIcon($this->GetIDForIdent("Titel"), "Melody");

			$this->RegisterVariableString("TimeElapsed","Dauer","",4);
			IPS_SetIcon($this->GetIDForIdent("TimeElapsed"), "Clock");

			$this->RegisterProfileIntegerEx("Mpd.Status", "Information", "", "", Array( Array(0, " << ", "", -1),
																					Array(1, " Stop ",   "", -1),
																					Array(2, " Pause ",  "", -1),
																					Array(3, " Play ",   "", -1),
																					Array(4, " >> ",    "", -1) ));

			$this->RegisterVariableInteger("Status","Status","Mpd.Status",6);
			SetValue($this->GetIDForIdent("Status"), 1);
			$this->EnableAction("Status");

			//Connect to available splitter or create a new one
			$this->ForceParent("{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}");

			$associations = [];
			$profileName = 'MPD.Station';
			foreach (json_decode($this->ReadPropertyString('RadioStations'), true) as $RadioStation) {
				$associations[] = [$RadioStation['position'], $RadioStation['station'], '', -1];
			}
			$this->RegisterProfileIntegerEx($profileName, "Database", "", "", $associations);
			//$this->RegisterProfileAssociation($profileName, 'Music', '', '', 0, 0, 0, 0, VARIABLETYPE_INTEGER, $associations);
			$this->RegisterVariableInteger("Senderliste", "Sender", $profileName, 5);
			$this->EnableAction("Senderliste");

			if($this->ReadPropertyBoolean('HideVolume')) {
				IPS_SetHidden($this->GetIDForIdent("Volume"), TRUE);
			} else {
				IPS_SetHidden($this->GetIDForIdent("Volume"), FALSE);
			}

			if($this->ReadPropertyBoolean('HideTitle')) {
				IPS_SetHidden($this->GetIDForIdent("Titel"), TRUE);
			} else {
				IPS_SetHidden($this->GetIDForIdent("Titel"), FALSE);
			}

			if($this->ReadPropertyBoolean('HideTimeElapsed')) {
				IPS_SetHidden($this->GetIDForIdent("TimeElapsed"), TRUE);
			} else {
				IPS_SetHidden($this->GetIDForIdent("TimeElapsed"), FALSE);
			}


		}

		public function RequestAction($Ident, $Value) {


			SetValue($this->GetIDForIdent($Ident), $Value);

			switch($Ident) {
				case "Power":
					$this->SetPower($Value);
					break;

				case "Volume":
					$this->SetVolume($Value);
					break;


				case "Status":
					switch($Value) {
						case 0: //Prev
								$this->Previous();
								break;
						case 1: //Stop
								$this->Stop();
								break;
						case 2: //Pause
								$this->Pause(1);
								break;
						case 3: //Play
								if(GetValue($this->GetIDForIdent($Ident))!=2) {
										$this->Play();
								}
								else {
										$this->Pause(0);
								}
								break;
						case 4: //Next
								$this->Next();
								break;
					}
					break;

				case "Senderliste":
					$this->SetNewStation($Value);
					break;

				default:
					throw new Exception("Invalid Ident");
			}

		}

		public function SetPower(bool $Value) {
			$ClientSocketID = $this->GetClientSocketID($this->InstanceID);

			if($Value) {
				IPS_SetProperty($ClientSocketID,"Open",TRUE);

				IPS_SetDisabled($this->GetIDForIdent("Volume"), false);
				IPS_SetDisabled($this->GetIDForIdent("Titel"), false);
				IPS_SetDisabled($this->GetIDForIdent("TimeElapsed"), false);
				IPS_SetDisabled($this->GetIDForIdent("Senderliste"), false);
				IPS_SetDisabled($this->GetIDForIdent("Status"), false);

			} else {
				$this->Stop();
				usleep(250000);
				IPS_SetProperty($ClientSocketID,"Open",FALSE);

				IPS_SetDisabled($this->GetIDForIdent("Volume"), true);
				IPS_SetDisabled($this->GetIDForIdent("Titel"), true);
				IPS_SetDisabled($this->GetIDForIdent("TimeElapsed"), true);
				IPS_SetDisabled($this->GetIDForIdent("Senderliste"), true);
				IPS_SetDisabled($this->GetIDForIdent("Status"), true);

			}
			IPS_ApplyChanges($ClientSocketID);

		}

		private function GetClientSocketID($instance): int {
			$arInstance = IPS_GetInstance($instance);

			return $arInstance["ConnectionID"];;
		}

		public function Play() {
			$this->Send("play\n");
		}

		public function Pause(int $status) {
			$this->Send("pause ".$status."\n");
		}

		public function Stop() {
			$this->Send("stop\n");

			SetValue($this->GetIDForIdent("Titel"),"-");
			SetValue($this->GetIDForIdent("TimeElapsed"),"-");
		}

		public function Previous() {
			$this->Send("previous\n");
		}

		public function Next() {
			$this->Send("next\n");
		}

		public function SetNewStation(int $newStation) {

			$StationURL = $this->GetStationURL($newStation);

			$this->Send("clear\n");
			$this->Send("add ".$StationURL." \n");
			usleep(50000);

//			if($this->GetValue("Status")==3) {
				$this->Send("play\n");
//			}
		}

		public function SetVolume(int $newVolume) {
			$this->Send("setvol ".$newVolume."\n");
		}


		private function GetStationURL(int $preset): string
		{
			$list_json = $this->ReadPropertyString('RadioStations');
			$list      = json_decode($list_json, true);
			$stationid = '';
			foreach ($list as $station) {
				if ($preset === $station['position']) {
					$stationurl  = $station['station_url'];
					$stationname = $station['station'];
				}
			}

			// Stationname in Info
			SetValue($this->GetIDForIdent("Titel"), $stationname);

			return $stationurl;
		}

		public function Send(string $Text)
		{
			if($this->HasActiveParent()) {
				$this->SendDataToParent(json_encode(Array("DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}", "Buffer" => $Text)));
			} else {
				IPS_LogMessage("MPDPlayer","Not connected - Unable send command: ". $Text);
			}
		}

		public function ReceiveData($JSONString)
		{
			$data = json_decode($JSONString);

			//Kontrollieren ob Buffer leer ist.
			$bufferData = $this->GetBuffer("DataBuffer");
			$bufferData .= $data->Buffer;
			$bufferParts = explode("\n", $bufferData);
			//Letzten Eintrag nicht auswerten, da dieser nicht vollständig ist.
			if(sizeof($bufferParts) > 1) {
				for($i=0; $i<sizeof($bufferParts)-1; $i++) {
					$this->AnalyseData($bufferParts[$i]);
				}
			}
			$bufferData = $bufferParts[sizeof($bufferParts)-1];
			//Uebriggebliebene Daten auf den Buffer schreiben
			$this->SetBuffer("DataBuffer", $bufferData);
		}

		private function AnalyseData($data) {
			$dataParts = explode(":", $data);
			//IPS_LogMessage("MPDPlayer", $dataParts[1]);

			switch ($dataParts[0]) {
				case "volume":
					$this->GetVolume($dataParts[1]);
					break;

				case "state":
					$this->GetState($dataParts[1]);
					break;

				case "elapsed":
					$this->GetTimeElapsed($dataParts[1]);
					break;

				case "songid":
					break;

				default:
					break;
			}
		}

		public function GetVolume(int $volume) {
			SetValue($this->GetIDForIdent("Volume"), $volume);
		}

		public function GetState(string $state) {
			switch (trim($state)) {
				case "play":
					SetValue($this->GetIDForIdent("Status"), 3);
					break;

				case "stop":
					SetValue($this->GetIDForIdent("Status"), 1);
					break;

				case "pause":
					SetValue($this->GetIDForIdent("Status"), 2);
					break;

				default:
					break;
			}
		}

		public function GetTimeElapsed(int $elapsed) {
			$stunden = floor($elapsed / 3600);
			$minuten = floor(($elapsed - ($stunden * 3600)) / 60);
			$sekunden = round($elapsed - ($stunden * 3600) - ($minuten * 60), 0);

			$niced_elapsed = sprintf("%02d:%02d:%02d", $stunden, $minuten, $sekunden);

			SetValue($this->GetIDForIdent("TimeElapsed"), $niced_elapsed);
		}

		public function KeepAlive()
		{
			if($this->HasActiveParent()) {
				$this->Send("status\n");
			}
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