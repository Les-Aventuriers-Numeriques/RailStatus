<?php
class RailStatusView extends \Slim\View {
  public function render($template) {
    $return = parent::render('global/header.php');
    $return .= parent::render($template);
    $return .= parent::render('global/footer.php');
    
    return $return;
  }
}