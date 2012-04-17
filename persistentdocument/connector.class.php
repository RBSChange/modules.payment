<?php
/**
 * payment_persistentdocument_connector
 * @package payment.persistentdocument
 */
class payment_persistentdocument_connector extends payment_persistentdocument_connectorbase 
{
	/**
	 * @var string
	 */
	private $HTMLPayment;
	
	/**
	 * @return string
	 */	
	public function getTemplateViewName()
	{
		return 'Error';
	}
	
	/**
	 * @return string
	 */
	public function getHTMLSelection()
	{
		return $this->HTMLSelection;
	}
	
	/**
	 * @param string $html
	 */
	public function setHTMLPayment($html)
	{
		$this->HTMLPayment = $html;
	}
	
	/**
	 * @return string
	 */	
	public function getHTMLPayment()
	{
		return $this->HTMLPayment;
	}
	
	/**
	 * @return string
	 */	
	public function getSelectionAsHtml()
	{
		return $this->getDocumentService()->getSelectionAsHtml($this);
	}

	/**
	 * @return string
	 */
	public function getLogLabel()
	{
		if (Framework::inDevelopmentMode())
		{
			return $this->getId() .": ".$this->getLabel();
		}
		return null;
	}
}