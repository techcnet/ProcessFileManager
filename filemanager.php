 <?php
  /**
   * This is a modified version of PHP File Manager:
   * https://github.com/alexantr/filemanager
   */
  
  if(!defined("PROCESSWIRE")) die();
  
  // Root path for file manager
  $root_path = $_SERVER['DOCUMENT_ROOT'];
  
  // Root url for links in file manager.Relative to $http_host. Variants: '', 'path/to/subfolder'
  // Will not working if $root_path will be outside of server document root
  $root_url = '';
  
  // Server hostname. Can set manually if wrong
  $http_host = $_SERVER['HTTP_HOST'];
  
  $is_https = isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https';
  
  // clean and check $root_path
  $root_path = rtrim($root_path, '\\/');
  $root_path = str_replace('\\', '/', $root_path);
  if (!@is_dir($root_path)) {
    echo sprintf('<h1>Root path "%s" not found!</h1>', fm_enc($root_path));
    return;
  }
  
  // clean $root_url
  $root_url = fm_clean_path($root_url);
  
  // abs path for site
  defined('FM_ROOT_PATH') || define('FM_ROOT_PATH', $root_path);
  defined('FM_ROOT_URL') || define('FM_ROOT_URL', ($is_https ? 'https' : 'http') . '://' . $http_host . (!empty($root_url) ? '/' . $root_url : ''));
  defined('FM_SELF_URL') || define('FM_SELF_URL', ($is_https ? 'https' : 'http') . '://' . $http_host . $_SERVER['PHP_SELF']);
  
  define('FM_IS_WIN', DIRECTORY_SEPARATOR == '\\');
  
  // always use ?p=
  if (!isset($_GET['p'])) {
    fm_redirect(FM_SELF_URL . '?p=');
  }
  
  // get path
  $p = isset($_GET['p']) ? $_GET['p'] : (isset($_POST['p']) ? $_POST['p'] : '');
  
  // clean path
  $p = fm_clean_path($p);
  define('FM_PATH', $p);
  unset($p);

  defined('FM_ICONV_INPUT_ENC') || define('FM_ICONV_INPUT_ENC', 'CP1252');
  defined('FM_DATETIME_FORMAT') || define('FM_DATETIME_FORMAT', 'd.m.y H:i:s');
  defined('FM_DATETIME_ZONE') || define('FM_DATETIME_ZONE', 'UTC');
  defined('FM_ACE_THEME') || define('FM_ACE_THEME', 'monokai');
  defined('FM_ACE_KEYBINDING') || define('FM_ACE_KEYBINDING', 'none');
  defined('FM_ACE_HEIGHT') || define('FM_ACE_HEIGHT', '400');
  defined('FM_ACE_BEHAVIORS') || define('FM_ACE_BEHAVIORS', '0');
  
  /*************************** ACTIONS ***************************/
  
  // Delete file / folder
  if (isset($_GET['del'])) {
    $del = $_GET['del'];
    $del = fm_clean_path($del);
    $del = str_replace('/', '', $del);
    if ($del != '' && $del != '..' && $del != '.') {
      $path = FM_ROOT_PATH;
      if (FM_PATH != '') {
        $path .= '/' . FM_PATH;
      }
      $is_dir = is_dir($path . '/' . $del);
      if (fm_rdelete($path . '/' . $del)) {
        $msg = $is_dir ? 'Folder <b>%s</b> deleted' : 'File <b>%s</b> deleted';
        fm_set_msg(sprintf($msg, fm_enc($del)));
      } else {
        $msg = $is_dir ? 'Folder <b>%s</b> not deleted' : 'File <b>%s</b> not deleted';
        fm_set_msg(sprintf($msg, fm_enc($del)), 'error');
      }
    } else {
      fm_set_msg('Wrong file or folder name', 'error');
    }
    fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
  }
  
  // Create folder
  if (isset($_GET['newfolder'])) {
    $new = strip_tags($_GET['newfolder']); // remove unwanted characters from folder name
    $new = fm_clean_path($new);
    $new = str_replace('/', '', $new);
    if ($new != '' && $new != '..' && $new != '.') {
      $path = FM_ROOT_PATH;
      if (FM_PATH != '') {
        $path .= '/' . FM_PATH;
      }
      if (fm_mkdir($path . '/' . $new, false) === true) {
        fm_set_msg(sprintf('Folder <b>%s</b> created', fm_enc($new)));
      } elseif (fm_mkdir($path . '/' . $new, false) === $path . '/' . $new) {
        fm_set_msg(sprintf('Folder <b>%s</b> already exists', fm_enc($new)), 'alert');
      } else {
        fm_set_msg(sprintf('Folder <b>%s</b> not created', fm_enc($new)), 'error');
      }
    } else {
      fm_set_msg('Wrong folder name', 'error');
    }
    fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
  }

  // Create file
  if (isset($_GET['newfile'])) {
    $new = strip_tags($_GET['newfile']); // remove unwanted characters from file name
    $new = fm_clean_path($new);
    $new = str_replace('/', '', $new);
    if ($new != '' && $new != '..' && $new != '.') {
      $path = FM_ROOT_PATH;
      if (FM_PATH != '') {
        $path .= '/' . FM_PATH;
      }
      if (file_exists($path.'/'.$new)) {
        fm_set_msg(sprintf('File <b>%s</b> already exists', fm_enc($new)), 'alert');
      } else if (file_put_contents($path.'/'.$new, '') === false) {
        fm_set_msg(sprintf('File <b>%s</b> not created', fm_enc($new)), 'error');
      } else {
        fm_set_msg(sprintf('File <b>%s</b> created', fm_enc($new)));
      }
    } else {
      fm_set_msg('Wrong file name', 'error');
    }
    fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
  }

  // Copy folder / file
  if (isset($_GET['copy'], $_GET['finish'])) {
    // from
    $copy = $_GET['copy'];
    $copy = fm_clean_path($copy);
    // empty path
    if ($copy == '') {
      fm_set_msg('Source path not defined', 'error');
      fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
    }
    // abs path from
    $from = FM_ROOT_PATH . '/' . $copy;
    // abs path to
    $dest = FM_ROOT_PATH;
    if (FM_PATH != '') {
      $dest .= '/' . FM_PATH;
    }
    $dest .= '/' . basename($from);
    // move?
    $move = isset($_GET['move']);
    // copy/move
    if ($from != $dest) {
      $msg_from = trim(FM_PATH . '/' . basename($from), '/');
      if ($move) {
        $rename = fm_rename($from, $dest);
        if ($rename) {
          fm_set_msg(sprintf('Moved from <b>%s</b> to <b>%s</b>', fm_enc($copy), fm_enc($msg_from)));
        } elseif ($rename === null) {
          fm_set_msg('File or folder with this path already exists', 'alert');
        } else {
          fm_set_msg(sprintf('Error while moving from <b>%s</b> to <b>%s</b>', fm_enc($copy), fm_enc($msg_from)), 'error');
        }
      } else {
        if (fm_rcopy($from, $dest)) {
          fm_set_msg(sprintf('Copyied from <b>%s</b> to <b>%s</b>', fm_enc($copy), fm_enc($msg_from)));
        } else {
          fm_set_msg(sprintf('Error while copying from <b>%s</b> to <b>%s</b>', fm_enc($copy), fm_enc($msg_from)), 'error');
        }
      }
    } else {
      fm_set_msg('Paths must be not equal', 'alert');
    }
    fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
  }
  
  // Mass copy files/ folders
  if (isset($_POST['file'], $_POST['copy_to'], $_POST['finish'])) {
    // from
    $path = FM_ROOT_PATH;
    if (FM_PATH != '') {
      $path .= '/' . FM_PATH;
    }
    // to
    $copy_to_path = FM_ROOT_PATH;
    $copy_to      = fm_clean_path($_POST['copy_to']);
    if ($copy_to != '') {
      $copy_to_path .= '/' . $copy_to;
    }
    if ($path == $copy_to_path) {
      fm_set_msg('Paths must be not equal', 'alert');
      fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
    }
    if (!is_dir($copy_to_path)) {
      if (!fm_mkdir($copy_to_path, true)) {
        fm_set_msg('Unable to create destination folder', 'error');
        fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
      }
    }
    // move?
    $move   = isset($_POST['move']);
    // copy/move
    $errors = 0;
    $files  = $_POST['file'];
    if (is_array($files) && count($files)) {
      foreach ($files as $f) {
        if ($f != '') {
          // abs path from
          $from = $path . '/' . $f;
          // abs path to
          $dest = $copy_to_path . '/' . $f;
          // do
          if ($move) {
            $rename = fm_rename($from, $dest);
            if ($rename === false) {
              $errors++;
            }
          } else {
            if (!fm_rcopy($from, $dest)) {
              $errors++;
            }
          }
        }
      }
      if ($errors == 0) {
        $msg = $move ? 'Selected files and folders moved' : 'Selected files and folders copied';
        fm_set_msg($msg);
      } else {
        $msg = $move ? 'Error while moving items' : 'Error while copying items';
        fm_set_msg($msg, 'error');
      }
    } else {
      fm_set_msg('Nothing selected', 'alert');
    }
    fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
  }
  
  // Rename
  if (isset($_GET['ren'], $_GET['to'])) {
    // old name
    $old  = $_GET['ren'];
    $old  = fm_clean_path($old);
    $old  = str_replace('/', '', $old);
    // new name
    $new  = $_GET['to'];
    $new  = fm_clean_path($new);
    $new  = str_replace('/', '', $new);
    // path
    $path = FM_ROOT_PATH;
    if (FM_PATH != '') {
      $path .= '/' . FM_PATH;
    }
    // rename
    if ($old != '' && $new != '') {
      if (fm_rename($path . '/' . $old, $path . '/' . $new)) {
        fm_set_msg(sprintf('Renamed from <b>%s</b> to <b>%s</b>', fm_enc($old), fm_enc($new)));
      } else {
        fm_set_msg(sprintf('Error while renaming from <b>%s</b> to <b>%s</b>', fm_enc($old), fm_enc($new)), 'error');
      }
    } else {
      fm_set_msg('Names not set', 'error');
    }
    fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
  }
  
  // Download
  if (isset($_GET['dl'])) {
    $dl   = $_GET['dl'];
    $dl   = fm_clean_path($dl);
    $dl   = str_replace('/', '', $dl);
    $path = FM_ROOT_PATH;
    if (FM_PATH != '') {
      $path .= '/' . FM_PATH;
    }
    if ($dl != '' && is_file($path . '/' . $dl)) {
      header('Content-Description: File Transfer');
      header('Content-Type: application/octet-stream');
      header('Content-Disposition: attachment; filename="' . basename($path . '/' . $dl) . '"');
      header('Content-Transfer-Encoding: binary');
      header('Connection: Keep-Alive');
      header('Expires: 0');
      header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
      header('Pragma: public');
      header('Content-Length: ' . filesize($path . '/' . $dl));
      readfile($path . '/' . $dl);
      exit;
    } else {
      fm_set_msg('File not found', 'error');
      fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
    }
  }
  
  // Upload
  if (isset($_POST['upl'])) {
    $path = FM_ROOT_PATH;
    if (FM_PATH != '') {
      $path .= '/' . FM_PATH;
    }
    
    $errors  = 0;
    $uploads = 0;
    $total   = count($_FILES['upload']['name']);
    
    for ($i = 0; $i < $total; $i++) {
      $tmp_name = $_FILES['upload']['tmp_name'][$i];
      if (empty($_FILES['upload']['error'][$i]) && !empty($tmp_name) && $tmp_name != 'none') {
        if (move_uploaded_file($tmp_name, $path . '/' . $_FILES['upload']['name'][$i])) {
          $uploads++;
        } else {
          $errors++;
        }
      }
    }
    
    if ($errors == 0 && $uploads > 0) {
      fm_set_msg(sprintf('All files uploaded to <b>%s</b>', fm_enc($path)));
    } elseif ($errors == 0 && $uploads == 0) {
      fm_set_msg('Nothing uploaded', 'alert');
    } else {
      fm_set_msg(sprintf('Error while uploading files. Uploaded files: %s', $uploads), 'error');
    }
    
    fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
  }
  
  // Mass deleting
  if (isset($_POST['group'], $_POST['delete'])) {
    $path = FM_ROOT_PATH;
    if (FM_PATH != '') {
      $path .= '/' . FM_PATH;
    }
    
    $errors = 0;
    $files  = $_POST['file'];
    if (is_array($files) && count($files)) {
      foreach ($files as $f) {
        if ($f != '') {
          $new_path = $path . '/' . $f;
          if (!fm_rdelete($new_path)) {
            $errors++;
          }
        }
      }
      if ($errors == 0) {
        fm_set_msg('Selected files and folder deleted');
      } else {
        fm_set_msg('Error while deleting items', 'error');
      }
    } else {
      fm_set_msg('Nothing selected', 'alert');
    }
    
    fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
  }
  
  // Pack files
  if (isset($_POST['group'], $_POST['zip'])) {
    $path = FM_ROOT_PATH;
    if (FM_PATH != '') {
      $path .= '/' . FM_PATH;
    }
    
    if (!class_exists('ZipArchive')) {
      fm_set_msg('Operations with archives are not available', 'error');
      fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
    }
    
    $files = $_POST['file'];
    if (!empty($files)) {
      chdir($path);
      
      if (count($files) == 1) {
        $one_file = reset($files);
        $one_file = basename($one_file);
        $zipname  = $one_file . '_' . date('ymd_His') . '.zip';
      } else {
        $zipname = 'archive_' . date('ymd_His') . '.zip';
      }
      
      $zipper = new FM_Zipper();
      $res    = $zipper->create($zipname, $files);
      
      if ($res) {
        fm_set_msg(sprintf('Archive <b>%s</b> created', fm_enc($zipname)));
      } else {
        fm_set_msg('Archive not created', 'error');
      }
    } else {
      fm_set_msg('Nothing selected', 'alert');
    }
    
    fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
  }
  
  // Unpack
  if (isset($_GET['unzip'])) {
    $unzip = $_GET['unzip'];
    $unzip = fm_clean_path($unzip);
    $unzip = str_replace('/', '', $unzip);
    
    $path = FM_ROOT_PATH;
    if (FM_PATH != '') {
      $path .= '/' . FM_PATH;
    }
    
    if (!class_exists('ZipArchive')) {
      fm_set_msg('Operations with archives are not available', 'error');
      fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
    }
    
    if ($unzip != '' && is_file($path . '/' . $unzip)) {
      $zip_path = $path . '/' . $unzip;
      
      //to folder
      $tofolder = '';
      if (isset($_GET['tofolder'])) {
        $tofolder = pathinfo($zip_path, PATHINFO_FILENAME);
        if (fm_mkdir($path . '/' . $tofolder, true)) {
          $path .= '/' . $tofolder;
        }
      }
      
      $zipper = new FM_Zipper();
      $res    = $zipper->unzip($zip_path, $path);
      
      if ($res) {
        fm_set_msg('Archive unpacked');
      } else {
        fm_set_msg('Archive not unpacked', 'error');
      }
      
    } else {
      fm_set_msg('File not found', 'error');
    }
    fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
  }
  
  // Change Perms (not for Windows)
  if (isset($_POST['chmod']) && !FM_IS_WIN) {
    $path = FM_ROOT_PATH;
    if (FM_PATH != '') {
      $path .= '/' . FM_PATH;
    }
    
    $file = $_POST['chmod'];
    $file = fm_clean_path($file);
    $file = str_replace('/', '', $file);
    if ($file == '' || (!is_file($path . '/' . $file) && !is_dir($path . '/' . $file))) {
      fm_set_msg('File not found', 'error');
      fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
    }
    
    $mode = 0;
    if (!empty($_POST['ur'])) {
      $mode |= 0400;
    }
    if (!empty($_POST['uw'])) {
      $mode |= 0200;
    }
    if (!empty($_POST['ux'])) {
      $mode |= 0100;
    }
    if (!empty($_POST['gr'])) {
      $mode |= 0040;
    }
    if (!empty($_POST['gw'])) {
      $mode |= 0020;
    }
    if (!empty($_POST['gx'])) {
      $mode |= 0010;
    }
    if (!empty($_POST['or'])) {
      $mode |= 0004;
    }
    if (!empty($_POST['ow'])) {
      $mode |= 0002;
    }
    if (!empty($_POST['ox'])) {
      $mode |= 0001;
    }
    
    if (@chmod($path . '/' . $file, $mode)) {
      fm_set_msg('Permissions changed');
    } else {
      fm_set_msg('Permissions not changed', 'error');
    }
    
    fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
  }
  
  /*************************** /ACTIONS ***************************/
  
  // get current path
  $path = FM_ROOT_PATH;
  if (FM_PATH != '') {
    $path .= '/' . FM_PATH;
  }
  
  // check path
  if (!is_dir($path)) {
    fm_redirect(FM_SELF_URL . '?p=');
  }
  
  // get parent folder
  $parent = fm_get_parent_path(FM_PATH);
  
  $objects = is_readable($path) ? scandir($path) : array();
  $folders = array();
  $files   = array();
  if (is_array($objects)) {
    foreach ($objects as $file) {
      if ($file == '.' || $file == '..') {
        continue;
      }
      $new_path = $path . '/' . $file;
      if (is_file($new_path)) {
        $files[] = $file;
      } elseif (is_dir($new_path) && $file != '.' && $file != '..') {
        $folders[] = $file;
      }
    }
  }
  
  if (!empty($files)) {
    natcasesort($files);
  }
  if (!empty($folders)) {
    natcasesort($folders);
  }
  
  // upload form
  if (isset($_GET['upload'])) {
    fm_show_header(); // HEADER
    fm_show_nav_path(FM_PATH); // current path
?>
   <div class="path">
        <p><b>Uploading files</b></p>
        <p class="break-word">Destination folder: <?php
    echo fm_enc(fm_convert_win(FM_ROOT_PATH . '/' . FM_PATH));
?></p>
        <form action="" method="post" enctype="multipart/form-data">
            <input type="hidden" name="p" value="<?php
    echo fm_enc(FM_PATH);
?>">
            <input type="hidden" name="upl" value="1">
            <input type="file" name="upload[]"><br>
            <input type="file" name="upload[]"><br>
            <input type="file" name="upload[]"><br>
            <input type="file" name="upload[]"><br>
            <input type="file" name="upload[]"><br>
            <br>
            <p>
                <button class="btn"><i class="icon-apply"></i> Upload</button> &nbsp;
                <b><a href="?p=<?php
    echo urlencode(FM_PATH);
?>"><i class="icon-cancel"></i> Cancel</a></b>
            </p>
        </form>
    </div>
    <?php
    fm_show_footer();
    return;
  }
  
  // copy form POST
  if (isset($_POST['copy'])) {
    $copy_files = $_POST['file'];
    if (!is_array($copy_files) || empty($copy_files)) {
      fm_set_msg('Nothing selected', 'alert');
      fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
    }
    
    fm_show_header(); // HEADER
    fm_show_nav_path(FM_PATH); // current path
?>
   <div class="path">
        <p><b>Copying</b></p>
        <form action="" method="post">
            <input type="hidden" name="p" value="<?php
    echo fm_enc(FM_PATH);
?>">
            <input type="hidden" name="finish" value="1">
            <?php
    foreach ($copy_files as $cf) {
      echo '<input type="hidden" name="file[]" value="' . fm_enc($cf) . '">' . PHP_EOL;
    }
    $copy_files_enc = array_map('fm_enc', $copy_files);
?>
           <p class="break-word">Files: <b><?php
    echo implode('</b>, <b>', $copy_files_enc);
?></b></p>
            <p class="break-word">Source folder: <?php
    echo fm_enc(fm_convert_win(FM_ROOT_PATH . '/' . FM_PATH));
?><br>
                <label for="inp_copy_to">Destination folder:</label>
                <?php
    echo FM_ROOT_PATH;
?>/<input name="copy_to" id="inp_copy_to" value="<?php
    echo fm_enc(FM_PATH);
?>">
            </p>
            <p><label><input type="checkbox" name="move" value="1"> Move</label></p>
            <p>
                <button class="btn"><i class="icon-apply"></i> Copy</button> &nbsp;
                <b><a href="?p=<?php
    echo urlencode(FM_PATH);
?>"><i class="icon-cancel"></i> Cancel</a></b>
            </p>
        </form>
    </div>
    <?php
    fm_show_footer();
    return;
  }
  
  // copy form
  if (isset($_GET['copy']) && !isset($_GET['finish'])) {
    $copy = $_GET['copy'];
    $copy = fm_clean_path($copy);
    if ($copy == '' || !file_exists(FM_ROOT_PATH . '/' . $copy)) {
      fm_set_msg('File not found', 'error');
      fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
    }
    
    fm_show_header(); // HEADER
    fm_show_nav_path(FM_PATH); // current path
?>
   <div class="path">
        <p><b>Copying</b></p>
        <p class="break-word">
            Source path: <?php
    echo fm_enc(fm_convert_win(FM_ROOT_PATH . '/' . $copy));
?><br>
            Destination folder: <?php
    echo fm_enc(fm_convert_win(FM_ROOT_PATH . '/' . FM_PATH));
?>
       </p>
        <p>
            <b><a href="?p=<?php
    echo urlencode(FM_PATH);
?>&amp;copy=<?php
    echo urlencode($copy);
?>&amp;finish=1"><i class="icon-apply"></i> Copy</a></b> &nbsp;
            <b><a href="?p=<?php
    echo urlencode(FM_PATH);
?>&amp;copy=<?php
    echo urlencode($copy);
?>&amp;finish=1&amp;move=1"><i class="icon-apply"></i> Move</a></b> &nbsp;
            <b><a href="?p=<?php
    echo urlencode(FM_PATH);
?>"><i class="icon-cancel"></i> Cancel</a></b>
        </p>
        <p><i>Select folder:</i></p>
        <ul class="folders break-word">
            <?php
    if ($parent !== false) {
?>
               <li><a href="?p=<?php
      echo urlencode($parent);
?>&amp;copy=<?php
      echo urlencode($copy);
?>"><i class="icon-arrow_up"></i> ..</a></li>
            <?php
    }
    foreach ($folders as $f) {
?>
               <li><a href="?p=<?php
      echo urlencode(trim(FM_PATH . '/' . $f, '/'));
?>&amp;copy=<?php
      echo urlencode($copy);
?>"><i class="icon-folder"></i> <?php
      echo fm_enc(fm_convert_win($f));
?></a></li>
            <?php
    }
?>
       </ul>
    </div>
    <?php
    fm_show_footer();
    return;
  }
  
  // file viewer
  if (isset($_GET['view'])) {
    $file = $_GET['view'];
    $file = fm_clean_path($file);
    $file = str_replace('/', '', $file);
    if ($file == '' || !is_file($path . '/' . $file)) {
      fm_set_msg('File not found', 'error');
      fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
    }
    
    fm_show_header(); // HEADER
    fm_show_nav_path(FM_PATH); // current path
    
    $file_url  = FM_ROOT_URL . fm_convert_win((FM_PATH != '' ? '/' . FM_PATH : '') . '/' . $file);
    $file_path = $path . '/' . $file;
    
    $ext       = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    $mime_type = fm_get_mime_type($file_path);
    $filesize  = filesize($file_path);
    
    $is_zip   = false;
    $is_image = false;
    $is_audio = false;
    $is_video = false;
    $is_text  = false;
    
    $view_title = 'File';
    $filenames  = false; // for zip
    $content    = ''; // for text
    
    if ($ext == 'zip') {
      $is_zip     = true;
      $view_title = 'Archive';
      $filenames  = fm_get_zif_info($file_path);
    } elseif (in_array($ext, fm_get_image_exts())) {
      $is_image   = true;
      $view_title = 'Image';
    } elseif (in_array($ext, fm_get_audio_exts())) {
      $is_audio   = true;
      $view_title = 'Audio';
    } elseif (in_array($ext, fm_get_video_exts())) {
      $is_video   = true;
      $view_title = 'Video';
    } elseif (in_array($ext, fm_get_text_exts()) || substr($mime_type, 0, 4) == 'text' || in_array($mime_type, fm_get_text_mimes())) {
      if ((isset($_POST['action'])) && (isset($_POST['ace_code']))) {
        if ($_POST['action'] == 'save') {
          if (file_put_contents($file_path, $_POST['ace_code']) === false) {
            echo '<p class="message error">'.sprintf('File <b>%s</b> not saved', fm_enc($file)).'</p>';
          } else {
            echo '<p class="message ok">'.sprintf('File <b>%s</b> saved', fm_enc($file)).'</p>';
          }
        }
      }
      $is_text = true;
      $content = file_get_contents($file_path);
    }
    
?>
   <div class="path">
        <p class="break-word"><b><?php
    echo $view_title;
?> "<?php
    echo fm_enc(fm_convert_win($file));
?>"</b></p>
        <p class="break-word">
            Full path: <?php
    echo fm_enc(fm_convert_win($file_path));
?><br>
            File size: <?php
    echo fm_get_filesize($filesize);
?><?php
    if ($filesize >= 1000):
?> (<?php
      echo sprintf('%s bytes', $filesize);
?>)<?php
    endif;
?><br>
            MIME-type: <?php
    echo $mime_type;
?><br>
            <?php
    // ZIP info
    if ($is_zip && $filenames !== false) {
      $total_files  = 0;
      $total_comp   = 0;
      $total_uncomp = 0;
      foreach ($filenames as $fn) {
        if (!$fn['folder']) {
          $total_files++;
        }
        $total_comp += $fn['compressed_size'];
        $total_uncomp += $fn['filesize'];
      }
?>
               Files in archive: <?php
      echo $total_files;
?><br>
                Total size: <?php
      echo fm_get_filesize($total_uncomp);
?><br>
                Size in archive: <?php
      echo fm_get_filesize($total_comp);
?><br>
                Compression: <?php
      echo round(($total_comp / $total_uncomp) * 100);
?>%<br>
                <?php
    }
    // Image info
    if ($is_image) {
      $image_size = getimagesize($file_path);
      echo 'Image sizes: ' . (isset($image_size[0]) ? $image_size[0] : '0') . ' x ' . (isset($image_size[1]) ? $image_size[1] : '0') . '<br>';
    }
    // Text info
    if ($is_text) {
      $is_utf8 = fm_is_utf8($content);
      if (function_exists('iconv')) {
        if (!$is_utf8) {
          $content = iconv(FM_ICONV_INPUT_ENC, 'UTF-8//IGNORE', $content);
        }
      }
      echo 'Charset: ' . ($is_utf8 ? 'utf-8' : '8 bit') . '<br>';
    }
?>
       </p>
        <p>
            <b><a href="?p=<?php
    echo urlencode(FM_PATH);
?>&amp;dl=<?php
    echo urlencode($file);
?>"><i class="icon-download"></i> Download</a></b> &nbsp;
            <b><a href="<?php
    echo fm_enc($file_url);
?>" target="_blank"><i class="icon-chain"></i> Open</a></b> &nbsp;
            <?php
    // ZIP actions
    if ($is_zip && $filenames !== false) {
      $zip_name = pathinfo($file_path, PATHINFO_FILENAME);
?>
               <b><a href="?p=<?php
      echo urlencode(FM_PATH);
?>&amp;unzip=<?php
      echo urlencode($file);
?>"><i class="icon-apply"></i> Unpack</a></b> &nbsp;
                <b><a href="?p=<?php
      echo urlencode(FM_PATH);
?>&amp;unzip=<?php
      echo urlencode($file);
?>&amp;tofolder=1" title="Unpack to <?php
      echo fm_enc($zip_name);
?>"><i class="icon-apply"></i>
                    Unpack to folder</a></b> &nbsp;
                <?php
    }
?>
           <b><a href="?p=<?php
    echo urlencode(FM_PATH);
?>"><i class="icon-goback"></i> Back</a></b>
        </p>
        <?php
    if ($is_zip) {
      // ZIP content
      if ($filenames !== false) {
        echo '<code class="maxheight">';
        foreach ($filenames as $fn) {
          if ($fn['folder']) {
            echo '<b>' . fm_enc($fn['name']) . '</b><br>';
          } else {
            echo $fn['name'] . ' (' . fm_get_filesize($fn['filesize']) . ')<br>';
          }
        }
        echo '</code>';
      } else {
        echo '<p>Error while fetching archive info</p>';
      }
    } elseif ($is_image) {
      // Image content
      if (in_array($ext, array(
        'gif',
        'jpg',
        'jpeg',
        'png',
        'bmp',
        'ico'
      ))) {
        echo '<p><img src="' . fm_enc($file_url) . '" alt="" class="preview-img"></p>';
      }
    } elseif ($is_audio) {
      // Audio content
      echo '<p><audio src="' . fm_enc($file_url) . '" controls preload="metadata"></audio></p>';
    } elseif ($is_video) {
      // Video content
      echo '<div class="preview-video"><video src="' . fm_enc($file_url) . '" width="640" height="360" controls preload="metadata"></video></div>';
    } elseif ($is_text) {
      if (file_exists(__DIR__.'/codeeditor.php')) {
        require_once(__DIR__.'/codeeditor.php');
        showEditor($content, $file, $ext);
      } else {
        if (in_array($ext, array(
          'php',
          'php4',
          'php5',
          'phtml',
          'phps'
        ))) {
          $content = highlight_string($content, true);
        } else {
          $content = '<pre>' . fm_enc($content) . '</pre>';
        }
        echo $content;
      }
    }
?>
   </div>
    <?php
    fm_show_footer();
    return;
  }
  
  // chmod (not for Windows)
  if (isset($_GET['chmod']) && !FM_IS_WIN) {
    $file = $_GET['chmod'];
    $file = fm_clean_path($file);
    $file = str_replace('/', '', $file);
    if ($file == '' || (!is_file($path . '/' . $file) && !is_dir($path . '/' . $file))) {
      fm_set_msg('File not found', 'error');
      fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
    }
    
    fm_show_header(); // HEADER
    fm_show_nav_path(FM_PATH); // current path
    
    $file_url  = FM_ROOT_URL . (FM_PATH != '' ? '/' . FM_PATH : '') . '/' . $file;
    $file_path = $path . '/' . $file;
    
    $mode = fileperms($path . '/' . $file);
    
?>
   <div class="path">
        <p><b>Change Permissions</b></p>
        <p>
            Full path: <?php
    echo fm_enc($file_path);
?><br>
        </p>
        <form action="" method="post">
            <input type="hidden" name="p" value="<?php
    echo fm_enc(FM_PATH);
?>">
            <input type="hidden" name="chmod" value="<?php
    echo fm_enc($file);
?>">

            <table class="compact-table">
                <tr>
                    <td></td>
                    <td><b>Owner</b></td>
                    <td><b>Group</b></td>
                    <td><b>Other</b></td>
                </tr>
                <tr>
                    <td style="text-align: right"><b>Read</b></td>
                    <td><label><input type="checkbox" name="ur" value="1"<?php
    echo ($mode & 00400) ? ' checked' : '';
?>></label></td>
                    <td><label><input type="checkbox" name="gr" value="1"<?php
    echo ($mode & 00040) ? ' checked' : '';
?>></label></td>
                    <td><label><input type="checkbox" name="or" value="1"<?php
    echo ($mode & 00004) ? ' checked' : '';
?>></label></td>
                </tr>
                <tr>
                    <td style="text-align: right"><b>Write</b></td>
                    <td><label><input type="checkbox" name="uw" value="1"<?php
    echo ($mode & 00200) ? ' checked' : '';
?>></label></td>
                    <td><label><input type="checkbox" name="gw" value="1"<?php
    echo ($mode & 00020) ? ' checked' : '';
?>></label></td>
                    <td><label><input type="checkbox" name="ow" value="1"<?php
    echo ($mode & 00002) ? ' checked' : '';
?>></label></td>
                </tr>
                <tr>
                    <td style="text-align: right"><b>Execute</b></td>
                    <td><label><input type="checkbox" name="ux" value="1"<?php
    echo ($mode & 00100) ? ' checked' : '';
?>></label></td>
                    <td><label><input type="checkbox" name="gx" value="1"<?php
    echo ($mode & 00010) ? ' checked' : '';
?>></label></td>
                    <td><label><input type="checkbox" name="ox" value="1"<?php
    echo ($mode & 00001) ? ' checked' : '';
?>></label></td>
                </tr>
            </table>

            <p>
                <button class="btn"><i class="icon-apply"></i> Change</button> &nbsp;
                <b><a href="?p=<?php
    echo urlencode(FM_PATH);
?>"><i class="icon-cancel"></i> Cancel</a></b>
            </p>

        </form>

    </div>
    <?php
    fm_show_footer();
    return;
  }
  
  //--- FILEMANAGER MAIN
  fm_show_header(); // HEADER
  fm_show_nav_path(FM_PATH); // current path
  
  // messages
  fm_show_message();
  
  $num_files      = count($files);
  $num_folders    = count($folders);
  $all_files_size = 0;
