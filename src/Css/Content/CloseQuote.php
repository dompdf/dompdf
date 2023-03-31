<?php
namespace Dompdf\Css\Content;

final class CloseQuote extends ContentPart
{
    public function __toString(): string
    {
        return "close-quote";
    }
}
