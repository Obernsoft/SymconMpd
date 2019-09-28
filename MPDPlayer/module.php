<?
	class MPDPlayer extends IPSModule
	{
		public function create {
            // Diese Zeile nicht löschen.
            parent::Create();

			$this->RegisterPropertyString("IPAddress", "");
			$this->RegisterPropertyString("Host", "");
			$this->RegisterPropertyString("Password", "");

			$this->RegisterTimer("KeepAliveTimer", 30000, 'MPDPlayer_KeepAlive()');
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
			$this->ForceParent("{0749F7C9-2EE0-444F-91D5-6450D494208F}");
		}

		public function Send($Text)
		{
			$this->SendDataToParent(json_encode(Array("DataID" => "{B87AC955-F258-468B-92FE-F4E0866A9E18}", "Buffer" => $Text)));
		}

		public function ReceiveData($JSONString)
		{
			$data = json_decode($JSONString);
			IPS_LogMessage("MPDPlayer", utf8_decode($data->Buffer));
			//Parse and write values to our variables
			echo $data;
		}

		public function KeepAlive()
		{
			$this->Send("ping");


		}
	}
?>