<?
//MySQL
Class CIdeaManagmentEmailNotify extends CAllIdeaManagmentEmailNotify {

    public static function Add($Entity, $UserId = false)
    {
        global $USER, $DB;
        if(!$UserId)
            $UserId = $USER->GetId();
        
        if(is_numeric($Entity))
            $Entity = self::SUBSCRIBE_IDEA_COMMENT.$Entity;
        
        //Return if Subscription already exists
        if(self::GetList(array(), array("ID" => array($Entity), "USER_ID" => $UserId))->Fetch())
            return false;
        
        $arFields = array(
            "USER_ID" => $UserId,
            "ID" => $Entity
        );
        
        $arInsert = $DB->PrepareInsert("b_idea_email_subscribe", $arFields);
        $strSql =
            "INSERT INTO b_idea_email_subscribe(".$arInsert[0].") ".
            "VALUES(".$arInsert[1].")";
        $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
        
        return $DB->LastID()!==false; //Think...
    }
    
    public static function Delete($Entity, $UserId = false)
    {
        global $USER, $DB;
        if(!$UserId)
            $UserId = $USER->GetId();
        
        if(is_numeric($Entity))
            $Entity = self::SUBSCRIBE_IDEA_COMMENT.$Entity;
        
        $strSql =
            "DELETE FROM b_idea_email_subscribe 
            WHERE ID='".$DB->ForSql($Entity, 25)."' AND USER_ID=".intval($UserId);

        $DB->Query($strSql, true);
        
        return true; //Think
        //return $DB->LastID()!==false; //Think...
    }
    
    public static function GetList($arOrder = Array("ID" => "DESC"), $arFilter = Array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
    {
        if(!CModule::IncludeModule('blog'))
            return false;
        
        global $DB;

        if (count($arSelectFields) == 0)
            $arSelectFields = array("ID", "USER_ID", "USER_EMAIL");
        
        // FIELDS -->
        $arFields = array(
            "ID" => array("FIELD" => "IES.ID", "TYPE" => "string"),
            "USER_ID" => array("FIELD" => "IES.USER_ID", "TYPE" => "int"),
            "USER_EMAIL" => array("FIELD" => "U.EMAIL", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (IES.USER_ID = U.ID)"),
        );
        
        $arSqls = CBlog::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);
        $arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);
        
        //Make Query
        $strSql =
            "SELECT ".$arSqls["SELECT"]." ".
            "FROM b_idea_email_subscribe IES ".
            " ".$arSqls["FROM"]." ";
        if (strlen($arSqls["WHERE"]) > 0)
            $strSql .= "WHERE ".$arSqls["WHERE"]." ";
        if (strlen($arSqls["GROUPBY"]) > 0)
            $strSql .= "GROUP BY ".$arSqls["GROUPBY"]." ";
        if (strlen($arSqls["ORDERBY"]) > 0)
            $strSql .= "ORDER BY ".$arSqls["ORDERBY"]." ";

        return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
    }
}
?>