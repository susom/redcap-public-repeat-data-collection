<?php
namespace Stanford\PRDC;

require_once("emLoggerTrait.php");
require_once("RepeatingForms.php");

class PRDC extends \ExternalModules\AbstractExternalModule {

    use emLoggerTrait;

    public $lookup_title;
    public $lookup_header;
    public $lookup_field;
    public $lookup_event_id;
    public $validate_mrn;
    public $found_action;
    public $repeating_event_id;
    public $repeating_form_name;

    public $is_loaded = false;
    public $is_valid = false;      // This is also the name of the project-setting
    public $errors = [];           // An array to hold validation errors

	public function __construct() {
		parent::__construct();
		// Other code to run when object is instantiated
	}


    /**
     * Check to see if module is valid
     * @param $project_id
     * @param $link
     * @return null
     */
	public function redcap_module_link_check_display($project_id, $link) {
        // Display current project validation status based on a quick validation
	    if(!$this->validate(true)) {
            $link['icon'] = "exclamation";
        };
        return $link;
    }


    /**
     * Validate the project when the module is saved
     * @param $project_id
     */
    public function redcap_module_save_configuration($project_id) {
        // Do a full validation on save
        if (!empty($project_id)) $this->validate();
    }


	public function redcap_survey_page_top($project_id, $record, $instrument, $event_id, $group_id, $survey_hash, $response_id, $repeat_instance = 1 ) {

        // Determine if we are on the public survey url
        $publicUrl = $this->getPublicSurveyUrl();
        $publicHash = str_replace(APP_PATH_SURVEY_FULL . "?s=","",$publicUrl);

        if ($publicHash !== $survey_hash) {
            $this->emDebug("Not a public survey");
            return;
        }

        if (!$this->is_loaded) $this->load();
        if (!$this->validate()) {
            $this->emDebug("Module is not configured correctly - skipping");
            echo "<div class='m-3 alert alert-danger'>The external module " . $this->getModuleName()
                . " is not properly configured.  Please notify a project administrator</div>";
            return;
        }

        // Hide page content and load javascript
        include "assets/insert.php";
	}


    /**
     * Lookup the record
     * @param $search
     * @return bool|int|string|null
     */
	public function lookupRecord($search) {
        if (!$this->is_loaded) $this->load();

        // Build params for query
        $params = array(
            "fields" => $this->lookup_field
        );
        if (\REDCap::isLongitudinal()) {
    	    $event_name = \REDCap::getEventNames(true, true, $this->lookup_event_id);
    	    $this->emDebug("EventName", $event_name);
            $filter = "[$event_name][$this->lookup_field] = '$search'";
            $params["filterLogic"] = $filter;
            $params["events"] = $this->lookup_event_id;
        } else {
            $filter = "[$this->lookup_field] = '$search'";
            $params["filterLogic"] = $filter;
        }
        $q = \REDCap::getData($params);

        if (count($q) == 1) {
            $result = key($q);
        } elseif (count($q) > 1) {
            // We have more than one match for that FK.  This shouldn't happen.
            \REDCap::logEvent($this->getModuleName() . " found more than one match for $this->lookup_field = '$search'.  As a result, a new record is being created");
        } else {
            $result = false;
        }
        // $this->emDebug("lookup result:", $params, $q, count($q), $result);
        return $result;
    }


    /**
     * Load the project settings
     */
	public function load() {
        // Load settings
	    $this->lookup_title        = $this->getProjectSetting('lookup-title');
	    $this->lookup_header       = $this->getProjectSetting('lookup-header');
	    $this->lookup_field        = $this->getProjectSetting('lookup-field');
	    $this->lookup_event_id     = $this->getProjectSetting('lookup-event-id');
	    $this->validate_mrn        = $this->getProjectSetting('validate-mrn');
	    $this->found_action        = $this->getProjectSetting('found-action');
	    $this->repeating_event_id  = $this->getProjectSetting('repeating-event-id');
	    $this->repeating_form_name = $this->getProjectSetting('repeating-form-name');
	    $this->is_loaded = true;
    }


    /**
     * Validate the configuration of the module
     * $quick  bool Do a fast validation
     * @return bool
     */
    public function validate($quick = false) {
        // Try a quick validation
        if ($quick) {
            $this->is_valid = $this->getProjectSetting("configuration-valid");
            if ($this->is_valid === true || $this->is_valid === false) return $this->is_valid;
        }

        // Do a full validation
        if (!$this->is_loaded) $this->load();
        global $Proj;

        // Check for configuration errors
        $errors = [];
        if (empty($this->lookup_field))     $errors[] = "Missing required lookup field";
        if (empty($this->lookup_event_id))  $errors[] = "Missing required lookup event id";
        if (empty($this->found_action))     $errors[] = "Missing required found action";

        // Check for repeating instance errors
        if ($this->found_action == 1) {
            if (empty($this->repeating_form_name)) {
                $errors[] = "Missing required repeating survey form";
            } else {
                // See if there are any issues with the repeating form
                $rf = new RepeatingForms($this->getProjectId(), $this->repeating_form_name);
                if (!empty($rf->last_error_message)) $errors[] = $rf->last_error_message;
                if (!$rf->is_survey) $errors[] = "The repeating form must be enabled as a survey";
            }

            // Make sure form is enabled on event
            if (!in_array($this->repeating_form_name, $Proj->eventsForms[$this->repeating_event_id])) {
                $errors[] = $this->repeating_form_name . " is not enabled on the specified event_id ($this->repeating_event_id)";
            }

        }

        // Check for survey queue errors
        if ($this->found_action == 2) {
            if (!\Survey::surveyQueueEnabled($this->getProjectId())) $errors[] = "Survey Queue is not enabled";
        }

        // Make sure public survey is enabled
        if (empty($this->getPublicSurveyUrl())) $errors[] = "Public survey is not enabled for this project";



        $this->is_valid = empty($errors);
        $this->errors = $errors;

        // Store the validation to the database
        $this->setProjectSetting("configuration-valid", $this->is_valid);

        return $this->is_valid;
    }



}
