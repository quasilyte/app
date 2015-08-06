<?php

namespace Wikia\Service\User\Preferences;

use User;
use Wikia\DependencyInjection\InjectorBuilder;
use Wikia\DependencyInjection\Module;
use Wikia\Persistence\User\Preferences\PreferencePersistence;
use Wikia\Persistence\User\Preferences\PreferencePersistenceModuleMySQL;
use Wikia\Persistence\User\Preferences\PreferencePersistenceSwaggerService;

class PreferenceModule implements Module {
	public function configure(InjectorBuilder $builder) {
		$builder
			->bind(PreferenceService::class)->toClass(PreferenceKeyValueService::class)
			->bind(UserPreferences::HIDDEN_PREFS)->to(function() {
				global $wgHiddenPrefs;
				return $wgHiddenPrefs;
			})
			->bind(UserPreferences::DEFAULT_PREFERENCES)->to(function() {
				return User::getDefaultPreferences();
			})
			->bind(UserPreferences::FORCE_SAVE_PREFERENCES)->to(function() {
				global $wgGlobalUserProperties;
				return $wgGlobalUserProperties;
			});

		self::bindMysqlService($builder);
//		self::bindSwaggerService($builder);
	}

	private static function bindMysqlService(InjectorBuilder $builder) {
		$masterProvider = function() {
			global $wgExternalSharedDB, $wgSharedDB;

			if (isset($wgSharedDB)) {
				$db = wfGetDB(DB_MASTER, [], $wgExternalSharedDB);
			} else {
				$db = wfGetDB(DB_MASTER);
			}

			return $db;
		};
		$slaveProvider = function() {
			global $wgExternalSharedDB, $wgSharedDB;

			if (isset($wgSharedDB)) {
				$db = wfGetDB(DB_SLAVE, [], $wgExternalSharedDB);
			} else {
				$db = wfGetDB(DB_SLAVE);
			}

			return $db;
		};
		$whiteListProvider = function() {
			global $wgUserPreferenceWhiteList;
			return $wgUserPreferenceWhiteList;
		};

		$builder->addModule(new PreferencePersistenceModuleMySQL($masterProvider, $slaveProvider, $whiteListProvider));
	}

	private static function bindSwaggerService(InjectorBuilder $builder) {
		$builder->bind(PreferencePersistence::class)->toClass(PreferencePersistenceSwaggerService::class);
	}
}
