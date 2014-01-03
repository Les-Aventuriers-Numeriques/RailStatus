<div class="page-header">
  <h1>Editeur<?php if (!empty($selected_map)): echo ' : '.$selected_map->name; endif; ?></h1>
</div>

<style>
div.block {
  display: inline-block;
  cursor: pointer;
}

div.block.group {
  border: 1px solid #333;
}

div#blocks div.block {
  margin-right: 5px;
}

/* Position du curseur sur la carte */
div#current_pos {
  text-align: center;
  margin-bottom: 5px;
}

</style>

<div class="row">
  <div class="col-lg-2">
    <div>
      <a href="#" id="action_load" class="btn btn-default btn-block"><i class="fa fa-folder-open"></i> Charger</a>
      <a href="#" id="action_new" class="btn btn-default btn-block"><i class="fa fa-plus"></i> Nouvelle</a>
      <?php if (!empty($selected_map)): ?>
        <a href="#" id="action_rename" class="btn btn-default btn-block"><i class="fa fa-font"></i> Renommer</a>
        <a href="#" id="action_delete" class="btn btn-danger btn-block"><i class="fa fa-trash-o"></i> Supprimer</a>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-lg-10">
    <?php if (!empty($selected_map)): ?>
    <ul class="nav nav-tabs">
      <li class="active"><a href="#tabs-map" data-toggle="tab"><i class="fa fa-picture-o"></i> Carte</a></li>
      <li><a href="#tabs-computers" data-toggle="tab"><i class="fa fa-hdd-o"></i> Ordinateurs</a></li>
    </ul>

    <div class="tab-content">
      <div class="tab-pane active" id="tabs-map">
        <div>
          <a href="#" id="action_save" class="btn btn-primary"><i class="fa fa-save"></i> Enregistrer</a>
          <a href="#" id="action_clear" class="btn btn-danger"><i class="fa fa-trash-o"></i> Effacer</a>
          <a href="#" id="action_resize" class="btn btn-warning"><i class="fa fa-arrows"></i> Redimensionner</a>
        </div>

        <div id="current_pos">Position curseur : X: <span id="current_pos_x">0</span>, Y : <span id="current_pos_y">0</span></div>

        <table id="map" align="center">
          <?php
          $selected_map_blocks = json_decode($selected_map->blocks, true);

          for ($iy = 1; $iy <= $selected_map->height; $iy++) {
            echo '<tr>'.PHP_EOL;

            for ($ix = 1; $ix <= $selected_map->width; $ix++) {

              if ($selected_map_blocks !== null) {
                if (isset($selected_map_blocks[$iy][$ix])) {
                  $class = $selected_map_blocks[$iy][$ix]['type'];

                  unset($selected_map_blocks[$iy][$ix]['type']);

                  $attributes = '';

                  foreach ($selected_map_blocks[$iy][$ix] as $atttributes_name => $attribute_value) {
                    $attributes .= ' data-'.$atttributes_name.'="'.$attribute_value.'"';
                  }
                } else {
                  $attributes = '';
                  $class = 'none';
                }
              } else {
                $attributes = '';
                $class = 'none';
              }

              echo '<td class="block '.$class.'" data-x="'.$ix.'" data-y="'.$iy.'"'.$attributes.'></td>'.PHP_EOL;
            }

            echo '</tr>'.PHP_EOL;
          }
          ?>
        </table>

        <div class="group_bar well well-sm">
          <h4>Outils</h4>
          Bloc :
          <div id="blocks" class="btn-group" data-toggle="buttons">
            <?php
            foreach (unserialize(AVAILABLE_BLOCKS) as $block_id => $block_infos) {
              echo '
                <label class="btn btn-default" for="selected_block_'.$block_id.'">
                  <input type="radio" id="selected_block_'.$block_id.'" name="selected_block" value="'.$block_id.'" /> <div class="block '.$block_id.'"></div> '.$block_infos['name'].'
                </label>';
            }
            ?>
          </div>

          Pinceau :
          <div id="pens" class="btn-group" data-toggle="buttons">
            <label class="btn btn-default" for="pen_square">
              <input type="radio" id="pen_square" name="pen" value="square" checked="checked" /> <img src="<?php echo ROOT; ?>/img/pen_square.png"> Bloc
            </label>
            <label class="btn btn-default" for="pen_square">
              <input type="radio" id="pen_line" name="pen" value="square" checked="checked" /> <img src="<?php echo ROOT; ?>/img/pen_line.png"> Ligne
            </label>
          </div>
        </div>
        <div class="group_bar well well-sm">
          <div id="options">
            <h4>Options</h4>
            <input type="checkbox" id="display_grid" <?php echo $selected_map->show_grid == 1 ? 'checked="checked"' : ''; ?>> <label for="display_grid">Afficher le quadrillage</label>
          </div>
        </div>

        <script>
        var BLOCK_OPTIONS = {
          <?php
          foreach (unserialize(AVAILABLE_BLOCKS) as $block_id => $block_infos) {
            echo '\''.$block_id.'\': '.(!empty($block_infos['options']) ? 'true' : 'false').','.PHP_EOL;
          }
          ?>
        };

        var table_map = $('table#map');
        var map_blocks = table_map.find('tr td');

        var span_current_pos_x = $('span#current_pos_x');
        var span_current_pos_y = $('span#current_pos_y');

        var input_display_grid = $('input#display_grid');

        $(function() {
          // Hover entrant sur les blocs de la carte
          map_blocks.mouseover(function() {
            // Position courante du curseur
            span_current_pos_x.text($(this).attr('data-x'));
            span_current_pos_y.text($(this).attr('data-y'));
            $(this).css('opacity', '0.5');
          });

          // Hover sortant sur les blocs de la carte
          map_blocks.mouseout(function() {
            $(this).css('opacity', '1');
          });

          // Clic GAUCHE sur les blocs de la carte
          map_blocks.mousedown(function(e) {
            if (e.which == 1) {
              if ($('input[name="selected_block"]:checked').val() == null) {
                showModalDialog('Placement impossible', 'Vous n\'avez pas sélectionné de bloc à placer (guignol).');
                return;
              }

              $(this).attr('class', 'block '+$('input[name="selected_block"]:checked').val());
            }
          });

          // Clic DROIT sur les blocs de la carte
          map_blocks.bind('contextmenu', function(e) {
            e.preventDefault();

            var block = $(this);

            var block_id = block.attr('class').replace('block ', '');
            var pos_x = block.attr('data-x');
            var pos_y = block.attr('data-y');

            if (BLOCK_OPTIONS[block_id] == false) {
              return;
            }

            // Ouverture des options du bloc
            jQuery.ajaxSetup({async:false});

            showModalDialog('Options du bloc en '+pos_x+':'+pos_y, $.get('<?php echo ROOT; ?>/editor/block_options/'+block_id+'/<?php echo $selected_map->id; ?>').responseText, {
              'Enregistrer': {
                style: 'primary',
                  callback: function(modal_dialog) {
                  switch (block_id) {
                    case 'switch':
                      var switch_option_color = modal_dialog.find('select#switch_option_color').first().val();
                      var switch_option_computer = modal_dialog.find('select#switch_option_computer').first().val();
                      var switch_option_arrow_on = modal_dialog.find('select#switch_option_arrow_on').first().val();
                      var switch_option_arrow_off = modal_dialog.find('select#switch_option_arrow_off').first().val();

                      block.attr('data-switch-color', switch_option_color);
                      block.attr('data-computer-id', switch_option_computer);
                      block.attr('data-arrow-on', switch_option_arrow_on);
                      block.attr('data-arrow-off', switch_option_arrow_off);
                    break;
                    default:
                      alert('Ce bloc n\'a pas d\'options');
                  }

                  modal_dialog.modal('hide');
                }
              },
              'Annuler': {
                style: 'default',
                callback: function(modal_dialog) {
                  modal_dialog.modal('hide');
                }
              }
            },
            function(ui) {
              ui.find('select#switch_option_color').first().val(block.attr('data-switch-color'));
              ui.find('select#switch_option_computer').first().val(block.attr('data-computer-id'));
              ui.find('select#switch_option_arrow_on').first().val(block.attr('data-arrow-on'));
              ui.find('select#switch_option_arrow_off').first().val(block.attr('data-arrow-off'));
            });

            jQuery.ajaxSetup({async:true});
          });

          // ---------------------------------------------------------------------- //

          input_display_grid.click(function() {
            var checked = $(this).is(':checked');

            if (checked) {
              map_blocks.css('border', '1px solid #353535');
            } else {
              map_blocks.css('border', 'none');
            }

            jQuery.ajaxSetup({async:false});

            $.post('<?php echo ROOT; ?>/editor/showgrid/<?php echo $selected_map->id; ?>', {b: (checked == true ? 1 : 0)});

            jQuery.ajaxSetup({async:true});
          });

          <?php if ($selected_map->show_grid == 1): ?>
          map_blocks.css('border', '1px solid #353535');
          <?php endif; ?>
        });
        </script>
      </div>
      <div class="tab-pane" id="tabs-computers">
          <div>
            <a href="#" id="computer_action_new" class="btn btn-primary"><i class="fa fa-plus"></i> Nouveau</a>
          </div>

        <table align="center" class="table table-striped table-hover " id="table_computers">
          <tr>
            <th>ID</th>
            <th>Nom</th>
            <th></th>
          </tr>
        <?php foreach ($map_computers as $map_computer): ?>
          <tr id="tr_computer_<?php echo $map_computer->id; ?>">
            <td><?php echo $map_computer->id; ?></td>
            <td><?php echo $map_computer->name; ?></td>
            <td><a href="#" data-computer-id="<?php echo $map_computer->id; ?>" class="delete_computer btn btn-danger btn-sm"><i class="fa fa-trash-o"></i></a> <a href="#" data-computer-id="<?php echo $map_computer->id; ?>" class="edit_computer btn btn-default btn-sm"><i class="fa fa-pencil"></i></a></td>
          </tr>
        <?php endforeach; ?>
        </table>

        <script>
        var a_delete_computer = $('a.delete_computer');
        var a_edit_computer = $('a.edit_computer');

        var a_computer_action_new = $('a#computer_action_new');

        $(function() {
          // Ordinateur : Clic sur le bouton Nouveau
          a_computer_action_new.click(function(e) {
            e.preventDefault();

            jQuery.ajaxSetup({async:false});

            showModalDialog('Nouvel ordinateur', $.get('<?php echo ROOT; ?>/editor/computer/new').responseText, {
              'Enregistrer': {
                style: 'primary',
                callback: function(modal_dialog) {
                  var computer_name = modal_dialog.find('input#computer_name').first().val();

                  if (computer_name == '') {
                    return;
                  }

                  jQuery.ajaxSetup({async:false});

                  var computer = $.parseJSON($.post('<?php echo ROOT; ?>/editor/computer/new/<?php echo $selected_map->id; ?>', {computer_name: computer_name}).responseText);

                  jQuery.ajaxSetup({async:true});

                  $('table#table_computers').append('<tr><td>'+computer.id+'</td><td>'+computer.name+'</td><td><a href="#" data-computer-id="'+computer.id+'" class="delete_computer btn btn-danger btn-sm"><i class="fa fa-trash-o"></i></a> <a href="#" data-computer-id="'+computer.id+'" class="edit_computer btn btn-default btn-sm"><i class="fa fa-pencil"></i></a></td></tr>');

                  modal_dialog.modal('hide');
                }
              },
              'Annuler': {
                style: 'default',
                callback: function(modal_dialog) {
                  modal_dialog.modal('hide');
                }
              }
            });

            jQuery.ajaxSetup({async:true});
          });

          // Liste des ordis : clic sur les liens Supprimer
          a_delete_computer.on('click', function(e) {
            e.preventDefault();

            var computer_id = $(this).attr('data-computer-id');

            showModalDialog('Confirmation', 'Etes-vous certain de vouloir supprimer cet ordinateur ?<br><br>Attention : les statuts des aiguillages liés à cet ordinateur seront perdus !', {
              'Confirmer': {
                style: 'primary',
                callback: function(modal_dialog) {
                  jQuery.ajaxSetup({async:false});

                  $.get('<?php echo ROOT; ?>/editor/computer/delete/'+computer_id);

                  jQuery.ajaxSetup({async:true});

                  $('tr#tr_computer_'+computer_id).remove();

                  modal_dialog.modal('hide');
                }
              },
              'Annuler': {
                style: 'default',
                callback: function(modal_dialog) {
                  modal_dialog.modal('hide');
                }
              }
            });
          });

          // Liste des ordis : clic sur les liens Modifier
          a_edit_computer.on('click', function(e) {
            e.preventDefault();

            var computer_id = $(this).attr('data-computer-id');

            jQuery.ajaxSetup({async:false});

            showModalDialog('Modifier ordinateur', $.get('<?php echo ROOT; ?>/editor/computer/edit/'+computer_id).responseText, {
              'Confirmer': {
                style: 'primary',
                callback: function(modal_dialog) {
                  jQuery.ajaxSetup({async:false});

                  var computer_name = modal_dialog.find('input#computer_name').first().val();

                  if (computer_name == '') {
                    return;
                  }

                  $.post('<?php echo ROOT; ?>/editor/computer/edit/'+computer_id, {computer_name: computer_name});

                  jQuery.ajaxSetup({async:true});

                  $('tr#tr_computer_'+computer_id+' td').eq(1).text(computer_name);

                  modal_dialog.modal('hide');
                }
              },
              'Annuler': {
                style: 'default',
                callback: function(modal_dialog) {
                  modal_dialog.modal('hide');
                }
              }
            });

            jQuery.ajaxSetup({async:true});
          });
        });
        </script>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<script>
