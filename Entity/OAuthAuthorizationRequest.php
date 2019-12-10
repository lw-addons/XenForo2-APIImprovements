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
 * @property string authorization_request_id
 * @property string client_id
 * @property int user_id
 * @property int creation_date
 * @property string response_type
 * @property string redirect_uri
 * @property string|null code_challenge
 * @property string|null code_challenge_method
 * @property array scopes
 * @property string|null state
 * @property array extra
 *
 * RELATIONS
 * @property \LiamW\APIImprovements\Entity\OAuthClient OAuthClient
 * @property \XF\Entity\User User
 */
class OAuthAuthorizationRequest extends Entity
{
	protected function _preSave()
	{
		if ($this->isInsert())
		{
			$this->authorization_request_id = Utils::generateKeyValue('authorization_request_');
		}
	}

	public static function getStructure(Structure $structure)
	{
		$structure->shortName = 'LiamW\APIImprovements:OAuthAuthorizationRequest';
		$structure->table = 'xf_liamw_apiimprovements_oauth2_authorization_request';
		$structure->primaryKey = 'authorization_request_id';

		$structure->columns = [
			'authorization_request_id' => [
				'type' => self::STR,
				'maxLength' => 64
			],
			'client_id' => [
				'type' => self::STR,
				'maxLength' => 64,
				'required' => true
			],
			'user_id' => [
				'type' => self::UINT,
				'required' => true
			],
			'creation_date' => [
				'type' => self::UINT,
				'default' => \XF::$time
			],
			'response_type' => [
				'type' => self::STR,
				'required' => true
			],
			'redirect_uri' => [
				'type' => self::STR,
				'required' => true
			],
			'code_challenge' => [
				'type' => self::STR,
				'nullable' => true
			],
			'code_challenge_method' => [
				'type' => self::STR,
				'nullable' => true
			],
			'scopes' => [
				'type' => self::JSON_ARRAY,
				'default' => []
			],
			'state' => [
				'type' => self::STR,
				'nullable' => true
			],
			'extra' => [
				'type' => self::JSON_ARRAY,
				'default' => []
			]
		];
		$structure->relations = [
			'OAuthClient' => [
				'entity' => 'LiamW\APIImprovements:OAuthClient',
				'type' => self::TO_ONE,
				'conditions' => 'client_id',
				'primary' => true
			],
			'User' => [
				'entity' => 'XF:User',
				'type' => self::TO_ONE,
				'conditions' => 'user_id',
				'primary' => true
			]
		];
		$structure->defaultWith = ['OAuthClient'];

		return $structure;
	}
}