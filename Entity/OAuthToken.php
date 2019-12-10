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
 * @property string token
 * @property string code
 * @property int creation_date
 *
 * GETTERS
 * @property mixed expiry_date
 * @property mixed user_id
 *
 * RELATIONS
 * @property \LiamW\APIImprovements\Entity\OAuthCode OAuthCode
 */
class OAuthToken extends Entity
{
	const TOKEN_LIFETIME_SECONDS = 60 * 60; // 1 hour

	public function hasExpired()
	{
		return $this->expiry_date < \XF::$time;
	}

	public function getExpiryDate()
	{
		return $this->creation_date + self::TOKEN_LIFETIME_SECONDS;
	}

	public function getUserId()
	{
		return $this->OAuthCode->user_id;
	}

	protected function _preSave()
	{
		if ($this->isInsert())
		{
			$this->token = Utils::generateKeyValue('token_');
		}
	}

	public static function getStructure(Structure $structure)
	{
		$structure->shortName = 'LiamW\APIImprovements:OAuthToken';
		$structure->table = 'xf_liamw_apiimprovements_oauth2_token';
		$structure->primaryKey = 'token';

		$structure->columns = [
			'token' => [
				'type' => self::STR,
				'maxLength' => 64
			],
			'code' => [
				'type' => self::STR,
				'maxLength' => 64
			],
			'creation_date' => [
				'type' => self::UINT,
				'default' => \XF::$time
			]
		];
		$structure->getters = [
			'expiry_date' => true,
			'user_id' => true
		];
		$structure->relations = [
			'OAuthCode' => [
				'entity' => 'LiamW\APIImprovements:OAuthCode',
				'type' => self::TO_ONE,
				'conditions' => 'code',
				'primary' => true
			]
		];
		$structure->defaultWith = [
			'OAuthCode'
		];

		return $structure;
	}
}