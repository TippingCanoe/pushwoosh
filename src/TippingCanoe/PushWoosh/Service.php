<?php namespace TippingCanoe\Pushwoosh {


	class Service {

		/** @var string */
		protected $applicationCode;

		/** @var string */
		protected $groupCode;

		/** @var string */
		protected $accessToken;

		public function setApplicationCode($applicationCode) {
			$this->applicationCode = $applicationCode;
		}

		public function setAccessToken($accessToken) {
			$this->accessToken = $accessToken;
		}

		// Sending to a device should be in Laravel Mobile Devices?
		// ToDo: Create a push driver system in LMD and then create & register a driver.
		//public function sendToDevice() {
		//
		//}
		// ToDo: Register/unregister a device
		// ToDo: Add/remove a tag


		public function push(Message $message) {

			$data = [
				'notifications' => [
					$message->toArray()
				]
			];
			$this->addApplicationCode($data);
			$this->addAccessToken($data);

			return $this->callApi('createMessage', $data);

		}

		//
		//
		//

		protected function addAccessToken(array &$data) {
			$data['auth'] = $this->accessToken;
		}

		protected function addApplicationCode(array &$data) {
			$data['application'] = $this->applicationCode;
		}

		protected function callApi($method, $data) {

			$url = 'https://cp.pushwoosh.com/json/1.3/' . $method;
			$request = json_encode([
				'request' => $data
			]);

			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
			curl_setopt($ch, CURLOPT_HEADER, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $request);

			$response = curl_exec($ch);
			//$info = curl_getinfo($ch);
			curl_close($ch);

			return json_decode($response);

		}

	}

}