?>
<form action="" method="post">
<input type="hidden" name="p" value="<?php
  echo fm_enc(FM_PATH);
?>">
<input type="hidden" name="group" value="1">
<div class="dragscroll">
<table><tr>
<th style="width:3%"><label><input type="checkbox" title="Invert selection" onclick="checkbox_toggle()"></label></th>
<th>Name</th><th style="width:10%">Size</th>
<th style="width:12%">Modified</th>
<?php
  if (!FM_IS_WIN):
?><th style="width:6%">Perms</th><th style="width:10%">Owner</th><?php
  endif;
?>
<th style="width:13%"></th></tr>
<?php
  // link to parent folder
  if ($parent !== false) {
?>
<tr><td></td><td colspan="<?php
    echo !FM_IS_WIN ? '6' : '4';
?>"><a href="?p=<?php
    echo urlencode($parent);
?>"><i class="icon-arrow_up"></i> ..</a></td></tr>
<?php
  }
  $datetime = new \DateTime();
  foreach ($folders as $f) {
    $is_link = is_link($path . '/' . $f);
    $img     = $is_link ? 'icon-link_folder' : 'icon-folder';
    $datetime->setTimezone(new \DateTimeZone(FM_DATETIME_ZONE));
    $datetime->setTimestamp(filemtime($path . '/' . $f));
    $modif = $datetime->format(FM_DATETIME_FORMAT);
    //$modif   = date(FM_DATETIME_FORMAT, filemtime($path . '/' . $f));
    $perms   = substr(decoct(fileperms($path . '/' . $f)), -4);
    if (function_exists('posix_getpwuid') && function_exists('posix_getgrgid')) {
      $owner = posix_getpwuid(fileowner($path . '/' . $f));
      $group = posix_getgrgid(filegroup($path . '/' . $f));
    } else {
      $owner = array(
        'name' => '?'
      );
      $group = array(
        'name' => '?'
      );
    }
?>
<tr>
<td><label><input type="checkbox" name="file[]" value="<?php
    echo fm_enc($f);
?>"></label></td>
<td><div class="filename"><a href="?p=<?php
    echo urlencode(trim(FM_PATH . '/' . $f, '/'));
?>"><i class="<?php
    echo $img;
?>"></i> <?php
    echo fm_enc(fm_convert_win($f));
?></a><?php
    echo ($is_link ? ' &rarr; <i>' . fm_enc(readlink($path . '/' . $f)) . '</i>' : '');
?></div></td>
<td>Folder</td><td><?php
    echo $modif;
?></td>
<?php
    if (!FM_IS_WIN):
?>
<td><a title="Change Permissions" href="?p=<?php
      echo urlencode(FM_PATH);
?>&amp;chmod=<?php
      echo urlencode($f);
?>"><?php
      echo $perms;
?></a></td>
<td><?php
      echo fm_enc($owner['name'] . ':' . $group['name']);
?></td>
<?php
    endif;
?>
<td>
<a title="Delete" href="?p=<?php
    echo urlencode(FM_PATH);
?>&amp;del=<?php
    echo urlencode($f);
?>" onclick="return confirm('Delete folder?');"><i class="icon-cross"></i></a>
<a title="Rename" href="#" onclick="rename('<?php
    echo fm_enc(FM_PATH);
?>', '<?php
    echo fm_enc($f);
?>');return false;"><i class="icon-rename"></i></a>
<a title="Copy to..." href="?p=&amp;copy=<?php
    echo urlencode(trim(FM_PATH . '/' . $f, '/'));
?>"><i class="icon-copy"></i></a>
<a title="Direct link" href="<?php
    echo fm_enc(FM_ROOT_URL . (FM_PATH != '' ? '/' . FM_PATH : '') . '/' . $f . '/');
?>" target="_blank"><i class="icon-chain"></i></a>
</td></tr>
    <?php
    flush();
  }
  
  $datetime = new \DateTime();
  foreach ($files as $f) {
    $is_link      = is_link($path . '/' . $f);
    $img          = $is_link ? 'icon-link_file' : fm_get_file_icon_class($path . '/' . $f);
    $datetime->setTimezone(new \DateTimeZone(FM_DATETIME_ZONE));
    $datetime->setTimestamp(filemtime($path . '/' . $f));
    $modif = $datetime->format(FM_DATETIME_FORMAT);
    //$modif        = date(FM_DATETIME_FORMAT, filemtime($path . '/' . $f));
    $filesize_raw = filesize($path . '/' . $f);
    $filesize     = fm_get_filesize($filesize_raw);
    $filelink     = '?p=' . urlencode(FM_PATH) . '&view=' . urlencode($f);
    $all_files_size += $filesize_raw;
    $perms = substr(decoct(fileperms($path . '/' . $f)), -4);
    if (function_exists('posix_getpwuid') && function_exists('posix_getgrgid')) {
      $owner = posix_getpwuid(fileowner($path . '/' . $f));
      $group = posix_getgrgid(filegroup($path . '/' . $f));
    } else {
      $owner = array(
        'name' => '?'
      );
      $group = array(
        'name' => '?'
      );
    }
?>
<tr>
<td><label><input type="checkbox" name="file[]" value="<?php echo fm_enc($f);?>"></label></td>
<td><div class="filename"><a href="<?php
    echo fm_enc($filelink);
?>" title="File info"><i class="<?php
    echo $img;
?>"></i> <?php
    echo fm_enc(fm_convert_win($f));
?></a><?php
    echo ($is_link ? ' &rarr; <i>' . fm_enc(readlink($path . '/' . $f)) . '</i>' : '');
?></div></td>
<td><span class="gray" title="<?php
    printf('%s bytes', $filesize_raw);
?>"><?php
    echo $filesize;
?></span></td>
<td><?php
    echo $modif;
?></td>
<?php
    if (!FM_IS_WIN):
?>
<td><a title="Change Permissions" href="?p=<?php
      echo urlencode(FM_PATH);
?>&amp;chmod=<?php
      echo urlencode($f);
?>"><?php
      echo $perms;
?></a></td>
<td><?php
      echo fm_enc($owner['name'] . ':' . $group['name']);
?></td>
<?php
    endif;
?>
<td>
<a title="Delete" href="?p=<?php
    echo urlencode(FM_PATH);
?>&amp;del=<?php
    echo urlencode($f);
?>" onclick="return confirm('Delete file?');"><i class="icon-cross"></i></a>
<a title="Rename" href="#" onclick="rename('<?php
    echo fm_enc(FM_PATH);
?>', '<?php
    echo fm_enc($f);
?>');return false;"><i class="icon-rename"></i></a>
<a title="Copy to..." href="?p=<?php
    echo urlencode(FM_PATH);
?>&amp;copy=<?php
    echo urlencode(trim(FM_PATH . '/' . $f, '/'));
?>"><i class="icon-copy"></i></a>
<a title="Direct link" href="<?php
    echo fm_enc(FM_ROOT_URL . (FM_PATH != '' ? '/' . FM_PATH : '') . '/' . $f);
?>" target="_blank"><i class="icon-chain"></i></a>
<a title="Download" href="?p=<?php
    echo urlencode(FM_PATH);
?>&amp;dl=<?php
    echo urlencode($f);
?>"><i class="icon-download"></i></a>
</td></tr>
    <?php
    flush();
  }
  
  if (empty($folders) && empty($files)) {
?>
<tr><td></td><td colspan="<?php
    echo !FM_IS_WIN ? '6' : '4';
?>"><em>Folder is empty</em></td></tr>
<?php
  } else {
?>
<tr><td class="gray"></td><td class="gray" colspan="<?php
    echo !FM_IS_WIN ? '6' : '4';
?>">
Full size: <span title="<?php
    printf('%s bytes', $all_files_size);
?>"><?php
    echo fm_get_filesize($all_files_size);
?></span>,
files: <?php
    echo $num_files;
?>,
folders: <?php
    echo $num_folders;
?>
</td></tr>
<?php
  }
