<?php namespace TippingCanoe\Pushwoosh {

	use Carbon\Carbon;
	use SimpleXMLElement;


	class Message {

		/** @var string|array|object */
		public $data;

		/** @var string|string[] */
		public $content;

		/** @var string[] */
		public $secondaryContent;

		/** @var string */
		public $imageUri;

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

		/** @var int */
		public $sendRate;
		//
		//
		//

		/** @var \TippingCanoe\Pushwoosh\Device[] */
		protected $devices = array();

		/** @var array */
		protected $conditions = array();

		const IOS = 1;
		const BLACKBERRY = 2;
		const ANDROID = 3;
		const NOKIA_ASHA = 4;
		const WINDOWS_PHONE = 5;
		//const ??? = 6;
		const OSX = 7;
		const WINDOWS_8 = 8;
		const AMAZON = 9;
		const SAFARI = 10;

		/** @var int[] */
		public $platforms;

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
		// Windows Notification Services Attributes
		//

		const WNS_TILE = 1;
		const WNS_TOAST = 2;
		const WNS_BADGE = 3;
		const WNS_RAW = 4;

		/** @var int */
		public $wnsType = self::WNS_TOAST;

		const TOAST_TYPE_1 = 1;
		const TOAST_TYPE_2 = 2;
		const TOAST_TYPE_3 = 3;

		/** @var int */
		public $wnsToastType = self::TOAST_TYPE_1;

		/** @var string */
		public $wnsContent;

		/** @var string */
		public $wnsTag;

		//
		//
		//

		/**
		 * Adds a condition to the push message as per Pushwoosh
		 *
		 * @param string $name
		 * @param string $operator
		 * @param string|array $operand
		 */
		public function addCondition($name, $operator, $operand) {

			$condition = new Condition();
			$condition->name = $name;
			$condition->operator = $operator;
			$condition->operand = $operand;

			$this->conditions[] = $condition;

		}

		/**
		 * Adds a single device to receive the push.
		 *
		 * @param \TippingCanoe\Pushwoosh\Device $device
		 */
		public function addDevice(Device $device) {
			$this->devices[] = $device;
		}

		/**
		 * Adds an array of devices to receive the push.
		 *
		 * @param \TippingCanoe\Pushwoosh\Device[] $devices
		 */
		public function addDevices($devices) {

			if(!is_array($devices))
				$devices = func_get_args();

			$this->devices = array_merge(
				$devices,
				$this->devices
			);

		}

		/**
		 * Wipes out the internal devices array.
		 */
		public function clearDevices() {
			$this->devices = array();
		}

		//
		//
		//

		/**
		 * Correctly serializes all attributes.
		 *
		 * @return array
		 */
		public function toArray() {

			$data = array();

			foreach($this as $attribute => $value) {

				// Never send empty arrays.
				if(
					(is_array($value) && empty($value))
					// Some fields are meta fields offered as a convenience
					// by this library when targeting multiple platforms.
					|| in_array($attribute, array(
						'wnsToastType',
						'imageUri',
						'secondaryContent'
					))
				)
					continue;

				switch($attribute) {
					case 'sendRate':
						// Not supported for device specific sends.
						if(is_array($this->devices) && count($this->devices))
							$value = null;
					break;

					case 'sendDate':
						if($value instanceof Carbon)
							$value = $value->format('Y-m-d G:i');
					break;

					case 'devices':

						$value = array_map(function ($device) {
							return $device->id;
						}, $value);

					break;

					case 'conditions':

						$value = array_map(function (Condition $condition) {

							if(is_array($condition->operand))
								return $condition->operand;

							return sprintf('%s %s %s', $condition->name, $condition->operator, $condition->operand);

						}, $value);

					break;

					case 'wnsType':

						if($value == self::WNS_TOAST)
							$value = 'Toast';
						elseif($value == self::WNS_TILE)
							$value = 'Tile';

					break;

					// WNS Schema
					// http://msdn.microsoft.com/en-us/library/windows/apps/br212853.aspx
					case 'wnsContent':

						// Generate WNS notification data if none has been assigned.
						if(!$value) {

							// ToDo: Break this out into a more sophisticated pipeline.
							if($this->wnsType == self::WNS_TOAST) {

								// Toast Schema
								// http://msdn.microsoft.com/en-us/library/windows/apps/br230849.aspx
								// http://msdn.microsoft.com/en-us/library/windows/apps/br230846.aspx
								$toast = new SimpleXMLElement('<?xml version="1.0" ?><toast></toast>');

								if($this->data)
									$toast->addAttribute('launch', json_encode($this->data));

								$visual = $toast->addChild('visual');
								$binding = $visual->addChild('binding');

								// Make sure to pick the right template.
								// http://msdn.microsoft.com/en-ca/library/windows/apps/hh761494.aspx
								if($this->imageUri) {
									$binding->addAttribute('template', sprintf('ToastImageAndText0%s', $this->wnsToastType));
									$image = $binding->addChild('image');
									$image->addAttribute('id', '1');
									$image->addAttribute('src', $this->imageUri);
									$image->addAttribute('alt', '');
								}
								else {
									$binding->addAttribute('template', sprintf('ToastText0%s', $this->wnsToastType));
								}

								// Build out the text elements in order, attending to the id in sequence.
								$textId = 1;

								$primary = $binding->addChild('text');
								$primary[0] = $this->content;
								$primary->addAttribute('id', $textId);
								++$textId;

								if($this->wnsToastType > self::TOAST_TYPE_1 && $this->secondaryContent) {

									foreach($this->secondaryContent as $secondaryContent) {
										$secondary = $binding->addChild('text');
										$secondary[0] = $secondaryContent;
										$secondary->addAttribute('id', $textId);
										++$textId;
									}

								}

								$value = base64_encode($toast->asXML());

							}

						}

					break;

					default:
						// By default, do not serialize nulls.
						if(is_null($value))
							continue(2);

					break;

				}

				// By default, do not serialize nulls.
				if(!is_null($attribute) && !is_null($value))
					$data[$this->snake($attribute)] = $value;

			}

			return $data;

		}

		//
		//
		//

		/**
		 * Utility method to perform snake_casing of attributes.
		 *
		 * @param $value
		 * @param string $delimiter
		 * @return string
		 */
		protected function snake($value, $delimiter = '_') {
			$replace = '$1'.$delimiter.'$2';
			return ctype_lower($value) ? $value : strtolower(preg_replace('/(.)([A-Z])/', $replace, $value));
		}

	/*
	//
	// Unimplemented
	//

	public $filter;

	//
	//
	//

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

	 // Safari related
	"safari_title": "Title", // Title of the notification
	"safari_action": "Click here", // Optional
	"safari_url_args": ["firstArgument", "secondArgument"], // Optional if your application url template has no placeholders
	"safari_ttl": 3600, // Optional. Time to live parameter - the maximum lifespan of a message in seconds
	*/

	}

}
