<?
//System, not for use
Class CAllIdeaManagmentEmailNotify {
    
    const SUBSCRIBE_ALL = 'A';
    const SUBSCRIBE_ALL_IDEA = 'AI';
    //const SUBSCRIBE_ALL_IDEA_COMMENT = 'AIC';
    const SUBSCRIBE_IDEA_COMMENT = 'I';
    
    private $Notify = NULL;
    private static $Enable = true;
    
    public function __construct($parent)
    {
        $this->Notify = $parent;
    }
    
    public function IsAvailable()
    {
        return CModule::IncludeModule('blog') && NULL!=$this->Notify && self::$Enable;
    }
    
    public function Send()
    {
        if(!$this->IsAvailable())
            return false;
        
        $arNotification = $this->Notify->getNotification();
        
        //No need to send about updates;
        if($arNotification["ACTION"] == "UPDATE")
            return 0;
        
        $arEmailSubscribe = array();
        $arAllSubscribe = $this->GetList(array(), array("ID" => array(self::SUBSCRIBE_ALL, self::SUBSCRIBE_IDEA_COMMENT.$arNotification["POST_ID"])), false, false, array("USER_ID", "USER_EMAIL"));
        while($r = $arAllSubscribe->Fetch())
            if(check_email($r["USER_EMAIL"]))
                $arEmailSubscribe[$r["USER_ID"]] = $r["USER_EMAIL"];

        foreach($arEmailSubscribe as $UserId => $Email)
        {
            //Avoid to send notification to author
            if($UserId == $arNotification["AUTHOR_ID"])
                continue;
            
            $arNotification["EMIAL_TO"] = $Email;
            //ADD_IDEA_COMMENT, ADD_IDEA
            CEvent::Send($arNotification["ACTION"].'_'.$arNotification["TYPE"], SITE_ID, $arNotification);
        }
        
        return count($arEmailSubscribe)>0;
    }
    
    static public function Disable()
    {
        self::$Enable = false;
    }
    
    static public function Enable()
    {
        self::$Enable = true;
    }
}
?>