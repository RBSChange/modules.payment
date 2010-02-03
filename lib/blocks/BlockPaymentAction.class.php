<?php
/**
 * payment_BlockPaymentAction
 * @package modules.payment.lib.blocks
 */
class payment_BlockPaymentAction extends website_BlockAction
{
	/**
	 * @see website_BlockAction::execute()
	 *
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	function execute($request, $response)
	{
		try 
		{
			$connector = $this->getConnector();	
			$order = $this->getOrderDocument();
			$connector->getDocumentService()->setPaymentInfo($connector, $this->getOrderDocument());
			$request->setAttribute('connector', $connector);
			$request->setAttribute('order', $order);
			if ($this->isInBackoffice())
			{
				$viewName = website_BlockView::BACKOFFICE;
			}
			else
			{
				$viewName = $connector->getTemplateViewName();
			}
		}
		catch (Exception $e)
		{
			Framework::exception($e);
			return website_BlockView::ERROR;
		}		
		return $viewName;
	}
	
	
	/**
	 * @return payment_persistentdocument_connector
	 */
	private function getConnector()
	{
		$connectorId = $this->findLocalParameterValue('connectorid');
		$connector = DocumentHelper::getDocumentInstance($connectorId);
		if (!$connector instanceof payment_persistentdocument_connector) 
		{
			throw new Exception('Invalid payment mode');
		}
		return $connector;	
	}
	
	/**
	 * @return payment_Order
	 */
	private function getOrderDocument()
	{
		$orderId = $this->findLocalParameterValue('orderid');
		$order = DocumentHelper::getDocumentInstance($orderId);
		if (!$order instanceof payment_Order) 
		{
			throw new Exception('Invalid order');
		}
		return $order;
	}
}