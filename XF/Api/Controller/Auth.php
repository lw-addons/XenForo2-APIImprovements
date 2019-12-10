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

namespace LiamW\APIImprovements\XF\Api\Controller;

use LiamW\APIImprovements\Entity\OAuthClient;
use LiamW\APIImprovements\Entity\OAuthCode;
use LiamW\APIImprovements\Entity\OAuthToken;

class Auth extends XFCP_Auth
{
	public function actionPostToken()
	{
		$client = $this->getOAuthClient();

		if (!$client)
		{
			return $this->noPermission();
		}

		$input = $this->filter([
			'grant_type' => 'str',
			'code' => 'str',
			'redirect_uri' => 'str'
		]);

		switch ($input['grant_type'])
		{
			case 'authorization_code':
				/** @var OAuthCode $code */ $code = $this->repository('LiamW\APIImprovements:OAuth')->findPendingCode($input['code'], $input['redirect_uri']);

				if (!$code)
				{
					return $this->apiError(null, null, [
						'error' => 'invalid_grant'
					], 400);
				}

				$token = $code->createToken();

				if ($token->expiry_date < \XF::$time)
				{
					return $this->apiError(null, null, [
						'error' => 'invalid_grant'
					], 400);
				}

				return $this->apiResult([
					'access_token' => $token->token,
					'token_type' => 'bearer',
					'expires_in' => OAuthToken::TOKEN_LIFETIME_SECONDS
				]);
				break;
		}

		return $this->error('', 400);
	}

	/**
	 * @return \XF\Mvc\Entity\Entity|OAuthClient
	 */
	protected function getOAuthClient()
	{
		$clientId = $this->request->get('client_id');

		return $this->em()->find('LiamW\APIImprovements:OAuthClient', $clientId);
	}
}