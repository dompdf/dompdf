<?php /* Smarty version 2.6.0, created on 2016-01-01 10:11:26
         compiled from class.tpl */ ?>
<?php require_once(SMARTY_DIR . 'core' . DIRECTORY_SEPARATOR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'default', 'class.tpl', 16, false),array('function', 'assign', 'class.tpl', 34, false),)), $this); ?>
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "header.tpl", 'smarty_include_vars' => array('eltype' => 'class','hasel' => true,'contents' => $this->_tpl_vars['classcontents'])));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>

<?php if ($this->_tpl_vars['conflicts']['conflict_type']): ?><div class="warning">Conflicts with classes:<br />
	<?php if (isset($this->_sections['me'])) unset($this->_sections['me']);
$this->_sections['me']['name'] = 'me';
$this->_sections['me']['loop'] = is_array($_loop=$this->_tpl_vars['conflicts']['conflicts']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
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
	<?php echo $this->_tpl_vars['conflicts']['conflicts'][$this->_sections['me']['index']]; ?>
<br />
	<?php endfor; endif; ?>
</div>
	<?php endif; ?>

<table width="100%" border="0">
<tr><td valign="top">

<h3><a href="#class_details"><?php if ($this->_tpl_vars['is_interface']): ?>Interface<?php else: ?>Class<?php endif; ?> Overview</a></h3>
<pre><?php if (isset($this->_sections['tree'])) unset($this->_sections['tree']);
$this->_sections['tree']['name'] = 'tree';
$this->_sections['tree']['loop'] = is_array($_loop=$this->_tpl_vars['class_tree']['classes']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['tree']['show'] = true;
$this->_sections['tree']['max'] = $this->_sections['tree']['loop'];
$this->_sections['tree']['step'] = 1;
$this->_sections['tree']['start'] = $this->_sections['tree']['step'] > 0 ? 0 : $this->_sections['tree']['loop']-1;
if ($this->_sections['tree']['show']) {
    $this->_sections['tree']['total'] = $this->_sections['tree']['loop'];
    if ($this->_sections['tree']['total'] == 0)
        $this->_sections['tree']['show'] = false;
} else
    $this->_sections['tree']['total'] = 0;
if ($this->_sections['tree']['show']):

            for ($this->_sections['tree']['index'] = $this->_sections['tree']['start'], $this->_sections['tree']['iteration'] = 1;
                 $this->_sections['tree']['iteration'] <= $this->_sections['tree']['total'];
                 $this->_sections['tree']['index'] += $this->_sections['tree']['step'], $this->_sections['tree']['iteration']++):
$this->_sections['tree']['rownum'] = $this->_sections['tree']['iteration'];
$this->_sections['tree']['index_prev'] = $this->_sections['tree']['index'] - $this->_sections['tree']['step'];
$this->_sections['tree']['index_next'] = $this->_sections['tree']['index'] + $this->_sections['tree']['step'];
$this->_sections['tree']['first']      = ($this->_sections['tree']['iteration'] == 1);
$this->_sections['tree']['last']       = ($this->_sections['tree']['iteration'] == $this->_sections['tree']['total']);
 echo $this->_tpl_vars['class_tree']['classes'][$this->_sections['tree']['index']];  echo $this->_tpl_vars['class_tree']['distance'][$this->_sections['tree']['index']];  endfor; endif; ?></pre><br />
<div class="description"><?php echo ((is_array($_tmp=@$this->_tpl_vars['sdesc'])) ? $this->_run_mod_handler('default', true, $_tmp, '') : smarty_modifier_default($_tmp, '')); ?>
</div><br /><br />
<?php if ($this->_tpl_vars['tutorial']): ?>
<h4 class="classtutorial"><?php if ($this->_tpl_vars['is_interface']): ?>Interface<?php else: ?>Class<?php endif; ?> Tutorial:</h4>
<ul>
	<li><?php echo $this->_tpl_vars['tutorial']; ?>
</li>
</ul>
<?php endif; ?>
<?php if (count ( $this->_tpl_vars['tags'] ) > 0): ?>
<h4>Author(s):</h4>
<ul>
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
    <?php if ($this->_tpl_vars['tags'][$this->_sections['tag']['index']]['keyword'] == 'author'): ?>
    <li><?php echo $this->_tpl_vars['tags'][$this->_sections['tag']['index']]['data']; ?>
</li>
    <?php endif; ?>
  <?php endfor; endif; ?>
</ul>
<?php endif; ?>

<?php echo smarty_function_assign(array('var' => 'version','value' => ""), $this);?>

<?php echo smarty_function_assign(array('var' => 'copyright','value' => ""), $this);?>


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
  <?php if ($this->_tpl_vars['tags'][$this->_sections['tag']['index']]['keyword'] == 'version'): ?>
  <?php echo smarty_function_assign(array('var' => 'version','value' => $this->_tpl_vars['tags'][$this->_sections['tag']['index']]['data']), $this);?>

  <?php endif; ?>
  <?php if ($this->_tpl_vars['tags'][$this->_sections['tag']['index']]['keyword'] == 'copyright'): ?>
  <?php echo smarty_function_assign(array('var' => 'copyright','value' => $this->_tpl_vars['tags'][$this->_sections['tag']['index']]['data']), $this);?>

  <?php endif; ?>
<?php endfor; endif; ?>

<?php if ($this->_tpl_vars['version']): ?>
<h4>Version:</h4>
<ul>
  <li><?php echo $this->_tpl_vars['version']; ?>
</li>
</ul>
<?php endif; ?>

<?php if ($this->_tpl_vars['copyright']): ?>
<h4>Copyright:</h4>
<ul>
  <li><?php echo $this->_tpl_vars['copyright']; ?>
</li>
</ul>
<?php endif; ?>
        <?php if ($this->_tpl_vars['implements']): ?>
        <p class="implements">
            Implements interfaces:
            <ul>
                <?php if (count($_from = (array)$this->_tpl_vars['implements'])):
    foreach ($_from as $this->_tpl_vars['int']):
?><li><?php echo $this->_tpl_vars['int']; ?>
</li><?php endforeach; unset($_from); endif; ?>
            </ul>
        </p>
        <?php endif; ?>

</td>

<?php if (count ( $this->_tpl_vars['contents']['var'] ) > 0): ?>
<td valign="top">
<h3><a href="#class_vars">Variables</a></h3>
<ul>
  <?php if (isset($this->_sections['contents'])) unset($this->_sections['contents']);
$this->_sections['contents']['name'] = 'contents';
$this->_sections['contents']['loop'] = is_array($_loop=$this->_tpl_vars['contents']['var']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['contents']['show'] = true;
$this->_sections['contents']['max'] = $this->_sections['contents']['loop'];
$this->_sections['contents']['step'] = 1;
$this->_sections['contents']['start'] = $this->_sections['contents']['step'] > 0 ? 0 : $this->_sections['contents']['loop']-1;
if ($this->_sections['contents']['show']) {
    $this->_sections['contents']['total'] = $this->_sections['contents']['loop'];
    if ($this->_sections['contents']['total'] == 0)
        $this->_sections['contents']['show'] = false;
} else
    $this->_sections['contents']['total'] = 0;
if ($this->_sections['contents']['show']):

            for ($this->_sections['contents']['index'] = $this->_sections['contents']['start'], $this->_sections['contents']['iteration'] = 1;
                 $this->_sections['contents']['iteration'] <= $this->_sections['contents']['total'];
                 $this->_sections['contents']['index'] += $this->_sections['contents']['step'], $this->_sections['contents']['iteration']++):
$this->_sections['contents']['rownum'] = $this->_sections['contents']['iteration'];
$this->_sections['contents']['index_prev'] = $this->_sections['contents']['index'] - $this->_sections['contents']['step'];
$this->_sections['contents']['index_next'] = $this->_sections['contents']['index'] + $this->_sections['contents']['step'];
$this->_sections['contents']['first']      = ($this->_sections['contents']['iteration'] == 1);
$this->_sections['contents']['last']       = ($this->_sections['contents']['iteration'] == $this->_sections['contents']['total']);
?>
  <li><?php echo $this->_tpl_vars['contents']['var'][$this->_sections['contents']['index']]; ?>
</li>
  <?php endfor; endif; ?>
</ul>
</td>
<?php endif; ?>

<?php if (count ( $this->_tpl_vars['contents']['const'] ) > 0): ?>
<td valign="top">
<h3><a href="#class_consts">Constants</a></h3>
<ul>
  <?php if (isset($this->_sections['contents'])) unset($this->_sections['contents']);
$this->_sections['contents']['name'] = 'contents';
$this->_sections['contents']['loop'] = is_array($_loop=$this->_tpl_vars['contents']['const']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['contents']['show'] = true;
$this->_sections['contents']['max'] = $this->_sections['contents']['loop'];
$this->_sections['contents']['step'] = 1;
$this->_sections['contents']['start'] = $this->_sections['contents']['step'] > 0 ? 0 : $this->_sections['contents']['loop']-1;
if ($this->_sections['contents']['show']) {
    $this->_sections['contents']['total'] = $this->_sections['contents']['loop'];
    if ($this->_sections['contents']['total'] == 0)
        $this->_sections['contents']['show'] = false;
} else
    $this->_sections['contents']['total'] = 0;
if ($this->_sections['contents']['show']):

            for ($this->_sections['contents']['index'] = $this->_sections['contents']['start'], $this->_sections['contents']['iteration'] = 1;
                 $this->_sections['contents']['iteration'] <= $this->_sections['contents']['total'];
                 $this->_sections['contents']['index'] += $this->_sections['contents']['step'], $this->_sections['contents']['iteration']++):
$this->_sections['contents']['rownum'] = $this->_sections['contents']['iteration'];
$this->_sections['contents']['index_prev'] = $this->_sections['contents']['index'] - $this->_sections['contents']['step'];
$this->_sections['contents']['index_next'] = $this->_sections['contents']['index'] + $this->_sections['contents']['step'];
$this->_sections['contents']['first']      = ($this->_sections['contents']['iteration'] == 1);
$this->_sections['contents']['last']       = ($this->_sections['contents']['iteration'] == $this->_sections['contents']['total']);
?>
  <li><?php echo $this->_tpl_vars['contents']['const'][$this->_sections['contents']['index']]; ?>
</li>
  <?php endfor; endif; ?>
</ul>
</td>
<?php endif; ?>

<?php if (count ( $this->_tpl_vars['contents']['method'] ) > 0): ?>
<td valign="top">
<h3><a href="#class_methods">Methods</a></h3>
<ul>
  <?php if (isset($this->_sections['contents'])) unset($this->_sections['contents']);
$this->_sections['contents']['name'] = 'contents';
$this->_sections['contents']['loop'] = is_array($_loop=$this->_tpl_vars['contents']['method']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['contents']['show'] = true;
$this->_sections['contents']['max'] = $this->_sections['contents']['loop'];
$this->_sections['contents']['step'] = 1;
$this->_sections['contents']['start'] = $this->_sections['contents']['step'] > 0 ? 0 : $this->_sections['contents']['loop']-1;
if ($this->_sections['contents']['show']) {
    $this->_sections['contents']['total'] = $this->_sections['contents']['loop'];
    if ($this->_sections['contents']['total'] == 0)
        $this->_sections['contents']['show'] = false;
} else
    $this->_sections['contents']['total'] = 0;
if ($this->_sections['contents']['show']):

            for ($this->_sections['contents']['index'] = $this->_sections['contents']['start'], $this->_sections['contents']['iteration'] = 1;
                 $this->_sections['contents']['iteration'] <= $this->_sections['contents']['total'];
                 $this->_sections['contents']['index'] += $this->_sections['contents']['step'], $this->_sections['contents']['iteration']++):
$this->_sections['contents']['rownum'] = $this->_sections['contents']['iteration'];
$this->_sections['contents']['index_prev'] = $this->_sections['contents']['index'] - $this->_sections['contents']['step'];
$this->_sections['contents']['index_next'] = $this->_sections['contents']['index'] + $this->_sections['contents']['step'];
$this->_sections['contents']['first']      = ($this->_sections['contents']['iteration'] == 1);
$this->_sections['contents']['last']       = ($this->_sections['contents']['iteration'] == $this->_sections['contents']['total']);
?>
  <li><?php echo $this->_tpl_vars['contents']['method'][$this->_sections['contents']['index']]; ?>
</li>
  <?php endfor; endif; ?>
</ul>
</td>
<?php endif; ?>

</tr></table>
<hr />

<table width="100%" border="0"><tr>


<?php if ($this->_tpl_vars['children']): ?>
<td valign="top">
<h3>Child classes:</h3>
<div class="tags">
<?php if (isset($this->_sections['kids'])) unset($this->_sections['kids']);
$this->_sections['kids']['name'] = 'kids';
$this->_sections['kids']['loop'] = is_array($_loop=$this->_tpl_vars['children']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['kids']['show'] = true;
$this->_sections['kids']['max'] = $this->_sections['kids']['loop'];
$this->_sections['kids']['step'] = 1;
$this->_sections['kids']['start'] = $this->_sections['kids']['step'] > 0 ? 0 : $this->_sections['kids']['loop']-1;
if ($this->_sections['kids']['show']) {
    $this->_sections['kids']['total'] = $this->_sections['kids']['loop'];
    if ($this->_sections['kids']['total'] == 0)
        $this->_sections['kids']['show'] = false;
} else
    $this->_sections['kids']['total'] = 0;
if ($this->_sections['kids']['show']):

            for ($this->_sections['kids']['index'] = $this->_sections['kids']['start'], $this->_sections['kids']['iteration'] = 1;
                 $this->_sections['kids']['iteration'] <= $this->_sections['kids']['total'];
                 $this->_sections['kids']['index'] += $this->_sections['kids']['step'], $this->_sections['kids']['iteration']++):
$this->_sections['kids']['rownum'] = $this->_sections['kids']['iteration'];
$this->_sections['kids']['index_prev'] = $this->_sections['kids']['index'] - $this->_sections['kids']['step'];
$this->_sections['kids']['index_next'] = $this->_sections['kids']['index'] + $this->_sections['kids']['step'];
$this->_sections['kids']['first']      = ($this->_sections['kids']['iteration'] == 1);
$this->_sections['kids']['last']       = ($this->_sections['kids']['iteration'] == $this->_sections['kids']['total']);
?>
<dl>
<dt><?php echo $this->_tpl_vars['children'][$this->_sections['kids']['index']]['link']; ?>
</dt>
	<dd><?php echo $this->_tpl_vars['children'][$this->_sections['kids']['index']]['sdesc']; ?>
</dd>
</dl>
<?php endfor; endif; ?>
</div>
</td>
<?php endif; ?>

<?php if ($this->_tpl_vars['iconsts'] && count ( $this->_tpl_vars['iconsts'] ) > 0): ?>
<td valign="top">
<h3>Inherited Constants</h3>
<?php if (isset($this->_sections['iconsts'])) unset($this->_sections['iconsts']);
$this->_sections['iconsts']['name'] = 'iconsts';
$this->_sections['iconsts']['loop'] = is_array($_loop=$this->_tpl_vars['iconsts']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['iconsts']['show'] = true;
$this->_sections['iconsts']['max'] = $this->_sections['iconsts']['loop'];
$this->_sections['iconsts']['step'] = 1;
$this->_sections['iconsts']['start'] = $this->_sections['iconsts']['step'] > 0 ? 0 : $this->_sections['iconsts']['loop']-1;
if ($this->_sections['iconsts']['show']) {
    $this->_sections['iconsts']['total'] = $this->_sections['iconsts']['loop'];
    if ($this->_sections['iconsts']['total'] == 0)
        $this->_sections['iconsts']['show'] = false;
} else
    $this->_sections['iconsts']['total'] = 0;
if ($this->_sections['iconsts']['show']):

            for ($this->_sections['iconsts']['index'] = $this->_sections['iconsts']['start'], $this->_sections['iconsts']['iteration'] = 1;
                 $this->_sections['iconsts']['iteration'] <= $this->_sections['iconsts']['total'];
                 $this->_sections['iconsts']['index'] += $this->_sections['iconsts']['step'], $this->_sections['iconsts']['iteration']++):
$this->_sections['iconsts']['rownum'] = $this->_sections['iconsts']['iteration'];
$this->_sections['iconsts']['index_prev'] = $this->_sections['iconsts']['index'] - $this->_sections['iconsts']['step'];
$this->_sections['iconsts']['index_next'] = $this->_sections['iconsts']['index'] + $this->_sections['iconsts']['step'];
$this->_sections['iconsts']['first']      = ($this->_sections['iconsts']['iteration'] == 1);
$this->_sections['iconsts']['last']       = ($this->_sections['iconsts']['iteration'] == $this->_sections['iconsts']['total']);
?>
<div class="tags">
<h4>Class: <?php echo $this->_tpl_vars['iconsts'][$this->_sections['iconsts']['index']]['parent_class']; ?>
</h4>
<dl>
<?php if (isset($this->_sections['iconsts2'])) unset($this->_sections['iconsts2']);
$this->_sections['iconsts2']['name'] = 'iconsts2';
$this->_sections['iconsts2']['loop'] = is_array($_loop=$this->_tpl_vars['iconsts'][$this->_sections['iconsts']['index']]['iconsts']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['iconsts2']['show'] = true;
$this->_sections['iconsts2']['max'] = $this->_sections['iconsts2']['loop'];
$this->_sections['iconsts2']['step'] = 1;
$this->_sections['iconsts2']['start'] = $this->_sections['iconsts2']['step'] > 0 ? 0 : $this->_sections['iconsts2']['loop']-1;
if ($this->_sections['iconsts2']['show']) {
    $this->_sections['iconsts2']['total'] = $this->_sections['iconsts2']['loop'];
    if ($this->_sections['iconsts2']['total'] == 0)
        $this->_sections['iconsts2']['show'] = false;
} else
    $this->_sections['iconsts2']['total'] = 0;
if ($this->_sections['iconsts2']['show']):

            for ($this->_sections['iconsts2']['index'] = $this->_sections['iconsts2']['start'], $this->_sections['iconsts2']['iteration'] = 1;
                 $this->_sections['iconsts2']['iteration'] <= $this->_sections['iconsts2']['total'];
                 $this->_sections['iconsts2']['index'] += $this->_sections['iconsts2']['step'], $this->_sections['iconsts2']['iteration']++):
$this->_sections['iconsts2']['rownum'] = $this->_sections['iconsts2']['iteration'];
$this->_sections['iconsts2']['index_prev'] = $this->_sections['iconsts2']['index'] - $this->_sections['iconsts2']['step'];
$this->_sections['iconsts2']['index_next'] = $this->_sections['iconsts2']['index'] + $this->_sections['iconsts2']['step'];
$this->_sections['iconsts2']['first']      = ($this->_sections['iconsts2']['iteration'] == 1);
$this->_sections['iconsts2']['last']       = ($this->_sections['iconsts2']['iteration'] == $this->_sections['iconsts2']['total']);
?>
<dt>
  <?php echo $this->_tpl_vars['iconsts'][$this->_sections['iconsts']['index']]['iconsts'][$this->_sections['iconsts2']['index']]['link']; ?>

</dt>
<dd>
  <?php echo $this->_tpl_vars['iconsts'][$this->_sections['iconsts']['index']]['iconsts'][$this->_sections['iconsts2']['index']]['iconsts_sdesc']; ?>
 
</dd>
<?php endfor; endif; ?>
</dl>
</div>
<?php endfor; endif; ?>
</td>
<?php endif; ?>

<?php if ($this->_tpl_vars['ivars'] && count ( $this->_tpl_vars['ivars'] ) > 0): ?>
<td valign="top">
<h3>Inherited Variables</h3>
<?php if (isset($this->_sections['ivars'])) unset($this->_sections['ivars']);
$this->_sections['ivars']['name'] = 'ivars';
$this->_sections['ivars']['loop'] = is_array($_loop=$this->_tpl_vars['ivars']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['ivars']['show'] = true;
$this->_sections['ivars']['max'] = $this->_sections['ivars']['loop'];
$this->_sections['ivars']['step'] = 1;
$this->_sections['ivars']['start'] = $this->_sections['ivars']['step'] > 0 ? 0 : $this->_sections['ivars']['loop']-1;
if ($this->_sections['ivars']['show']) {
    $this->_sections['ivars']['total'] = $this->_sections['ivars']['loop'];
    if ($this->_sections['ivars']['total'] == 0)
        $this->_sections['ivars']['show'] = false;
} else
    $this->_sections['ivars']['total'] = 0;
if ($this->_sections['ivars']['show']):

            for ($this->_sections['ivars']['index'] = $this->_sections['ivars']['start'], $this->_sections['ivars']['iteration'] = 1;
                 $this->_sections['ivars']['iteration'] <= $this->_sections['ivars']['total'];
                 $this->_sections['ivars']['index'] += $this->_sections['ivars']['step'], $this->_sections['ivars']['iteration']++):
$this->_sections['ivars']['rownum'] = $this->_sections['ivars']['iteration'];
$this->_sections['ivars']['index_prev'] = $this->_sections['ivars']['index'] - $this->_sections['ivars']['step'];
$this->_sections['ivars']['index_next'] = $this->_sections['ivars']['index'] + $this->_sections['ivars']['step'];
$this->_sections['ivars']['first']      = ($this->_sections['ivars']['iteration'] == 1);
$this->_sections['ivars']['last']       = ($this->_sections['ivars']['iteration'] == $this->_sections['ivars']['total']);
?>
<div class="tags">
<h4>Class: <?php echo $this->_tpl_vars['ivars'][$this->_sections['ivars']['index']]['parent_class']; ?>
</h4>
<dl>
<?php if (isset($this->_sections['ivars2'])) unset($this->_sections['ivars2']);
$this->_sections['ivars2']['name'] = 'ivars2';
$this->_sections['ivars2']['loop'] = is_array($_loop=$this->_tpl_vars['ivars'][$this->_sections['ivars']['index']]['ivars']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['ivars2']['show'] = true;
$this->_sections['ivars2']['max'] = $this->_sections['ivars2']['loop'];
$this->_sections['ivars2']['step'] = 1;
$this->_sections['ivars2']['start'] = $this->_sections['ivars2']['step'] > 0 ? 0 : $this->_sections['ivars2']['loop']-1;
if ($this->_sections['ivars2']['show']) {
    $this->_sections['ivars2']['total'] = $this->_sections['ivars2']['loop'];
    if ($this->_sections['ivars2']['total'] == 0)
        $this->_sections['ivars2']['show'] = false;
} else
    $this->_sections['ivars2']['total'] = 0;
if ($this->_sections['ivars2']['show']):

            for ($this->_sections['ivars2']['index'] = $this->_sections['ivars2']['start'], $this->_sections['ivars2']['iteration'] = 1;
                 $this->_sections['ivars2']['iteration'] <= $this->_sections['ivars2']['total'];
                 $this->_sections['ivars2']['index'] += $this->_sections['ivars2']['step'], $this->_sections['ivars2']['iteration']++):
$this->_sections['ivars2']['rownum'] = $this->_sections['ivars2']['iteration'];
$this->_sections['ivars2']['index_prev'] = $this->_sections['ivars2']['index'] - $this->_sections['ivars2']['step'];
$this->_sections['ivars2']['index_next'] = $this->_sections['ivars2']['index'] + $this->_sections['ivars2']['step'];
$this->_sections['ivars2']['first']      = ($this->_sections['ivars2']['iteration'] == 1);
$this->_sections['ivars2']['last']       = ($this->_sections['ivars2']['iteration'] == $this->_sections['ivars2']['total']);
?>
<dt>
  <?php echo $this->_tpl_vars['ivars'][$this->_sections['ivars']['index']]['ivars'][$this->_sections['ivars2']['index']]['link']; ?>

  </dt>
<dd>
  <?php echo $this->_tpl_vars['ivars'][$this->_sections['ivars']['index']]['ivars'][$this->_sections['ivars2']['index']]['ivars_sdesc']; ?>
 
</dd>
<?php endfor; endif; ?>
</dl>
</div>
<?php endfor; endif; ?>
</td>
<?php endif; ?>

<?php if ($this->_tpl_vars['imethods'] && count ( $this->_tpl_vars['imethods'] ) > 0): ?>
<td valign="top">
<h3>Inherited Methods</h3>
<div class="tags">
<?php if (isset($this->_sections['imethods'])) unset($this->_sections['imethods']);
$this->_sections['imethods']['name'] = 'imethods';
$this->_sections['imethods']['loop'] = is_array($_loop=$this->_tpl_vars['imethods']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['imethods']['show'] = true;
$this->_sections['imethods']['max'] = $this->_sections['imethods']['loop'];
$this->_sections['imethods']['step'] = 1;
$this->_sections['imethods']['start'] = $this->_sections['imethods']['step'] > 0 ? 0 : $this->_sections['imethods']['loop']-1;
if ($this->_sections['imethods']['show']) {
    $this->_sections['imethods']['total'] = $this->_sections['imethods']['loop'];
    if ($this->_sections['imethods']['total'] == 0)
        $this->_sections['imethods']['show'] = false;
} else
    $this->_sections['imethods']['total'] = 0;
if ($this->_sections['imethods']['show']):

            for ($this->_sections['imethods']['index'] = $this->_sections['imethods']['start'], $this->_sections['imethods']['iteration'] = 1;
                 $this->_sections['imethods']['iteration'] <= $this->_sections['imethods']['total'];
                 $this->_sections['imethods']['index'] += $this->_sections['imethods']['step'], $this->_sections['imethods']['iteration']++):
$this->_sections['imethods']['rownum'] = $this->_sections['imethods']['iteration'];
$this->_sections['imethods']['index_prev'] = $this->_sections['imethods']['index'] - $this->_sections['imethods']['step'];
$this->_sections['imethods']['index_next'] = $this->_sections['imethods']['index'] + $this->_sections['imethods']['step'];
$this->_sections['imethods']['first']      = ($this->_sections['imethods']['iteration'] == 1);
$this->_sections['imethods']['last']       = ($this->_sections['imethods']['iteration'] == $this->_sections['imethods']['total']);
?>
<h4>Class: <?php echo $this->_tpl_vars['imethods'][$this->_sections['imethods']['index']]['parent_class']; ?>
</h4>
<dl>
  <?php if (isset($this->_sections['im2'])) unset($this->_sections['im2']);
$this->_sections['im2']['name'] = 'im2';
$this->_sections['im2']['loop'] = is_array($_loop=$this->_tpl_vars['imethods'][$this->_sections['imethods']['index']]['imethods']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['im2']['show'] = true;
$this->_sections['im2']['max'] = $this->_sections['im2']['loop'];
$this->_sections['im2']['step'] = 1;
$this->_sections['im2']['start'] = $this->_sections['im2']['step'] > 0 ? 0 : $this->_sections['im2']['loop']-1;
if ($this->_sections['im2']['show']) {
    $this->_sections['im2']['total'] = $this->_sections['im2']['loop'];
    if ($this->_sections['im2']['total'] == 0)
        $this->_sections['im2']['show'] = false;
} else
    $this->_sections['im2']['total'] = 0;
if ($this->_sections['im2']['show']):

            for ($this->_sections['im2']['index'] = $this->_sections['im2']['start'], $this->_sections['im2']['iteration'] = 1;
                 $this->_sections['im2']['iteration'] <= $this->_sections['im2']['total'];
                 $this->_sections['im2']['index'] += $this->_sections['im2']['step'], $this->_sections['im2']['iteration']++):
$this->_sections['im2']['rownum'] = $this->_sections['im2']['iteration'];
$this->_sections['im2']['index_prev'] = $this->_sections['im2']['index'] - $this->_sections['im2']['step'];
$this->_sections['im2']['index_next'] = $this->_sections['im2']['index'] + $this->_sections['im2']['step'];
$this->_sections['im2']['first']      = ($this->_sections['im2']['iteration'] == 1);
$this->_sections['im2']['last']       = ($this->_sections['im2']['iteration'] == $this->_sections['im2']['total']);
?>
  <dt>
    <?php echo $this->_tpl_vars['imethods'][$this->_sections['imethods']['index']]['imethods'][$this->_sections['im2']['index']]['link']; ?>

  </dt>
  <dd>
    <?php echo $this->_tpl_vars['imethods'][$this->_sections['imethods']['index']]['imethods'][$this->_sections['im2']['index']]['sdesc']; ?>

  </dd>
  <?php endfor; endif; ?>
</dl>
<?php endfor; endif; ?>
</div>
</td>
<?php endif; ?>

</tr></table>
<hr />

<a name="class_details"></a>
<h3>Class Details</h3>
<div class="tags">
[line <?php if ($this->_tpl_vars['class_slink']):  echo $this->_tpl_vars['class_slink'];  else:  echo $this->_tpl_vars['line_number'];  endif; ?>]<br />
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "docblock.tpl", 'smarty_include_vars' => array('type' => 'class','sdesc' => $this->_tpl_vars['sdesc'],'desc' => $this->_tpl_vars['desc'])));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
</div><br /><br />
<div class="top">[ <a href="#top">Top</a> ]</div><br />

<?php if ($this->_tpl_vars['vars'] && count ( $this->_tpl_vars['vars'] ) > 0): ?>
<hr />
<a name="class_vars"></a>
<h3>Class Variables</h3>
<div class="tags">
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "var.tpl", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
</div><br />
<?php endif; ?>

<?php if ($this->_tpl_vars['methods'] & count ( $this->_tpl_vars['methods'] ) > 0): ?>
<hr />
<a name="class_methods"></a>
<h3>Class Methods</h3>
<div class="tags">
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "method.tpl", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
</div><br />
<?php endif; ?>

<?php if ($this->_tpl_vars['consts'] && count ( $this->_tpl_vars['consts'] ) > 0): ?>
<hr />
<a name="class_constss"></a>
<h3>Class Constants</h3>
<div class="tags">
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "const.tpl", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
</div><br />
<?php endif; ?>

<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "footer.tpl", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>