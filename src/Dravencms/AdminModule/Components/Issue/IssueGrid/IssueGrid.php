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

namespace Dravencms\AdminModule\Components\Issue;

use Dravencms\Components\BaseGridFactory;
use App\Model\Locale\Repository\LocaleRepository;
use Gitlab\Exception\RuntimeException;
use Michelf\MarkdownExtra;
use Nette\Application\UI\Control;
use Nette\Utils\Html;
use Nette\Utils\Strings;
use Salamek\Gitlab\Gitlab;

/**
 * Description of TagGrid
 *
 * @author Adam Schubert <adam.schubert@sg1-game.net>
 */
class IssueGrid extends Control
{

    /** @var BaseGridFactory */
    private $baseGridFactory;

    /** @var Gitlab */
    private $gitlab;

    /** @var LocaleRepository */
    private $localeRepository;

    /**
     * IssueGrid constructor.
     * @param Gitlab $gitlab
     * @param BaseGridFactory $baseGridFactory
     * @param LocaleRepository $localeRepository
     */
    public function __construct(Gitlab $gitlab, BaseGridFactory $baseGridFactory, LocaleRepository $localeRepository)
    {
        parent::__construct();

        $this->baseGridFactory = $baseGridFactory;
        $this->gitlab = $gitlab;
        $this->localeRepository = $localeRepository;
    }


    /**
     * @param $name
     * @return \Dravencms\Components\BaseGrid
     */
    public function createComponentGrid($name)
    {
        $grid = $this->baseGridFactory->create($this, $name);

        $dataSource = new \Salamek\Gitlab\Grido\DataSources\Gitlab(function ($page, $perPage, $parameters, $orderBy, $orderByDirection) {
            $params = [];
            $params['order_by'] = $orderBy;
            $params['sort'] = $orderByDirection;
            if (array_key_exists('state', $parameters)) {
                $params['state'] = $parameters['state'];
            }

            return $this->gitlab->getIssues($page, $perPage, $params);
        });
        $grid->setModel($dataSource);


        $grid->setDefaultFilter(['state' => 'opened']);

        
        $grid->addColumnNumber('iid', 'ID')
            ->setCustomRender(function($row){
                return '#'.$row->iid;
            });

        $grid->addColumnText('title', 'Title')
            ->setCustomRender(function($row) {
                $htmlLables = [];
                foreach ($row->labels AS $label)
                {
                    $el = Html::el('span', $label->name);
                    $el->class = 'label label-default';
                    $el->style = 'background: '.$label->color.';';
                    $htmlLables[] = $el;
                }
                return $row->title.Html::el('br').implode(' ', $htmlLables);
            });

        $grid->addColumnText('description', 'Description')
            ->setCustomRender(function($row){
                return MarkdownExtra::defaultTransform(Strings::truncate($row->description, 200));
            });

        $grid->addColumnDate('created_at', 'Created', $this->localeRepository->getLocalizedDateTimeFormat())
            ->setSortable();

        $grid->addColumnDate('updated_at', 'Updated', $this->localeRepository->getLocalizedDateTimeFormat())
            ->setSortable();

        $stateTexts = [
            'opened' => 'Opened',
            'closed' => 'Closed',
        ];

        $grid->addColumnText('state', 'State')
            ->setCustomRender(function($row) use($stateTexts){
                $el = Html::el('span', $stateTexts[$row->state]);
                $el->class = 'label label-'.($row->isClosed() ? 'success' : 'danger');
                return $el;
            })
            ->setFilterSelect($stateTexts);
        $grid->getColumn('state')->cellPrototype->class[] = 'center';


        if ($this->presenter->isAllowed('issue', 'edit')) {
            $grid->addActionHref('detail', 'Detail')
                ->setIcon('pencil');
        }

        $grid->setExport();

        return $grid;
    }

    public function render()
    {
        $template = $this->template;
        $template->gitlabError = false;
        try
        {
            $this->gitlab->getIssues();
        }
        catch (RuntimeException $e)
        {
            $template->gitlabError = true;

            $this->flashMessage('RuntimeException: Failed to load data from gitlab, check your configuration', 'alert-danger');
        }
        catch (\Exception $e)
        {
            $template->gitlabError = true;
            $this->flashMessage('Exception: Failed to load data from GitLab', 'alert-danger');
        }
        
        $template->setFile(__DIR__ . '/IssueGrid.latte');
        $template->render();
    }
}
