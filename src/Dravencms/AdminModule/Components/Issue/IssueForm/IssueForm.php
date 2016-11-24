<?php
/*
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301  USA
 */

namespace Dravencms\AdminModule\Components\Issue\IssueForm;

use Dravencms\Components\BaseFormFactory;
use Gitlab\Exception\RuntimeException;
use Gitlab\Model\Issue;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Salamek\Gitlab\Gitlab;

/**
 * Description of IssueForm
 *
 * @author Adam Schubert <adam.schubert@sg1-game.net>
 */
class IssueForm extends Control
{
    /** @var BaseFormFactory */
    private $baseFormFactory;

    /** @var Gitlab */
    private $gitlab;

    /** @var Issue|null */
    private $issue = null;

    /** @var array */
    public $onSuccess = [];

    /**
     * IssueForm constructor.
     * @param BaseFormFactory $baseFormFactory
     * @param Gitlab $gitlab
     * @param Issue|null $issue
     */
    public function __construct(
        BaseFormFactory $baseFormFactory,
        Gitlab $gitlab,
        Issue $issue = null
    ) {
        parent::__construct();

        $this->issue = $issue;

        $this->baseFormFactory = $baseFormFactory;
        $this->gitlab = $gitlab;


        if ($this->issue) {
            $labelsPairs = [];
            foreach($this->issue->labels AS $label)
            {
                $labelsPairs[$label->name] = $label->name;
            }

            $this['form']->setDefaults([
                'title' => $this->issue->title,
                'description' => $this->issue->description,
                'labels' => $labelsPairs
            ]);
        }
    }

    /**
     * @return \Dravencms\Components\BaseForm
     */
    protected function createComponentForm()
    {
        $form = $this->baseFormFactory->create();

        $form->addText('title')
            ->setRequired('Please enter title.');

        $form->addTextArea('description');

        $labelsPairs = [];
        foreach($this->gitlab->getLabels() AS $label)
        {
            $labelsPairs[$label->name] = $label->name;
        }

        $form->addMultiSelect('labels', null, $labelsPairs);

        $form->addSubmit('send');

        $form->onValidate[] = [$this, 'editFormValidate'];
        $form->onSuccess[] = [$this, 'editFormSucceeded'];

        return $form;
    }

    /**
     * @param Form $form
     */
    public function editFormValidate(Form $form)
    {
        if (!$this->presenter->isAllowed('issue', 'edit')) {
            $form->addError('Nemáte oprávění editovat issue.');
        }
    }

    /**
     * @param Form $form
     * @throws \Exception
     */
    public function editFormSucceeded(Form $form)
    {
        $values = $form->getValues();

        $params = [
            'title' => $values->title,
            'description' => $values->description,
            'labels' => implode(',', $values->labels)
        ];


        try
        {
            if ($this->issue)
            {
                $this->issue->update($params);
            }
            else
            {
                $this->gitlab->createIssue($values->title, $params);
            }
            $this->onSuccess();
        }
        catch (RuntimeException $e)
        {
            $form->addError('RuntimeException: Failed to load data from GitLab');
        }
    }

    public function render()
    {
        $template = $this->template;
        $template->gitlabError = false;
        try
        {
            $this->gitlab->getLabels();
        }
        catch (RuntimeException $e)
        {
            $template->gitlabError = true;

            $this->flashMessage('RuntimeException: Failed to load data from GitLab', 'alert-danger');
        }
        catch (\Exception $e)
        {
            $template->gitlabError = true;
            $this->flashMessage('Exception: Failed to load data from GitLab', 'alert-danger');
        }
        $template->setFile(__DIR__ . '/IssueForm.latte');
        $template->render();
    }
}