<?php

/**
 * @CLASS_FILE modules/crm/classes/general/restservice.php
 * 
 */
if (!CModule::IncludeModule('rest')) {
	return;
}

use Bitrix\Main;
use Bitrix\Rest\AccessException;
use Bitrix\Rest\RestException;

trait CCrmRestProxyHotMod {


	public static function logErr($var, $varName, $suffix = 'error') {
		$clName = get_called_class();
		$clName = $clName ? $clName : "proxy";
		//write to lof file !!!
	}

	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server) {
		$actionName = $name . ($nameDetails ? ('.' . implode('.', $nameDetails)) : '');
		try {
			$ownerTypeID = $this->getOwnerTypeID();
			$result = parent::processMethodRequest($name, $nameDetails, $arParams, $nav, $server);
			return $result;
		} catch (RestException $e) {
			self::logErr($arParams, "$actionName error: " . $e->getMessage());
			throw $e;
		} catch (\Throwable $t) {
			self::logErr($arParams, "THROWABLE in $actionName : " . $t->getMessage()."\n".$t->getTraceAsString(), 'throwable');
			throw $e;
		}
	}


}

class CCrmRequisiteRestProxy__ extends CCrmRequisiteRestProxy {

	use CCrmRestProxyHotMod;
}

class CCrmRequisiteLinkRestProxy__ extends CCrmRequisiteLinkRestProxy {
	//use CCrmRestProxyHotMod;
}

class CCrmRequisiteBankDetailRestProxy__ extends CCrmRequisiteBankDetailRestProxy {

	use CCrmRestProxyHotMod;
}

class CCrmAddressRestProxy__ extends CCrmAddressRestProxy {

	use CCrmRestProxyHotMod;
}

class CCrmContactRestProxy__ extends CCrmContactRestProxy {

	use CCrmRestProxyHotMod;
}

class CCrmCompanyRestProxy__ extends CCrmCompanyRestProxy {

	use CCrmRestProxyHotMod;
}

class CCrmDealRestProxy__ extends CCrmDealRestProxy {

	use CCrmRestProxyHotMod;


}

class CCrmProductRestProxy__ extends CCrmProductRestProxy {

	use CCrmRestProxyHotMod;
}

class CCrmProductRowRestProxy__ extends CCrmProductRowRestProxy {

	use CCrmRestProxyHotMod;
}
