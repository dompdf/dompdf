<?php /* Smarty version 2.6.0, created on 2016-01-01 10:11:30
         compiled from function.tpl */ ?>
<?php require_once(SMARTY_DIR . 'core' . DIRECTORY_SEPARATOR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'escape', 'function.tpl', 14, false),)), $this); ?>
<?php if (isset($this->_sections['func'])) unset($this->_sections['func']);
$this->_sections['func']['name'] = 'func';
$this->_sections['func']['loop'] = is_array($_loop=$this->_tpl_vars['functions']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['func']['show'] = true;
$this->_sections['func']['max'] = $this->_sections['func']['loop'];
$this->_sections['func']['step'] = 1;
$this->_sections['func']['start'] = $this->_sections['func']['step'] > 0 ? 0 : $this->_sections['func']['loop']-1;
if ($this->_sections['func']['show']) {
    $this->_sections['func']['total'] = $this->_sections['func']['loop'];
    if ($this->_sections['func']['total'] == 0)
        $this->_sections['func']['show'] = false;
} else
    $this->_sections['func']['total'] = 0;
if ($this->_sections['func']['show']):

            for ($this->_sections['func']['index'] = $this->_sections['func']['start'], $this->_sections['func']['iteration'] = 1;
                 $this->_sections['func']['iteration'] <= $this->_sections['func']['total'];
                 $this->_sections['func']['index'] += $this->_sections['func']['step'], $this->_sections['func']['iteration']++):
$this->_sections['func']['rownum'] = $this->_sections['func']['iteration'];
$this->_sections['func']['index_prev'] = $this->_sections['func']['index'] - $this->_sections['func']['step'];
$this->_sections['func']['index_next'] = $this->_sections['func']['index'] + $this->_sections['func']['step'];
$this->_sections['func']['first']      = ($this->_sections['func']['iteration'] == 1);
$this->_sections['func']['last']       = ($this->_sections['func']['iteration'] == $this->_sections['func']['total']);
?>
<?php if ($this->_tpl_vars['show'] == 'summary'): ?>
function <?php echo $this->_tpl_vars['functions'][$this->_sections['func']['index']]['id']; ?>
, <?php echo $this->_tpl_vars['functions'][$this->_sections['func']['index']]['sdesc']; ?>
<br />
<?php else: ?>
  <hr />
	<a name="<?php echo $this->_tpl_vars['functions'][$this->_sections['func']['index']]['function_dest']; ?>
"></a>
	<h3><?php echo $this->_tpl_vars['functions'][$this->_sections['func']['index']]['function_name']; ?>
 <span class="smalllinenumber">[line <?php if ($this->_tpl_vars['functions'][$this->_sections['func']['index']]['slink']):  echo $this->_tpl_vars['functions'][$this->_sections['func']['index']]['slink'];  else:  echo $this->_tpl_vars['functions'][$this->_sections['func']['index']]['line_number'];  endif; ?>]</span></h3>
	<div class="function">
    <table width="90%" border="0" cellspacing="0" cellpadding="1"><tr><td class="code_border">
    <table width="100%" border="0" cellspacing="0" cellpadding="2"><tr><td class="code">
		<code><?php echo $this->_tpl_vars['functions'][$this->_sections['func']['index']]['function_return']; ?>
 <?php if ($this->_tpl_vars['functions'][$this->_sections['func']['index']]['ifunction_call']['returnsref']): ?>&amp;<?php endif;  echo $this->_tpl_vars['functions'][$this->_sections['func']['index']]['function_name']; ?>
(
<?php if (count ( $this->_tpl_vars['functions'][$this->_sections['func']['index']]['ifunction_call']['params'] )): ?>
<?php if (isset($this->_sections['params'])) unset($this->_sections['params']);
$this->_sections['params']['name'] = 'params';
$this->_sections['params']['loop'] = is_array($_loop=$this->_tpl_vars['functions'][$this->_sections['func']['index']]['ifunction_call']['params']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['params']['show'] = true;
$this->_sections['params']['max'] = $this->_sections['params']['loop'];
$this->_sections['params']['step'] = 1;
$this->_sections['params']['start'] = $this->_sections['params']['step'] > 0 ? 0 : $this->_sections['params']['loop']-1;
if ($this->_sections['params']['show']) {
    $this->_sections['params']['total'] = $this->_sections['params']['loop'];
    if ($this->_sections['params']['total'] == 0)
        $this->_sections['params']['show'] = false;
} else
    $this->_sections['params']['total'] = 0;
if ($this->_sections['params']['show']):

            for ($this->_sections['params']['index'] = $this->_sections['params']['start'], $this->_sections['params']['iteration'] = 1;
                 $this->_sections['params']['iteration'] <= $this->_sections['params']['total'];
                 $this->_sections['params']['index'] += $this->_sections['params']['step'], $this->_sections['params']['iteration']++):
$this->_sections['params']['rownum'] = $this->_sections['params']['iteration'];
$this->_sections['params']['index_prev'] = $this->_sections['params']['index'] - $this->_sections['params']['step'];
$this->_sections['params']['index_next'] = $this->_sections['params']['index'] + $this->_sections['params']['step'];
$this->_sections['params']['first']      = ($this->_sections['params']['iteration'] == 1);
$this->_sections['params']['last']       = ($this->_sections['params']['iteration'] == $this->_sections['params']['total']);
?>
<?php if ($this->_sections['params']['iteration'] != 1): ?>, <?php endif;  if ($this->_tpl_vars['functions'][$this->_sections['func']['index']]['ifunction_call']['params'][$this->_sections['params']['index']]['hasdefault']): ?>[<?php endif;  echo $this->_tpl_vars['functions'][$this->_sections['func']['index']]['ifunction_call']['params'][$this->_sections['params']['index']]['type']; ?>
 <?php echo $this->_tpl_vars['functions'][$this->_sections['func']['index']]['ifunction_call']['params'][$this->_sections['params']['index']]['name'];  if ($this->_tpl_vars['functions'][$this->_sections['func']['index']]['ifunction_call']['params'][$this->_sections['params']['index']]['hasdefault']): ?> = <?php echo ((is_array($_tmp=$this->_tpl_vars['functions'][$this->_sections['func']['index']]['ifunction_call']['params'][$this->_sections['params']['index']]['default'])) ? $this->_run_mod_handler('escape', true, $_tmp, 'html') : smarty_modifier_escape($_tmp, 'html')); ?>
]<?php endif; ?>
<?php endfor; endif; ?>
<?php endif; ?>)</code>
    </td></tr></table>
    </td></tr></table><br />

		<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "docblock.tpl", 'smarty_include_vars' => array('sdesc' => $this->_tpl_vars['functions'][$this->_sections['func']['index']]['sdesc'],'desc' => $this->_tpl_vars['functions'][$this->_sections['func']['index']]['desc'],'tags' => $this->_tpl_vars['functions'][$this->_sections['func']['index']]['tags'])));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
    <br /><br />
	<?php if ($this->_tpl_vars['functions'][$this->_sections['func']['index']]['function_conflicts']['conflict_type']): ?>
	<p><b>Conflicts with functions:</b> 
	<?php if (isset($this->_sections['me'])) unset($this->_sections['me']);
