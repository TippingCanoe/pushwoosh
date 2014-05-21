<?php namespace TippingCanoe\Pushwoosh {

	use Carbon\Carbon;


	class Message {

		/** @var string|string[] */
		public $content;

		/** @var string|array|object */
		public $data;

		/** @var \Carbon\Carbon|string */
		public $sendDate = 'now';

		/** @var bool */
		public $ignoreUserTimezone = true;

		/** @var int */
		public $pageId;

		/** @var string */
		public $link;

		/** @var int */
		public $minimizeLink;

		/** @var int[] */
		public $platforms;

		/** @var string[] */
		protected $devices = [];

		/** @var string */
		public $filter;

		/** @var array */
		public $conditions;

		//
		// iOS Attributes
		//

		/** @var int */
		public $iosBadges;

		/** @var string */
		public $iosSound;

		/** @var int */
		public $iosTtl;

		/** @var array */
		public $iosRootParams;

		/** @var int */
		public $apnsTrimContent = 1;

		//
		// Android Attributes
		//

		/** @var array */
		public $androidRootParams;

		/** @var string */
		public $androidSound;

		/** @var string */
		public $androidHeader;

		/** @var string */
		// public $androidCustomIcon;

		/** @var string */
		public $androidBanner;

		/** @var int */
		public $androidGcmTtl;

		//
		//
		//

		/**
		 * @param \TippingCanoe\PushWoosh\Device $device
		 */
		public function addDevice(Device $device) {
			$this->devices[] = $device->id;
		}

		/**
		 * @param \TippingCanoe\PushWoosh\Device[] $devices
		 */
		public function addDevices(array $devices) {
			array_merge(
				array_map([$this, 'addDevice'], $devices),
				$this->devices
			);
		}

		//
		//
		//

		public function toArray() {

			$data = [];

			foreach($this as $attribute => $value) {

				switch($attribute) {

					case 'sendDate':
						if($value instanceof Carbon)
							$value = $value->format('Y-m-d G:i');
					break;

					default:
						// By default, do not serialize...
						if(
							// null
							is_null($value)
							// Empty arrays
							|| (is_array($value) && empty($value))
						) {
							continue(2);
						}
					break;

				}

				$data[$this->snake($attribute)] = $value;

			}

			return $data;

		}

		//
		//
		//

		protected function snake($value, $delimiter = '_') {
			$replace = '$1'.$delimiter.'$2';
			return ctype_lower($value) ? $value : strtolower(preg_replace('/(.)([A-Z])/', $replace, $value));
		}

/*

	// Amazon related
	"adm_root_params": {"key": "value"}, // custom key-value object
	"adm_sound": "push.mp3",
	"adm_header": "Header",
	"adm_icon": "icon.png",
	"adm_custom_icon": "http://example.com/image.png",
	"adm_banner": "http://example.com/banner.png",
	"adm_ttl": 3600, // Optional. Time to live parameter - the maximum lifespan of a message in seconds

	// Windows Phone related.
	"wp_type": "Tile",           // notification type. 'Tile' or 'Toast'. Raw notifications are not supported. 'Tile' if default
	"wp_background": "/Resources/Red.jpg", // Tile image
	"wp_backbackground": "/Resources/Green.jpg", // Back tile image
	"wp_backtitle": "back title",  // Back tile title
	"wp_count": 3,               // Optional. Integer. Badge for notification

	// Mac OS X related
	"mac_badges": 3,
	"mac_sound": "sound.caf",
	"mac_root_params": {"content-available":1},
	"mac_ttl": 3600, // Optional. Time to live parameter - the maximum lifespan of a message in seconds

	// WNS related
	"wns_content": { // Content (XML or raw) of notification encoded in MIME's base64 in form of Object( language1: 'content1', language2: 'content2' ) OR String
	   "en": "PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz48YmFkZ2UgdmFsdWU9ImF2YWlsYWJsZSIvPg==",
	   "de": "PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz48YmFkZ2UgdmFsdWU9Im5ld01lc3NhZ2UiLz4="
	},
	"wns_type": "Badge", // 'Tile' | 'Toast' | 'Badge' | 'Raw'
	"wns_tag": "myTag", // Optional. Used in the replacement policy of the Tile. An alphanumeric string of no more than 16 characters.

	 // Safari related
	"safari_title": "Title", // Title of the notification
	"safari_action": "Click here", // Optional
	"safari_url_args": ["firstArgument", "secondArgument"], // Optional if your application url template has no placeholders
	"safari_ttl": 3600, // Optional. Time to live parameter - the maximum lifespan of a message in seconds

*/

	}

}