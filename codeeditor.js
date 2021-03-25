function initCodeAce() {
  var hcCode = $("#ace_code"); 
  if (hcCode.size() < 1) return;

  var hcCodeDiv = $('<div id="ace_code_div"></div>').css('height', hcCode.attr('data-height') + 'px'); 
  hcCode.after(hcCodeDiv); 
  var editor=ace.edit('ace_code_div');

  hcCode.hide();

  editor.getSession().setValue(hcCode.val());
  editor.getSession().on('change', function() { 
    hcCode.val(editor.getSession().getValue())
  });

  var hcTheme = hcCode.attr('data-theme');
  var _hcTheme = editor.getTheme();
  if (hcTheme) editor.setTheme('ace/theme/' + hcTheme); 

  var hcKeybinding = hcCode.attr('data-keybinding'); 
  var _hcKeybinding = editor.getKeyboardHandler();
  if (hcKeybinding && hcKeybinding != 'none') editor.setKeyboardHandler('ace/keyboard/' + hcKeybinding);

  var hcBehaviors = parseInt(hcCode.attr('data-behaviors')); 
  editor.setBehavioursEnabled(hcBehaviors & 2); 
  editor.setWrapBehavioursEnabled(hcBehaviors & 4);
  
  $('#ace_type').change(function() {
    var val = $(this).val();
    if (val == 'php') {
      var editorValue = editor.getSession().getValue();
      if (editorValue.length < 1) editor.getSession().setValue("<?php\n\n");
    }
    editor.getSession().setMode("ace/mode/" + val);
  }).change();
}

$(document).ready(function() {
  setTimeout('initCodeAce()', 250);
});