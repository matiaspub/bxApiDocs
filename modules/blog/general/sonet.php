<?
class CBlogSoNetPost
{
	public static function CanUserDeletePost($ID, $userID, $blogOwnerID, $groupOwnerID)
	{
		$ID = IntVal($ID);
		$userID = IntVal($userID);
		$blogOwnerID = IntVal($blogOwnerID);
		$groupOwnerID = IntVal($groupOwnerID);

		$blogModulePermissions = $GLOBALS["APPLICATION"]->GetGroupRight("blog");
		if ($blogModulePermissions >= "W")
			return True;
	
		$arPost = CBlogPost::GetByID($ID);

		if (empty($arPost))
			return False;
			
		if($groupOwnerID > 0)
		{
			$arBlogUser = CBlogUser::GetByID($userID, BLOG_BY_USER_ID);
			if ($arBlogUser && $arBlogUser["ALLOW_POST"] != "Y")
				return False;

			$perms = BLOG_PERMS_DENY;
			if (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_GROUP, $groupOwnerID, "blog", "view_post"))
				$perms = BLOG_PERMS_READ;
			if (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_GROUP, $groupOwnerID, "blog", "write_post"))
				$perms = BLOG_PERMS_WRITE;
			if (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_GROUP, $groupOwnerID, "blog", "full_post"))
				$perms = BLOG_PERMS_FULL;

			if($perms >= BLOG_PERMS_WRITE  && $arPost["AUTHOR_ID"] == $userID)
				return true;

			if($perms > BLOG_PERMS_WRITE)
				return true;
		
		}
		else
		{
			$arBlog = CBlog::GetByID($arPost["BLOG_ID"]);

			$arBlogUser = CBlogUser::GetByID($userID, BLOG_BY_USER_ID);
			if ($arBlogUser && $arBlogUser["ALLOW_POST"] != "Y")
				return False;

			$perms = BLOG_PERMS_DENY;
			if (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_USER, $blogOwnerID, "blog", "view_post"))
				$perms = BLOG_PERMS_READ;
			if (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_USER, $blogOwnerID, "blog", "write_post"))
				$perms = BLOG_PERMS_WRITE;
			if (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_USER, $blogOwnerID, "blog", "full_post"))
				$perms = BLOG_PERMS_FULL;

			if($perms >= BLOG_PERMS_WRITE  && $arPost["AUTHOR_ID"] == $userID)
				return true;

			if($perms > BLOG_PERMS_WRITE)
				return true;
		}
		
		return False;
	}
	
	public static function OnGroupDelete($ID)
	{
		$ID = IntVal($ID);
		if($ID <= 0)
			return false;
		$arBlog = CBlog::GetBySocNetGroupID($ID);
		if(!empty($arBlog))
		{
			CBlog::Delete($arBlog["ID"]);
		}
	}
}
?>