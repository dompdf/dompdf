<?php /* Smarty version 2.6.0, created on 2016-01-01 10:11:26
         compiled from header.tpl */ ?>
<?php require_once(SMARTY_DIR . 'core' . DIRECTORY_SEPARATOR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('function', 'assign', 'header.tpl', 15, false),array('function', 'eval', 'header.tpl', 76, false),array('modifier', 'capitalize', 'header.tpl', 95, false),)), $this); ?>
<html>
<head>
<title><?php echo $this->_tpl_vars['title']; ?>
</title>
<link rel="stylesheet" type="text/css" href="<?php echo $this->_tpl_vars['subdir']; ?>
media/style.css">
</head>
<body>

<table border="0" cellspacing="0" cellpadding="0" height="48" width="100%">
  <tr>
    <td class="header_top"><?php echo $this->_tpl_vars['package']; ?>
</td>
  </tr>
  <tr><td class="header_line"><img src="<?php echo $this->_tpl_vars['subdir']; ?>
media/empty.png" width="1" height="1" border="0" alt=""  /></td></tr>
  <tr>
    <td class="header_menu">
        <?php echo smarty_function_assign(array('var' => 'packagehaselements','value' => false), $this);?>

        <?php if (count($_from = (array)$this->_tpl_vars['packageindex'])):
    foreach ($_from as $this->_tpl_vars['thispackage']):
