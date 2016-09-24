<?
Class CIdeaManagmentIdeaComment
{
	private $CommentId = false;

	public function __construct($CommentId = false)
	{
		$this->SetId($CommentId);
	}

	public function IsAvailable()
	{
		return $this->CommentId>0 && CModule::IncludeModule('blog');
	}

	public function SetID($StatusId)
	{
		$this->CommentId = $StatusId;
		return $this;
	}

	public function Get()
	{
		if(!$this->IsAvailable())
			return false;

		return CBlogComment::GetList(
			array(),
			array("ID" => $this->CommentId)
		)->Fetch();
	}

	public function Bind()
	{
		if(!$this->IsAvailable())
			return false;

		//Comment doesn't exists
		$arComment = $this->Get();
		if(!$arComment)
			return false;

		$arIdea = CBlogPost::GetList(
			array(),
			array("ID" => $arComment["POST_ID"]),
			false,
			false,
			array("ID", CIdeaManagment::UFAnswerIdField)
		)->Fetch();
		//Post doesn't exists
		if($arIdea)
		{
			//Already binded
			if(is_array($arIdea[CIdeaManagment::UFAnswerIdField]) && in_array($arComment["ID"], $arIdea[CIdeaManagment::UFAnswerIdField]))
			{
				return false;
			}
			elseif(!is_array($arIdea[CIdeaManagment::UFAnswerIdField]))
			{
				$arIdea[CIdeaManagment::UFAnswerIdField] = array();
			}

			$arIdea[CIdeaManagment::UFAnswerIdField][] = $arComment["ID"];
			unset($arIdea["ID"]);

			return CBlogPost::Update($arComment["POST_ID"], $arIdea)>0;
		}

		return false;
	}

	public function UnBind()
	{
		if(!$this->IsAvailable())
			return false;

		//Comment doesn't exists
		$arComment = $this->Get();
		if(!$arComment)
			return false;

		$arIdea = CBlogPost::GetList(array(), array("ID" => $arComment["POST_ID"]), false, false, array("ID", CIdeaManagment::UFAnswerIdField))->Fetch();
		if($arIdea)
		{
			if(!is_array($arIdea[CIdeaManagment::UFAnswerIdField]))
				$arIdea[CIdeaManagment::UFAnswerIdField] = array();
			$arIdea[CIdeaManagment::UFAnswerIdField] = array_unique($arIdea[CIdeaManagment::UFAnswerIdField]);

			$key = array_search($arComment["ID"], $arIdea[CIdeaManagment::UFAnswerIdField]);
			if(is_numeric($key))
			{
				unset($arIdea[CIdeaManagment::UFAnswerIdField][$key], $arIdea["ID"]);
				return CBlogPost::Update($arComment["POST_ID"], $arIdea)>0;
			}
		}

		return false;
	}
}
?>