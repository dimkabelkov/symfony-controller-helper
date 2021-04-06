<?php

namespace Dimkabelkov\ControllerHelper\Type\Table;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Validator\Constraints as Assert;

class PagingType extends AbstractType
{
    /**
     * @Assert\Type("string")
    */
    protected $skip = 0;

    /**
     * @Assert\Type("string")
     */
    protected $limit = 25;

    /**
     * @return int
     */
    public function getSkip(): int
    {
        return intval($this->skip);
    }

    /**
     * @param string|null $skip
     */
    public function setSkip(?string $skip)
    {
        $this->skip = $skip;
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return intval($this->limit) ? intval($this->limit) : 25 ;
    }

    /**
     * @param string|null $limit
     */
    public function setLimit(?string $limit)
    {
        $this->limit = $limit;
    }
}
