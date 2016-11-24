<?php
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\Latte\Issue\Filters;

use Michelf\MarkdownExtra;

/**
 * Class Markdown
 * @package Latte\Filters
 */
class Markdown
{
   public function markdownExtra($string)
   {
       return MarkdownExtra::defaultTransform($string);
   }
}