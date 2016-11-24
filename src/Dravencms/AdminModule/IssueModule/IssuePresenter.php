<?php

namespace Dravencms\AdminModule\IssueModule;

use Dravencms\AdminModule\Components\Issue\IssueDetailFactory;
use Dravencms\AdminModule\Components\Issue\IssueFormFactory;
use Dravencms\AdminModule\Components\Issue\IssueGridFactory;
use Dravencms\AdminModule\SecuredPresenter;
use Gitlab\Model\Issue;
use Nette;
use Salamek\Gitlab\Gitlab;

/**
 * Homepage presenter.
 */
class IssuePresenter extends SecuredPresenter
{
    /** @var Gitlab @inject */
    public $gitlab;

    /** @var IssueGridFactory @inject */
    public $issueGridFactory;

    /** @var IssueFormFactory @inject */
    public $issueFormFactory;

    /** @var IssueDetailFactory @inject */
    public $issueDetailFactory;

    /** @var null|Issue */
    private $issue = null;

    public function renderDefault()
    {
        $this->template->h1 = 'Issue';
    }

    /**
     * @param $id
     */
    public function actionDetail($id)
    {
        try
        {
            $this->issue = $this->gitlab->getIssue($id);
        }
        catch (\Exception $e)
        {
            $this->flashMessage('Failed to load issue detail', 'alert-danger');
        }
    }

    /**
     * @param integer|null $id
     */
    public function actionEdit($id = null)
    {
        if ($id)
        {
            try
            {
                $this->issue = $this->gitlab->getIssue($id);
            }
            catch (\Exception $e)
            {
                $this->flashMessage('Failed to load issue detail', 'alert-danger');
            }
        }
        else
        {
            $this->template->h1 = 'Creating new issue';
        }
    }

    /**
     * @return \AdminModule\Components\Issue\IssueGrid
     */
    protected function createComponentGridIssue()
    {
        return $this->issueGridFactory->create();
    }

    /**
     * @return \AdminModule\Components\Issue\IssueDetail
     */
    protected function createComponentDetailIssue()
    {
        return $this->issueDetailFactory->create($this->issue);
    }

    /**
     * @return \AdminModule\Components\Issue\IssueForm
     */
    protected function createComponentFormIssue()
    {
        $control = $this->issueFormFactory->create($this->issue);
        $control->onSuccess[] = function(){
            $this->flashMessage('Issue has been successfully saved', 'alert-success');
            $this->redirect('Issue:');
        };
        return $control;
    }
}
