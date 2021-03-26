function initCodeAce() {
  var textarea = $("#ace_code"); 
  if (textarea.size() < 1) return;

  var acecodediv = $('<div id="ace_code_div"></div>').css('height', textarea.attr('data-height') + 'px'); 
  textarea.after(acecodediv); 
  var editor=ace.edit('ace_code_div');

  textarea.hide();

  editor.getSession().setValue(textarea.val());
  editor.getSession().on('change', function() { 
    textarea.val(editor.getSession().getValue())
  });

  var theme = textarea.attr('data-theme');
  if (theme) editor.setTheme('ace/theme/' + theme); 

  var keybinding = textarea.attr('data-keybinding'); 
  if (keybinding && keybinding != 'none') editor.setKeyboardHandler('ace/keyboard/' + keybinding);

  var behaviorsenabled = textarea.attr('data-behaviors-enabled');
  if (behaviorsenabled == 'on') editor.setBehavioursEnabled(true);

  var wrapbehaviorsenabled = textarea.attr('data-wrap-behaviors-enabled');
  if (wrapbehaviorsenabled == 'on') editor.setWrapBehavioursEnabled(true);

  $('#ace_type').change(function() {
    var val = $(this).val();
    if (val == 'php') {
      var editorValue = editor.getSession().getValue();
      if (editorValue.length < 1) editor.getSession().setValue("<?php\n\n");
    }
    editor.getSession().setMode('ace/mode/' + val);
  }).change();
}

$(document).ready(function() {
  setTimeout('initCodeAce()', 250);
});