?>
            <?php if (in_array ( $this->_tpl_vars['package'] , $this->_tpl_vars['thispackage'] )): ?>
                <?php echo smarty_function_assign(array('var' => 'packagehaselements','value' => true), $this);?>

            <?php endif; ?>
        <?php endforeach; unset($_from); endif; ?>
        <?php if ($this->_tpl_vars['packagehaselements']): ?>
  		  [ <a href="<?php echo $this->_tpl_vars['subdir']; ?>
classtrees_<?php echo $this->_tpl_vars['package']; ?>
.html" class="menu">class tree: <?php echo $this->_tpl_vars['package']; ?>
</a> ]
		  [ <a href="<?php echo $this->_tpl_vars['subdir']; ?>
elementindex_<?php echo $this->_tpl_vars['package']; ?>
.html" class="menu">index: <?php echo $this->_tpl_vars['package']; ?>
</a> ]
		<?php endif; ?>
  	    [ <a href="<?php echo $this->_tpl_vars['subdir']; ?>
elementindex.html" class="menu">all elements</a> ]
    </td>
  </tr>
  <tr><td class="header_line"><img src="<?php echo $this->_tpl_vars['subdir']; ?>
media/empty.png" width="1" height="1" border="0" alt=""  /></td></tr>
</table>

<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr valign="top">
    <td width="200" class="menu">
<?php if (count ( $this->_tpl_vars['ric'] ) >= 1): ?>
	<div id="ric">
		<?php if (isset($this->_sections['ric'])) unset($this->_sections['ric']);
$this->_sections['ric']['name'] = 'ric';
$this->_sections['ric']['loop'] = is_array($_loop=$this->_tpl_vars['ric']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['ric']['show'] = true;
$this->_sections['ric']['max'] = $this->_sections['ric']['loop'];
$this->_sections['ric']['step'] = 1;
$this->_sections['ric']['start'] = $this->_sections['ric']['step'] > 0 ? 0 : $this->_sections['ric']['loop']-1;
if ($this->_sections['ric']['show']) {
    $this->_sections['ric']['total'] = $this->_sections['ric']['loop'];
    if ($this->_sections['ric']['total'] == 0)
        $this->_sections['ric']['show'] = false;
} else
    $this->_sections['ric']['total'] = 0;
if ($this->_sections['ric']['show']):

            for ($this->_sections['ric']['index'] = $this->_sections['ric']['start'], $this->_sections['ric']['iteration'] = 1;
                 $this->_sections['ric']['iteration'] <= $this->_sections['ric']['total'];
                 $this->_sections['ric']['index'] += $this->_sections['ric']['step'], $this->_sections['ric']['iteration']++):
$this->_sections['ric']['rownum'] = $this->_sections['ric']['iteration'];
$this->_sections['ric']['index_prev'] = $this->_sections['ric']['index'] - $this->_sections['ric']['step'];
$this->_sections['ric']['index_next'] = $this->_sections['ric']['index'] + $this->_sections['ric']['step'];
$this->_sections['ric']['first']      = ($this->_sections['ric']['iteration'] == 1);
$this->_sections['ric']['last']       = ($this->_sections['ric']['iteration'] == $this->_sections['ric']['total']);
?>
			<p><a href="<?php echo $this->_tpl_vars['subdir'];  echo $this->_tpl_vars['ric'][$this->_sections['ric']['index']]['file']; ?>
"><?php echo $this->_tpl_vars['ric'][$this->_sections['ric']['index']]['name']; ?>
</a></p>
		<?php endfor; endif; ?>
	</div>
<?php endif; ?>
<?php if ($this->_tpl_vars['hastodos']): ?>
	<div id="todolist">
			<p><a href="<?php echo $this->_tpl_vars['subdir'];  echo $this->_tpl_vars['todolink']; ?>
">Todo List</a></p>
	</div>
<?php endif; ?>
      <b>Packages:</b><br />
      <?php if (isset($this->_sections['packagelist'])) unset($this->_sections['packagelist']);
$this->_sections['packagelist']['name'] = 'packagelist';
$this->_sections['packagelist']['loop'] = is_array($_loop=$this->_tpl_vars['packageindex']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['packagelist']['show'] = true;
$this->_sections['packagelist']['max'] = $this->_sections['packagelist']['loop'];
$this->_sections['packagelist']['step'] = 1;
$this->_sections['packagelist']['start'] = $this->_sections['packagelist']['step'] > 0 ? 0 : $this->_sections['packagelist']['loop']-1;
if ($this->_sections['packagelist']['show']) {
    $this->_sections['packagelist']['total'] = $this->_sections['packagelist']['loop'];
    if ($this->_sections['packagelist']['total'] == 0)
        $this->_sections['packagelist']['show'] = false;
} else
    $this->_sections['packagelist']['total'] = 0;
if ($this->_sections['packagelist']['show']):

            for ($this->_sections['packagelist']['index'] = $this->_sections['packagelist']['start'], $this->_sections['packagelist']['iteration'] = 1;
                 $this->_sections['packagelist']['iteration'] <= $this->_sections['packagelist']['total'];
                 $this->_sections['packagelist']['index'] += $this->_sections['packagelist']['step'], $this->_sections['packagelist']['iteration']++):
$this->_sections['packagelist']['rownum'] = $this->_sections['packagelist']['iteration'];
$this->_sections['packagelist']['index_prev'] = $this->_sections['packagelist']['index'] - $this->_sections['packagelist']['step'];
$this->_sections['packagelist']['index_next'] = $this->_sections['packagelist']['index'] + $this->_sections['packagelist']['step'];
$this->_sections['packagelist']['first']      = ($this->_sections['packagelist']['iteration'] == 1);
$this->_sections['packagelist']['last']       = ($this->_sections['packagelist']['iteration'] == $this->_sections['packagelist']['total']);
?>
        <a href="<?php echo $this->_tpl_vars['subdir'];  echo $this->_tpl_vars['packageindex'][$this->_sections['packagelist']['index']]['link']; ?>
"><?php echo $this->_tpl_vars['packageindex'][$this->_sections['packagelist']['index']]['title']; ?>
</a><br />
      <?php endfor; endif; ?>
      <br /><br />
<?php if ($this->_tpl_vars['tutorials']): ?>
		<b>Tutorials/Manuals:</b><br />
		<?php if ($this->_tpl_vars['tutorials']['pkg']): ?>
			<strong>Package-level:</strong>
			<?php if (isset($this->_sections['ext'])) unset($this->_sections['ext']);
$this->_sections['ext']['name'] = 'ext';
$this->_sections['ext']['loop'] = is_array($_loop=$this->_tpl_vars['tutorials']['pkg']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['ext']['show'] = true;
$this->_sections['ext']['max'] = $this->_sections['ext']['loop'];
$this->_sections['ext']['step'] = 1;
$this->_sections['ext']['start'] = $this->_sections['ext']['step'] > 0 ? 0 : $this->_sections['ext']['loop']-1;
if ($this->_sections['ext']['show']) {
    $this->_sections['ext']['total'] = $this->_sections['ext']['loop'];
    if ($this->_sections['ext']['total'] == 0)
        $this->_sections['ext']['show'] = false;
} else
    $this->_sections['ext']['total'] = 0;
if ($this->_sections['ext']['show']):

            for ($this->_sections['ext']['index'] = $this->_sections['ext']['start'], $this->_sections['ext']['iteration'] = 1;
                 $this->_sections['ext']['iteration'] <= $this->_sections['ext']['total'];
                 $this->_sections['ext']['index'] += $this->_sections['ext']['step'], $this->_sections['ext']['iteration']++):
$this->_sections['ext']['rownum'] = $this->_sections['ext']['iteration'];
$this->_sections['ext']['index_prev'] = $this->_sections['ext']['index'] - $this->_sections['ext']['step'];
$this->_sections['ext']['index_next'] = $this->_sections['ext']['index'] + $this->_sections['ext']['step'];
$this->_sections['ext']['first']      = ($this->_sections['ext']['iteration'] == 1);
$this->_sections['ext']['last']       = ($this->_sections['ext']['iteration'] == $this->_sections['ext']['total']);
?>
				<?php echo $this->_tpl_vars['tutorials']['pkg'][$this->_sections['ext']['index']]; ?>

			<?php endfor; endif; ?>
		<?php endif; ?>
		<?php if ($this->_tpl_vars['tutorials']['cls']): ?>
			<strong>Class-level:</strong>
			<?php if (isset($this->_sections['ext'])) unset($this->_sections['ext']);
$this->_sections['ext']['name'] = 'ext';
$this->_sections['ext']['loop'] = is_array($_loop=$this->_tpl_vars['tutorials']['cls']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['ext']['show'] = true;
$this->_sections['ext']['max'] = $this->_sections['ext']['loop'];
$this->_sections['ext']['step'] = 1;
$this->_sections['ext']['start'] = $this->_sections['ext']['step'] > 0 ? 0 : $this->_sections['ext']['loop']-1;
if ($this->_sections['ext']['show']) {
    $this->_sections['ext']['total'] = $this->_sections['ext']['loop'];
    if ($this->_sections['ext']['total'] == 0)
        $this->_sections['ext']['show'] = false;
} else
    $this->_sections['ext']['total'] = 0;
if ($this->_sections['ext']['show']):

            for ($this->_sections['ext']['index'] = $this->_sections['ext']['start'], $this->_sections['ext']['iteration'] = 1;
                 $this->_sections['ext']['iteration'] <= $this->_sections['ext']['total'];
                 $this->_sections['ext']['index'] += $this->_sections['ext']['step'], $this->_sections['ext']['iteration']++):
$this->_sections['ext']['rownum'] = $this->_sections['ext']['iteration'];
$this->_sections['ext']['index_prev'] = $this->_sections['ext']['index'] - $this->_sections['ext']['step'];
$this->_sections['ext']['index_next'] = $this->_sections['ext']['index'] + $this->_sections['ext']['step'];
$this->_sections['ext']['first']      = ($this->_sections['ext']['iteration'] == 1);
$this->_sections['ext']['last']       = ($this->_sections['ext']['iteration'] == $this->_sections['ext']['total']);
?>
				<?php echo $this->_tpl_vars['tutorials']['cls'][$this->_sections['ext']['index']]; ?>

			<?php endfor; endif; ?>
		<?php endif; ?>
		<?php if ($this->_tpl_vars['tutorials']['proc']): ?>
			<strong>Procedural-level:</strong>
			<?php if (isset($this->_sections['ext'])) unset($this->_sections['ext']);
$this->_sections['ext']['name'] = 'ext';
$this->_sections['ext']['loop'] = is_array($_loop=$this->_tpl_vars['tutorials']['proc']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['ext']['show'] = true;
$this->_sections['ext']['max'] = $this->_sections['ext']['loop'];
$this->_sections['ext']['step'] = 1;
$this->_sections['ext']['start'] = $this->_sections['ext']['step'] > 0 ? 0 : $this->_sections['ext']['loop']-1;
if ($this->_sections['ext']['show']) {
    $this->_sections['ext']['total'] = $this->_sections['ext']['loop'];
    if ($this->_sections['ext']['total'] == 0)
        $this->_sections['ext']['show'] = false;
} else
    $this->_sections['ext']['total'] = 0;
if ($this->_sections['ext']['show']):

            for ($this->_sections['ext']['index'] = $this->_sections['ext']['start'], $this->_sections['ext']['iteration'] = 1;
                 $this->_sections['ext']['iteration'] <= $this->_sections['ext']['total'];
                 $this->_sections['ext']['index'] += $this->_sections['ext']['step'], $this->_sections['ext']['iteration']++):
$this->_sections['ext']['rownum'] = $this->_sections['ext']['iteration'];
$this->_sections['ext']['index_prev'] = $this->_sections['ext']['index'] - $this->_sections['ext']['step'];
$this->_sections['ext']['index_next'] = $this->_sections['ext']['index'] + $this->_sections['ext']['step'];
$this->_sections['ext']['first']      = ($this->_sections['ext']['iteration'] == 1);
$this->_sections['ext']['last']       = ($this->_sections['ext']['iteration'] == $this->_sections['ext']['total']);
?>
				<?php echo $this->_tpl_vars['tutorials']['proc'][$this->_sections['ext']['index']]; ?>

			<?php endfor; endif; ?>
		<?php endif; ?>
<?php endif; ?>
      <?php if (! $this->_tpl_vars['noleftindex']):  echo smarty_function_assign(array('var' => 'noleftindex','value' => false), $this); endif; ?>
      <?php if (! $this->_tpl_vars['noleftindex']): ?>
      <?php if ($this->_tpl_vars['compiledfileindex']): ?>
      <b>Files:</b><br />
      <?php echo smarty_function_eval(array('var' => $this->_tpl_vars['compiledfileindex']), $this);?>

      <?php endif; ?>

      <?php if ($this->_tpl_vars['compiledinterfaceindex']): ?>
      <b>Interfaces:</b><br />
      <?php echo smarty_function_eval(array('var' => $this->_tpl_vars['compiledinterfaceindex']), $this);?>

      <?php endif; ?>

      <?php if ($this->_tpl_vars['compiledclassindex']): ?>
      <b>Classes:</b><br />
      <?php echo smarty_function_eval(array('var' => $this->_tpl_vars['compiledclassindex']), $this);?>

      <?php endif; ?>
      <?php endif; ?>
    </td>
    <td>
      <table cellpadding="10" cellspacing="0" width="100%" border="0"><tr><td valign="top">

<?php if (! $this->_tpl_vars['hasel']):  echo smarty_function_assign(array('var' => 'hasel','value' => false), $this); endif; ?>
<?php if ($this->_tpl_vars['hasel']): ?>
<h1><?php echo ((is_array($_tmp=$this->_tpl_vars['eltype'])) ? $this->_run_mod_handler('capitalize', true, $_tmp) : smarty_modifier_capitalize($_tmp)); ?>
: <?php echo $this->_tpl_vars['class_name']; ?>
</h1>
Source Location: <?php echo $this->_tpl_vars['source_location']; ?>
<br /><br />
<?php endif; ?>