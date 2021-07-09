<?php /* Smarty version 2.6.0, created on 2016-01-01 10:11:27
         compiled from docblock.tpl */ ?>
<?php require_once(SMARTY_DIR . 'core' . DIRECTORY_SEPARATOR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'default', 'docblock.tpl', 1, false),)), $this); ?>
<?php if ($this->_tpl_vars['sdesc'] != ''):  echo ((is_array($_tmp=@$this->_tpl_vars['sdesc'])) ? $this->_run_mod_handler('default', true, $_tmp, '') : smarty_modifier_default($_tmp, '')); ?>
<br /><br /><?php endif; ?>
<?php if ($this->_tpl_vars['desc'] != ''):  echo ((is_array($_tmp=@$this->_tpl_vars['desc'])) ? $this->_run_mod_handler('default', true, $_tmp, '') : smarty_modifier_default($_tmp, '')); ?>
<br /><?php endif; ?>
<?php if (count ( $this->_tpl_vars['tags'] ) > 0): ?>
<br /><br />
<h4>Tags:</h4>
<div class="tags">
<table border="0" cellspacing="0" cellpadding="0">
<?php if (isset($this->_sections['tag'])) unset($this->_sections['tag']);
$this->_sections['tag']['name'] = 'tag';
$this->_sections['tag']['loop'] = is_array($_loop=$this->_tpl_vars['tags']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['tag']['show'] = true;
$this->_sections['tag']['max'] = $this->_sections['tag']['loop'];
$this->_sections['tag']['step'] = 1;
$this->_sections['tag']['start'] = $this->_sections['tag']['step'] > 0 ? 0 : $this->_sections['tag']['loop']-1;
if ($this->_sections['tag']['show']) {
    $this->_sections['tag']['total'] = $this->_sections['tag']['loop'];
    if ($this->_sections['tag']['total'] == 0)
        $this->_sections['tag']['show'] = false;
} else
    $this->_sections['tag']['total'] = 0;
if ($this->_sections['tag']['show']):

            for ($this->_sections['tag']['index'] = $this->_sections['tag']['start'], $this->_sections['tag']['iteration'] = 1;
                 $this->_sections['tag']['iteration'] <= $this->_sections['tag']['total'];
                 $this->_sections['tag']['index'] += $this->_sections['tag']['step'], $this->_sections['tag']['iteration']++):
$this->_sections['tag']['rownum'] = $this->_sections['tag']['iteration'];
$this->_sections['tag']['index_prev'] = $this->_sections['tag']['index'] - $this->_sections['tag']['step'];
$this->_sections['tag']['index_next'] = $this->_sections['tag']['index'] + $this->_sections['tag']['step'];
$this->_sections['tag']['first']      = ($this->_sections['tag']['iteration'] == 1);
$this->_sections['tag']['last']       = ($this->_sections['tag']['iteration'] == $this->_sections['tag']['total']);
?>
  <tr>
    <td><b><?php echo $this->_tpl_vars['tags'][$this->_sections['tag']['index']]['keyword']; ?>
:</b>&nbsp;&nbsp;</td><td><?php echo $this->_tpl_vars['tags'][$this->_sections['tag']['index']]['data']; ?>
</td>
  </tr>
<?php endfor; endif; ?>
</table>
</div>
<?php endif; ?>