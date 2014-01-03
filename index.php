<?php
session_start();

switch ($_SERVER['SERVER_NAME']) {
  case 'localhost':
    define('ROOT', 'http://localhost/RailStatus');
  break;
  case 'railstatus.easy-company.fr':
    define('ROOT', 'http://railstatus.easy-company.fr');
  break;
}

define('MAP_SQUARE_SIZE', 10);

$ca = array();

$ca['white'] = 'Blanc';
$ca['orange'] = 'Orange';
$ca['magenta'] = 'Magenta';
$ca['lightBlue'] = 'Bleu clair';
$ca['yellow'] = 'Jaune';
$ca['lime'] = 'Vert clair';
$ca['pink'] = 'Rose';
$ca['gray'] = 'Gris';
$ca['lightGray'] = 'Gris clair';
$ca['cyan'] = 'Cyan';
$ca['purple'] = 'Violet';
$ca['blue'] = 'Bleu';
$ca['brown'] = 'Brum';
$ca['green'] = 'Vert';
$ca['red'] = 'Rouge';
$ca['black'] = 'Noir';

define('COLORS_ARRAY', serialize($ca));

// Blocs possibles
$ab = array(
  'rail' => array(
    'name' => 'Rail',
    'style' => 'background-color: #BA8D15;',
    'options' => array()
  ),
  'rail-top' => array(
    'name' => 'Rail (Haut)',
    'style' => 'background: #BA8D15 url("'.ROOT.'/img/block_rail_arrow.png") center center no-repeat; -webkit-transform: rotate(90deg);',
    'options' => array()
  ),
  'rail-bottom' => array(
    'name' => 'Rail (Bas)',
    'style' => 'background: #BA8D15 url("'.ROOT.'/img/block_rail_arrow.png") center center no-repeat; -webkit-transform: rotate(-90deg);',
    'options' => array()
  ),
  'rail-left' => array(
    'name' => 'Rail (Gauche)',
    'style' => 'background: #BA8D15 url("'.ROOT.'/img/block_rail_arrow.png") center center no-repeat;',
    'options' => array()
  ),
  'rail-right' => array(
    'name' => 'Rail (Droite)',
    'style' => 'background: #BA8D15 url("'.ROOT.'/img/block_rail_arrow.png") center center no-repeat; -webkit-transform: rotate(180deg);',
    'options' => array()
  ),
  'none' => array(
    'name' => 'Rien',
    'style' => 'background-color: #000000;',
    'options' => array()
  ),
  'switch' => array(
    'name' => 'Moteur aiguillage',
    'style' => 'background-color: #FF0000;',
    'options' => array('switch-color', 'computer-id', 'arrow-on', 'arrow-off')
  ),
  'group' => array(
    'name' => 'Groupe',
    'style' => 'background-color: #ffffff;',
    'options' => array()
  )
);

define('AVAILABLE_BLOCKS', serialize($ab));

$dir = array();

$dir['top'] = 'Haut';
$dir['bottom'] = 'Bas';
$dir['left'] = 'Gauche';
$dir['right'] = 'Droite';

define('DIRECTIONS_ARRAY', serialize($dir));

// -------------------------------------------------------------------------------------- //

require('inc/idiorm.php');
require('Slim/Slim.php');

\Slim\Slim::registerAutoloader();

require('inc/RailStatusView.php');

$app = new \Slim\Slim(array(
  'view' => new RailStatusView(),
  'debug' => true,
  'templates.path' => 'templates'
));

ORM::configure(array(
  'connection_string' => 'sqlite:storage/railstatus.db',
  'driver_options' => array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES UTF8'),
  'error_mode' => PDO::ERRMODE_EXCEPTION,
  'return_result_sets' => true,
  'id_column' => 'id'
));

// -------------------------------------------------------------------------------------- //

$app->get('/', function () use ($app) {
  $maps = ORM::for_table('maps')->find_many();

  $app->render('main.php', array('maps' => $maps, 'active_nav_tab' => 'visualiser'));
});

// -------------------------------------------------------------------------------------- //

$app->get('/editor', function () use ($app) {
  $app->render('editor.php', array('selected_map' => null, 'active_nav_tab' => 'editer'));
});

