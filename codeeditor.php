<?php
  if(!defined("PROCESSWIRE")) die();

  function showEditor($content, $file_name, $file_ext) {
    echo '<form method="POST" action="'.'?p='.urlencode(FM_PATH).'&amp;view='.urlencode($file_name).'">';
    
    $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(__DIR__.'/ace/', RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    $exts = array();
    foreach ($files as $file) {
      if (!$file->isDir()) {
        if (substr($file->getFilename(), 0, 5) === 'mode-') {
          $ext = substr($file->getFilename(), 5);
          if (substr($ext, -3) === '.js') {
            $ext = substr($ext, 0, -3);
            if ($ext != 'plain_text') {
              $exts[] = $ext;
            }
          }
        }
      }
    }
    sort($exts);

    switch ($ext) {
      case 'js':
        $ext = 'javascript';
        break;
      case 'pas':
        $ext = 'pascal';
        break;
      case 'pl':
        $ext = 'perl';
        break;
      case 'c':
      case 'cpp':
        $ext = 'c_cpp';
        break;
      case 'htaccess':
        $ext = 'apache_conf';
        break;
    }

    $isselected = false;
    echo '<label>Type: <select name="ace_type" id="ace_type">';
    foreach ($exts as $ext) {
      if ($ext == $file_ext) {
        $selected = ' selected="selected"';
        $isselected = true;
      } else {
        $selected = '';
      }
      echo '<option value="'.$ext.'"'.$selected.'>'.$ext.'</option>';
    }
    if ($isselected == true) {
      echo '<option value="plain_text">Plain text</option>';
    } else {
      echo '<option value="plain_text" selected="selected">Plain text</option>';
    }
    echo '</select></label>';

    echo '<textarea id="ace_code" name="ace_code" rows="20" data-theme="'.FM_ACE_THEME.'" data-keybinding="'.FM_ACE_KEYBINDING.'" data-height="'.FM_ACE_HEIGHT.'" data-behaviors-enabled="'.FM_ACE_BEHAVIORS_ENABLED.'" data-wrap-behaviors-enabled="'.FM_ACE_WRAP_BEHAVIORS_ENABLED.'">'.$content.'</textarea>';

    echo '<input type="hidden" name="action" value="save" />';
    echo '<button class="ui-button ui-state-default">Save</button>';
    echo '</form>';
  }
?>
