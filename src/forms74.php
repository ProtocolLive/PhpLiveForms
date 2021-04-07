<?php
// Protocol Corporation Ltda.
// https://github.com/ProtocolLive/PhpLive/
// Version 2020.11.27.01

class PhpLiveForms{
  private PhpLivePdo $PhpLivePdo;

  public function __construct(PhpLivePdo &$PhpLivePdo){
    $this->PhpLivePdo = $PhpLivePdo;
  }

  private function PhpError(int $Type):bool{
    return (ini_get('error_reporting') & $Type) === $Type;
  }

  private function Error(string $Msg):void{
    if(ini_get('display_errors') === '1' and ($this->PhpError(E_ALL) or $this->PhpError(E_WARNING))):
      $debug = debug_backtrace();
      print 'PhpLiveForms warning: '. $Msg;
      print ' in <b>' . $debug[1]['file'] . '</b>';
      print ' line <b>' . $debug[1]['line'] . '</b><br>';
    endif;
  }

  public function Form(array $Options):bool{
    if($this->PhpLivePdo === null):
      return false;
    endif;
    $Options['PdoDebug'] ??= false;
    $Options['AjaxAppend'] ??= false;

    // Get site
    if(isset($Options['Site'])):
      $site[0] = 'site=:site';
      $site[1] = [
        [':site', $Options['Site'], PdoStr],
        [':form', $Options['Form'], PdoStr]
      ];
    elseif(session_name() !== 'PHPSESSID'):
      $site[0] = 'site=:site';
      $site[1] = [
        [':site', session_name(), PdoStr],
        [':form', $Options['Form'], PdoStr]
      ];
    else:
      $site[0] = 'site is null';
      $site[1] = [[':form', $Options['Form'], PdoStr]];
    endif;
    // Get form
    $form = $this->PhpLivePdo->Run('
      select *
      from forms_forms
      where ' . $site[0] . '
        and form=:form',
      $site[1],
      ['Debug' => $Options['PdoDebug']]
    );
    // check if form exist
    if(count($form) === 0):
      if(session_name() !== 'PHPSESSID'):
        $site = ' (site ' . session_name() . ')';
      else:
        $site = '';
      endif;
      $this->Error('Form ' . $Options['Form'] . $site . ' not found');
      return false;
    endif;
    // Build form
    printf('<form name="%s"', $form[0]['form']);
    if($form[0]['method'] === 'ajax'):
      print ' onsubmit="return false;"';
    else:
      printf(' method="%s" action="%s"', $form[0]['method'], $Options['Page']);
    endif;
    if($form[0]['autocomplete'] === 0):
      print ' autocomplete="off"';
    endif;
    print '>';
    // Get fields
    $fields = $this->PhpLivePdo->Run("
      select *
      from forms_fields
      where form_id=?
      order by `order`",
      [
        [1, $form[0]['form_id'], PdoInt]
      ],
      ['Debug' => $Options['PdoDebug']]
    );
    foreach($fields as $field):
      //Opening element
      {
        if($field['type'] === 'submit'):
          print '<p><input type="submit"';
        elseif($field['type'] === 'select'):
          // Check if select data exist
          if(isset($Options['Selects'][$field['name']]) === false):
            if(session_name() !== 'PHPSESSID'):
              $site = ' (site ' . session_name() . ')';
            else:
              $site = '';
            endif;
            $this->Error('Data for select ' . $field['name'] . ', form ' . $Options['Form'] . $site . ' not found');
            return false;
          endif;
          print $field['label'] . ':<br>';
          print '<select name="' . $field['name'] . '"';
        elseif($field['type'] === 'checkbox'):
          print "<p>";
          if(strpos($field['class'], 'switch;') !== false):
            print '<label class="switch">';
          endif;
          print '<input type="checkbox" name="' . $field['name'] . '"';
          if(isset($Options['Data']) and $Options['Data'][$field['name']] === '1'):
            print ' checked';
          elseif($field['default'] === '1'):
            print ' checked';
          endif;
        elseif($field['type'] === 'hidden'):
          print '<input type="hidden" name="' . $field['name'] . '"';
        elseif($field['type'] === 'textarea'):
          print $field['label'] . ':<br>';
          print '<textarea name="' . $field['name'] . '"';
        elseif($field['type'] === 'button'):
          print '<p><input type="button"';
        else:
          print $field['label'] . ':<br>';
          print '<input type="' . $field['type'] . '" name="' . $field['name'] . '"';
        endif;
      }
      //Attributes
      {
        if($field['size'] !== null):
          print ' size="' . $field['size'] . '"';
        endif;
        if($field['style'] !== null):
          print ' style="' . $field['style'] . '"';
        endif;
        $class = str_replace('switch;', '', $field['class']);
        if($class !== ''):
          print ' class="' . $class . '"';
        endif;

        //event onfocus for texts to always select all
        $onfocus = '';
        if($field['type'] === 'text'
        or $field['type'] === 'textarea'
        or $field['type'] === 'email'
        or $field['type'] === 'number'
        or $field['type'] === 'password'):
          $onfocus = 'this.select();';
        endif;
        if($field['js_event'] !== null and $field['js_event'] === 'onfocus'):
          $onfocus .= $field['js_code'];
        endif;
        if($onfocus !== ''):
          print ' onfocus="' . $onfocus . '"';
        endif;

        //JS event onclick to submit button
        $onclick = '';
        if($field['type'] === 'submit' and $form[0]['method'] === 'ajax'):
          $onclick = 'Ajax(' .
            "'" . $Options['Page'] . "'," .
            "'" . $Options['Place'] . "'," .
            "'" . $Options['Form'] . "'"
          ;
          if($Options['AjaxAppend'] === true):
            $onclick .= ',' . $Options['AjaxAppend'];
          endif;
          $onclick .= ');';
        endif;
        if($field['js_event'] !== null and $field['js_event'] === 'onclick'):
          $onclick .= $field['js_code'];
        endif;
        if($onclick !== ''):
          print ' onclick="' . $onclick . '"';
        endif;

        if($field['js_event'] !== null
        and $field['js_event'] !== 'onfocus'
        and $field['js_event'] !== 'onclick'):
          print ' ' . $field['js_event'] . '="' . $field['js_code'] . '"';
        endif;
        if($field['mode'] === '1' and isset($Options['Data'])):
          print ' readonly';
        elseif($field['mode'] === '2' and isset($Options['Data']) === false):
          print ' readonly';
        endif;

        //value
        if($field['type'] === 'hidden' and isset($Options['Hiddens'])):
          print ' value="' . $Options['Hiddens'][$field['name']] . '"';
        elseif($field['type'] === 'text'
        or $field['type'] === 'date'
        or $field['type'] === 'datetime-local'
        or $field['type'] === 'number'):
          if(isset($Options['Data'])):
            print ' value="' . $Options['Data'][$field['name']] . '"';
          elseif($field['default'] !== null):
            print ' value="' . $field['default'] . '"';
          endif;
        elseif($field['type'] === 'button'
        or $field['type'] === 'submit'):
          print ' value="' . $field['label'] . '"';
        endif;

        print '>';
      }
      //Content
      {
        if($field['type'] === 'select'):
          // Check the first option of select data
          // Show a default value if not specified
          // or a default value not specified
          if($Options['Selects'][$field['name']][0][0] > 0 and $field['default'] === null):
            print '<option value="0" selected disabled></option>';
          endif;
          foreach($Options['Selects'][$field['name']] as $select):
            print '<option value="' . $select[0] . '"';
            if(isset($Options['Data']) and $select[0] == $Options['Data'][$field['name']]):
              print ' selected';
            elseif(isset($Options['Data']) === false and $field['default'] !== null and $select[0] === $field['default']):
              print ' selected';
            endif;
            print '>' . $select[1] . '</option>';
          endforeach;
        elseif($field['type'] === 'checkbox'):
          if(strpos($field['class'], 'switch;') !== false):
            print '<span class="slider"></span></label> ';
          endif;
          print $field['label'] . '</p>';
        elseif($field['type'] === 'textarea'):
          if(isset($Options['Data'])):
            print $Options['Data'][$field['name']];
          elseif($field['default'] !== null):
            print $field['default'];
          endif;
        else:
          print '<br>';
        endif;
      }
      //close
      {
        if($field['type'] === 'select'):
          print '</select><br>';
        elseif($field['type'] === 'textarea'):
          print '</textarea><br>';
        elseif($field['type'] === 'button'):
          print '</p>';
        endif;
      }
    endforeach;
    print '</form>';
    return true;
  }
}