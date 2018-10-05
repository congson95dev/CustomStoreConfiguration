<?php

namespace Gssi\AccessRequest\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class GetConfigAccessRequestData extends AbstractHelper
{

	const XML_PATH_ACCESS_REQUEST = 'access_request/';

	public function getConfigValue($field, $storeId = null)
	{
		return $this->scopeConfig->getValue(
			$field, ScopeInterface::SCOPE_STORE, $storeId
		);
	}

	public function isEnable($code, $storeId = null)
	{

		return $this->getConfigValue(self::XML_PATH_ACCESS_REQUEST .'module_enable_group/'. $code, $storeId);
	}

	public function getEmailOptions($code, $storeId = null)
	{

		return $this->getConfigValue(self::XML_PATH_ACCESS_REQUEST .'email_options/'. $code, $storeId);
	}

}