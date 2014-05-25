<?php

class cGenTest
{
	var $id, $name;
}

class cGenTest2
{
	var $id, $tests /*array of classes cGenTest*/;
}

class CGenericWSDLTestWS extends IWebService
{
	public static function wsTestStart($str1, $str2)
	{
		return $str1.$str2." :Эта функция склеила два входящих параметра.";
	}	
	
public static 	function wsTestStart2($str1, $stra /*array str*/)
	{
		return $str1.":".implode(".",$stra);
	}
	
public static 	function wsTestStart3($sGetTest /*struct*/)
	{
		return $sGetTest["id"]."worked!;)\n".mydump($sGetTest);
	}
	
public static 	function wsTestStart4($str1, $sGetTestX /*array of struct*/)
	{
		return "{$str1}worked!;)\n".mydump($sGetTestX);
	}
	
public static 	function wsTestStart5($cGetTest /* simple class */)
	{
		
		return $cGetTest->id."worked!;)\n".mydump($cGetTest);
	}
	
public static 	function wsTestStart6($cGetTest2 /* class that contains array of classes */)
	{		
		return $cGetTest2->id."worked!;)\n".mydump($cGetTest2);
	}
	
public static 	function wsTestStartOut1($str1, $str2, $int3)
	{
		/* return array of strings */
		return array($str1, $str2, (string)$int3);
	}
	
public static 	function wsTestStartOut2($str1)
	{
		/* return struct (assoc array) */
		return array("id" => "51212312", "name" => "struct return test test xxx.");
	}
	
public static 	function wsTestStartOut3($str1)
	{
		/* return class */
		$test = new cGenTest();
		$test->id = "123";
		$test->name = "asd";
		return $test;
	}
	
public static 	function wsTestStartOut4($str1)
	{
		/* return array of structs */
		return array(array("id" => "123", "name" => "yyy.".$str1),
			array("id" => "234", "name" => "xxx.".$str1));
	}
	
public static 	function wsTestStartOut5($str1)
	{
		/* return array of classes */
		$test = new cGenTest();
		$test->id = "123";
		$test->name = "asd";
		$test2 = new cGenTest();
		$test2->id = "222";
		$test2->name = "xxx";
		return array($test, $test2);
	}
	
public static 	function wsTestStartOut6($str1)
	{
		/* return array of classes that contain array of classes */
		$testx = new cGenTest2();
		$testx->id = 7564378;
		$test = new cGenTest();
		$test->id = "123";
		$test->name = "asd";
		$test2 = new cGenTest();
		$test2->id = "222";
		$test2->name = "xxx";
		$testx->tests = array($test, $test2);
		
		$testy = new cGenTest2();
		$testy->id = 123456;
		$test = new cGenTest();
		$test->id = "999";
		$test->name = "lll";
		$test2 = new cGenTest();
		$test2->id = "888";
		$test2->name = "kkk";
		$testy->tests = array($test, $test2);
		return array($testx, $testy);
	}

