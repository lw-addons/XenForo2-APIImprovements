<?php
/**
 * API Improvements - XenForo add-on to add various additional features to the XF 2.1 API
 * Copyright (C) 2019 Liam Williams
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace LiamW\APIImprovements\Entity;

use LiamW\APIImprovements\Utils;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;
use XF\Util\Random;

/**
 * COLUMNS
 *
 * @property string code
 * @property string authorization_request_id
 * @property int creation_date
 * @property int user_id
 * @property array extra
 *
 * GETTERS
 * @property mixed expiry_date
 *
 * RELATIONS
 * @property \LiamW\APIImprovements\Entity\OAuthAuthorizationRequest OAuthAuthorizationRequest
 * @property \XF\Mvc\Entity\AbstractCollection|\LiamW\APIImprovements\Entity\OAuthToken[] OAuthTokens
 * @property \XF\Entity\User User
 */
class OAuthCode extends Entity
{
	const CODE_LIFETIME_SECONDS = 5 * 60; // 5 minutes

	public function getExpiryDate()
	{
		return $this->creation_date + self::CODE_LIFETIME_SECONDS;
	}

	public function hasExpired()
	{
		return $this->expiry_date < \XF::$time;
	}

	protected function _preSave()
	{
		if ($this->isInsert())
		{
			$this->code = Utils::generateKeyValue('code_');
		}
	}

	public static function getStructure(Structure $structure)
	{
		$structure->shortName = 'LiamW\APIImprovements:OAuthCode';
		$structure->table = 'xf_liamw_apiimprovements_oauth2_code';
		$structure->primaryKey = 'code';

		$structure->columns = [
			'code' => [
				'type' => self::STR,
				'maxLength' => 64
			],
			'authorization_request_id' => [
				'type' => self::STR,
				'required' => true
			],
			'creation_date' => [
				'type' => self::UINT,
				'default' => \XF::$time
			],
			'user_id' => [
				'type' => self::UINT,
				'required' => true
			],
			'extra' => [
				'type' => self::JSON_ARRAY
			]
		];
		$structure->getters = [
			'expiry_date' => true
		];
		$structure->relations = [
			'OAuthAuthorizationRequest' => [
				'entity' => 'LiamW\APIImprovements:OAuthAuthorizationRequest',
				'type' => self::TO_ONE,
				'conditions' => 'authorization_request_id',
				'primary' => true
			],
			'OAuthTokens' => [
				'entity' => 'LiamW\APIImprovements:OAuthToken',
				'type' => self::TO_MANY,
				'conditions' => 'code',
				'primary' => true
			],
			'User' => [
				'entity' => 'XF:User',
				'type' => self::TO_ONE,
				'conditions' => 'user_id',
				'primary' => true
			]
		];
		$structure->defaultWith = ['OAuthAuthorizationRequest'];

		return $structure;
	}
}