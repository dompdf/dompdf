<?php

declare(strict_types=1);

namespace Dompdf\StructTree;

use Dompdf\StructTree;
use DOMNode;

class DummyStructTree implements StructTree
{
    public function render(DOMNode $node): void
    {
    }

    public function inArtifact(): void
    {
    }
}