<?php if (!empty($selected_map)): ?>
var a_action_save = $('a#action_save');
var a_action_clear = $('a#action_clear');
var a_action_delete = $('a#action_delete');
var a_action_rename = $('a#action_rename');
var a_action_resize = $('a#action_resize');
<?php endif; ?>

var a_action_load = $('a#action_load');
var a_action_new = $('a#action_new');

$(function() {
  <?php if (!empty($selected_map)): ?>
  // Clic sur bouton Enregistrer
  a_action_save.click(function(e) {
    e.preventDefault();

    jQuery.ajaxSetup({async:false});

    var blocks = {};

    map_blocks.each(function() {
      var block = $(this);

      var block_id = block.attr('class').replace('block ', '');
      var pos_x = parseInt(block.attr('data-x'));
      var pos_y = parseInt(block.attr('data-y'));

      $(block[0].attributes).each(function() {
        var key = this.nodeName.replace('data-', '');

        if (key != 'x' && key != 'y' && key != 'style') {
          switch (key) {
            case 'class':
              key = 'type';
              var value = this.nodeValue.replace('block ', '');
            break;
            default:
              var value = this.nodeValue;
          }

          if (!(pos_y in blocks)) {
            blocks[pos_y] = {};
          }

          if (!(pos_x in blocks[pos_y])) {
            blocks[pos_y][pos_x] = {};
          }

          blocks[pos_y][pos_x][key] = value;
        }
      });
    });

    $.post('<?php echo ROOT; ?>/editor/save/<?php echo $selected_map->id; ?>', {blocks: JSON.stringify(blocks)});

    jQuery.ajaxSetup({async:true});

    showModalDialog('Confirmation', 'Carte enregistrée');
  });

  // Clic sur bouton Effacer
  a_action_clear.click(function(e) {
    e.preventDefault();

    showModalDialog('Confirmation', 'Etes-vous certain de vouloir effacer le contenu de la carte ?', {
      'Confirmer': {
        style: 'primary',
        callback: function(modal_dialog) {
          map_blocks.attr('class', 'block none');
          modal_dialog.modal('hide');
        }
      },
      'Annuler': {
        style: 'default',
        callback: function(modal_dialog) {
          modal_dialog.modal('hide');
        }
      }
    });
  });

  // Clic sur bouton Supprimer
  a_action_delete.click(function(e) {
    e.preventDefault();

    showModalDialog('Confirmation', 'Etes-vous certain de vouloir supprimer cette carte ?', {
      'Confirmer': {
        style: 'primary',
        callback: function(modal_dialog) {
          jQuery.ajaxSetup({async:false});

          $.get('<?php echo ROOT; ?>/editor/delete_map/<?php echo $selected_map->id; ?>');

          jQuery.ajaxSetup({async:true});

          window.location.replace('<?php echo ROOT; ?>/editor');
        }
      },
      'Annuler': {
        style: 'default',
        callback: function(modal_dialog) {
          modal_dialog.modal('hide');
        }
      }
    });
  });

  // Clic sur bouton Renommer
  a_action_rename.click(function(e) {
    e.preventDefault();

    jQuery.ajaxSetup({async:false});

    showModalDialog('Renommer carte', $.get('<?php echo ROOT; ?>/editor/rename/<?php echo $selected_map->id; ?>').responseText, {
      'Confirmer': {
        style: 'primary',
        callback: function(modal_dialog) {
          jQuery.ajaxSetup({async:false});

          var map_name = modal_dialog.find('input#map_name').first().val();

          if (map_name == '') {
            return;
          }

          $.post('<?php echo ROOT; ?>/editor/rename/<?php echo $selected_map->id; ?>', {map_name: map_name});

          jQuery.ajaxSetup({async:true});

          location.reload();
        }
      },
      'Annuler': {
        style: 'default',
        callback: function(modal_dialog) {
          modal_dialog.modal('hide');
        }
      }
    });

    jQuery.ajaxSetup({async:true});
  });

  // Clic sur bouton Redimensionner
  a_action_resize.click(function(e) {
    e.preventDefault();

    // TODO

    /*jQuery.ajaxSetup({async:false});

    showModalDialog('Renommer carte', $.get('<?php echo ROOT; ?>/editor/rename/<?php echo $selected_map->id; ?>').responseText, {
      'Confirmer': {
        style: 'primary',
        callback: function(modal_dialog) {
          jQuery.ajaxSetup({async:false});

          var map_name = modal_dialog.find('input#map_name').first().val();

          if (map_name == '') {
            return;
          }

          $.post('<?php echo ROOT; ?>/editor/rename/<?php echo $selected_map->id; ?>', {map_name: map_name});

          jQuery.ajaxSetup({async:true});

          location.reload();
        }
      },
      'Annuler': {
        style: 'default',
        callback: function(modal_dialog) {
          modal_dialog.modal('hide');
        }
      }
    });

    jQuery.ajaxSetup({async:true});*/
  });
  <?php endif; ?>

  // Clic sur bouton Charger
  a_action_load.click(function(e) {
    e.preventDefault();

    jQuery.ajaxSetup({async:false});

    showModalDialog('Choisir une carte', $.get('<?php echo ROOT; ?>/editor/maplist').responseText, {
      'Annuler': {
        style: 'default',
        callback: function(modal_dialog) {
          modal_dialog.modal('hide');
        }
      }
    });

    jQuery.ajaxSetup({async:true});
  });

  // Clic sur bouton Nouvelle
  a_action_new.click(function(e) {
    e.preventDefault();

    jQuery.ajaxSetup({async:false});

    showModalDialog('Nouvelle carte', $.get('<?php echo ROOT; ?>/editor/new').responseText, {
      'Enregistrer': {
        style: 'primary',
        callback: function(modal_dialog) {
          var map_name = modal_dialog.find('input#map_name').first().val();
          var map_width = modal_dialog.find('input#map_width').first().val();
          var map_height = modal_dialog.find('input#map_height').first().val();

          if (map_name == '' || map_width == '' || map_height == '') {
            return;
          }

          jQuery.ajaxSetup({async:false});

          var map_id = $.post('<?php echo ROOT; ?>/editor/new', {map_name: map_name, map_width: map_width, map_height: map_height}).responseText;

          jQuery.ajaxSetup({async:true});

          window.location.replace('<?php echo ROOT; ?>/editor/'+map_id);
        }
      },
      'Annuler': {
        style: 'default',
        callback: function(modal_dialog) {
          modal_dialog.modal('hide');
        }
      }
    });

    jQuery.ajaxSetup({async:true});
  });
});
</script>