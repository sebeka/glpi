<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2012 by the INDEPNET Development Team.

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
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Olivier Andreotti
// Purpose of file:
// ----------------------------------------------------------------------
if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// LDAP criteria class
class NotImportedEmail extends CommonDBTM {

   const MATCH_NO_RULE = 0;
   const USER_UNKNOWN  = 1;
   const FAILED_INSERT = 2;

   function getForbiddenStandardMassiveAction() {
      $forbidden = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'delete';
      $forbidden[] = 'purge';
      $forbidden[] = 'restore';
      return $forbidden;
   }


   function canCreate() {
      return Session::haveRight('config', 'w');
   }


   function canView() {
      return Session::haveRight('config', 'w');
   }


   static function getTypeName($nb=0) {
      return _n('Refused email', 'Refused emails', $nb);
   }

   function getSpecificMassiveActions($checkitem=NULL) {
      $isadmin = $this->canUpdate();
      $actions = parent::getSpecificMassiveActions($checkitem);

      if ($isadmin) {
         $actions['delete_email'] = __('Delete emails');
         $actions['import_email'] = _x('button', 'Import');
      }
      return $actions;
   }
   
   function showSpecificMassiveActionsParameters($input = array()) {
      switch ($input['action']) {
         case "import_email" :
            Entity::dropdown();
            echo "<br><br><input type='submit' name='massiveaction' class='submit' value='".
                           _sx('button', 'Import')."'>";
            return true;
            break;

         default :
            return parent::showSpecificMassiveActionsParameters($input);
            break;

      }
      return false;
   }   
   function doSpecificMassiveActions($input = array()) {
      $res = array('ok'      => 0,
                   'ko'      => 0,
                   'noright' => 0);
      switch ($input['action']) {
         case 'delete_email' :
         case 'import_email' :
            if (!$this->canCreate()) {
               $res['noright']++;
            } else {
               $emails_ids = array();
               foreach ($input["item"] as $key => $val) {
                  if ($val == 1) {
                     $emails_ids[$key] = $key;
                  }
               }
               if (count($emails_ids)) {
                  $mailcollector = new MailCollector();
                  if ($input["action"] == 'delete_email') {
                     $mailcollector->deleteOrImportSeveralEmails($emails_ids, 0);
                  }
                  else {
                     $mailcollector->deleteOrImportSeveralEmails($emails_ids, 1, $input['entities_id']);
                  }
               }
               $res['ok']++;
            }
            break;
         default :
            return parent::doSpecificMassiveActions($input);
      }
      return $res;
   }

   function getSearchOptions() {

      $tab                       = array();

      $tab[1]['table']           = 'glpi_notimportedemails';
      $tab[1]['field']           = 'from';
      $tab[1]['name']            = __('From email header');
      $tab[1]['massiveaction']   = false;

      $tab[2]['table']           = 'glpi_notimportedemails';
      $tab[2]['field']           = 'to';
      $tab[2]['name']            = __('To email header');
      $tab[2]['massiveaction']   = false;

      $tab[3]['table']           = 'glpi_notimportedemails';
      $tab[3]['field']           = 'subject';
      $tab[3]['name']            = __('Subject email header');
      $tab[3]['massiveaction']   = false;

      $tab[4]['table']           = 'glpi_mailcollectors';
      $tab[4]['field']           = 'name';
      $tab[4]['name']            = __('Mails receiver');
      $tab[4]['datatype']        = 'itemlink';

      $tab[5]['table']           = 'glpi_notimportedemails';
      $tab[5]['field']           = 'messageid';
      $tab[5]['name']            = __('Message-ID email header');
      $tab[5]['massiveaction']   = false;

      $tab[6]['table']           = 'glpi_users';
      $tab[6]['field']           = 'name';
      $tab[6]['name']            = __('Requester');
      $tab[6]['datatype']        = 'dropdown';
      $tab[6]['right']           = 'all';
      

      $tab[16]['table']          = 'glpi_notimportedemails';
      $tab[16]['field']          = 'reason';
      $tab[16]['name']           = __('Reason of rejection');
      $tab[16]['datatype']       = 'text';
      $tab[16]['massiveaction']  = false;

      $tab[19]['table']          = 'glpi_notimportedemails';
      $tab[19]['field']          = 'date';
      $tab[19]['name']           = __('Date');
      $tab[19]['datatype']       = 'datetime';
      $tab[19]['massiveaction']  = false;

      return $tab;
   }


   static function deleteLog() {
      global $DB;

      $query = "TRUNCATE `glpi_notimportedemails`";
      $DB->query($query);
   }


   /**
    * @param $reason_id
   **/
   static function getReason($reason_id) {

      switch ($reason_id) {
         case self::MATCH_NO_RULE :
            return __('Unable to affect the email to an entity');

         case self::USER_UNKNOWN :
            return __('Email not found. Impossible import');

         case self::FAILED_INSERT :
            return __('Failed operation');

         default :
            return '';
      }
   }

}
?>
