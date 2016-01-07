<?php

class MemoryStream {
    private $position;
    private $varname;

    public function stream_open($path, $mode, $options, &$opened_path) {
        $this->varname = $path;
        $this->position = 0;
        return true;
    }

    public function stream_read($count) {
        $p=&$this->position;
        $ret = substr($GLOBALS[$this->varname], $p, $count);
        $p += strlen($ret);
        return $ret;
    }

    public function stream_write($data){
        $v=&$GLOBALS[$this->varname];
        $l=strlen($data);
        $p=&$this->position;
        $v = substr($v, 0, $p) . $data . substr($v, $p += $l);
        return $l;
    }

    public function stream_tell() {
        return $this->position;
    }

    public function stream_eof() {
        return $this->position >= strlen($GLOBALS[$this->varname]);
    }

    public function stream_seek($offset, $whence) {
        $l=strlen($GLOBALS[$this->varname]);
        $p=&$this->position;
        switch ($whence) {
            case SEEK_SET: $newPos = $offset; break;
            case SEEK_CUR: $newPos = $p + $offset; break;
            case SEEK_END: $newPos = $l + $offset; break;
            default: return false;
        }
        $ret = ($newPos >=0 && $newPos <=$l);
        if ($ret) $p=$newPos;
        return $ret;
    }

    public function unlink() {
        unset($GLOBALS[$this->varname]);
    }

    public function stream_stat() {
        $stats = [];
        $stats[0] = $stats["dev"] = 0;      // device number
        $stats[1] = $stats["ino"] = 0;      // inode number
        $stats[2] = $stats["mode"] = 33188; // inode protection mode - file, read, write, execute
        $stats[3] = $stats["nlink"] = 1;    // number of links
        $stats[4] = $stats["uid"] = 0;      // userid of owner
        $stats[5] = $stats["gid"] = 0;      // groupid of owner
        $stats[6] = $stats["rdev"] = 0;     // device type, if inode device
        $stats[7] = $stats["size"] = isset($GLOBALS[$this->varname]) ? strlen($GLOBALS[$this->varname]) : 0; // 	size in bytes
        $stats[8] = $stats["atime"] = 0;    // time of last access (Unix timestamp)
        $stats[9] = $stats["mtime"] = 0;    // time of last modification (Unix timestamp)
        $stats[10] = $stats["ctime"] = 0;   // time of last inode change (Unix timestamp)
        $stats[11] = $stats["blksize"] = 4096; // blocksize of filesystem IO
        $stats[12] = $stats["blocks"] = 1;  // number of blicks on device
        return $stats;
    }

    public function url_stat($path, $flags) {
        $this->varname = $path;
        return $this->stream_stat();
    }
}

if (!stream_wrapper_register("memory", "MemoryStream"))
    throw new \Exception("Unable to register memory stream");