$this->_sections['me']['name'] = 'me';
$this->_sections['me']['loop'] = is_array($_loop=$this->_tpl_vars['functions'][$this->_sections['func']['index']]['function_conflicts']['conflicts']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['me']['show'] = true;
$this->_sections['me']['max'] = $this->_sections['me']['loop'];
$this->_sections['me']['step'] = 1;
$this->_sections['me']['start'] = $this->_sections['me']['step'] > 0 ? 0 : $this->_sections['me']['loop']-1;
if ($this->_sections['me']['show']) {
    $this->_sections['me']['total'] = $this->_sections['me']['loop'];
    if ($this->_sections['me']['total'] == 0)
        $this->_sections['me']['show'] = false;
} else
    $this->_sections['me']['total'] = 0;
if ($this->_sections['me']['show']):

            for ($this->_sections['me']['index'] = $this->_sections['me']['start'], $this->_sections['me']['iteration'] = 1;
                 $this->_sections['me']['iteration'] <= $this->_sections['me']['total'];
                 $this->_sections['me']['index'] += $this->_sections['me']['step'], $this->_sections['me']['iteration']++):
$this->_sections['me']['rownum'] = $this->_sections['me']['iteration'];
$this->_sections['me']['index_prev'] = $this->_sections['me']['index'] - $this->_sections['me']['step'];
$this->_sections['me']['index_next'] = $this->_sections['me']['index'] + $this->_sections['me']['step'];
$this->_sections['me']['first']      = ($this->_sections['me']['iteration'] == 1);
$this->_sections['me']['last']       = ($this->_sections['me']['iteration'] == $this->_sections['me']['total']);
?>
	<?php echo $this->_tpl_vars['functions'][$this->_sections['func']['index']]['function_conflicts']['conflicts'][$this->_sections['me']['index']]; ?>
<br />
	<?php endfor; endif; ?>
	</p>
	<?php endif; ?>

    <?php if (count ( $this->_tpl_vars['functions'][$this->_sections['func']['index']]['params'] ) > 0): ?>
		<h4>Parameters</h4>
    <table border="0" cellspacing="0" cellpadding="0">
		<?php if (isset($this->_sections['params'])) unset($this->_sections['params']);
$this->_sections['params']['name'] = 'params';
$this->_sections['params']['loop'] = is_array($_loop=$this->_tpl_vars['functions'][$this->_sections['func']['index']]['params']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['params']['show'] = true;
$this->_sections['params']['max'] = $this->_sections['params']['loop'];
$this->_sections['params']['step'] = 1;
$this->_sections['params']['start'] = $this->_sections['params']['step'] > 0 ? 0 : $this->_sections['params']['loop']-1;
if ($this->_sections['params']['show']) {
    $this->_sections['params']['total'] = $this->_sections['params']['loop'];
    if ($this->_sections['params']['total'] == 0)
        $this->_sections['params']['show'] = false;
} else
    $this->_sections['params']['total'] = 0;
if ($this->_sections['params']['show']):

            for ($this->_sections['params']['index'] = $this->_sections['params']['start'], $this->_sections['params']['iteration'] = 1;
                 $this->_sections['params']['iteration'] <= $this->_sections['params']['total'];
                 $this->_sections['params']['index'] += $this->_sections['params']['step'], $this->_sections['params']['iteration']++):
$this->_sections['params']['rownum'] = $this->_sections['params']['iteration'];
$this->_sections['params']['index_prev'] = $this->_sections['params']['index'] - $this->_sections['params']['step'];
$this->_sections['params']['index_next'] = $this->_sections['params']['index'] + $this->_sections['params']['step'];
$this->_sections['params']['first']      = ($this->_sections['params']['iteration'] == 1);
$this->_sections['params']['last']       = ($this->_sections['params']['iteration'] == $this->_sections['params']['total']);
?>
      <tr>
        <td class="type"><?php echo $this->_tpl_vars['functions'][$this->_sections['func']['index']]['params'][$this->_sections['params']['index']]['datatype']; ?>
&nbsp;&nbsp;</td>
        <td><b><?php echo $this->_tpl_vars['functions'][$this->_sections['func']['index']]['params'][$this->_sections['params']['index']]['var']; ?>
</b>&nbsp;&nbsp;</td>
        <td><?php echo $this->_tpl_vars['functions'][$this->_sections['func']['index']]['params'][$this->_sections['params']['index']]['data']; ?>
</td>
      </tr>
		<?php endfor; endif; ?>
		</table>
    <?php endif; ?>
	<div class="top">[ <a href="#top">Top</a> ]</div><br /><br />
	</div>
<?php endif; ?>
<?php endfor; endif; ?>