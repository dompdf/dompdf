<?php /* Smarty version 2.6.0, created on 2016-01-01 10:17:31
         compiled from errors.tpl */ ?>
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "header.tpl", 'smarty_include_vars' => array('noleftindex' => true)));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
<?php if (isset($this->_sections['files'])) unset($this->_sections['files']);
$this->_sections['files']['name'] = 'files';
$this->_sections['files']['loop'] = is_array($_loop=$this->_tpl_vars['files']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['files']['show'] = true;
$this->_sections['files']['max'] = $this->_sections['files']['loop'];
$this->_sections['files']['step'] = 1;
$this->_sections['files']['start'] = $this->_sections['files']['step'] > 0 ? 0 : $this->_sections['files']['loop']-1;
if ($this->_sections['files']['show']) {
    $this->_sections['files']['total'] = $this->_sections['files']['loop'];
    if ($this->_sections['files']['total'] == 0)
        $this->_sections['files']['show'] = false;
} else
    $this->_sections['files']['total'] = 0;
if ($this->_sections['files']['show']):

            for ($this->_sections['files']['index'] = $this->_sections['files']['start'], $this->_sections['files']['iteration'] = 1;
                 $this->_sections['files']['iteration'] <= $this->_sections['files']['total'];
                 $this->_sections['files']['index'] += $this->_sections['files']['step'], $this->_sections['files']['iteration']++):
$this->_sections['files']['rownum'] = $this->_sections['files']['iteration'];
$this->_sections['files']['index_prev'] = $this->_sections['files']['index'] - $this->_sections['files']['step'];
$this->_sections['files']['index_next'] = $this->_sections['files']['index'] + $this->_sections['files']['step'];
$this->_sections['files']['first']      = ($this->_sections['files']['iteration'] == 1);
$this->_sections['files']['last']       = ($this->_sections['files']['iteration'] == $this->_sections['files']['total']);
?>
<a href="#<?php echo $this->_tpl_vars['files'][$this->_sections['files']['index']]['file']; ?>
"><?php echo $this->_tpl_vars['files'][$this->_sections['files']['index']]['file']; ?>
</a><br>
<?php endfor; endif; ?>
<?php if (count($_from = (array)$this->_tpl_vars['all'])):
    foreach ($_from as $this->_tpl_vars['file'] => $this->_tpl_vars['issues']):
?>
<a name="<?php echo $this->_tpl_vars['file']; ?>
"></a>
<h1><?php echo $this->_tpl_vars['file']; ?>
</h1>
<?php if (count ( $this->_tpl_vars['issues']['warnings'] )): ?>
<h2>Warnings:</h2><br>
<?php if (isset($this->_sections['warnings'])) unset($this->_sections['warnings']);
$this->_sections['warnings']['name'] = 'warnings';
$this->_sections['warnings']['loop'] = is_array($_loop=$this->_tpl_vars['issues']['warnings']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['warnings']['show'] = true;
$this->_sections['warnings']['max'] = $this->_sections['warnings']['loop'];
$this->_sections['warnings']['step'] = 1;
$this->_sections['warnings']['start'] = $this->_sections['warnings']['step'] > 0 ? 0 : $this->_sections['warnings']['loop']-1;
if ($this->_sections['warnings']['show']) {
    $this->_sections['warnings']['total'] = $this->_sections['warnings']['loop'];
    if ($this->_sections['warnings']['total'] == 0)
        $this->_sections['warnings']['show'] = false;
} else
    $this->_sections['warnings']['total'] = 0;
if ($this->_sections['warnings']['show']):

            for ($this->_sections['warnings']['index'] = $this->_sections['warnings']['start'], $this->_sections['warnings']['iteration'] = 1;
                 $this->_sections['warnings']['iteration'] <= $this->_sections['warnings']['total'];
                 $this->_sections['warnings']['index'] += $this->_sections['warnings']['step'], $this->_sections['warnings']['iteration']++):
$this->_sections['warnings']['rownum'] = $this->_sections['warnings']['iteration'];
$this->_sections['warnings']['index_prev'] = $this->_sections['warnings']['index'] - $this->_sections['warnings']['step'];
$this->_sections['warnings']['index_next'] = $this->_sections['warnings']['index'] + $this->_sections['warnings']['step'];
$this->_sections['warnings']['first']      = ($this->_sections['warnings']['iteration'] == 1);
$this->_sections['warnings']['last']       = ($this->_sections['warnings']['iteration'] == $this->_sections['warnings']['total']);
?>
<b><?php echo $this->_tpl_vars['issues']['warnings'][$this->_sections['warnings']['index']]['name']; ?>
</b> - <?php echo $this->_tpl_vars['issues']['warnings'][$this->_sections['warnings']['index']]['listing']; ?>
<br>
<?php endfor; endif; ?>
<?php endif; ?>
<?php if (count ( $this->_tpl_vars['issues']['errors'] )): ?>
<h2>Errors:</h2><br>
<?php if (isset($this->_sections['errors'])) unset($this->_sections['errors']);
$this->_sections['errors']['name'] = 'errors';
$this->_sections['errors']['loop'] = is_array($_loop=$this->_tpl_vars['issues']['errors']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['errors']['show'] = true;
$this->_sections['errors']['max'] = $this->_sections['errors']['loop'];
$this->_sections['errors']['step'] = 1;
$this->_sections['errors']['start'] = $this->_sections['errors']['step'] > 0 ? 0 : $this->_sections['errors']['loop']-1;
if ($this->_sections['errors']['show']) {
    $this->_sections['errors']['total'] = $this->_sections['errors']['loop'];
    if ($this->_sections['errors']['total'] == 0)
        $this->_sections['errors']['show'] = false;
} else
    $this->_sections['errors']['total'] = 0;
if ($this->_sections['errors']['show']):

            for ($this->_sections['errors']['index'] = $this->_sections['errors']['start'], $this->_sections['errors']['iteration'] = 1;
                 $this->_sections['errors']['iteration'] <= $this->_sections['errors']['total'];
                 $this->_sections['errors']['index'] += $this->_sections['errors']['step'], $this->_sections['errors']['iteration']++):
$this->_sections['errors']['rownum'] = $this->_sections['errors']['iteration'];
$this->_sections['errors']['index_prev'] = $this->_sections['errors']['index'] - $this->_sections['errors']['step'];
$this->_sections['errors']['index_next'] = $this->_sections['errors']['index'] + $this->_sections['errors']['step'];
$this->_sections['errors']['first']      = ($this->_sections['errors']['iteration'] == 1);
$this->_sections['errors']['last']       = ($this->_sections['errors']['iteration'] == $this->_sections['errors']['total']);
?>
<b><?php echo $this->_tpl_vars['issues']['errors'][$this->_sections['errors']['index']]['name']; ?>
</b> - <?php echo $this->_tpl_vars['issues']['errors'][$this->_sections['errors']['index']]['listing']; ?>
<br>
<?php endfor; endif; ?>
<?php endif; ?>
<?php endforeach; unset($_from); endif; ?>
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "footer.tpl", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>