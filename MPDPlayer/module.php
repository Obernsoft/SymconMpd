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

			$this->RegisterTimer("KeepAliveTimer", 5000, 'MPDP_KeepAlive($_IPS[\'TARGET\']);');
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

			$this->RegisterVariableString("Titel", "Titel","",2);

			$this->RegisterVariableInteger("Dauer","Dauer","~UnixTimestampTime",3);

			$this->RegisterProfileIntegerEx("Mpd.Status", "Information", "", "", Array( Array(0, " << ", "", -1),
																					Array(1, " Stop ",   "", -1),
																					Array(2, " Pause ",  "", -1),
																					Array(3, " Play ",   "", -1),
																					Array(4, " >> ",    "", -1) ));

			$this->RegisterVariableInteger("Status","Status","Mpd.Status",4);
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
			$this->RegisterVariableInteger("Senderliste", "Sender", $profileName, 3);
			$this->EnableAction("Senderliste");

		}

		public function RequestAction($Ident, $Value) {

			switch($Ident) {
				case "Power":
					SetValue($this->GetIDForIdent($Ident), $Value);
					break;

				case "Volume":
					$this->SetVolume($Value);
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

				case "Senderliste":
					$this->SetNewStation($Value);
					SetValue($this->GetIDForIdent($Ident), $Value);
					break;

				default:
					throw new Exception("Invalid Ident");
			}

		}


		public function Play() {
			$this->Send("play\n");
		}

		public function Pause(int $status) {
			$this->Send("pause ".$status."\n");
		}

		public function Stop() {
			$this->Send("stop\n");
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

			if($this->GetValue("Status")==3) {
				$this->Send("play\n");
			}
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
					$stationurl    = $station['station_url'];
				}
			}
			return $stationurl;
		}

		public function Send(string $Text)
		{
			$this->SendDataToParent(json_encode(Array("DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}", "Buffer" => $Text)));
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
			//Übriggebliebene Daten auf den Buffer schreiben
			$this->SetBuffer("DataBuffer", $bufferData);
		}


		private function AnalyseData($data) {

			switch ($data) {
				case "volume":
					break;

				case "state":
					break;

				case "elapsed":
					break;

				case "songid":
					break;

				default:
					break;
			}
		}

		private function KeepAlive()
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