// -------------------------------------------------------------------------------------- //

$app->post('/api', function () use ($app) {
  $req = $app->request();

  $computer_id = $req->post('ci');

  $status = array();

  foreach (unserialize(COLORS_ARRAY) as $id => $name) {
    $v = $req->post($id);

    $status[$id] = 0;

    if (!empty($v)) {
      $status[$id] = (int) $v;
    }
  }

  $computer = ORM::for_table('computers')->find_one($computer_id);

  if (!$computer) {
    $computer = ORM::for_table('computers')->create();
    $computer->id = $computer_id;
  }

  $computer->status = json_encode($status);
  $computer->save();
});

// -------------------------------------------------------------------------------------- //

$app->get('/api/:map_id', function ($map_id) use ($app) {
  $map = ORM::for_table('maps')->find_one($map_id);

  if (!$map) {
    $app->halt(404);
  }

  $res = $app->response();
  $res['Content-Type'] = 'application/json';

  $return = array();

  $return['map_width'] = (int) $map->width;
  $return['map_height'] = (int) $map->height;
  $return['map_data'] = json_decode($map->blocks);

  $res->body(json_encode($return));
});

// -------------------------------------------------------------------------------------- //

$app->get('/show/:map_id', function ($map_id) use ($app) {
  if (!$app->request()->isAjax()) {
    $app->halt(400);
  }

  $map = ORM::for_table('maps')->find_one($map_id);

  if (!$map) {
    $app->halt(404);
  }

  $computers = ORM::for_table('computers')->where('map_id', $map->id)->find_many();

  $res = $app->response();
  $res['Content-Type'] = 'application/json';

  $response = array();

  foreach ($computers as $computer) {
    $computer_status = json_decode($computer->status);

    $response['statuses'][$computer->id] = $computer_status;
  }

  $res->body(json_encode($response));
});

// -------------------------------------------------------------------------------------- //

$app->get('/:id', function ($id) use ($app) {
  $map = ORM::for_table('maps')->find_one($id);

  if (!$map) {
    $app->halt(404);
  }

  $map_computers = ORM::for_table('computers')->where('map_id', $map->id)->find_many();

  $maps = ORM::for_table('maps')->find_many();

  $app->render('main.php', array('selected_map' => $map, 'map_computers' => $map_computers, 'maps' => $maps, 'active_nav_tab' => 'visualiser'));
});

// -------------------------------------------------------------------------------------- //

$app->get('/editor/computer/delete/:id', function ($id) use ($app) {
  if (!$app->request()->isAjax()) {
    $app->halt(400);
  }

  $computer = ORM::for_table('computers')->find_one($id);

  if (!$computer) {
    $app->halt(404);
  }

  $computer->delete();
});

// -------------------------------------------------------------------------------------- //

