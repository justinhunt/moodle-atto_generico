<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Atto text editor integration version file.
 *
 * @package    atto_generico
 * @copyright  COPYRIGHTINFO
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Initialise this plugin
 * @param string $elementid
 */
function atto_generico_strings_for_js() {
    global $PAGE;

    $PAGE->requires->strings_for_js(array('insert',
                                          'cancel',
                                          'chooseinsert',
                                          'fieldsheader',
                                          'nofieldsheader',
                                          'dialogtitle'),
                                    'atto_generico');
}

/**
 * Return the js params required for this module.
 * @return array of additional params to pass to javascript init function for this module.
 */
function atto_generico_params_for_js($elementid, $options, $fpoptions) {
	global $USER, $COURSE;
	
	//coursecontext
	$coursecontext=context_course::instance($COURSE->id);	
	
	//generico specific
	$templates = get_object_vars(get_config('filter_generico'));
	
	$keys = array();
	$variables = array();
	$defaults = array();
	
	//put our template into a form thats easy to process in JS
	for($tempindex=1;$tempindex<21;$tempindex++){
			if(empty($templates['template_' . $tempindex])){
				continue;
			}
		
			//stash the key for this tempalte
			$keys[] = $templates['templatekey_' . $tempindex];
			
			//NB each of the $allvariables contains an array of variables (not a string) 
			//there might be duplicates where the variable is used multiple times in a template
			//se we uniqu'ify it. That makes it look complicated. But we are just removing doubles
			$allvariables = atto_generico_fetch_variables($templates['template_' . $tempindex] . $templates['templatescript_' . $tempindex] );
			$uniquevariables = array_unique($allvariables);
			$usevariables=array();
			
			//we need to reallocate array keys if the array size was changed in unique'ifying it
			//we also take the opportunity to remove user variables, since they aren't needed here.
			while(count($uniquevariables)>0){
					$tempvar = array_shift($uniquevariables);
					if(strpos($tempvar, 'USER:')===false && $tempvar!='AUTOID'){
						$usevariables[] = $tempvar;
					}
			}
			$variables[] = $usevariables;
			
			//stash the defaults for this template
			//$defaults[] = $templates['templatedefaults_' . $tempindex];
			$defaults[] = atto_generico_fetch_filter_properties($templates['templatedefaults_' . $tempindex]);
			
			$ends[] = $templates['templateend_' . $tempindex];
	}
	

	
	//config our array of data
	$params = array();
	$params['keys'] = $keys;
	$params['variables'] = $variables;
	$params['defaults'] = $defaults;
	$params['ends'] = $ends;

	//If they don't have permission don't show it
	$disabled = false;
	if(!has_capability('atto/generico:visible', $coursecontext) ){
		$disabled=true;
	 }
	
	//add our disabled param
	$params['disabled'] = $disabled;


    return $params;
}

/**
 * Return an array of variable names
 * @param string template containing @@variable@@ variables 
 * @return array of variable names parsed from template string
 */
function atto_generico_fetch_variables($template){
	$matches = array();
	$t = preg_match_all('/@@(.*?)@@/s', $template, $matches);
	if(count($matches)>1){
		return($matches[1]);
	}else{
		return array();
	}
}

function atto_generico_fetch_filter_properties($propstring){
	//Now we just have our properties string
	//Lets run our regular expression over them
	//string should be property=value,property=value
	//got this regexp from http://stackoverflow.com/questions/168171/regular-expression-for-parsing-name-value-pairs
	$regexpression='/([^=,]*)=("[^"]*"|[^,"]*)/';
	$matches; 	

	//here we match the filter string and split into name array (matches[1]) and value array (matches[2])
	//we then add those to a name value array.
	$itemprops = array();
	if (preg_match_all($regexpression, $propstring,$matches,PREG_PATTERN_ORDER)){		
		$propscount = count($matches[1]);
		for ($cnt =0; $cnt < $propscount; $cnt++){
			// echo $matches[1][$cnt] . "=" . $matches[2][$cnt] . " ";
			$newvalue = $matches[2][$cnt];
			//this could be done better, I am sure. WE are removing the quotes from start and end
			//this wil however remove multiple quotes id they exist at start and end. NG really
			$newvalue = trim($newvalue,'"');
			$itemprops[trim($matches[1][$cnt])]=$newvalue;
		}
	}
	return $itemprops;
}