	public static function GetWebServiceDesc() 
	{
		$wsdesc = new CWebServiceDesc();
		$wsdesc->wsname = "bitrix.wsdl.test1";
		$wsdesc->wsclassname = "CGenericWSDLTestWS";
		$wsdesc->wsdlauto = true;
		$wsdesc->wsendpoint = CWebService::GetDefaultEndpoint();
		$wsdesc->wstargetns = CWebService::GetDefaultTargetNS();
		
		$wsdesc->classTypes = array();
		$wsdesc->structTypes = array();
		
		/* this passed as assoc array */
		$wsdesc->structTypes["sGenTest"] =
		array(
			"id" => array("varType" => "integer"),
			"name" => array("varType" => "string", "strict" => "no"),
			"testa" => array("varType" => "ArrayOfString5",
							"arrType" => "string",
							"maxOccursA" => "5",
							"nillableA" => "true", "strict" => "no"),
			"testb" => array("varType" => "ArrayOfInteger",
							"arrType" => "integer", "strict" => "no"),
			"xxx" => array("varType" => "integer", "strict" => "no"),
		);
		
		$wsdesc->structTypes["sGenLite"] =
		array(
			"id" => array("varType" => "integer"),
			"name" => array("varType" => "string", "strict" => "no")			
		);
		
		$wsdesc->structTypes["sGenTestX"] =
		array(
			"id" => array("varType" => "integer"),
			"name" => array("varType" => "string", "strict" => "no"),
			"testa" => array("varType" => "ArrayOfsGenLite",
							"arrType" => "sGenLite",
							"nillableA" => "true")		
		);
		
		/* this tryed to be loaded into a class */
		$wsdesc->classTypes["cGenTest"] =
		array(
			"id" => array("varType" => "integer"),
			"name" => array("varType" => "string"),
			/*array of string, somthing complex*/
		);
		
		$wsdesc->classTypes["cGenTest2"] =
		array(
			"id" => array("varType" => "integer"),
			"tests" => array(
				"varType" => "ArrayOfcGenTest",
				"arrType" => "cGenTest")			
		);
		
		$wsdesc->classes = array(
			"CGenericWSDLTestWS" => array(
				"wsTestStart" => array(
					"type"		=> "public",
					"name"		=> "wsTestStart",
					"description"	=> "Test function, shows us base functionality.<br/> [out]string wsTest1([in]string, [in]string);",
					/*
					 * strict default on. can be "no".
					 */
					"input"		=> array(
						"str1" =>array("varType" => "string", "strict" => "no"),
						"str2" =>array("varType" => "string")
						),
					"output"	=> array(
						"simpleType" => array("varType" => "string")
						)
				),
				"wsTestStart2" => array(
					"type"		=> "public",
					"name"		=> "wsTestStart2",
					"input"		=> array(
						"str1" =>array("varType" => "string"),
						"stra" =>array("varType" => "ArrayOfString",
										"arrType" => "string")
						),
					"output"	=> array(
						"simpleType" => array("varType" => "string")
						)
				),
				"wsTestStart3" => array(
					"type"		=> "public",
					"name"		=> "wsTestStart3",
					"input"		=> array(
						"str1" =>array("varType" => "sGenTest")
						),
					"output"	=> array(
						"str2" => array("varType" => "string")
						)
				),
				"wsTestStart4" => array(
					"type"		=> "public",
					"name"		=> "wsTestStart4",
					"input"		=> array(
						"str1" =>array("varType" => "string"),
						"str2" =>array("varType" => "ArrayOfsGenTestX",
										"arrType" => "sGenTestX")
						),
					"output"	=> array(
						"str3" => array("varType" => "string")
						)
				),
				"wsTestStart5" => array(
					"type"		=> "public",
					"name"		=> "wsTestStart5",
					"input"		=> array(
						"str1" =>array("varType" => "cGenTest")
						),
					"output"	=> array(
						"str2" => array("varType" => "string")
						)
				),
				"wsTestStart6" => array(
					"type"		=> "public",
					"name"		=> "wsTestStart6",
					"input"		=> array(
						"cGetTest2" =>array("varType" => "cGenTest2")
						),
					"output"	=> array(
						"return" => array("varType" => "string")
						)
				),
				"wsTestStartOut1" => array(
					"type"		=> "public",
					"name"		=> "wsTestStartOut1",
					"input"		=> array(
						"str1" =>array("varType" => "string"),
						"str2" =>array("varType" => "string"),
						"int3" =>array("varType" => "int")
						),
					"output"	=> array(
						"return" => array(
							"varType" => "ArrayOfStringX",
							"arrType" => "string")
						)
				),
				"wsTestStartOut2" => array(
					"type"		=> "public",
					"name"		=> "wsTestStartOut2",
					"input"		=> array("str1" =>array("varType" => "string")),
					"output"	=> array("return" => array("varType" => "sGenLite"))
				),
				"wsTestStartOut3" => array(
					"type"		=> "public",
					"name"		=> "wsTestStartOut3",
					"input"		=> array("str1" =>array("varType" => "string")),
					"output"	=> array("return" => array("varType" => "cGenTest"))
				),
				"wsTestStartOut4" => array(
					"type"		=> "public",
					"name"		=> "wsTestStartOut4",
					"input"		=> array("str1" =>array("varType" => "string")),
					"output"	=> array("return" => array("varType" => "ArrayOfSGenLite", "arrType" => "sGenLite"))
				),
				"wsTestStartOut5" => array(
					"type"		=> "public",
					"name"		=> "wsTestStartOut5",
					"input"		=> array("str1" =>array("varType" => "string")),
					"output"	=> array("return" => array("varType" => "ArrayOfCGenTest", "arrType" => "cGenTest"))
				),
				"wsTestStartOut6" => array(
					"type"		=> "public",
					"name"		=> "wsTestStartOut6",
					"input"		=> array("str1" =>array("varType" => "string")),
					"output"	=> array("return" => array("varType" => "ArrayOfCGenTest2", "arrType" => "cGenTest2"))
				)
			)	
		);
		
		return $wsdesc;
	}
	
public static 	function TestComponent() 
	{
		global $APPLICATION;
		$client = new CSOAPClient( $_SERVER["HTTP_HOST"], $APPLICATION->GetCurPage() );
		$request = new CSOAPRequest( "wsTestStart", CWebService::GetDefaultTargetNS() );
		//$request->addParameter("str1", "qwe");
		$request->addParameter("str2", "asd");
		$response = $client->send( $request );
		echo "<b>Call wsTestStart </b>";
		if ( $response->isFault() )
		{
		    print( "SOAP fault: " . $response->faultCode(). " - " . $response->faultString() . "" );
		}
		else
		    echo "[OK]: ".mydump($response->Value)."<br>";
		    
		$request = new CSOAPRequest( "wsTestStart2", "http://bitrix.soap/" );
		$request->addParameter("str1", "testx");
		$request->addParameter("stra", array("1:ArrayOfStringEl" => "asd", "2:ArrayOfStringEl" => "zxc"));
		$response = $client->send( $request );
		echo "<b>Call wsTestStart2 </b>";
		if ( $response->isFault() )
		{
		    print( "SOAP fault: " . $response->faultCode(). " - " . $response->faultString() . "" );
		}
		else
		    echo "[OK]: ".mydump($response->Value)."<br>";
		    
		$request = new CSOAPRequest( "wsTestStart3", "http://bitrix.soap/" );
		$request->addParameter("str1", array("id"=>"123", "name"=>"qwe"));
		$response = $client->send( $request );
		echo "<b>Call wsTestStart3 </b>";
		if ( $response->isFault() )
		{
		    print( "SOAP fault: " . $response->faultCode(). " - " . $response->faultString() . "" );
		}
		else
		    echo "[OK]: ".mydump($response->Value)."<br>";
		    
		$request = new CSOAPRequest( "wsTestStart4", "http://bitrix.soap/" );
		$request->addParameter("str1", "qwe");
		$request->addParameter("str2", array(
			"1:ArrayOfsGenTestXEl" => array(
				"id" => "123",
				"name" => "qwe",
				"testa" => array(
						"1:ArrayOfsGenLiteEl" => array("id" => "56", "name" => "asd"),
						"2:ArrayOfsGenLiteEl" => array("id" => "13", "name" => "fjhg")
					)
			),
			"2:ArrayOfsGenTestXEl" => array(
			"id" => "7653",
				"name" => "dfgsdf DASD",
				"testa" => array(
						"1:ArrayOfsGenLiteEl" => array("id" => "78", "name" => "ty"),
						"2:ArrayOfsGenLiteEl" => array("id" => "99", "name" => "3425rte")
					)
			)
			));
		$response = $client->send( $request );
		echo "<b>Call wsTestStart4 </b>";
		if ( $response->isFault() )
		{
		    print( "SOAP fault: " . $response->faultCode(). " - " . $response->faultString() . "" );
		}
		else
		    echo "[OK]: ".mydump($response->Value)."<br>";
		    
		$request = new CSOAPRequest( "wsTestStartOut1", "http://bitrix.soap/" );
		$request->addParameter("str1", "qwe");
		$request->addParameter("str2", "fjdfhgfdh");
		$request->addParameter("int3", "123");
		$response = $client->send( $request );
		echo "<b>Call wsTestStartOut1 </b>";
		if ( $response->isFault() )
		{
		    print( "SOAP fault: " . $response->faultCode(). " - " . $response->faultString() . "" );
		}
		else
		    echo "[OK]: ".mydump($response->Value)."<br>";
		    
		$request = new CSOAPRequest( "wsTestStartOut2", "http://bitrix.soap/" );
		$request->addParameter("str1", "qwe");
		$response = $client->send( $request );
		echo "<b>Call wsTestStartOut2 </b>";
		if ( $response->isFault() ) print( "SOAP fault: " . $response->faultCode(). " - " . $response->faultString() . "" );
		else echo "[OK]: ".mydump($response->Value)."<br>";
		
		$request = new CSOAPRequest( "wsTestStartOut3", "http://bitrix.soap/" );
		$request->addParameter("str1", "qwe");
		$response = $client->send( $request );
		echo "<b>Call wsTestStartOut3 </b>";
		if ( $response->isFault() ) print( "SOAP fault: " . $response->faultCode(). " - " . $response->faultString() . "" );
		else echo "[OK]: ".mydump($response->Value)."<br>";
		
		$request = new CSOAPRequest( "wsTestStartOut4", "http://bitrix.soap/" );
		$request->addParameter("str1", "qwe");
		$response = $client->send( $request );
		echo "<b>Call wsTestStartOut4 </b>";
		if ( $response->isFault() ) print( "SOAP fault: " . $response->faultCode(). " - " . $response->faultString() . "" );
		else echo "[OK]: ".mydump($response->Value)."<br>";
		
		$request = new CSOAPRequest( "wsTestStartOut5", "http://bitrix.soap/" );
		$request->addParameter("str1", "qwe");
		$response = $client->send( $request );
		echo "<b>Call wsTestStartOut5 </b>";
		if ( $response->isFault() ) print( "SOAP fault: " . $response->faultCode(). " - " . $response->faultString() . "" );
		else echo "[OK]: ".mydump($response->Value)."<br>";
		
		$request = new CSOAPRequest( "wsTestStartOut6", "http://bitrix.soap/" );
		$request->addParameter("str1", "qwe");
		$response = $client->send( $request );
		echo "<b>Call wsTestStartOut6 </b>";
		if ( $response->isFault() ) print( "SOAP fault: " . $response->faultCode(). " - " . $response->faultString() . "" );
		else echo "[OK]: ".mydump($response->Value)."<br>";
	}
}

function TestWSDocumentService()
{
	$client = new CSOAPClient("ws.strikeiron.com", "/relauto/iplookup/DNS");
	$request = new CSOAPRequest( "DNSLookup", "http://tempuri.org/");
	$request->addSOAPHeader( "LicenseInfo xmlns=\"http://ws.strikeiron.com\"",
			array("UnregisteredUser" => array( "EmailAddress" => "qwerty@mail.ru" ))
		);
	$request->addParameter("server", "www.yandex.ru");
	$response = $client->send( $request );
		
	echo "<h2>SOAPRequest:</h2> <br>".str_replace("\n", "<br>", str_replace("&lt;","<br>&lt;",htmlspecialchars($client->getRawRequest())));
	echo "<h2>SOAPResponse:</h2> <br>".str_replace("\n", "<br>", htmlspecialchars($client->getRawResponse()));
}

?>
