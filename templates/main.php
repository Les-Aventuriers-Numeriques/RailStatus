<style>
.block {
  cursor: default;
}
</style>

<div class="page-header">
  <h1>Visualiser</h1>
</div>

<div class="row">
  <div class="col-lg-3">
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><i class="icon-hand-right"></i> Choisissez une carte</h3>
      </div>
      <div class="panel-body">
        <div class="list-group">
        <?php foreach ($maps as $map): ?>
          <a href="<?php echo ROOT.'/'.$map->id; ?>" class="list-group-item <?php echo (!empty($selected_map) and $selected_map->id == $map->id) ? 'active' : ''; ?>"><?php echo $map->name; ?></a>
        <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-9">
    <?php
    if (!empty($selected_map)): ?>
    <p class="text-center"><button id="refresh" class="btn btn-primary"><i class="icon-repeat"></i> Actualiser</button></p>

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

              if ($class == 'switch') {
                $comp = ORM::for_table('computers')->find_one($selected_map_blocks[$iy][$ix]['computer-id']);

                $attributes .= 'data-toggle="tooltip" title="'.$comp->name.' : &lt;span style=&quot;color: '.$selected_map_blocks[$iy][$ix]['switch-color'].'&quot;&gt;'.$selected_map_blocks[$iy][$ix]['switch-color'].'&lt;/span&gt;"';
              }
            } else {
              $attributes = '';
              $class = 'none';
            }
          } else {
            $attributes = '';
            $class = 'none';
          }

          echo '<td class="block '.$class.'"'.$attributes.'></td>'.PHP_EOL;
        }

        echo '</tr>'.PHP_EOL;
      }
      ?>
    </table>

    <script>
    var table_map = $('table#map');
    var map_blocks = table_map.find('tr td');

    var switches = map_blocks.filter('[class$="switch"]');

    function createSwitches() {
      switches.each(function() {
        $(this).attr('class', 'block switch-unknow');
      });
    }

    function updateSwitches() {
      $.get('<?php echo ROOT; ?>/show/<?php echo $selected_map->id; ?>', function(response) {
        var statuses = response.statuses;

        switches.each(function() {
          var onoff = statuses[$(this).attr('data-computer-id')][$(this).attr('data-switch-color')];

          if (onoff == 1) {
            $(this).attr('class', 'block switch-'+$(this).attr('data-arrow-on'));
          } else if (onoff == 0) {
            $(this).attr('class', 'block switch-'+$(this).attr('data-arrow-off'));
          }
        });
      });
    }

    $(function() {
      createSwitches();
      updateSwitches();

      $('[data-toggle="tooltip"]').tooltip({
        animation: false,
        html: true,
        container: 'body'
      });

      $('button#refresh').click(function(e) {
        updateSwitches();
      });
    });
    </script>
    <?php endif; ?>
  </div>
</div>