$app->get('/editor/computer/edit/:id', function ($id) use ($app) {
  if (!$app->request()->isAjax()) {
    $app->halt(400);
  }

  $computer = ORM::for_table('computers')->find_one($id);

  if (!$computer) {
    $app->halt(404);
  }

  $app->response->setBody('<form class="form-horizontal">
    <div class="form-group">
      <label for="computer_name" class="col-lg-5 control-label">Nom</label>
      <div class="col-lg-5">
        <input type="text" class="form-control" id="computer_name" value="'.$computer->name.'" required>
      </div>
    </div>
  </form>');
});

// -------------------------------------------------------------------------------------- //

$app->post('/editor/computer/edit/:id', function ($id) use ($app) {
  if (!$app->request()->isAjax()) {
    $app->halt(400);
  }

  $computer = ORM::for_table('computers')->find_one($id);

  if (!$computer) {
    $app->halt(404);
  }

  $computer_name = $app->request()->post('computer_name');

  $computer->name = $computer_name;
  $computer->save();
});

// -------------------------------------------------------------------------------------- //

$app->get('/editor/computer/new', function () use ($app) {
  if (!$app->request()->isAjax()) {
    $app->halt(400);
  }

  $app->response->setBody('<form class="form-horizontal">
    <div class="form-group">
      <label for="computer_name" class="col-lg-5 control-label">Nom</label>
      <div class="col-lg-5">
        <input type="text" class="form-control" id="computer_name" required>
      </div>
    </div>
  </form>');
});

// -------------------------------------------------------------------------------------- //

$app->post('/editor/computer/new/:map_id', function ($map_id) use ($app) {
  if (!$app->request()->isAjax()) {
    $app->halt(400);
  }

  $map = ORM::for_table('maps')->find_one($map_id);

  if (!$map) {
    $app->halt(404);
  }

  $computer_name = $app->request()->post('computer_name');

  $computer = ORM::for_table('computers')->create();
  $computer->name = $computer_name;
  $computer->map_id = $map->id;
  $computer->save();

  $app->response->setBody(json_encode($computer->as_array()));
});

// -------------------------------------------------------------------------------------- //

$app->get('/editor/maplist', function () use ($app) {
  if (!$app->request()->isAjax()) {
    $app->halt(400);
  }

  $maps = ORM::for_table('maps')->find_many();

  $res = '<ul>';

  foreach ($maps as $map) {
    $res .= '<li><a href="'.ROOT.'/editor/'.$map->id.'">'.$map->name.'</a></li>';
  }

  $res .= '</ul><div class="alert alert-danger">Attention : la carte chargée actuellement perdras toutes les modifications apportées si elle n\'a pas été enregistrée.</div>';

  $app->response->setBody($res);
});

// -------------------------------------------------------------------------------------- //

$app->get('/editor/delete_map/:id', function ($id) use ($app) {
  if (!$app->request()->isAjax()) {
    $app->halt(400);
  }

  $map = ORM::for_table('maps')->find_one($id);

  if (!$map) {
    $app->halt(404);
  }

  $map->delete();
});

// -------------------------------------------------------------------------------------- //

$app->get('/editor/block_options/:id/:map_id', function ($id, $map_id) use ($app) {
  if (!$app->request()->isAjax()) {
    $app->halt(400);
  }

  $map = ORM::for_table('maps')->find_one($map_id);

  if (!$map) {
    $app->halt(404);
  }

  $res = '<form class="form-horizontal">';

  switch ($id) {
    case 'switch':
      $res .= '<div class="form-group">
                <label for="switch_option_color" class="col-lg-5 control-label">Couleur du câble</label>
                <div class="col-lg-5">
                  <select id="switch_option_color" class="form-control">';

                  foreach (unserialize(COLORS_ARRAY) as $id => $name) {
                    $res .= '<option value="'.$id.'">'.$name.'</option>';
                  }

                  $res .= '</select>
               </div>
             </div>';

      $res .= '<div class="form-group">
                <label for="switch_option_computer" class="col-lg-5 control-label">Ordinateur</label>
                <div class="col-lg-5">
                  <select id="switch_option_computer" class="form-control">';

                  $computers = ORM::for_table('computers')->where('map_id', $map->id)->find_many();

                  foreach ($computers as $computer) {
                    $res .= '<option value="'.$computer->id.'">'.$computer->name.'</option>';
                  }

                  $res .= '</select>
               </div>
             </div>';

      $res .= '<div class="form-group">
                <label for="switch_option_arrow_on" class="col-lg-5 control-label">Flèche actif</label>
                <div class="col-lg-5">
                  <select id="switch_option_arrow_on" class="form-control">';

                  foreach (unserialize(DIRECTIONS_ARRAY) as $direction_id => $direction_name) {
                    $res .= '<option value="'.$direction_id.'">'.$direction_name.'</option>';
                  }

                  $res .= '</select>
               </div>
             </div>';

      $res .= '<div class="form-group">
                <label for="switch_option_arrow_off" class="col-lg-5 control-label">Flèche inactif</label>
                <div class="col-lg-5">
                  <select id="switch_option_arrow_off" class="form-control">';

                  foreach (unserialize(DIRECTIONS_ARRAY) as $direction_id => $direction_name) {
                    $res .= '<option value="'.$direction_id.'">'.$direction_name.'</option>';
                  }

                  $res .= '</select>
               </div>
             </div>';
    break;
    default:
      $app->halt(404);
  }

  $res .= '</form>';

  $app->response->setBody($res);
});

// -------------------------------------------------------------------------------------- //

$app->post('/editor/save/:id', function ($id) use ($app) {
  if (!$app->request()->isAjax()) {
    $app->halt(400);
  }

  $map = ORM::for_table('maps')->find_one($id);

  if (!$map) {
    $app->halt(404);
  }

  $blocks = $app->request()->post('blocks');

  $map->blocks = $blocks;
  $map->save();
});

// -------------------------------------------------------------------------------------- //

$app->get('/editor/rename/:id', function ($id) use ($app) {
  if (!$app->request()->isAjax()) {
    $app->halt(400);
  }

  $map = ORM::for_table('maps')->find_one($id);

  if (!$map) {
    $app->halt(404);
  }

  $app->response->setBody('<form class="form-horizontal">
    <div class="form-group">
      <label for="map_name" class="col-lg-5 control-label">Nom</label>
      <div class="col-lg-5">
        <input type="text" class="form-control" id="map_name" value="'.$map->name.'" required>
      </div>
    </div>
  </form>

  <div class="alert alert-danger">Attention : la carte chargée actuellement perdras toutes les modifications apportées si elle n\'a pas été enregistrée.</div>');
});

// -------------------------------------------------------------------------------------- //

$app->post('/editor/rename/:id', function ($id) use ($app) {
  if (!$app->request()->isAjax()) {
    $app->halt(400);
  }

  $map = ORM::for_table('maps')->find_one($id);

  if (!$map) {
    $app->halt(404);
  }

  $map_name = $app->request()->post('map_name');

  $map->name = $map_name;
  $map->save();
});

// -------------------------------------------------------------------------------------- //

$app->post('/editor/showgrid/:id', function ($id) use ($app) {
  if (!$app->request()->isAjax()) {
    $app->halt(400);
  }

  $map = ORM::for_table('maps')->find_one($id);

  if (!$map) {
    $app->halt(404);
  }

  $showgrid = (int) $app->request()->post('b');

  $map->show_grid = $showgrid;
  $map->save();
});

// -------------------------------------------------------------------------------------- //

$app->get('/editor/new', function () use ($app) {
  if (!$app->request()->isAjax()) {
    $app->halt(400);
  }

  $app->response->setBody('<form class="form-horizontal">
    <div class="form-group">
      <label for="map_name" class="col-lg-5 control-label">Nom</label>
      <div class="col-lg-5">
        <input type="text" class="form-control" id="map_name" required>
      </div>
    </div>

    <div class="form-group">
      <label for="map_width" class="col-lg-5 control-label">Largeur</label>
      <div class="col-lg-5">
        <input type="number" class="form-control" id="map_width" value="40" required>
      </div>
    </div>

    <div class="form-group">
      <label for="map_height" class="col-lg-5 control-label">Hauteur</label>
      <div class="col-lg-5">
        <input type="number" class="form-control" id="map_height" value="40" required>
      </div>
    </div>
  </form>

  <div class="alert alert-danger">Attention : la carte chargée actuellement perdras toutes les modifications apportées si elle n\'a pas été enregistrée.</div>');
});

// -------------------------------------------------------------------------------------- //

$app->post('/editor/new', function () use ($app) {
  if (!$app->request()->isAjax()) {
    $app->halt(400);
  }

  $map_name = $app->request()->post('map_name');
  $map_width = $app->request()->post('map_width');
  $map_height = $app->request()->post('map_height');

  $map = ORM::for_table('maps')->create();
  $map->name = $map_name;
  $map->width = $map_width;
  $map->height = $map_height;
  $map->save();

  $app->response->setBody($map->id);
});

// -------------------------------------------------------------------------------------- //

$app->get('/editor/:id', function ($id) use ($app) {
  $map = ORM::for_table('maps')->find_one($id);

  if (!$map) {
    $app->halt(404);
  }

  $map_computers = ORM::for_table('computers')->where('map_id', $id)->find_many();

  $app->render('editor.php', array('selected_map' => $map, 'map_computers' => $map_computers, 'active_nav_tab' => 'editer'));
});

// -------------------------------------------------------------------------------------- //

$app->run();
