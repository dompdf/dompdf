<?php
namespace Dompdf\Frame;

use Iterator;
use Dompdf\Frame;

/**
 * Linked-list Iterator
 *
 * Returns children in order and allows for list to change during iteration,
 * provided the changes occur to or after the current element
 *
 * @access private
 * @package dompdf
 */
class FrameListIterator implements Iterator
{

    /**
     * @var Frame
     */
    protected $_parent;

    /**
     * @var Frame
     */
    protected $_cur;

    /**
     * @var int
     */
    protected $_num;

    /**
     * @param Frame $frame
     */
    public function __construct(Frame $frame)
    {
        $this->_parent = $frame;
        $this->_cur = $frame->get_first_child();
        $this->_num = 0;
    }

    public function rewind(): void
    {
        $this->_cur = $this->_parent->get_first_child();
        $this->_num = 0;
    }

    /**
     * @return bool
     */
    public function valid(): bool
    {
        return isset($this->_cur); // && ($this->_cur->get_prev_sibling() === $this->_prev);
    }

    /**
     * @return int
     */
    public function key(): int
    {
        return $this->_num;
    }

    /**
     * @return Frame|null
     */
    public function current(): ?Frame
    {
        return $this->_cur;
    }

    public function next(): void
    {
        $ret = $this->_cur;
        if (!$ret) {
            return;
        }

        $this->_cur = $this->_cur->get_next_sibling();
        $this->_num++;
    }
}
