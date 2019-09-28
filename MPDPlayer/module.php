<?
	class MpdPlayer extends IPSModule
	{

		public function ApplyChanges()
		{
			//Never delete this line!
			parent::ApplyChanges();

			//Connect to available splitter or create a new one
			$this->ConnectParent("{0749F7C9-2EE0-444F-91D5-6450D494208F}");

			$this->RegisterTimer("KeepAliveTimer", 60000, 'MpdPlayer_KeepAlive()');

		}

		/**
		* This function will be available automatically after the module is imported with the module control.
		* Using the custom prefix this function will be callable from PHP and JSON-RPC through:
		*
		* IOT_Send($id, $text);
		*
		*/
		public function Send($Text)
		{
			$this->SendDataToParent(json_encode(Array("DataID" => "{B87AC955-F258-468B-92FE-F4E0866A9E18}", "Buffer" => $Text)));
		}

		public function ReceiveData($JSONString)
		{
			$data = json_decode($JSONString);
			IPS_LogMessage("IOTest", utf8_decode($data->Buffer));
			//Parse and write values to our variables
			echo $data;
		}

		public function KeepAlive()
		{
			$this->Send("ping");


		}
	}
?>