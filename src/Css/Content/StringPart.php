<?php
namespace Dompdf\Css\Content;

final class StringPart extends ContentPart
{
    /**
     * @var string
     */
    public $string;

    public function __construct(string $string)
    {
        $this->string = $string;
    }

    public function equals(ContentPart $other): bool
    {
        return $other instanceof self
            && $other->string === $this->string;
    }

    public function __toString(): string
    {
        return '"' . $this->string . '"';
    }
}
