<?php
/*
 * @version $Id: computer.tabs.php 14684 2011-06-11 06:32:40Z remi $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

define('GLPI_ROOT', '../../..');
include (GLPI_ROOT . "/inc/includes.php");

header("Content-Type: text/html; charset=UTF-8");
header_nocache();

if (!isset($_POST["id"])) {
   exit();
}
if (!isset($_POST["sort"])) {
   $_POST["sort"] = "";
}
if (!isset($_POST["order"])) {
   $_POST["order"] = "";
}
if (!isset($_POST["withtemplate"])) {
   $_POST["withtemplate"] = "";
}


$pluginMonitoringComponentscatalog = new PluginMonitoringComponentscatalog();

if ($_POST["id"]>0 && $pluginMonitoringComponentscatalog->can($_POST["id"],'r')) {

   switch($_POST['glpi_tab']) {
      case -1 :

         break;

      case 1:
         $pmComponentscatalog_Component = new PluginMonitoringComponentscatalog_Component();
         $pmComponentscatalog_Component->showComponents($_POST['id']);         
         break;
      
      case 2 :
         $pmComponentscatalog_Host = new PluginMonitoringComponentscatalog_Host();
         $pmComponentscatalog_Host->showStatichosts($_POST['id']);
         break;
      
      case 3 :
         /*
          * Rules
          */
         
         /*
          * 
          *
          *
          */

         break;
      
      case 4 :

         break;
      
      case 5 : 
         $pmContact_Item = new PluginMonitoringContact_Item();
         $pmContact_Item->showContacts("PluginMonitoringComponentscatalog", $_POST['id']);
         break;

      default :

   }
}

ajaxFooter();

?>
