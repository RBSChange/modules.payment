<?php
class payment_PaypalCancelAction extends f_action_BaseAction
{
	/**
	 * @see f_action_BaseAction::_execute()
	 *
	 * @param Context $context
	 * @param Request $request
	 */
	protected function _execute($context, $request)
	{	
 		$remoteAddr = $_SERVER['REMOTE_ADDR'];
        $requestUri = $_SERVER['REQUEST_URI'];
        $ms = payment_ModuleService::getInstance();	
		$ms->log("BANKING CANCEL PAYPAL from [".$remoteAddr." : ".$requestUri."]");
		
		$currentWebsite = website_WebsiteModuleService::getInstance()->getCurrentWebsite();
		$url = $currentWebsite->getUrlForLang(RequestContext::getInstance()->getLang());
		$context->getController()->redirectToUrl($url);
		return VIEW::NONE;
	}

	/**
	 * @return Integer
	 */
	public function getRequestMethods()
	{
		return Request::POST | Request::GET;
	}

	/**
	 * @return Boolean
	 */
	public final function isSecure()
	{
		return false;
	}
}