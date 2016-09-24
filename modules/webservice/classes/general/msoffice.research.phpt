<?php

class CMSSOAPResearch extends CSOAPServerResponser
{
	var $provider_id;	
	var $service_id;
	var $add_tittle;
	var $query_path;
	var $registration_path;

	public static function OnBeforeRequest(&$cserver) 
	{
		//AddMessage2Log(mydump($cserver->GetRequestData()));	
	}

	public function ProcessRequestBody(&$cserver, $body) 
	{
		$functionName = $body->name();
		$namespaceURI = $body->namespaceURI();
		$requestNode = $body;
		
		if ($functionName == "Registration")
		{
			$root = new CXMLCreator("RegistrationResponse");
			$root->setAttribute("xmlns", "urn:Microsoft.Search");
			
			$regres = new CXMLCreator("RegistrationResult");
			$root->addChild($regres);
						
			$prup = new CXMLCreator("ProviderUpdate");
			$prup->setAttribute("xmlns", "urn:Microsoft.Search.Registration.Response");
			$prup->setAttribute("revision", "1"); 			
			$prup->setAttribute("build", "1");
			$regres->addChild($prup);	
			
			$stat = new CXMLCreator("Status");
			$stat->setData("SUCCESS");
			$prup->addChild($stat);
						
			$providers = array(
				
					"Provider" => array (
						"Message" => "Тестовая служба.",
						"Id" => "{$this->provider_id}",
						"Name" => "Тестовая служба. {$this->add_tittle}",
						"QueryPath" => $this->query_path,
						"RegistrationPath" => $this->registration_path,
						"AboutPath" => "http://www.bitrix.ru/",
						"Type" => "SOAP",
						"Revision" => "1",
						"Services" => array(
							"Service" => array(
								"Id" => "{$this->service_id}",
								"Name" => "Тестовая служба. {$this->add_tittle}",
								"Description" => "Тестовая служба для тестирования soap сервера.",
								"Copyright" => "(c) Bitrix.",
								"Display" => "On",
								"Category" => "ECOMMERCE_GENERAL",
								"Parental" => "Unsupported",
							)
						)						
					)					
			
			);

			$providersEncoded = CSOAPRequest::encodeValueLight("Providers", $providers);
			$prup->addChild($providersEncoded);		
			
			$cserver->ShowRawResponse($root, true);
			
			//AddMessage2Log($cserver->GetResponseData());
			
			return true;
		}
		
		return false;
	}
}

?>
