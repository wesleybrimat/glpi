<?php
/*
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

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

/** @file
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}


/**
 * NotificationTargetChange Class
 *
 * @since version 0.85
**/
class NotificationTargetChange extends NotificationTargetCommonITILObject {

   public $private_profiles = array();

   public $html_tags        = array('##change.solution.description##');


   /**
    * Get events related to tickets
   **/
   function getEvents() {

      $events = array('new'               => __('New change'),
                      'update'            => __('Update of a change'),
                      'solved'            => __('Change solved'),
                      'validation'        => __('Validation request'),
                      'validation_answer' => __('Validation request answer'),
                      'add_task'          => __('New task'),
                      'update_task'       => __('Update of a task'),
                      'delete_task'       => __('Deletion of a task'),
                      'closed'            => __('Closure of a change'),
                      'delete'            => __('Deleting a change'));
      asort($events);
      return $events;
   }


   /**
    * @see NotificationTargetCommonITILObject::getDatasForObject()
   **/
   function getDatasForObject(CommonDBTM $item, array $options, $simple=false) {
      global $CFG_GLPI;

      // Common ITIL datas
      $datas                         = parent::getDatasForObject($item, $options, $simple);

      // Specific datas
      $datas['##change.urlvalidation##']
                     = $this->formatURL($options['additionnaloption']['usertype'],
                                        "change_".$item->getField("id")."_ChangeValidation$1");
      $datas['##change.globalvalidation##']
                     = ChangeValidation::getStatus($item->getField('global_validation'));

      $datas['##change.impactcontent##']      = $item->getField("impactcontent");
      $datas['##change.controlistcontent##']  = $item->getField("controlistcontent");
      $datas['##change.rolloutplancontent##'] = $item->getField("rolloutplancontent");
      $datas['##change.backoutplancontent##'] = $item->getField("backoutplancontent");
      $datas['##change.checklistcontent##']   = $item->getField("checklistcontent");

      // $datas["##problem.impacts##"]  = $item->getField('impactcontent');
      // $datas["##problem.causes##"]   = $item->getField('causecontent');
      // $datas["##problem.symptoms##"] = $item->getField('symptomcontent');

      // Complex mode
      if (!$simple) {
         $restrict = "`changes_id`='".$item->getField('id')."'";
         $tickets  = getAllDatasFromTable('glpi_changes_tickets', $restrict);

         $datas['tickets'] = array();
         if (count($tickets)) {
            $ticket = new Ticket();
            foreach ($tickets as $data) {
               if ($ticket->getFromDB($data['tickets_id'])) {
                  $tmp = array();
                  $tmp['##ticket.id##']      = $data['tickets_id'];
                  $tmp['##ticket.date##']    = $ticket->getField('date');
                  $tmp['##ticket.title##']   = $ticket->getField('name');
                  $tmp['##ticket.url##']     = $this->formatURL($options['additionnaloption']['usertype'],
                                                                "Ticket_".$data['tickets_id']);
                  $tmp['##ticket.content##'] = $ticket->getField('content');

                  $datas['tickets'][] = $tmp;
               }
            }
         }

         $datas['##change.numberoftickets##'] = count($datas['tickets']);

         $restrict = "`changes_id`='".$item->getField('id')."'";
         $problems = getAllDatasFromTable('glpi_changes_problems', $restrict);

         $datas['problems'] = array();
         if (count($problems)) {
            $problem = new Problem();
            foreach ($problems as $data) {
               if ($problem->getFromDB($data['problems_id'])) {
                  $tmp = array();
                  $tmp['##problem.id##']
                                       = $data['problems_id'];
                  $tmp['##problem.date##']
                                       = $problem->getField('date');
                  $tmp['##problem.title##']
                                       = $problem->getField('name');
                  $tmp['##problem.url##']
                                       = $this->formatURL($options['additionnaloption']['usertype'],
                                                          "Problem_".$data['problems_id']);
                  $tmp['##problem.content##']
                                       = $problem->getField('content');

                  $datas['problems'][] = $tmp;
               }
            }
         }

         $datas['##change.numberofproblems##'] = count($datas['problems']);

         $restrict = "`changes_id` = '".$item->getField('id')."'";
         $items    = getAllDatasFromTable('glpi_changes_items', $restrict);

         $datas['items'] = array();
         if (count($items)) {
            foreach ($items as $data) {
               if ($item2 = getItemForItemtype($data['itemtype'])) {
                  if ($item2->getFromDB($data['items_id'])) {
                     $tmp = array();
                     $tmp['##item.itemtype##']    = $item2->getTypeName();
                     $tmp['##item.name##']        = $item2->getField('name');
                     $tmp['##item.serial##']      = $item2->getField('serial');
                     $tmp['##item.otherserial##'] = $item2->getField('otherserial');
                     $tmp['##item.contact##']     = $item2->getField('contact');
                     $tmp['##item.contactnum##']  = $item2->getField('contactnum');
                     $tmp['##item.location##']    = '';
                     $tmp['##item.user##']        = '';
                     $tmp['##item.group##']       = '';
                     $tmp['##item.model##']       = '';

                     //Object location
                     if ($item2->getField('locations_id') != NOT_AVAILABLE) {
                        $tmp['##item.location##']
                               = Dropdown::getDropdownName('glpi_locations',
                                                           $item2->getField('locations_id'));
                     }

                     //Object user
                     if ($item2->getField('users_id')) {
                        $user_tmp = new User();
                        if ($user_tmp->getFromDB($item2->getField('users_id'))) {
                           $tmp['##item.user##'] = $user_tmp->getName();
                        }
                     }

                     //Object group
                     if ($item2->getField('groups_id')) {
                        $tmp['##item.group##']
                                       = Dropdown::getDropdownName('glpi_groups',
                                                                   $item2->getField('groups_id'));
                     }

                     $modeltable = getSingular($item2->getTable())."models";
                     $modelfield = getForeignKeyFieldForTable($modeltable);

                     if ($item2->isField($modelfield)) {
                        $tmp['##item.model##'] = $item2->getField($modelfield);
                     }

                     $datas['items'][] = $tmp;
                  }
               }
            }
         }

         $datas['##change.numberofitems##'] = count($datas['items']);

         //Validation infos
         $restrict = "`changes_id`='".$item->getField('id')."'";

         if (isset($options['validation_id']) && $options['validation_id']) {
            $restrict .= " AND `glpi_changevalidations`.`id` = '".$options['validation_id']."'";
         }

         $restrict .= " ORDER BY `submission_date` DESC, `id` ASC";

         $validations = getAllDatasFromTable('glpi_changevalidations', $restrict);
         $datas['validations'] = array();
         foreach ($validations as $validation) {
            $tmp = array();
            $tmp['##validation.submission.title##']
                                 //TRANS: %s is the user name
                     = sprintf(__('An approval request has been submitted by %s'),
                                  Html::clean(getUserName($validation['users_id'])));

            $tmp['##validation.answer.title##']
                                 //TRANS: %s is the user name
                     = sprintf(__('An answer to an an approval request was produced by %s'),
                                  Html::clean(getUserName($validation['users_id_validate'])));

            $tmp['##validation.author##']
                     = Html::clean(getUserName($validation['users_id']));

            $tmp['##validation.status##']
                     = TicketValidation::getStatus($validation['status']);

            $tmp['##validation.storestatus##']
                     = $validation['status'];

            $tmp['##validation.submissiondate##']
                     = Html::convDateTime($validation['submission_date']);

            $tmp['##validation.commentsubmission##']
                     = $validation['comment_submission'];

            $tmp['##validation.validationdate##']
                     = Html::convDateTime($validation['validation_date']);

            $tmp['##validation.validator##']
                     =  Html::clean(getUserName($validation['users_id_validate']));

            $tmp['##validation.commentvalidation##']
                     = $validation['comment_validation'];

            $datas['validations'][] = $tmp;
         }

      }
      return $datas;
   }