?>
</table>
</div>
<p class="path"><a href="#" onclick="select_all();return false;"><i class="icon-checkbox"></i> Select all</a> &nbsp;
<a href="#" onclick="unselect_all();return false;"><i class="icon-checkbox_uncheck"></i> Unselect all</a> &nbsp;
<a href="#" onclick="invert_all();return false;"><i class="icon-checkbox_invert"></i> Invert selection</a></p>
<p><input type="submit" name="delete" value="Delete" class="ui-button ui-state-default" onclick="return confirm('Delete selected files and folders?');">
<input type="submit" name="zip" value="Pack" class="ui-button ui-state-default" onclick="return confirm('Create archive?');">
<input type="submit" name="copy" value="Copy" class="ui-button ui-state-default"></p>
</form>

<?php
  fm_show_footer();
  
  //--- END
  
  // Functions
  
  /**
   * Delete  file or folder (recursively)
   * @param string $path
   * @return bool
   */
  function fm_rdelete($path) {
    if (is_link($path)) {
      return unlink($path);
    } elseif (is_dir($path)) {
      $objects = scandir($path);
      $ok      = true;
      if (is_array($objects)) {
        foreach ($objects as $file) {
          if ($file != '.' && $file != '..') {
            if (!fm_rdelete($path . '/' . $file)) {
              $ok = false;
            }
          }
        }
      }
      return ($ok) ? rmdir($path) : false;
    } elseif (is_file($path)) {
      return unlink($path);
    }
    return false;
  }
  
  /**
   * Recursive chmod
   * @param string $path
   * @param int $filemode
   * @param int $dirmode
   * @return bool
   * @todo Will use in mass chmod
   */
  function fm_rchmod($path, $filemode, $dirmode) {
    if (is_dir($path)) {
      if (!chmod($path, $dirmode)) {
        return false;
      }
      $objects = scandir($path);
      if (is_array($objects)) {
        foreach ($objects as $file) {
          if ($file != '.' && $file != '..') {
            if (!fm_rchmod($path . '/' . $file, $filemode, $dirmode)) {
              return false;
            }
          }
        }
      }
      return true;
    } elseif (is_link($path)) {
      return true;
    } elseif (is_file($path)) {
      return chmod($path, $filemode);
    }
    return false;
  }
  
  /**
   * Safely rename
   * @param string $old
   * @param string $new
   * @return bool|null
   */
  function fm_rename($old, $new) {
    return (!file_exists($new) && file_exists($old)) ? rename($old, $new) : null;
  }
  
  /**
   * Copy file or folder (recursively).
   * @param string $path
   * @param string $dest
   * @param bool $upd Update files
   * @param bool $force Create folder with same names instead file
   * @return bool
   */
  function fm_rcopy($path, $dest, $upd = true, $force = true) {
    if (is_dir($path)) {
      if (!fm_mkdir($dest, $force)) {
        return false;
      }
      $objects = scandir($path);
      $ok      = true;
      if (is_array($objects)) {
        foreach ($objects as $file) {
          if ($file != '.' && $file != '..') {
            if (!fm_rcopy($path . '/' . $file, $dest . '/' . $file)) {
              $ok = false;
            }
          }
        }
      }
      return $ok;
    } elseif (is_file($path)) {
      return fm_copy($path, $dest, $upd);
    }
    return false;
  }
  
  /**
   * Safely create folder
   * @param string $dir
   * @param bool $force
   * @return bool
   */
  function fm_mkdir($dir, $force) {
    if (file_exists($dir)) {
      if (is_dir($dir)) {
        return $dir;
      } elseif (!$force) {
        return false;
      }
      unlink($dir);
    }
    return mkdir($dir, 0777, true);
  }
  
  /**
   * Safely copy file
   * @param string $f1
   * @param string $f2
   * @param bool $upd
   * @return bool
   */
  function fm_copy($f1, $f2, $upd) {
    $time1 = filemtime($f1);
    if (file_exists($f2)) {
      $time2 = filemtime($f2);
      if ($time2 >= $time1 && $upd) {
        return false;
      }
    }
    $ok = copy($f1, $f2);
    if ($ok) {
      touch($f2, $time1);
    }
    return $ok;
  }
  
  /**
   * Get mime type
   * @param string $file_path
   * @return mixed|string
   */
  function fm_get_mime_type($file_path) {
    if (function_exists('finfo_open')) {
      $finfo = finfo_open(FILEINFO_MIME_TYPE);
      $mime  = finfo_file($finfo, $file_path);
      finfo_close($finfo);
      return $mime;
    } elseif (function_exists('mime_content_type')) {
      return mime_content_type($file_path);
    } elseif (!stristr(ini_get('disable_functions'), 'shell_exec')) {
      $file = escapeshellarg($file_path);
      $mime = shell_exec('file -bi ' . $file);
      return $mime;
    } else {
      return '--';
    }
  }
  
  /**
   * HTTP Redirect
   * @param string $url
   * @param int $code
   */
  function fm_redirect($url, $code = 302) {
    header('Location: ' . $url, true, $code);
    exit;
  }
  
  /**
   * Clean path
   * @param string $path
   * @return string
   */
  function fm_clean_path($path) {
    $path = trim($path);
    $path = trim($path, '\\/');
    $path = str_replace(array(
      '../',
      '..\\'
    ), '', $path);
    if ($path == '..') {
      $path = '';
    }
    return str_replace('\\', '/', $path);
  }
  
  /**
   * Get parent path
   * @param string $path
   * @return bool|string
   */
  function fm_get_parent_path($path) {
    $path = fm_clean_path($path);
    if ($path != '') {
      $array = explode('/', $path);
      if (count($array) > 1) {
        $array = array_slice($array, 0, -1);
        return implode('/', $array);
      }
      return '';
    }
    return false;
  }
  
  /**
   * Get nice filesize
   * @param int $size
   * @return string
   */
  function fm_get_filesize($size) {
    if ($size < 1000) {
      return sprintf('%s B', $size);
    } elseif (($size / 1024) < 1000) {
      return sprintf('%s KiB', round(($size / 1024), 2));
    } elseif (($size / 1024 / 1024) < 1000) {
      return sprintf('%s MiB', round(($size / 1024 / 1024), 2));
    } elseif (($size / 1024 / 1024 / 1024) < 1000) {
      return sprintf('%s GiB', round(($size / 1024 / 1024 / 1024), 2));
    } else {
      return sprintf('%s TiB', round(($size / 1024 / 1024 / 1024 / 1024), 2));
    }
  }
  
  /**
   * Get info about zip archive
   * @param string $path
   * @return array|bool
   */
  function fm_get_zif_info($path) {
    $arch = new ZipArchive();
    $arch->open($path, ZipArchive::RDONLY);
    if ($arch) {
      $filenames = array();
      for ($i=0; $i<$arch->numFiles; $i++) {
        $zip_name    = $arch->statIndex($i)['name'];
        $zip_folder  = substr($zip_name, -1) == '/';
        $filenames[] = array(
          'name' => $zip_name,
          'filesize' => $arch->statIndex($i)['size'],
          'compressed_size' => $arch->statIndex($i)['comp_size'],
          'folder' => $zip_folder
        );
      }
      $arch->close();
      return $filenames;
    }
    return false;
  }
  
  /**
   * Encode html entities
   * @param string $text
   * @return string
   */
  function fm_enc($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
  }
  
  /**
   * Save message in session
   * @param string $msg
   * @param string $status
   */
  function fm_set_msg($msg, $status = 'ok') {
    $_SESSION['message'] = $msg;
    $_SESSION['status']  = $status;
  }
  
  /**
   * Check if string is in UTF-8
   * @param string $string
   * @return int
   */
  function fm_is_utf8($string) {
    return preg_match('//u', $string);
  }
  
  /**
   * Convert file name to UTF-8 in Windows
   * @param string $filename
   * @return string
   */
  function fm_convert_win($filename) {
    if (FM_IS_WIN && function_exists('iconv')) {
      $filename = iconv(FM_ICONV_INPUT_ENC, 'UTF-8//IGNORE', $filename);
    }
    return $filename;
  }
  
  /**
   * Get CSS classname for file
   * @param string $path
   * @return string
   */
  function fm_get_file_icon_class($path) {
    // get extension
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    
    switch ($ext) {
      case 'ico':
      case 'gif':
      case 'jpg':
      case 'jpeg':
      case 'jpc':
      case 'jp2':
      case 'jpx':
      case 'xbm':
      case 'wbmp':
      case 'png':
      case 'bmp':
      case 'tif':
      case 'tiff':
        $img = 'icon-file_image';
        break;
      case 'txt':
      case 'css':
      case 'ini':
      case 'conf':
      case 'log':
      case 'htaccess':
      case 'passwd':
      case 'ftpquota':
      case 'sql':
      case 'js':
      case 'json':
      case 'sh':
      case 'config':
      case 'twig':
      case 'tpl':
      case 'md':
      case 'gitignore':
      case 'less':
      case 'sass':
      case 'scss':
      case 'c':
      case 'cpp':
      case 'cs':
      case 'py':
      case 'map':
      case 'lock':
      case 'dtd':
        $img = 'icon-file_text';
        break;
      case 'zip':
      case 'rar':
      case 'gz':
      case 'tar':
      case '7z':
        $img = 'icon-file_zip';
        break;
      case 'php':
      case 'php4':
      case 'php5':
      case 'phps':
      case 'phtml':
        $img = 'icon-file_php';
        break;
      case 'htm':
      case 'html':
      case 'shtml':
      case 'xhtml':
        $img = 'icon-file_html';
        break;
      case 'xml':
      case 'xsl':
      case 'svg':
        $img = 'icon-file_code';
        break;
      case 'wav':
      case 'mp3':
      case 'mp2':
      case 'm4a':
      case 'aac':
      case 'ogg':
      case 'oga':
      case 'wma':
      case 'mka':
      case 'flac':
      case 'ac3':
      case 'tds':
        $img = 'icon-file_music';
        break;
      case 'm3u':
      case 'm3u8':
      case 'pls':
      case 'cue':
        $img = 'icon-file_playlist';
        break;
      case 'avi':
      case 'mpg':
      case 'mpeg':
      case 'mp4':
      case 'm4v':
      case 'flv':
      case 'f4v':
      case 'ogm':
      case 'ogv':
      case 'mov':
      case 'mkv':
      case '3gp':
      case 'asf':
      case 'wmv':
        $img = 'icon-file_film';
        break;
      case 'eml':
      case 'msg':
        $img = 'icon-file_outlook';
        break;
      case 'xls':
      case 'xlsx':
        $img = 'icon-file_excel';
        break;
      case 'csv':
        $img = 'icon-file_csv';
        break;
      case 'doc':
      case 'docx':
        $img = 'icon-file_word';
        break;
      case 'ppt':
      case 'pptx':
        $img = 'icon-file_powerpoint';
        break;
      case 'ttf':
      case 'ttc':
      case 'otf':
      case 'woff':
      case 'woff2':
      case 'eot':
      case 'fon':
        $img = 'icon-file_font';
        break;
      case 'pdf':
        $img = 'icon-file_pdf';
        break;
      case 'psd':
        $img = 'icon-file_photoshop';
        break;
      case 'ai':
      case 'eps':
        $img = 'icon-file_illustrator';
        break;
      case 'fla':
        $img = 'icon-file_flash';
        break;
      case 'swf':
        $img = 'icon-file_swf';
        break;
      case 'exe':
      case 'msi':
        $img = 'icon-file_application';
        break;
      case 'bat':
        $img = 'icon-file_terminal';
        break;
      default:
        $img = 'icon-document';
    }
    
    return $img;
  }
  
  /**
   * Get image files extensions
   * @return array
   */
  function fm_get_image_exts() {
    return array(
      'ico',
      'gif',
      'jpg',
      'jpeg',
      'jpc',
      'jp2',
      'jpx',
      'xbm',
      'wbmp',
      'png',
      'bmp',
      'tif',
      'tiff',
      'psd'
    );
  }
  
  /**
   * Get video files extensions
   * @return array
   */
  function fm_get_video_exts() {
    return array(
      'webm',
      'mp4',
      'm4v',
      'ogm',
      'ogv',
      'mov'
    );
  }
  
  /**
   * Get audio files extensions
   * @return array
   */
  function fm_get_audio_exts() {
    return array(
      'wav',
      'mp3',
      'ogg',
      'm4a'
    );
  }
  
  /**
   * Get text file extensions
   * @return array
   */
  function fm_get_text_exts() {
    return array(
      'txt',
      'css',
      'ini',
      'conf',
      'log',
      'htaccess',
      'passwd',
      'ftpquota',
      'sql',
      'js',
      'json',
      'sh',
      'config',
      'php',
      'php4',
      'php5',
      'phps',
      'phtml',
      'htm',
      'html',
      'shtml',
      'xhtml',
      'xml',
      'xsl',
      'm3u',
      'm3u8',
      'pls',
      'cue',
      'eml',
      'msg',
      'csv',
      'bat',
      'twig',
      'tpl',
      'md',
      'gitignore',
      'less',
      'sass',
      'scss',
      'c',
      'cpp',
      'cs',
      'py',
      'map',
      'lock',
      'dtd',
      'svg'
    );
  }
  
  /**
   * Get mime types of text files
   * @return array
   */
  function fm_get_text_mimes() {
    return array(
      'application/xml',
      'application/javascript',
      'application/x-javascript',
      'image/svg+xml',
      'message/rfc822'
    );
  }
  
  /**
   * Get file names of text files w/o extensions
   * @return array
   */
  function fm_get_text_names() {
    return array(
      'license',
      'readme',
      'authors',
      'contributors',
      'changelog'
    );
  }
  
  /**
   * Class to work with zip files (using ZipArchive)
   */
  class FM_Zipper {
    private $zip;
    
    public function __construct() {
      $this->zip = new ZipArchive();
    }
    
    /**
     * Create archive with name $filename and files $files (RELATIVE PATHS!)
     * @param string $filename
     * @param array|string $files
     * @return bool
     */
    public function create($filename, $files) {
      $res = $this->zip->open($filename, ZipArchive::CREATE);
      if ($res !== true) {
        return false;
      }
      if (is_array($files)) {
        foreach ($files as $f) {
          if (!$this->addFileOrDir($f)) {
            $this->zip->close();
            return false;
          }
        }
        $this->zip->close();
        return true;
      } else {
        if ($this->addFileOrDir($files)) {
          $this->zip->close();
          return true;
        }
        return false;
      }
    }
    
    /**
     * Extract archive $filename to folder $path (RELATIVE OR ABSOLUTE PATHS)
     * @param string $filename
     * @param string $path
     * @return bool
     */
    public function unzip($filename, $path) {
      $res = $this->zip->open($filename);
      if ($res !== true) {
        return false;
      }
      if ($this->zip->extractTo($path)) {
        $this->zip->close();
        return true;
      }
      return false;
    }
    
    /**
     * Add file/folder to archive
     * @param string $filename
     * @return bool
     */
    private function addFileOrDir($filename) {
      if (is_file($filename)) {
        return $this->zip->addFile($filename);
      } elseif (is_dir($filename)) {
        return $this->addDir($filename);
      }
      return false;
    }
    
    /**
     * Add folder recursively
     * @param string $path
     * @return bool
     */
    private function addDir($path) {
      if (!$this->zip->addEmptyDir($path)) {
        return false;
      }
      $objects = scandir($path);
      if (is_array($objects)) {
        foreach ($objects as $file) {
          if ($file != '.' && $file != '..') {
            if (is_dir($path . '/' . $file)) {
              if (!$this->addDir($path . '/' . $file)) {
                return false;
              }
            } elseif (is_file($path . '/' . $file)) {
              if (!$this->zip->addFile($path . '/' . $file)) {
                return false;
              }
            }
          }
        }
        return true;
      }
      return false;
    }
  }
  
  //--- templates functions
  
  /**
   * Show nav block
   * @param string $path
   */
  function fm_show_nav_path($path) {
?>
<div class="path">
<div class="float-right">
<a title="Upload files" href="?p=<?php echo urlencode(FM_PATH);?>&amp;upload"><i class="icon-upload"></i></a>
<a title="New folder" href="#" onclick="newfolder('<?php echo fm_enc(FM_PATH);?>');return false;"><i class="icon-folder_add"></i></a>
<a title="New file" href="#" onclick="newfile('<?php echo fm_enc(FM_PATH);?>');return false;"><i class="icon-document"></i></a>
</div>
        <?php
    $path     = fm_clean_path($path);
    $root_url = "<a href='?p='><i class='icon-home' title='" . FM_ROOT_PATH . "'></i></a>";
    $sep      = '<i class="icon-separator"></i>';
    if ($path != '') {
      $exploded = explode('/', $path);
      $count    = count($exploded);
      $array    = array();
      $parent   = '';
      for ($i = 0; $i < $count; $i++) {
        $parent     = trim($parent . '/' . $exploded[$i], '/');
        $parent_enc = urlencode($parent);
        $array[]    = "<a href='?p={$parent_enc}'>" . fm_enc(fm_convert_win($exploded[$i])) . "</a>";
      }
      $root_url .= $sep . implode($sep, $array);
    }
    echo '<div class="break-word">' . $root_url . '</div>';
?>
</div>
<?php
  }
  
  /**
   * Show message from session
   */
  function fm_show_message() {
    if (isset($_SESSION['message'])) {
      $class = isset($_SESSION['status']) ? $_SESSION['status'] : 'ok';
      echo '<p class="message ' . $class . '">' . $_SESSION['message'] . '</p>';
      unset($_SESSION['message']);
      unset($_SESSION['status']);
    }
  }
  
  /**
   * Show page header
   */
  function fm_show_header() {
?>
<div id="wrapper">
<?php
  }
  
  /**
   * Show page footer
   */
  function fm_show_footer() {
?>
</div>
<script>
function newfolder(p){var n=prompt('New folder name','folder');if(n!==null&&n!==''){window.location.search='p='+encodeURIComponent(p)+'&newfolder='+encodeURIComponent(n);}}
function newfile(p){var n=prompt('New file name','file');if(n!==null&&n!==''){window.location.search='p='+encodeURIComponent(p)+'&newfile='+encodeURIComponent(n);}}
function rename(p,f){var n=prompt('New name',f);if(n!==null&&n!==''&&n!=f){window.location.search='p='+encodeURIComponent(p)+'&ren='+encodeURIComponent(f)+'&to='+encodeURIComponent(n);}}
function change_checkboxes(l,v){for(var i=l.length-1;i>=0;i--){l[i].checked=(typeof v==='boolean')?v:!l[i].checked;}}
function get_checkboxes(){var i=document.getElementsByName('file[]'),a=[];for(var j=i.length-1;j>=0;j--){if(i[j].type='checkbox'){a.push(i[j]);}}return a;}
function select_all(){var l=get_checkboxes();change_checkboxes(l,true);}
function unselect_all(){var l=get_checkboxes();change_checkboxes(l,false);}
function invert_all(){var l=get_checkboxes();change_checkboxes(l);}
function checkbox_toggle(){var l=get_checkboxes();l.push(this);change_checkboxes(l);}
</script>
<?php
  }