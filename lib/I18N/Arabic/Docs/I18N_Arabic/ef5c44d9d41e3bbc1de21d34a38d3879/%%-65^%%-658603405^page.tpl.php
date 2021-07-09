<?php /* Smarty version 2.6.0, created on 2016-01-01 10:11:30
         compiled from page.tpl */ ?>
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "header.tpl", 'smarty_include_vars' => array('eltype' => 'Procedural file','class_name' => $this->_tpl_vars['name'],'hasel' => true,'contents' => $this->_tpl_vars['pagecontents'])));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>

<br>
<br>

<?php if ($this->_tpl_vars['classes']): ?>
<div class="contents">
<?php if ($this->_tpl_vars['tutorial']): ?>
<span class="maintutorial">Main Tutorial: <?php echo $this->_tpl_vars['tutorial']; ?>
</span>
<?php endif; ?>
<h2>Classes:</h2>
<?php if (isset($this->_sections['classes'])) unset($this->_sections['classes']);
$this->_sections['classes']['name'] = 'classes';
$this->_sections['classes']['loop'] = is_array($_loop=$this->_tpl_vars['classes']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['classes']['show'] = true;
$this->_sections['classes']['max'] = $this->_sections['classes']['loop'];
$this->_sections['classes']['step'] = 1;
$this->_sections['classes']['start'] = $this->_sections['classes']['step'] > 0 ? 0 : $this->_sections['classes']['loop']-1;
if ($this->_sections['classes']['show']) {
    $this->_sections['classes']['total'] = $this->_sections['classes']['loop'];
    if ($this->_sections['classes']['total'] == 0)
        $this->_sections['classes']['show'] = false;
} else
    $this->_sections['classes']['total'] = 0;
if ($this->_sections['classes']['show']):

            for ($this->_sections['classes']['index'] = $this->_sections['classes']['start'], $this->_sections['classes']['iteration'] = 1;
                 $this->_sections['classes']['iteration'] <= $this->_sections['classes']['total'];
                 $this->_sections['classes']['index'] += $this->_sections['classes']['step'], $this->_sections['classes']['iteration']++):
$this->_sections['classes']['rownum'] = $this->_sections['classes']['iteration'];
$this->_sections['classes']['index_prev'] = $this->_sections['classes']['index'] - $this->_sections['classes']['step'];
$this->_sections['classes']['index_next'] = $this->_sections['classes']['index'] + $this->_sections['classes']['step'];
$this->_sections['classes']['first']      = ($this->_sections['classes']['iteration'] == 1);
$this->_sections['classes']['last']       = ($this->_sections['classes']['iteration'] == $this->_sections['classes']['total']);
?>
<dt><?php echo $this->_tpl_vars['classes'][$this->_sections['classes']['index']]['link']; ?>
</dt>
	<dd><?php echo $this->_tpl_vars['classes'][$this->_sections['classes']['index']]['sdesc']; ?>
</dd>
<?php endfor; endif; ?>
</div><br /><br />
<?php endif; ?>

<h2>Page Details:</h2>
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "docblock.tpl", 'smarty_include_vars' => array('type' => 'page')));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
<br /><br />
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "include.tpl", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
<br /><br />
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "global.tpl", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
<br /><br />
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "define.tpl", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
<br />
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "function.tpl", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>

<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "footer.tpl", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
