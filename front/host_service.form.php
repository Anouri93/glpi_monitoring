<?php

/*
   ----------------------------------------------------------------------
   Monitoring plugin for GLPI
   Copyright (C) 2010-2011 by the GLPI plugin monitoring Team.

   https://forge.indepnet.net/projects/monitoring/
   ----------------------------------------------------------------------

   LICENSE

   This file is part of Monitoring plugin for GLPI.

   Monitoring plugin for GLPI is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 2 of the License, or
   any later version.

   Monitoring plugin for GLPI is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with Monitoring plugin for GLPI.  If not, see <http://www.gnu.org/licenses/>.

   ------------------------------------------------------------------------
   Original Author of file: David DURIEUX
   Co-authors of file:
   Purpose of file:
   ----------------------------------------------------------------------
 */

define('GLPI_ROOT', '../../..');

include (GLPI_ROOT . "/inc/includes.php");

commonHeader($LANG['plugin_monitoring']['title'][0],$_SERVER["PHP_SELF"], "plugins",
             "monitoring", "host_service");


$pMonitoringHost_Service = new PluginMonitoringHost_Service();

if (isset($_POST['update'])) {
   foreach ($_POST['id'] as $key=>$id) {
      $input = array();
      $input['id'] = $id;
      $input['plugin_monitoring_services_id'] = $_POST['plugin_monitoring_services_id'][$key];
      $a_arguments = array();
      foreach ($_POST as $key=>$value) {
         if (strstr($key, "arg".$id."||")) {
            $a_ex = explode("||", $key);
            $a_arguments[$a_ex[1]] = $value;
         }
      }
      $input['arguments'] = exportArrayToDB($a_arguments);
      $pMonitoringHost_Service->update($input);
   }
}
glpi_header($_SERVER['HTTP_REFERER']);

commonFooter();

?>