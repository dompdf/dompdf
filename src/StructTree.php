<?php

declare(strict_types=1);

namespace Dompdf;

use DOMNode;

interface StructTree
{
    public function render(DOMNode $node): void;
    public function inArtifact(): void;
}
