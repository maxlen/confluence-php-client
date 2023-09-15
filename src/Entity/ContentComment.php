<?php
declare(strict_types=1);

namespace Maxlen\ConfluenceClient\Entity;


use Maxlen\ConfluenceClient\Api\Content;

class ContentComment extends AbstractContent
{
    protected string $type = Content::CONTENT_TYPE_COMMENT;

}