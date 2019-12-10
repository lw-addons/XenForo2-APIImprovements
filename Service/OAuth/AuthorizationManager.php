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

namespace LiamW\APIImprovements\Service\OAuth;

use LiamW\APIImprovements\Entity\OAuthAuthorizationRequest;
use LiamW\APIImprovements\Entity\OAuthClient;
use LiamW\APIImprovements\Entity\OAuthCode;
use LiamW\APIImprovements\Entity\OAuthToken;
use LiamW\APIImprovements\Utils;
use XF\Service\AbstractService;

class AuthorizationManager extends AbstractService
{
	/** @var OAuthClient */
	protected $client;

	public function __construct(\XF\App $app, OAuthClient $client)
	{
		parent::__construct($app);

		$this->client = $client;
	}

	public function createAuthorizationRequest($responseType, $redirectUri, $codeChallenge, $codeChallengeMethod, $state)
	{
		/** @var OAuthAuthorizationRequest $authzRequest */
		$authzRequest = $this->em()->create('LiamW\APIImprovements:OAuthAuthorizationRequest');
		$authzRequest->client_id = $this->client->client_id;
		$authzRequest->user_id = \XF::visitor()->user_id;
		$authzRequest->response_type = $responseType;
		$authzRequest->redirect_uri = $redirectUri;
		$authzRequest->code_challenge = $codeChallenge;
		$authzRequest->code_challenge_method = $codeChallengeMethod;
		$authzRequest->state = $state;
		$authzRequest->save();

		return $authzRequest;
	}

	/**
	 * Generate a new OAuthCode.
	 *
	 * @param OAuthAuthorizationRequest $authorizationRequest
	 *
	 * @return OAuthCode
	 * @throws \Exception
	 */
	public function createAuthorizationCode(OAuthAuthorizationRequest $authorizationRequest)
	{
		/** @var OAuthCode $code */
		$code = $this->em()->create('LiamW\APIImprovements:OAuthCode');
		$code->client_id = $this->client->client_id;
		$code->user_id = $authorizationRequest->user_id;
		$code->extra = [
			'code_challenge' => $authorizationRequest->code_challenge,
			'code_challenge_method' => $authorizationRequest->code_challenge_method,
			'redirect_uri_hash' => $authorizationRequest->redirect_uri ? $this->hashRedirectUri($authorizationRequest->redirect_uri) : null
		];
		$code->save();

		return $code;
	}

	public function createOAuthToken(OAuthCode $code, $codeVerifier, &$oauthError, &$oauthErrorDescription)
	{
		if ($code->client_id != $this->client->client_id)
		{
			throw new \InvalidArgumentException("Code does not match client");
		}

		if ($code->extra['code_challenge_method'] != 'S256')
		{
			throw new \RuntimeException("Invalid code_challenge_method found for passed code.");
		}

		if ($code->OAuthTokens->count())
		{
			$oauthError = '';

			return null;
		}

		if ($code->hasExpired())
		{
			$oauthError = 'invalid_grant';
			$oauthErrorDescription = 'The code has expired.';

			return null;
		}

		if (Utils::s256($codeVerifier) !== $code->extra['code_challenge'])
		{
			$oauthError = 'invalid_request';
			$oauthErrorDescription = 'The code verifier does not match';

			return null;
		}

		/** @var OAuthToken $token */
		$token = $this->em()->create('LiamW\APIImprovements:OAuthToken');
		$token->code = $code->code;
		$token->client_id = $this->client->client_id;
		$token->user_id = $code->user_id;
		$token->save();

		return $token;
	}

	protected function hashRedirectUri($redirectUri)
	{
		return sha1($redirectUri);
	}
}