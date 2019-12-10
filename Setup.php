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

namespace LiamW\APIImprovements;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Create;

class Setup extends AbstractSetup
{
	use StepRunnerInstallTrait;
	use StepRunnerUpgradeTrait;
	use StepRunnerUninstallTrait;

	public function installStep1()
	{
		$this->schemaManager()->createTable('xf_liamw_apiimprovements_oauth2_client', function (Create $table)
		{
			$table->addColumn('client_id', 'varchar', 64)->primaryKey();
			$table->addColumn('client_secret', 'varchar', 64);
			$table->addColumn('type', 'enum')->values(['confidential', 'public']);
			$table->addColumn('redirect_uris', 'blob');
			$table->addColumn('user_id', 'int')->nullable();
			$table->addColumn('label', 'text');
			$table->addColumn('description', 'text')->nullable();
			$table->addColumn('creation_date', 'int');
			$table->addColumn('active', 'tinyint')->setDefault(1);
			$table->addKey('user_id');
			$table->addUniqueKey('client_secret');
		});

		$this->schemaManager()->createTable('xf_liamw_apiimprovements_oauth2_code', function(\XF\Db\Schema\Create $table)
		{
			$table->addColumn('code', 'varchar', 64)->primaryKey();
			$table->addColumn('authorization_request_id', 'text');
			$table->addColumn('creation_date', 'int')->setDefault(0);
			$table->addColumn('user_id', 'int');
			$table->addColumn('extra', 'blob');
			$table->addKey('user_id');
			$table->addKey('authorization_request_id');
		});

		$this->schemaManager()->createTable('xf_liamw_apiimprovements_oauth2_token', function (\XF\Db\Schema\Create $table)
		{
			$table->addColumn('token', 'varchar', 64)->primaryKey();
			$table->addColumn('code', 'varchar', 64);
			$table->addColumn('creation_date', 'int')->setDefault(0);
			$table->addKey('code');
		});

		$this->schemaManager()->createTable('xf_liamw_apiimprovements_oauth2_authorization_request', function (\XF\Db\Schema\Create $table)
		{
			$table->addColumn('authorization_request_id', 'varchar', 64)->primaryKey();
			$table->addColumn('client_id', 'varchar', 64);
			$table->addColumn('user_id', 'int');
			$table->addColumn('creation_date', 'int')->setDefault(0);
			$table->addColumn('response_type', 'text');
			$table->addColumn('redirect_uri', 'text');
			$table->addColumn('code_challenge', 'text')->nullable();
			$table->addColumn('code_challenge_method', 'text')->nullable();
			$table->addColumn('scopes', 'blob');
			$table->addColumn('state', 'text')->nullable();
			$table->addColumn('extra', 'blob');
			$table->addKey('client_id');
			$table->addKey('user_id');
		});
	}
}