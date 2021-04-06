<?php

namespace Dimkabelkov\ControllerHelper\Type\Table;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Validator\Constraints as Assert;

class SortType extends AbstractType
{
    /**
     * @Assert\Type("string")
     */
    protected ?string $sort = 'updatedAt';

    /**
     * @Assert\Type("string")
     */
    protected ?string $by = 'desc';

    /**
     * @return string
     */
    public function getSort(): string
    {
        return $this->sort;
    }

    /**
     * @param string|null $sort
     */
    public function setSort(?string $sort)
    {
        $this->sort = $sort;
    }

    /**
     * @return string
     */
    public function getBy(): string
    {
        return $this->by;
    }

    /**
     * @param string|null $by
     */
    public function setBy(?string $by)
    {
        $this->by = $by;
    }

    public function getSortBy(): array
    {
        return [$this->getSort() => $this->getBy()];
    }
}
