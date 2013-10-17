<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="utf-8">
    <title>RailStatus</title>

    <link rel="shortcut icon" href="<?php echo ROOT ?>/img/favicon.ico" />

    <link href="<?php echo ROOT; ?>/css/bootstrap.min.css" rel="stylesheet" media="screen">
    <link href="<?php echo ROOT; ?>/css/font-awesome.min.css" rel="stylesheet" media="screen">
    <link href="<?php echo ROOT ?>/css/railstatus.css" rel="stylesheet">

    <script src="<?php echo ROOT; ?>/js/jquery.min.js"></script>
    <script src="<?php echo ROOT; ?>/js/bootstrap.min.js"></script>
    <script src="<?php echo ROOT ?>/js/railstatus.js"></script>

    <!--[if lt IE 9]>
    <script src="<?php echo ROOT ?>/js/html5shiv.js"></script>
    <script src="<?php echo ROOT ?>/js/respond.min.js"></script>
    <![endif]-->

    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
    /* Carrés de la carte */
    .block {
      width: <?php echo MAP_SQUARE_SIZE; ?>px;
      height: <?php echo MAP_SQUARE_SIZE; ?>px;
      cursor: crosshair;
    }

    <?php
    foreach (unserialize(AVAILABLE_BLOCKS) as $block_id => $block_infos) {
      echo '.block.'.$block_id.' { '.$block_infos['style'].' }'.PHP_EOL.PHP_EOL;
    }
    ?>

    .block.switch-unknow {
      background: #FF0000 url("<?php echo ROOT; ?>/img/unknow.png") center center no-repeat;
    }

    .block.switch-top {
      background: #FF0000 url("<?php echo ROOT; ?>/img/block_rail_arrow.png") center center no-repeat;
      -webkit-transform: rotate(90deg);
    }

    .block.switch-bottom {
      background: #FF0000 url("<?php echo ROOT; ?>/img/block_rail_arrow.png") center center no-repeat;
      -webkit-transform: rotate(-90deg);
    }

    .block.switch-left {
      background: #FF0000 url("<?php echo ROOT; ?>/img/block_rail_arrow.png") center center no-repeat;
    }

    .block.switch-right {
      background: #FF0000 url("<?php echo ROOT; ?>/img/block_rail_arrow.png") center center no-repeat;
      -webkit-transform: rotate(180deg);
    }
    </style>
  </head>
  <body>
    <div class="container">
      <div class="masthead">
        <h3 class="text-muted"><a href="<?php echo ROOT; ?>">RailStatus</a></h3>
        <ul class="nav nav-justified">
          <li <?php echo ($active_nav_tab == 'visualiser') ? 'class="active"' : ''; ?>><a href="<?php echo ROOT ?>/"><i class="icon-eye-open"></i> Visualiser</a></li>
          <li <?php echo ($active_nav_tab == 'editer') ? 'class="active"' : ''; ?>><a href="<?php echo ROOT ?>/editor"><i class="icon-pencil"></i> Editeur</a></li>
        </ul>
      </div>
      <?php
      if (isset($flash['alert_type']) and isset($flash['alert_message'])) {
        switch ($flash['alert_type']) {
          case 'success';
          case 'info';
          case 'warning';
          case 'danger';
            echo '<div class="alert alert-'.$flash['alert_type'].'"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>'.$flash['alert_message'].'</div>';
          break;
        }
      }
      ?>
      <div class="modal" id="modal_dialog" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
              <h4 class="modal-title"></h4>
            </div>
            <div class="modal-body">

            </div>
            <div class="modal-footer">

            </div>
          </div>
        </div>
      </div>