   function getTags() {

      parent::getTags();

      //Locales
      $tags = array('change.numberoftickets'    => _x('quantity', 'Number of tickets'),
                    'change.numberofproblems'   => _x('quantity', 'Number of problems'),
                    'change.impactcontent'      => __('Impact'),
                    'change.controlistcontent'  => __('Control list'),
                    'change.rolloutplancontent' => __('Deployment plan'),
                    'change.backoutplancontent' => __('Backup plan'),
                    'change.checklistcontent'   => __('Checklist'),
                    // 'problem.impacts'           => __('Impacts'),
                    // 'problem.causes'            => __('Causes'),
                    // 'problem.symptoms'          => __('Symptoms'),
                    'item.name'                 => __('Associated item'),
                    'item.serial'               => __('Serial number'),
                    'item.otherserial'          => __('Inventory number'),
                    'item.location'             => __('Location'),
                    'item.model'                => __('Model'),
                    'item.contact'              => __('Alternate username'),
                    'item.contactnumber'        => __('Alternate username number'),
                    'item.user'                 => __('User'),
                    'item.group'                => __('Group'),
                    'change.globalvalidation'   => __('Global approval status'),);

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'    => $tag,
                                   'label'  => $label,
                                   'value'  => true,
                                   'events' => NotificationTarget::TAG_FOR_ALL_EVENTS));
      }

      //Events specific for validation
      $tags = array('validation.author'            => __('Requester'),
                    'validation.status'            => __('Status of the approval request'),
                    'validation.submissiondate'    => sprintf(__('%1$s: %2$s'), __('Request'),
                                                              __('Date')),
                    'validation.commentsubmission' => sprintf(__('%1$s: %2$s'), __('Request'),
                                                              __('Comments')),
                    'validation.validationdate'    => sprintf(__('%1$s: %2$s'), __('Validation'),
                                                             __('Date')),
                    'validation.validator'         => __('Decision-maker'),
                    'validation.commentvalidation' => sprintf(__('%1$s: %2$s'), __('Validation'),
                                                             __('Comments'))
      );

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'    => $tag,
                                   'label'  => $label,
                                   'value'  => true,
                                   'events' => array('validation', 'validation_answer')));
      }

      //Tags without lang for validation
      $tags = array('validation.submission.title'
                                    => __('A validation request has been submitted'),
                    'validation.answer.title'
                                    => __('An answer to a validation request was produced'),
                    'change.urlvalidation'
                                    => sprintf(__('%1$s: %2$s'), __('Validation request'),
                                               __('URL')));

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'   => $tag,
                                   'label' => $label,
                                   'value' => true,
                                   'lang'  => false,
                                   'events' => array('validation', 'validation_answer')));
      }

      //Foreach global tags
      $tags = array('tickets'     => _n('Ticket', 'Tickets', Session::getPluralNumber()),
                    'problems'    => _n('Problem', 'Problems', Session::getPluralNumber()),
                    'items'       => _n('Item', 'Items', Session::getPluralNumber()),
                    'validations' => _n('Validation', 'Validations', Session::getPluralNumber()),
                    'documents'   => _n('Document', 'Documents', Session::getPluralNumber()));

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'     => $tag,
                                   'label'   => $label,
                                   'value'   => false,
                                   'foreach' => true));
      }

      //Tags with just lang
      $tags = array('change.tickets'   => _n('Ticket', 'Tickets', Session::getPluralNumber()),
                    'change.problems'  => _n('Problem', 'Problems', Session::getPluralNumber()),
                    'items'            => _n('Item', 'Items', Session::getPluralNumber()));

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'   => $tag,
                                   'label' => $label,
                                   'value' => false,
                                   'lang'  => true));
      }

      //Tags without lang
      $tags = array('ticket.id'       => sprintf(__('%1$s: %2$s'), __('Ticket'), __('ID')),
                    'ticket.date'     => sprintf(__('%1$s: %2$s'), __('Ticket'), __('Date')),
                    'ticket.url'      => sprintf(__('%1$s: %2$s'), __('Ticket'), __('URL')),
                    'ticket.title'    => sprintf(__('%1$s: %2$s'), __('Ticket'), __('Title')),
                    'ticket.content'  => sprintf(__('%1$s: %2$s'), __('Ticket'), __('Description')),
                    'problem.id'      => sprintf(__('%1$s: %2$s'), __('Problem'), __('ID')),
                    'problem.date'    => sprintf(__('%1$s: %2$s'), __('Problem'), __('Date')),
                    'problem.url'     => sprintf(__('%1$s: %2$s'), __('Problem'), __('URL')),
                    'problem.title'   => sprintf(__('%1$s: %2$s'), __('Problem'), __('Title')),
                    'problem.content' => sprintf(__('%1$s: %2$s'), __('Problem'), __('Description')),
                    );

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'   => $tag,
                                   'label' => $label,
                                   'value' => true,
                                   'lang'  => false));
      }
      asort($this->tag_descriptions);
   }

}
