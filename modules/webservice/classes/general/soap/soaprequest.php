<?php


/**
 * <br><br>
 *
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre>
 * <code>// Формируем запрос к веб-сервису ws.strikeiron.com/relauto/iplookup/DNS $request = new CSOAPRequest( "DNSLookup", "http://tempuri.org/"); $request-&gt;addSOAPHeader( "LicenseInfo xmlns=\"http://ws.strikeiron.com\"", array("UnregisteredUser" =&gt; array( "EmailAddress" =&gt; "qwerty@mail.ru" )) ); $request-&gt;addParameter("server", "www.yandex.ru");</code>
 * </pre>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/webservice/classes/csoaprequest/index.php
 * @author Bitrix
 */
class CSOAPRequest extends CSOAPEnvelope
{
    /// The request name
    var $Name;

    /// The request target namespace
    var $Namespace;

	/// Headers
	var $Headers = array();

    /// Additional body element attributes.
    var $BodyAttributes = array();

    /// Contains the request parameters
    var $Parameters = array(); 
	
    public function CSOAPRequest( $name="", $namespace="", $parameters = array() )
    {
        $this->Name = $name;
        $this->Namespace = $namespace;

        // call the parents constructor
        $this->CSOAPEnvelope();

        foreach( $parameters as $name => $value )
        {
            $this->addParameter( $name, $value );
        }
    }

    public function name()
    {
        return $this->Name;
    }

	public function get_namespace()
    {
        return $this->Namespace;
    }
	
	public function GetSOAPAction($separator = '/')
	{			
		if ($this->Namespace[strlen($this->Namespace)-1] != $separator)
		{
			return $this->Namespace . $separator . $this->Name;
		}
		return $this->Namespace . $this->Name;
	}
    
    
    /**
    * <p>Метод добавляет в SOAP запрос часть заголовка. Метод динамичный.</p>
    *
    *
    * @param string $name  Название сообщения в заголовке soap запроса.
    *
    * @param  $value  Обычно - ассоциативный массив описывающий содержание сообщения в
    * заголовке запроса. См. CXMLCreator::encodeValueLight.
    *
    * @return void 
    *
    * <h4>Example</h4> 
    * <pre>
    * $request-&gt;addSOAPHeader( 
    *     "LicenseInfo xmlns=\"http://ws.strikeiron.com\"",
    *     array(
    *         "UnregisteredUser" =&gt; array( "EmailAddress" =&gt; "qwerty@mail.ru" ))
    *     );
    * </pre>
    *
    *
   * @link http://dev.1c-bitrix.ru/api_help/webservice/classes/csoaprequest/addsoapheader.php
    * @author Bitrix
    */
    public function addSOAPHeader( $name, $value )
    {
    	$this->Headers[] = CXMLCreator::encodeValueLight($name, $value);
    }

	//     Adds a new attribute to the body element.
    
    /**
    * <p>Метод добавляет атрибут к тегу <b>body</b> SOAP запроса. Метод динамичный.</p>
    *
    *
    * @param string $name  Название атрибута. </ht
    *
    * @param string $value  Значение атрибута. </ht
    *
    * @return void 
    *
   * @link http://dev.1c-bitrix.ru/api_help/webservice/classes/csoaprequest/addbodyattribute.php
    * @author Bitrix
    */
    public function addBodyAttribute( $name, $value )
    {
        $this->BodyAttributes[$name] = $value;
    }
	
	//      Adds a new parameter to the request. You have to provide a prameter name
	//      and value.
    
    /**
    * <p>Метод добавляет данные для передачи в SOAP запрос. Для веб-сервиса - параметры вызываемого метода. Метод динамичный.</p>
    *
    *
    * @param string $name  Название параметра. </ht
    *
    * @param  $value  Обычно - ассоциативный массив описывающий содержание сообщения в
    * заголовке запроса. См. <a
    * href="http://dev.1c-bitrix.ru/api_help/webservice/classes/cxmlcreator/index.php">CXMLCreator::encodeValueLight</a>.
    *
    * @return void 
    *
   * @link http://dev.1c-bitrix.ru/api_help/webservice/classes/csoaprequest/addparameter.php
    * @author Bitrix
    */
    public function addParameter( $name, $value )
    {
        $this->Parameters[$name] = $value;        
    }
    
	//      Returns the request payload
    public function payload()
    {
        $root = new CXMLCreator( "soap:Envelope" );
        $root->setAttribute("xmlns:soap", BX_SOAP_ENV);

        $root->setAttribute( BX_SOAP_XSI_PREFIX, BX_SOAP_SCHEMA_INSTANCE );
        $root->setAttribute( BX_SOAP_XSD_PREFIX, BX_SOAP_SCHEMA_DATA );
        $root->setAttribute( BX_SOAP_ENC_PREFIX, BX_SOAP_ENC );

		$header = new CXMLCreator( "soap:Header" );
		$root->addChild( $header );
		
		foreach ($this->Headers as $hx)
			$header->addChild($hx);

        // add the body
        $body = new CXMLCreator( "soap:Body" );
        
        foreach( $this->BodyAttributes as $attribute => $value)
        {
            $body->setAttribute( $attribute, $value );
        }

        // add the request
        $request = new CXMLCreator( $this->Name );
        $request->setAttribute("xmlns", $this->Namespace);

        // add the request parameters
        $param = null;
        foreach ( $this->Parameters as $parameter => $value )
        {
            unset( $param );
            $param = CXMLCreator::encodeValueLight( $parameter, $value );

            if ( $param == false )
                ShowError( "Error enconding data for payload" );
            $request->addChild( $param );
        }

        $body->addChild( $request );
        $root->addChild( $body );
        return CXMLCreator::getXMLHeader().$root->getXML();
    }
}

?>