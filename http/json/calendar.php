<?php
/**
 * ownCloud - Calendar App
 *
 * @author Georg Ehrke
 * @copyright 2014 Georg Ehrke <oc.list@georgehrke.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Calendar\Http\JSON;

use OCP\Calendar\ITimezone;
use OCA\Calendar\Utility\JSONUtility;

class JSONCalendar extends JSON {

	/**
	 * json-encoded data
	 * @var array
	 */
	private $jsonArray;


	/**
	 * @brief get headers for response
	 * @return array
	 */
	public function getHeaders() {
		return array_merge(
			parent::getHeaders(),
			array(
				'Content-type' => 'application/json; charset=utf-8',
			)
		);
	}


	/**
	 * @brief get json-encoded string containing all information
	 * @return array
	 */
	public function serialize() {
		$this->jsonArray = array();

		$properties = get_object_vars($this->object);
		foreach($properties as $key => $value) {
			$getter = 'get' . ucfirst($key);
			$value = $this->object->{$getter}();

			$this->setProperty(strtolower($key), $value);
		}

		return $this->jsonArray;
	}


	/**
	 * @brief set property 
	 * @param string $key
	 * @param mixed $value
	 */
	private function setProperty($key, $value) {
		switch($key) {
			case 'color':
			case 'description':
			case 'displayname':
			case 'backend':
				$this->jsonArray[$key] = strval($value);
				break;

			case 'timezone':
				$this->jsonArray[$key] = ($value instanceof ITimezone) ? $value->getTzId() : null;
				break;

			case 'publicuri':
				$this->jsonArray['uri'] = strval($value);
				break;

			case 'ctag':
			case 'id':
			case 'order':
				$this->jsonArray[$key] = intval($value);
				break;

			case 'enabled':
				$this->jsonArray[$key] = (bool) $value; //boolval is PHP >= 5.5 only
				break;

			case 'components':
				$this->jsonArray[$key] = JSONUtility::getComponents($value);
				break;

			case 'cruds':
				$this->jsonArray[$key] = JSONUtility::getCruds($value);
				break;

			case 'ownerId':
			case 'userId':
				$key = substr($key, 0, -2);
				$this->jsonArray[$key] = JSONUtility::getUserInformation($value);
				break;

			case 'lastpropertiesupdate':
			case 'lastobjectupdate':
			case 'privateuri':
				break;

			default:
				$this->jsonArray[$key] = $value;
				break;
			
		}
	}
}