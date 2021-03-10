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

    /**
     *
     */
    public function rewind()
    {
        $this->_cur = $this->_parent->get_first_child();
        $this->_num = 0;
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return isset($this->_cur); // && ($this->_cur->get_prev_sibling() === $this->_prev);
    }

    /**
     * @return int
     */
    public function key()
    {
        return $this->_num;
    }

    /**
     * @return Frame
     */
    public function current()
    {
        return $this->_cur;
    }

    /**
     * @return Frame
     */
    public function next()
    {
        $ret = $this->_cur;
        if (!$ret) {
            return null;
        }

        $this->_cur = $this->_cur->get_next_sibling();
        $this->_num++;
        return $ret;
    }
}
