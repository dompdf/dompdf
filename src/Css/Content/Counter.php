<?php
namespace Dompdf\Css\Content;

final class Counter extends ContentPart
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $style;

    public function __construct(string $name, string $style)
    {
        $this->name = $name;
        $this->style = $style;
    }

    public function equals(ContentPart $other): bool
    {
        return $other instanceof self
            && $other->name === $this->name
            && $other->style === $this->style;
    }

    public function __toString(): string
    {
        return "counter($this->name, $this->style)";
    }
}
