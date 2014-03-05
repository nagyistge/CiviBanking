<?php
/*
    org.project60.banking extension for CiviCRM

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/


require_once 'CRM/Banking/Helpers/OptionValue.php';

/**
 * This matcher use regular expressions to extract information from the payment meta information
 */
class CRM_Banking_PluginImpl_Matcher_RegexAnalyser extends CRM_Banking_PluginModel_Matcher {

  /**
   * class constructor
   */ 
  function __construct($config_name) {
    parent::__construct($config_name);

    // read config, set defaults
    $config = $this->_plugin_config;
    if (!isset($config->rules)) $config->rules = array();
  }

  /** 
   * this matcher does not really create suggestions, but rather enriches the parsed data
   */
  public function match(CRM_Banking_BAO_BankTransaction $btx, CRM_Banking_Matcher_Context $context) {
    $config = $this->_plugin_config;
    $data_parsed = $btx->getDataParsed();

    // itreate trough all rules
    foreach ($this->_plugin_config->rules as $rule) {
      if (empty($rule->fields)) {
        $fields = array('purpose');
      } else {
        $fields = $rule->fields;
      }

      // appy rule to all the fields listed...
      foreach ($fields as $field) {
        if (isset($data_parsed[$field])) {
          $field_data = $data_parsed[$field];
          $pattern = $rule->pattern;
          $matches = array();

          // match the pattern on the given field data
          $match_count = preg_match_all($pattern, $field_data, $matches);

          // and execute the actions for each match...
          for ($i=0; $i < $match_count; $i++) {
            $this->processMatch($matches, $i, $data_parsed, $rule);
          }
        }
      }
    }

    // save changes and that's it
    $btx->setDataParsed($data_parsed);
    return null;
  }

  /** 
   * execute all the action defined by the rule to the given match
   */
  function processMatch($match_data, $match_index, &$data_parsed, $rule) {
    foreach ($rule->actions as $action) {
      if ($action->action=='copy') {
        // COPY value from match group to parsed data
        //error_log($action->to." is set to ".$match_data[$action->from][$match_index]);
        $data_parsed[$action->to] = $match_data[$action->from][$match_index];
      } else {
        error_log("org.project60.banking: RegexAnalyser - bad action: '".$action->action."'");
      }
    }
  }
}

