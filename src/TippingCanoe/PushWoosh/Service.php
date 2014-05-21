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

		/**
		 * @param \TippingCanoe\PushWoosh\Message $message
		 * @return mixed
		 */
		public function push(Message $message) {

			$data = $this->prepareMessage($message);

			$this->addApplicationCode($data);
			$this->addAccessToken($data);
			return $this->callApi('createMessage', $data);

		}

		/**
		 * @param \TippingCanoe\PushWoosh\Message $message
		 * @param \TippingCanoe\PushWoosh\Device[] $devices
		 * @return mixed
		 */
		public function pushToDevices(Message $message, array $devices) {
			$message->addDevices($devices);
			return $this->push($message);
		}

		//
		//
		//

		protected function addMessage(Message $message, array &$data) {
			if(!array_key_exists('notifications', $data))
				$data['notifications'] = [];
			$data['notificaions'][] = $message->toArray();
		}

		/**
		 * Inserts the access token to an array.
		 *
		 * @param array $data
		 */
		protected function addAccessToken(array &$data) {
			$data['auth'] = $this->accessToken;
		}

		/**
		 * Inserts the application code to an array.
		 *
		 * @param array $data
		 */
		protected function addApplicationCode(array &$data) {
			$data['application'] = $this->applicationCode;
		}

		/**
		 * Handles the low level calls to the PushWoosh API
		 * @param $method
		 * @param $data
		 * @return mixed
		 */
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
			curl_close($ch);

			return json_decode($response);

		}

	}

}