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

namespace LiamW\APIImprovements\Admin\Controller;

use LiamW\APIImprovements\Repository\OAuth;
use LiamW\APIImprovements\Service\OAuth\ClientManager;
use XF\Admin\Controller\AbstractController;
use XF\Mvc\ParameterBag;

class OAuthClient extends AbstractController
{
	protected function preDispatchController($action, ParameterBag $params)
	{
		$this->assertSuperAdmin();
		$this->assertPasswordVerified(1800); // 30 minutes
	}

	public function actionIndex()
	{
		$repo = $this->getOAuthRepo();
		$clients = $repo->findOAuthClientsForList()->fetch();

		$newClientId = $this->filter('client_id_new', 'str');
		if ($newClientId)
		{
			/** @var \LiamW\APIImprovements\Entity\OAuthClient $newClient */
			$newClient = $this->em()->find('LiamW\APIImprovements:OAuthClient', $newClientId);

			if ($newClient->creation_date < \XF::$time - (1 * 60))
			{
				return $this->redirect($this->buildLink('oauth-clients'));
			}
		}
		else
		{
			$newClient = null;
		}

		$viewParams = [
			'clients' => $clients,
			'newClient' => $newClient
		];
		return $this->view('LiamW\APIImprovements:OAuthClient\List', 'liamw_apiimprovements_oauth_client_list', $viewParams);
	}

	protected function clientAddEdit(\LiamW\APIImprovements\Entity\OAuthClient $client)
	{
		$viewParams = [
			'client' => $client
		];
		return $this->view('LiamW\APIImprovements:OAuthClient\Edit', 'liamw_apiimprovements_oauth_client_edit', $viewParams);
	}

	public function actionEdit(ParameterBag $params)
	{
		$client = $this->assertOAuthClientExists($params->client_id);
		return $this->clientAddEdit($client);
	}

	public function actionAdd(ParameterBag $params)
	{
		$client = $this->em()->create('LiamW\APIImprovements:OAuthClient');
		return $this->clientAddEdit($client);
	}

	protected function clientSaveProcess(ClientManager $clientManager)
	{
		$form = $this->formAction();

		$form->basicValidateServiceSave($clientManager, function () use ($clientManager)
		{
			$input = $this->filter([
				'label' => 'str',
				'description' => '?str',
				'redirect_uris' => 'array-str',
				'active' => 'bool',
				'username' => '?str'
			]);

			$clientManager->setLabel($input['label']);
			$clientManager->setDescription($input['description']);
			$clientManager->setRedirectURIs($input['redirect_uris']);
			$clientManager->setActive($input['active']);
			if ($input['username'])
			{
				$clientManager->setClientUser($input['username']);
			}
		});

		return $form;
	}

	public function actionSave(ParameterBag $params)
	{
		$this->assertPostOnly();

		if ($params->client_id)
		{
			$client = $this->assertOAuthClientExists($params['client_id']);
			$newClient = false;
		}
		else
		{
			$client = $this->em()->create('LiamW\APIImprovements:OAuthClient');
			$newClient = true;
		}

		/** @var ClientManager $clientManager */
		$clientManager = $this->service('LiamW\APIImprovements:OAuth\ClientManager', $client);

		$this->clientSaveProcess($clientManager)->run();

		if ($newClient)
		{
			$params = ['client_id_new' => $client->client_id];
		}
		else
		{
			$params = [];
		}

		return $this->redirect($this->buildLink('oauth-clients', null, $params));
	}

	public function actionDelete(ParameterBag $params)
	{
		$client = $this->assertOAuthClientExists($params->client_id);

		/** @var \XF\ControllerPlugin\Delete $plugin */
		$plugin = $this->plugin('XF:Delete');
		return $plugin->actionDelete($client, $this->buildLink('oauth-clients/delete', $client), $this->buildLink('oauth-clients/edit', $client), $this->buildLink('oauth-clients'), $client->label);
	}

	public function actionViewKey(ParameterBag $params)
	{
		$client = $this->assertOAuthClientExists($params->client_id);

		$viewParams = [
			'client' => $client
		];
		return $this->view('LiamW\APIImprovements:OAuthClient\View', 'liamw_apiimprovements_oauth_client_view', $viewParams);
	}

	public function actionToggle()
	{
		/** @var \XF\ControllerPlugin\Toggle $plugin */
		$plugin = $this->plugin('XF:Toggle');
		return $plugin->actionToggle('LiamW\APIImprovements:OAuthClient');
	}

	/**
	 * @param $clientId
	 * @param null $with
	 * @param null $phraseKey
	 *
	 * @return \XF\Mvc\Entity\Entity|\LiamW\APIImprovements\Entity\OAuthClient
	 */
	protected function assertOAuthClientExists($clientId, $with = null, $phraseKey = null)
	{
		return $this->assertRecordExists('LiamW\APIImprovements:OAuthClient', $clientId, $with, $phraseKey);
	}

	/**
	 * @return \XF\Mvc\Entity\Repository|OAuth
	 */
	protected function getOAuthRepo()
	{
		return $this->repository('LiamW\APIImprovements:OAuth');
	}
}