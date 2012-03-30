<?php


/*
   ------------------------------------------------------------------------
   Plugin Monitoring for GLPI
   Copyright (C) 2011-2012 by the Plugin Monitoring for GLPI Development Team.

   https://forge.indepnet.net/projects/monitoring/
   ------------------------------------------------------------------------

   LICENSE

   This file is part of Plugin Monitoring project.

   Plugin Monitoring for GLPI is free software: you can redistribute it and/or modify
   it under the terms of the GNU Affero General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   Plugin Monitoring for GLPI is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
   GNU Affero General Public License for more details.

   You should have received a copy of the GNU Affero General Public License
   along with Behaviors. If not, see <http://www.gnu.org/licenses/>.

   ------------------------------------------------------------------------

   @package   Plugin Monitoring for GLPI
   @author    David Durieux
   @co-author 
   @comment   
   @copyright Copyright (c) 2011-2012 Plugin Monitoring for GLPI team
   @license   AGPL License 3.0 or (at your option) any later version
              http://www.gnu.org/licenses/agpl-3.0-standalone.html
   @link      https://forge.indepnet.net/projects/monitoring/
   @since     2012
 
   ------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginMonitoringCanvas {
   private $a_points             = array();
   private $a_devices            = array();
   private $a_devices_link       = array();
   private $a_networkequipments2 = array();

   
   
   static function onload() {
      return 'canvas();';
   }

   
   
   function show() {
      global $DB;

      $networkPort = new NetworkPort();
      $computer = new Computer();
      $pmHostconfig = new PluginMonitoringHostconfig();
      
      // Shinken server
      
      $source_id = $pmHostconfig->getValueAncestor('computers_id', '0');
      $switches_id = 0;
      $this->a_devices['Computer-'.$source_id] = "SHINKEN";
      $a_networkports = $networkPort->find("`itemtype`='Computer'
         AND `items_id`='".$source_id."'");
      foreach ($a_networkports as $data_n) {
         $networkports_id = $networkPort->getContact($data_n['id']);
         if ($networkports_id) {
            $networkPort->getFromDB($networkports_id);
            if ($networkPort->fields['itemtype'] == 'NetworkEquipment') {
               $this->a_devices['NetworkEquipment-'.$networkPort->fields['items_id']] = $this->getState('NetworkEquipment', $networkPort->fields['items_id']);
               $this->a_devices_link['NetworkEquipment-'.$networkPort->fields['items_id']]['Computer-'.$source_id]=1;
               $this->getNetworkEquipments($networkPort->fields['items_id']);
               $switches_id = $networkPort->fields['items_id'];
            }
         }
      }
      $this->addPoint('NetworkEquipment-'.$switches_id);
      $computer->getFromDB($source_id);
      $this->drawCanvas($computer, array(), array());
   } 
      
      
   function getNetworkEquipments($networkequipments_id) {
      $networkPort = new NetworkPort();
      
      $a_networkports = $networkPort->find("`itemtype`='NetworkEquipment'
         AND `items_id`='".$networkequipments_id."'");
      foreach ($a_networkports as $data_n) {
         $networkports_id = $networkPort->getContact($data_n['id']);
         if ($networkports_id) {
            $networkPort->getFromDB($networkports_id);
            switch ($networkPort->fields['itemtype']) {
               
               case 'NetworkEquipment':
                  if (!isset($this->a_devices['NetworkEquipment-'.$networkPort->fields['items_id']])) {
                     $this->a_devices['NetworkEquipment-'.$networkPort->fields['items_id']] = $this->getState('NetworkEquipment', $networkPort->fields['items_id']);
                     $this->a_devices_link['NetworkEquipment-'.$networkPort->fields['items_id']]['NetworkEquipment-'.$networkequipments_id]=1;
                     $this->getNetworkEquipments($networkPort->fields['items_id']);
                  }
                  break;
               
               case 'Computer':
               case 'Printer':
                  if (!isset($this->a_devices[$networkPort->fields['itemtype'].'-'.$networkPort->fields['items_id']])) {
                     $this->a_devices[$networkPort->fields['itemtype'].'-'.$networkPort->fields['items_id']] = $this->getState($networkPort->fields['itemtype'], $networkPort->fields['items_id']);
                     $this->a_devices_link['NetworkEquipment-'.$networkequipments_id][$networkPort->fields['itemtype'].'-'.$networkPort->fields['items_id']]=1;
                  }
                  break;

            }
         }
      }
   }   

   
   
   function getState($itemtype, $items_id) {
      global $DB;
      
      $state = 'ok';
      $critical = 0;
      $warning = 0;
      $query = "SELECT * FROM `glpi_plugin_monitoring_componentscatalogs_hosts`
         WHERE `itemtype`='".$itemtype."'
            AND `items_id`='".$items_id."'";
      $result = $DB->query($query);
      while ($data=$DB->fetch_array($result)) {
         $critical += countElementsInTable("glpi_plugin_monitoring_services", 
           "(`state`='DOWN' OR `state`='UNREACHABLE' OR `state`='CRITICAL' OR `state`='DOWNTIME')
              AND `state_type`='HARD'
              AND `plugin_monitoring_componentscatalogs_hosts_id`='".$data['id']."'");

         $warning += countElementsInTable("glpi_plugin_monitoring_services", 
           "(`state`='WARNING' OR `state`='UNKNOWN' OR `state`='RECOVERY' OR `state`='FLAPPING' OR `state` IS NULL)
           AND `state_type`='HARD'
              AND `plugin_monitoring_componentscatalogs_hosts_id`='".$data['id']."'");
      }
      if ($critical > 0) {
         $state = 'critical';
      } else if ($warning > 0) {
         $state = 'warning';
      }
      return $state;
   }

   
   function addPoint($itemdata, $ancestor = 0,$state = 'no') {
      $split = explode("-", $itemdata);
      $itemtype = $split[0];
      $item = new $itemtype();
      $item->getFromDB($split[1]);
      $monitored = 0;
      if (isset($this->a_devices[$itemdata])) {
         $monitored = 1;
         $state = $this->a_devices[$itemdata];
      }
      $this->a_points[$itemdata] = array('canvas_id' => $itemdata,
                                         'name' => $item->fields['name'],
                                         'ancestors_id' => array($ancestor),
                                         'id' => $item->fields['id'],
                                         'monitored' => $monitored,
                                         'state' => $state);
      // Case this device is a switch
      if ($itemtype == 'NetworkEquipment') {
         if (isset($this->a_devices_link[$itemdata])) {
            foreach ($this->a_devices_link[$itemdata] as $itemdatanext => $state) {
//               if (isset($this->a_devices_link[$itemdata][$itemdatanext])) {
                  if (strstr($itemdatanext, 'NetworkEquipment')) {
//                     unset($this->a_devices_link[$itemdatanext][$itemdata]);
                  }

                  if (!isset($this->a_points[$itemdatanext])) {
                     $this->addPoint($itemdatanext, $itemdata, $state);
                  } else {
                     if (!in_array($itemdata, $this->a_points[$itemdatanext]['ancestors_id'])) {
                        array_push($this->a_points[$itemdatanext]['ancestors_id'], $itemdata);
                     }
                  }
//               }
            }
         }
      }
   }

   
   
   private function drawCanvas($root, $ancestors, $params) {
      global $LANG,$CFG_GLPI;

      $link= array();
      $in_link = array();
      foreach ($this->a_devices_link as $id1=>$array) {
         foreach ($array as $id2=>$num) {
            $input = array();
            $input['id1'] = "i".$id1;
            $input['id2'] = "i".$id2;
//            $input['value'] = 1;
            $input['color'] = 'rgb(51,12,255)';
            $input['width'] = 3;
            $input['type'] = 'line';
            $link['edges'][] = $input;
            
            $in_link[] = $id2;
         }
         $in_link[] = $id1;
      }
      
      foreach ($this->a_devices as $itemdata => $state) {
         
         $split = explode("-", $itemdata);
         $itemtype = $split[0];
         $item = new $itemtype();
         $item->getFromDB($split[1]);
         
         $input = array();
         $input['id'] = "i".$itemdata;
         $input['name'] = $item->getName();
         $input['shape'] = 'square';
//            $pics = array('computer', 'laptop', 'printer', 'server');
//            $input['shape'] = 'image';
//            $input['imagePath'] = 'http://'.$_SERVER['SERVER_ADDR'].$CFG_GLPI['root_doc'].
//                    '/plugins/monitoring/pics/'.$pics[rand(0, 3)].'.png';
         $input['color'] = 'rgb(130,130,130)';

         switch ($state) {
            
            case 'critical':
               $input['color'] = 'rgb(255,0,0)';
               break;

            case 'warning':
               $input['color'] = 'rgb(255,187,0)';
               break;
            
            case 'ok':
               $input['color'] = 'rgb(0,255,0)';
               break;
            
            case 'SHINKEN':
               $input['shape'] = 'image';
               $input['imagePath'] = 'http://'.$_SERVER['SERVER_ADDR'].$CFG_GLPI['root_doc'].'/plugins/monitoring/pics/shinken.png';
               $input['width'] = '120';
               $input['height'] = '27';
               break;
            
         }
         $input['group'] = 0;

         $input['items_id'] = $split[1];
         
         if (in_array($itemdata, $in_link)) {
            $link['nodes'][] = $input;
         }
      }

      if (count($link['nodes']) > 0) {
         $canvas_config = array('graphType' => 'Network',
                                 'indicatorCenter' => 'rainbow',
                                 'layoutTime' => 30,
                                 'maxIterations' => 80,
                                 'gradient' => true,
                                 'backgroundGradient2Color' => 'rgb(242,242,242)',
                                 'backgroundGradient1Color' => 'rgb(242,242,242)',
                                 'nodeFontColor' => 'rgb(29,34,43)',
                                 'showAnimation' => true);

         $link_to_form = $root->getFormURL();
         $link_to_form .= (strpos($link_to_form,'?') ? '&amp;':'?').'id=';
         
         echo "<script>
var showcanvas = function () {
new CanvasXpress(
   'canvas',
   ".json_encode($link).",
   ".json_encode($canvas_config).", {
            click: function(obj) {
               if (obj.nodes) {
                  var items_id=obj.nodes[0].items_id;
                  window.location.href='$link_to_form'+items_id;
               } else {
                  var root_id=obj.edges[0].root_id;
                  window.location.href='".$_SERVER['PHP_SELF']."?root_id='+root_id;
               }
           }
    });
}
</script>";

         echo "<table class='tab_cadre_fixe'>";
         
         echo "<tr class='tab_bg_1'>";
         echo "<th>";
         echo $LANG['plugin_monitoring']['dependency'][0];
         echo "</th>";
         echo "</tr>";
         
         echo "<tr class='tab_bg_1'>";
         echo "<td>";
         echo "<canvas id='canvas' width='950' height='500'></canvas>";
         echo "</td>";
         echo "</tr>";
         echo "</table>";

         echo "<script>showcanvas();</script>";
      }
   }
}

?>