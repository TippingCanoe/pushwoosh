<?php namespace TippingCanoe\Pushwoosh {


	class Service {

		/** @var string */
		protected $applicationCode;

		/** @var string */
		protected $groupCode;

		/** @var string */
		protected $accessToken;

		/** @var array */
		public $lastLog;

		/**
		 * Configures the library with an application code from Pushwoosh
		 *
		 * @param $applicationCode
		 */
		public function setApplicationCode($applicationCode) {
			$this->applicationCode = $applicationCode;
		}

		/**
		 * Configures the library with an application code from Pushwoosh
		 *
		 * @param $accessToken
		 */
		public function setAccessToken($accessToken) {
			$this->accessToken = $accessToken;
		}

		/**
		 * Pushes the data supplied.
		 *
		 * @param \TippingCanoe\Pushwoosh\Message|\TippingCanoe\Pushwoosh\Message[] $data
		 * @return mixed
		 */
		public function push($data) {
			if($data instanceof Message) {
				return $this->pushSingle($data);
			}
			elseif(is_array($data)) {
				return $this->pushArray($data);
			}
		}

		/**
		 * Pushes a single message.
		 *
		 * @param Message $message
		 * @return mixed
		 */
		public function pushSingle(Message $message) {

			$data = array();

			$this->addMessage($message, $data);
			$this->addApplicationCode($data);
			$this->addAccessToken($data);

			return $this->callApi('createMessage', $data);

		}

		/**
		 * Pushes an array containing compatible data.
		 *
		 * @param array $array
		 * @return mixed
		 */
		public function pushArray(array $array) {

			$data = array();

			foreach($array as $dataItem) {
				if($dataItem instanceof Message) {
					$this->addMessage($dataItem, $data);
				}
			}

			if(count($data)) {
				$this->addApplicationCode($data);
				$this->addAccessToken($data);
				return $this->callApi('createMessage', $data);
			}

			return null;

		}

		/**
		 * Adds devices to a message and sends them, mostly a convenience method, but also ensures the message is reusable after.
		 *
		 * @param \TippingCanoe\Pushwoosh\Message $message
		 * @param \TippingCanoe\Pushwoosh\Device|\TippingCanoe\Pushwoosh\Device[] $devices
		 * @return mixed
		 */
		public function pushToDevices(Message $message, $devices) {
			$message->addDevices($devices);
			$result = $this->push($message);
			$message->clearDevices();
			return $result;
		}

		//
		// Low level
		//

		/**
		 * Adds a message to be sent.
		 *
		 * @param Message $message
		 * @param array $data
		 */
		protected function addMessage(Message $message, array &$data) {
			if(!array_key_exists('notifications', $data))
				$data['notifications'] = array();
			$data['notifications'][] = $message->toArray();
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
		 * Handles the low level calls to the Pushwoosh API
		 * @param $method
		 * @param $data
		 * @return mixed
		 */
		protected function callApi($method, $data) {

			$url = 'https://cp.pushwoosh.com/json/1.3/' . $method;
			$request = json_encode(array(
				'request' => $data
			));

			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
			curl_setopt($ch, CURLOPT_HEADER, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $request);

			$response = curl_exec($ch);

			// Store some logging information that can be picked up by anything interested.
			$this->lastLog = array(
				'data' => $data,
				'info' => curl_getinfo($ch)
			);

			curl_close($ch);

			return json_decode($response);

		}

	}

}