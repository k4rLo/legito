<?php

namespace App\Presenters;

use Nette;
use App\Model\ArticleManager;


class HomepagePresenter extends Nette\Application\UI\Presenter
{
	/** @var ArticleManager */
	private $articleManager;


	public function __construct(ArticleManager $articleManager)
	{
		$this->articleManager = $articleManager;
	}

	public function renderDefault($page = 1)
	{
		$this->template->page = $page;
		$this->template->posts = $this->articleManager->getPublicArticles($page);
	}

}
